<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StaffReport;
use App\Models\ChartHistory;
use App\Models\MedrxReports;
use App\Models\RewardReport;
use Illuminate\Http\Request;
use App\Models\PayrollReport;
use App\Models\AppointmentReport;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendEmailChartHistory;
use App\Models\InvoicingSalesReport;
use App\Models\PatientMetricsReport;
use App\Models\ProductMetricsReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\CustomerServiceReport;
use App\Models\EmailTextRewardReport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ReportsManageController extends BaseController
{
    /**
    * saveChartHistory
    */
    public function saveChartHistory(Request $request){
        $validator = Validator::make($request->all(), [
            'patient_id'   => 'required|integer|exists:patient,id',
            'email'        => 'required|email',
            'range_sdate'   => 'required|date',
            'range_edate'   => 'required|date',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        /*
        * ########################################
        * ########################################
        */
        $chart = ChartHistory::create([
            ...$request->all(),
            "created_at" => now()
        ]);

        /*
        * ########################################
        * ######### Emailing #############
        * ########################################
        */
        Artisan::call('app:chart-history-report');
        
        $success['chart'] = $chart;
        return $this->sendResponse($success, 'Successfully saved Chart History!');
    }

    /**
     * getAllChartHistory
     */
    public function getAllChartHistory(Request $request){
        $charts = ChartHistory::where('deleted', 0)->with('patient')->get();
        $success['charts'] = $charts;
        return $this->sendResponse($success, 'All Chart History');
    }

    /**
     * removeChartHistoryById
     */
    public function removeChartHistoryById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:chart_history,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        ChartHistory::where('id', $id)->delete();        
        return $this->sendResponse(true, "Removed Chart History $id");
    }

    /**
     * saveCustomerServiceReport
     */
    public function saveCustomerServiceReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',
            'arrive_stime' => 'required',
            'arrive_etime' => 'required',
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = CustomerServiceReport::create(
            [...$request->all(), "created_at" => now()]
        );

        /*
        * ########################################
        * ######### Emailing #############
        * ########################################
        */
        Artisan::call('app:customer-service-report');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Customer Service Report!');
    }

    /**
     * getAllCustomerServiceReport
     */
    public function getAllCustomerServiceReport(Request $request){
        $reports = CustomerServiceReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Customer Service Reports.');
    }

    /**
     * removeCustomerServiceReportById  
     */
    public function removeCustomerServiceReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:customer_service_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        CustomerServiceReport::where('id', $id)->delete();        
        return $this->sendResponse(true, "Removed Customer Service Report $id");
    }

    /**
     * patientMetrics
     */
    public function savePatientMetrics(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',
            'type'        => 'required|string',            
            'email'       => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = PatientMetricsReport::create(
            [...$request->all(), "created_at" => now()]
        );

        /*
        * ########################################
        * ######### Emailing #############
        * ########################################
        */
        Artisan::call('app:patient-metrics-notify');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Patient Metrics Report!');
    }

    /**
    * getAllPatientMetrics
    */
    public function getAllPatientMetrics(Request $request){
        $reports = PatientMetricsReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Patient Metrics Reports.');
    }

    /**
     * removePatientMetricsById  
     */
    public function removePatientMetricsById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:patient_metrics_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        PatientMetricsReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Patient Metrics Report $id");
    }

    /**
     * productMetrics
     */
    public function saveProductMetrics(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = ProductMetricsReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:product-metrics-notify');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Product Metrics Report!');
    }

    /**
    * getAllProductMetrics
    */
    public function getAllProductMetrics(Request $request){
        $reports = ProductMetricsReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Product Metrics Reports.');
    }

    /**
     * removeProductMetricsById  
     */
    public function removeProductMetricsById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:product_metrics_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        ProductMetricsReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Product Metrics Report $id");
    }

    /**
     * appointmentReport
     */
    public function saveAppointmentReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = AppointmentReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:appointment-report-notify'); 

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Appointment Report!');
    }

    /**
    * getAllAppointmentReports
    */
    public function getAllAppointmentReports(Request $request){
        $reports = AppointmentReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Appointment Reports.');
    }

    /**
     * removeAppointmentReportById
     */
    public function removeAppointmentReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:appointment_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        AppointmentReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Appointment Report $id");
    }

    /**
     * rewardReport
     */
    public function saveRewardReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = RewardReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //emailing
        Artisan::call('app:reward-report-notify');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Reward Report!');
    }

    /**
    * getAllRewardReports   
    */
    public function getAllRewardReports(Request $request){
        $reports = RewardReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Reward Reports.');
    }

    /**
     * removeReward ReportById
     */
    public function removeRewardReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:reward_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        RewardReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Reward Report $id");
    }

    /**
     * emailTextRewardReport
     */
    public function saveEmailTextRewardReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = EmailTextRewardReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:email-text-report');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Email Text Reward Report!');
    }

    /**
    * getAllEmailTextRewardReports
    */
    public function getAllEmailTextRewardReports(Request $request){
        $reports = EmailTextRewardReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Email Text Reward Reports.');
    }

    /**
     * removeEmailTextRewardReportById
     */
    public function removeEmailTextRewardReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:email_text_reward_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        EmailTextRewardReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Email Text Reward Report $id");
    }

    /**
     * invoicingSalesReport
     */
    public function saveInvoicingSalesReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = InvoicingSalesReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:invoicing-sales-report-notify');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Invoicing Sales Report!');
    }

    /**
    * getAllInvoicingSalesReports
    */
    public function getAllInvoicingSalesReports(Request $request){
        $reports = InvoicingSalesReport::where('deleted', 0)->get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Invoicing Sales Reports.');
    }

    /**
     * removeInvoicingSalesReportById
     */
    public function removeInvoicingSalesReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:invoicing_sales_report,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        InvoicingSalesReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Invoicing Sales Report $id");
    }

    /**
     * saveSignature
     */
    public function saveSignature(Request $request){
        $validator = Validator::make($request->all(), [
            'signature'    => 'required|string',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $user = User::find(Auth::id());
        $user->signature = $request->signature;
        $user->save();

        $success['signature'] = $user->signature;
        return $this->sendResponse($success, 'Successfully saved Signature!');
    }

    /**
    * getSignature
    */
    public function getSignature(Request $request){
        $user = User::where('role', 'admin')->first();
        $success['signature'] = $user->signature;
        return $this->sendResponse($success, 'Successfully retrieved Signature!');
    } 
    
    /**
     * saveStaffReport
     */
    public function saveStaffReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = StaffReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:staff-report-notify');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Staff Report!');
    }

    /**
    * getAllStaffReports
    */
    public function getAllStaffReports(Request $request){
        $reports = StaffReport::get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Staff Reports.');
    }

    /**
     * removeStaffReportById
     */
    public function removeStaffReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:staff_reports,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        StaffReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Staff Report $id");
    }

    /**
     * savePayrollReport
     */
    public function savePayrollReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = PayrollReport::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:payroll-report-notify');

        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Payroll Report!');
    }

    /**
    * getAllPayrollReports
    */
    public function getAllPayrollReports(Request $request){
        $reports = PayrollReport::get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Payroll Reports.');
    }

    /**
     * removePayrollReportById
     */
    public function removePayrollReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:payroll_reports,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        PayrollReport::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Payroll Report $id");
    }

    /**
     * saveMedrxReport
     */
    public function saveMedrxReport(Request $request){
        $validator = Validator::make($request->all(), [
            'frequency'    => 'required|string',
            'range_sdate'  => 'required|date',
            'range_edate'  => 'required|date',            
            'email'        => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $report = MedrxReports::create(
            [...$request->all(), "created_at" => now()]
        );

        //Emailing
        Artisan::call('app:medrx-report-notify');
        
        $success['report'] = $report;
        return $this->sendResponse($success, 'Successfully saved Med/RX Report!');
    }

    /**
    * getAllMedrxReports
    */
    public function getAllMedrxReports(Request $request){
        $reports = MedrxReports::get();
        $success['reports'] = $reports;
        return $this->sendResponse($success, 'All Medrx Reports.');
    }

    /**
     * removeMedrxReportById
     */
    public function removeMedrxReportById(Request $request, $id){
        $validator = Validator::make(['id' => $id], [
            'id'   => 'required|integer|exists:medrx_reports,id',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        MedrxReports::where('id', $id)->delete();
        return $this->sendResponse(true, "Removed Medrx Report $id");
    }
}
