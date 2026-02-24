<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use App\Models\AppointmentReport;
use App\Models\Intake1;
use App\Models\PatientAppointment;
use App\Models\PatientEncounter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailAppointmentReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointmentReport;
    
    /**
     * Create a new job instance.
     */
    public function __construct(AppointmentReport $appointmentReport)
    {
        $this->appointmentReport = $appointmentReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        /*
        ###############################################################
         * Get the Appointment List
        ###############################################################
        */
        //Online appointment        
        $range_sdate = $this->appointmentReport->range_sdate;
        $range_edate = Carbon::parse($this->appointmentReport->range_edate)->addDay();

        //Phone in Appointment
        $data['phoneInAppointment'] = PatientAppointment::where(['deleted' => 0])            
            ->whereBetween('created_at', [$range_sdate, $range_edate])
            ->where('appointed_type', 'Phone In')
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->get();
        

        //Walk in Appointment 
        $data['walkInAppointment'] = PatientAppointment::where(['deleted' => 0])
            ->whereBetween('created_at', [$range_sdate, $range_edate])
            ->where('appointed_type', 'Walk-In')
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->get();
        

        //Online Appointment
        $data['onlineAppointment'] = PatientAppointment::where(['deleted' => 0])
            ->whereBetween('created_at', [$range_sdate, $range_edate])
            ->where('appointed_type', 'Online')
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->get();            

        $data['noShowAppointment'] = [];

        $data['range_due']  = $this->appointmentReport->range_sdate." ~ ".$this->appointmentReport->range_edate;

        $receiverEmail = $this->appointmentReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.appointmentReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Appointment Report Notification');

        });
        
        //upate with reported
        $this->appointmentReport->update([
            'reported_date' => now(),
        ]); 
    }
}
