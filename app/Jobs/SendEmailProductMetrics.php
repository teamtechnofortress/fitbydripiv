<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use App\Models\ProductMetricsReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailProductMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productMetricsReport;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductMetricsReport $productMetricsReport)
    {
        $this->productMetricsReport = $productMetricsReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        /*
        ###############################################################
         * Get the IV Products
        ###############################################################
        */
        $ivList = [];
        $injectablesList = [];
        $peptideList = [];
        $otherList = [];
        $semaglutideList = [];
        $tirzepatideList = [];

        $range_sdate = $this->productMetricsReport->range_sdate;
        $range_edate = Carbon::parse($this->productMetricsReport->range_edate)->addDay();

        $invoiceList = Invoice::where(['deleted' => 0])            
            ->whereBetween('created_at', [$range_sdate, $range_edate])
            ->get();

        foreach ($invoiceList as $invoice) {
            $invoiceData = json_decode($invoice->data, true);
            foreach ($invoiceData as $rows) {
                foreach ($rows as $row) {
                    if (!isset($row['id'])) continue;                    

                    // Get the Patient Encounter                        
                    if (strtolower($row['type']) == 'iv' || strtolower($row['type']) == 'iv therapy') {
                        $item = [];   
                        $item['enc_name'] = $row['name'];
                        $item['enc_type'] = $row['type'];
                        $item['enc_dosage'] = $row['dosage'];
                        $item['unit'] = $row['unit'];
                        $item['enc_ingredients'] = $row['ingredients'];
                        $item['quantity'] = $row['quantity'];
                        $item['inv_name'] = $row['inventory']['name'];
                        $item['inv_price'] = $row['inventory']['price'];
                        $item['sub_price'] = ($row['quantity'] * $row['inventory']['price']);                        
                        $item['created_at'] = $row['created_at'];

                        $ivList[] = $item;
                    }else if(strtolower($row['type']) == 'injectables') {
                        $item = [];   
                        $item['enc_name'] = $row['name'];
                        $item['enc_type'] = $row['type'];
                        $item['enc_dosage'] = $row['dosage'];
                        $item['unit'] = $row['unit'];
                        $item['enc_ingredients'] = $row['ingredients'];
                        $item['quantity'] = $row['quantity'];
                        $item['inv_name'] = $row['inventory']['name'];
                        $item['inv_price'] = $row['inventory']['price'];
                        $item['sub_price'] = ($row['quantity'] * $row['inventory']['price']);                        
                        $item['created_at'] = $row['created_at'];

                        $injectablesList[] = $item;
                    }else if(strtolower($row['type']) == 'weight loss'){

                        $item = [];   
                        $item['enc_name'] = $row['name'];
                        $item['enc_type'] = $row['type'];
                        $item['enc_dosage'] = $row['dosage'];
                        $item['unit'] = $row['unit'];
                        $item['enc_ingredients'] = $row['ingredients'];
                        $item['quantity'] = $row['quantity'];
                        $item['inv_name'] = $row['inventory']['name'];
                        $item['inv_price'] = $row['inventory']['price'];
                        $item['sub_price'] = ($row['quantity'] * $row['inventory']['price']);                        
                        $item['created_at'] = $row['created_at'];

                        $peptideList[] = $item;
                    }else if(strtolower($row['type']) == 'other'){

                        $item = [];   
                        $item['enc_name'] = $row['name'];
                        $item['enc_type'] = $row['type'];
                        $item['enc_dosage'] = $row['dosage'];
                        $item['unit'] = $row['unit'];
                        $item['enc_ingredients'] = $row['ingredients'];
                        $item['quantity'] = $row['quantity'];
                        $item['inv_name'] = $row['inventory']['name'];
                        $item['inv_price'] = $row['inventory']['price'];
                        $item['sub_price'] = ($row['quantity'] * $row['inventory']['price']);                        
                        $item['created_at'] = $row['created_at'];

                        $otherList[] = $item;
                    }else{
                        $item = [];   
                        $item['enc_name'] = $row['name'];
                        $item['enc_type'] = $row['type'];
                        $item['enc_dosage'] = $row['dosage'];
                        $item['unit'] = $row['unit'];
                        $item['enc_ingredients'] = $row['ingredients'];
                        $item['quantity'] = $row['quantity'];
                        $item['inv_name'] = $row['inventory']['name'];
                        $item['inv_price'] = $row['inventory']['price'];
                        $item['sub_price'] = ($row['quantity'] * $row['inventory']['price']);                        
                        $item['created_at'] = $row['created_at'];

                        $semaglutideList[] = $item;
                        $tirzepatideList[] = $item;
                    }
                }
            }        
        }

        if($this->productMetricsReport->isIV){
            $data['ivList'] = $ivList;
        }

        if($this->productMetricsReport->isInjections){
            $data['injectablesList'] = $injectablesList;
        }

        if($this->productMetricsReport->isPeptides){
            $data['peptideList'] = $peptideList;
        }

        if($this->productMetricsReport->isOther){
            $data['otherList'] = $otherList;
        }

        if($this->productMetricsReport->isSemaglutide){
            $data['semaglutideList'] = $semaglutideList;
        }
        
        if($this->productMetricsReport->isTirzepatide){
            $data['tirzepatideList'] = $tirzepatideList;
        }        

        $data['range_due']  = $this->productMetricsReport->range_sdate." ~ ".$this->productMetricsReport->range_edate;       


        $receiverEmail = $this->productMetricsReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.productMectricsNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Product Metrics Report Notification');

        });
        
        //upate with reported
        $this->productMetricsReport->update([
            'reported_date' => now(),
        ]); 
    }
}
