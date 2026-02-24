<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\InvoicingSalesReport;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailInvoicingSalesReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceReport;
    /**
     * Create a new job instance.
     */
    public function __construct(InvoicingSalesReport $invoiceReport)
    {
        $this->invoiceReport = $invoiceReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $range_sdate = $this->invoiceReport->range_sdate;
        $range_edate = Carbon::parse($this->invoiceReport->range_edate)->addDay();        
        /* 
        *#############################################################
            * CreditCard Payment
        *#############################################################
        */
        if($this->invoiceReport->credit_card){

            $data['creditCardPayment'] = Invoice::where('deleted', 0)
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'creditCard')
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }
        
        /* 
        *#############################################################
            * Cash Payment
        *#############################################################
        */
        if($this->invoiceReport->cash){
            $data['cashPayment'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'cash')
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /* 
        *#############################################################
            Transfer Payment
        *#############################################################
        */
        if($this->invoiceReport->transfer){
            $data['transferPayment'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'transfer')                
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /* 
        *#############################################################
            Paypal Payment
        *#############################################################
        */
        if($this->invoiceReport->paypal){
            $data['paypalPayment'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'electronic')
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /* 
        *#############################################################
            Venmo Payment
        *#############################################################
        */
        if($this->invoiceReport->venmo){
            $data['venmoPayment'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'venmo')
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /* 
        *#############################################################
            Cashapp Payment
        *#############################################################
        */
        if($this->invoiceReport->cashapp){
            $data['cashappPayment'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'cashapp')
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /* 
        *#############################################################
            Crypto Payment
        *#############################################################
        */
        if($this->invoiceReport->crypto){
            $data['cryptoPayment'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('payment_type', 'crypto')
                ->selectRaw('COUNT(id) as cardCount, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /* 
        *#############################################################
            Sales total
        *#############################################################
        */
        if($this->invoiceReport->sales_totals){
            $data['salesTotals'] = Invoice::where(['deleted' => 0])
                ->whereBetween('created_at', [$range_sdate, $range_edate])                
                ->selectRaw('SUM(tip) as tip, SUM(totalPrice) as paidAmount')
                ->first();
        }

        /*
        ###############################################################
         * Sales Details
        ###############################################################
        */
        if($this->invoiceReport->sales_detail){            
            $invoiceList = Invoice::where(['deleted' => 0])            
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->get();

            foreach ($invoiceList as $invoice) {
                $invoiceData = json_decode($invoice->data, true);
                foreach ($invoiceData as $rows) {
                    foreach ($rows as $row) {
                        if (!isset($row['id'])) continue;                    

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

                        $data['salesDetail'][] = $item;                        
                    }
                }        
            }
        }

        /*
        ###############################################################
         * Sales tax
        ###############################################################
        */
        if($this->invoiceReport->sales_tax){
            $data['salesTaxes'] = Invoice::where(['deleted' => 0])            
                ->whereBetween('created_at', [$range_sdate, $range_edate])
                ->where('tax', '>', 0)
                ->with('patient')
                ->get();           
        }


        $data['range_due']  = $this->invoiceReport->range_sdate." ~ ".$this->invoiceReport->range_edate;

        $receiverEmail = $this->invoiceReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.invoicingSalesReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Invoicing Sales Report Notification');

        });

        //update with reported_date
        $this->invoiceReport->update([
            'reported_date' => now(),
        ]); 
    }
}
