<?php

namespace App\Jobs;

use App\Mail\SendEmail;
use App\Models\ChartHistory;
use App\Models\ChiefComplaint;
use App\Models\Patient;
use App\Models\PatientEncounter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailChartHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chartHistory;
    /**
     * Create a new job instance.
     */
    public function __construct(ChartHistory $chartHistory){
        $this->chartHistory = $chartHistory;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $encounters = [];
        $range_sdate = $this->chartHistory->range_sdate;
        $range_edate = Carbon::parse($this->chartHistory->range_edate)->addDay();

        //Get the encounter content if it is marked as Yes
        if($this->chartHistory->isEncounters){
            $encounters = PatientEncounter::where([
                'patient_id' => $this->chartHistory->patient_id,                 
                'deleted'    => 0, 
            ])
            ->where('created_at', '>=', $range_sdate)            
            ->where('created_at', '<=', $range_edate)
            ->with('inventory')->get();
        }

        $notes = "";
        //If Notes is Yes
        if($this->chartHistory->hasNotes){
            $notes = ChiefComplaint::where(['patient_id' => $this->chartHistory->patient_id])
                ->where('created_at', '>=', $range_sdate)
                ->where('created_at', '<=', $range_edate)
                ->get();
        }

        $data['notes'] = $notes;
        $data['encounters'] = $encounters;
        $data['due'] = $this->chartHistory->range_sdate." ~ ".$this->chartHistory->range_edate;
        $data['patient'] = Patient::find($this->chartHistory->patient_id);

        $data['isEncounters'] = $this->chartHistory->isEncounters;
        $data['isProducts'] = $this->chartHistory->isProducts;
        $data['hasNotes'] = $this->chartHistory->hasNotes;        

        //Generate the file
        $csvPath = $this->generateEncounterCsv($encounters);

        $this->doSendEmail($data, $this->chartHistory->email, $csvPath);

        //Delete the file after sending email
        if (file_exists($csvPath)) {
            unlink($csvPath);
        }
        
    }

    public function doSendEmail($data, $receiverEmail, $csvPath){        
        Mail::send('email.chartHistoryNotification', ['data' => $data], function($message) use($data, $receiverEmail, $csvPath){

            $fileName = $data['patient']->first_name . '_' . $data['patient']->last_name . '_encounter_' . now()->format('Ymd_His') . '.csv';
            $message->to($receiverEmail);
            $message->subject('Chart History Email');
            $message->attach($csvPath, [
                'as' => $fileName, // the name to be displayed in the email
                'mime' => 'text/csv',
            ]);

        });
        
        //upate with reported
        $this->chartHistory->update([
            'reported_date' => now(),
        ]); 
    }

    private function generateEncounterCsv($encounters)
    {
        // storage/app/chart_history/ generate the file path
        $fileName = 'encounters_' . now()->format('Ymd_His') . '.csv';
        $filePath = storage_path('app/chart_history/' . $fileName);

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        $file = fopen($filePath, 'w');

        // CSV Header
        fputcsv($file, ['Type', 'Name', 'Dosage', 'Ingredients', 'Quantity', 'Price', 'Encountered Date']);

        // CSV Content
        foreach ($encounters as $encounter) {
            $inventory = $encounter->inventory ?? [];

            fputcsv($file, [
                $inventory['type'] ?? '',
                $inventory['name'] ?? '',
                $inventory['inject_dosage'] ?? '',
                $inventory['ingredients'] ?? '',
                $encounter->quantity ?? '',
                isset($inventory['price']) ? number_format($inventory['price'], 2) : '',
                $encounter->created_at ? $encounter->created_at->format('Y-m-d H:i') : '',
            ]);
        }

        fclose($file);

        return $filePath;
    }
}
