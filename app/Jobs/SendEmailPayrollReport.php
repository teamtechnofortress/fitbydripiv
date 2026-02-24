<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\LoginHistory;
use App\Models\StaffPayroll;
use App\Models\StaffSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailPayrollReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payrollReport;

    /**
     * Create a new job instance.
     */
    public function __construct($payrollReport)
    {
        $this->payrollReport = $payrollReport;
    }

    public function getStaffTimeSlots($scheduleData){
        $staffs = [];
        foreach ($scheduleData as $time => $staffList) { 
            foreach ($staffList as $staff) {
                $staffData = json_decode($staff, true);
                $staffId   = $staffData['staffId'];

                if ( !in_array($staffId, array_keys($staffs)) ) {                    
                    $staffs[$staffId] = [
                        'staffId' => $staffId,
                        'name'    => $staffData['name'],
                        'timeSlots' => [$time]
                    ];
                }else{
                   $staffs[$staffId]['timeSlots'][] = $time;
                }
            }
        }
        return $staffs;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $range_sdate = $this->payrollReport->range_sdate;
        $range_edate = Carbon::parse($this->payrollReport->range_edate)->addDay();

        /*
        ###############################################################
         * Get the StaffReport List
        ###############################################################
        */
        $payrollList = StaffPayroll::where(['deleted' => 0])
            ->where('created_at', '>=', $range_sdate)
            ->where('created_at', '<=', $range_edate)
            ->orderBy('created_at', 'desc')
            ->with('staff')
            ->get();
        
        /*
        ###############################################################
        Hours Worked
        ##############################################################
        */
        $data['is_hours_worked'] = $this->payrollReport?->hours_worked;
        $data['hours_worked'] = [];

        //initialize summary
        $summary = [];

        $staffScheduleList = StaffSchedule::where(['deleted' => 0])
                ->whereBetween('date', [$range_sdate, $range_edate])
                ->orderBy('date', 'desc')
                ->get();

        foreach ($staffScheduleList as $schedule) {
            $scheduleData = json_decode($schedule->schedule, true);

            //get the staff timeslots
            $staffSlots = $this->getStaffTimeSlots($scheduleData);

            foreach ($staffSlots as $sid => $sData) {
                $shiftStart = Carbon::parse($schedule->date.' '.$sData['timeSlots'][0]);
                $shiftEnd   = Carbon::parse($schedule->date . ' ' . $sData['timeSlots'][count($sData['timeSlots']) - 1]);                    

                $history = LoginHistory::where('user_id', $sid)
                        ->whereNotNull('logged_in_at')
                        ->whereNotNull('logged_out_at')
                        ->where('logged_in_at', '>=', $shiftStart)
                        ->where('logged_out_at', '>=', $shiftEnd)
                        ->selectRaw('TIMESTAMPDIFF(HOUR, logged_in_at, logged_out_at) as hours_worked')                            
                        ->orderBy('logged_in_at', 'desc')
                        ->with('user')
                        ->first();

                
                if ($history) {
                    $staffInfo = User::where(['id' => $sid, 'role' => 'staff'])->with('staffpayroll')->first();
                    if(!empty($staffInfo)){
                        $data['hours_worked'][] = [
                            'staff_id'     => $sid,
                            'name'         => $sData['name'],
                            'worked_hrs'   => $history->hours_worked,
                            'worked_date'  => $schedule->date,
                            'staff'        => $staffInfo,
                        ];

                        //summary
                        if (!isset($summary[$sid])) {
                            $summary[$sid] = [
                                'staff_id' => $sid,
                                'name' => $sData['name'],
                                'total_worked_hours' => 0,
                                'staff' => $staffInfo,
                            ];
                        }

                        $summary[$sid]['total_worked_hours'] += (int)$history->hours_worked;
                    }
                }                    
            }
        }

        $data['hours_worked_summary'] = array_values($summary);   
        $data['is_calculated_overtime'] = $this->payrollReport?->calculated_overtime;   

        /*
        ###############################################################
        Salary
        ##############################################################
        */
        if($this->payrollReport?->salary){
            $data['is_salary'] = true;
            $data['salary'] = [];

            $staffScheduleList = StaffSchedule::where(['deleted' => 0])
                    ->whereBetween('date', [$range_sdate, $range_edate])
                    ->orderBy('date', 'desc')
                    ->get();

            foreach ($staffScheduleList as $schedule) {                
                $scheduleData = json_decode($schedule->schedule, true);

                //get the staff timeslots
                $staffSlots = $this->getStaffTimeSlots($scheduleData);

                foreach ($staffSlots as $sid => $sData) {
                    $shiftStart = Carbon::parse($schedule->date.' '.$sData['timeSlots'][0]);
                    $shiftEnd   = Carbon::parse($schedule->date . ' ' . $sData['timeSlots'][count($sData['timeSlots']) - 1]);                    

                    $history = LoginHistory::where('user_id', $sid)
                            ->whereNotNull('logged_in_at')
                            ->whereNotNull('logged_out_at')
                            ->where('logged_in_at', '>=', $shiftStart)
                            ->where('logged_out_at', '>=', $shiftEnd)
                            ->selectRaw('TIMESTAMPDIFF(HOUR, logged_in_at, logged_out_at) as hours_worked')                            
                            ->orderBy('logged_in_at', 'desc')
                            ->with('user')
                            ->first();

                    if ($history) {

                        $staffInfo = User::where('id', $sid)
                                            ->where('role', 'staff')
                                            ->whereHas('staffpayroll', function ($q) {
                                                $q->where('payrate', 'monthly');
                                            })
                                            ->with('staffpayroll')
                                            ->first();

                        if(!empty($staffInfo)){
                            $data['salary'][] = [
                                'staff_id'     => $sid,
                                'name'         => $sData['name'],
                                'worked_hrs'   => $history->hours_worked,
                                'worked_date'  => $schedule->date,
                                'staff'        => $staffInfo,
                            ];
                        }
                    }                    
                }
            }
        }

        $data['payrollList'] = $payrollList;

        $data['range_due']  = $this->payrollReport->range_sdate." ~ ".$this->payrollReport->range_edate;

        $receiverEmail = $this->payrollReport->email;

        $this->doSendEmail($data, $receiverEmail);
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.payrollReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Payroll Report Notification');

        });

        //update with reported
        $this->payrollReport->update([
            'reported_date' => now(),
        ]); 
    }
}
