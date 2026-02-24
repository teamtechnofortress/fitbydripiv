<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\StaffReport;
use App\Models\LoginHistory;
use App\Models\StaffSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailStaffReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $staffReport;

    /**
     * Create a new job instance.
     */
    public function __construct(StaffReport $staffReport)
    {
        $this->staffReport = $staffReport;
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
        $range_sdate = $this->staffReport->range_sdate;
        $range_edate = Carbon::parse($this->staffReport->range_edate)->addDay(); 
               
        /*
        ###############################################################
         * Get the StaffReport List
        ###############################################################
        */
        $staffScheduleList = StaffSchedule::where(['deleted' => 0])
            ->whereBetween('date', [$range_sdate, $range_edate])            
            ->orderBy('date', 'desc')
            ->get();

        /*
        ###############################################################
        Late Checkin
        ##############################################################
        */
        if($this->staffReport?->late_checkin){
            $data['is_late_checkin'] = true;
            $data['late_checkins'] = [];
            foreach ($staffScheduleList as $schedule) {                
                $scheduleData = json_decode($schedule->schedule, true);
                $data['late_checkins'] = [];

                foreach ($scheduleData as $time => $staffList) {                    
                    foreach ($staffList as $staff) {
                        $staffData = json_decode($staff, true);
                        $staffId   = $staffData['staffId'];
                        $staffName = $staffData['name'];

                        $loginHistory = LoginHistory::where('user_id', $staffId)
                            ->where('logged_in_at', '>', $schedule->date . ' ' . $time)
                            ->first();

                        if ($loginHistory) {
                            $data['late_checkins'][] = [
                                'staff_id' => $staffId,
                                'name' => $staffName,
                                'scheduled_time' => $time,
                                'scheduled_date' => $schedule->date
                            ];
                        }
                    }
                }
            }
        }

        /*
        ###############################################################
        Early Checkin
        ##############################################################
        */
        if($this->staffReport?->early_checkin){
            $data['is_early_checkin'] = true;
            $data['early_checkins'] = [];
            foreach ($staffScheduleList as $schedule) {                
                $scheduleData = json_decode($schedule->schedule, true);

                foreach ($scheduleData as $time => $staffList) {                    
                    foreach ($staffList as $staff) {
                        $staffData = json_decode($staff, true);
                        $staffId   = $staffData['staffId'];
                        $staffName = $staffData['name'];

                        $loginHistory = LoginHistory::where('user_id', $staffId)
                            ->where('logged_in_at', '<', $schedule->date . ' ' . $time)
                            ->first();
                        
                        if ($loginHistory) {
                            $data['early_checkins'][] = [
                                'staff_id' => $staffId,
                                'name' => $staffName,
                                'scheduled_time' => $time,
                                'scheduled_date' => $schedule->date
                            ];
                        }
                    }
                }
            }
        }

        /*
        ###############################################################
        Overtime incident
        ##############################################################
        */
        if($this->staffReport?->overtime_incident){
            $data['is_overtime_incident'] = true;
            $data['overtime_incidents'] = [];
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
                            ->whereRaw('TIMESTAMPDIFF(HOUR, logged_in_at, logged_out_at) > ?', [$shiftEnd->diffInHours($shiftStart)])
                            ->orderBy('logged_in_at', 'desc')
                            ->first();

                    
                    if ($history) {
                        $data['overtime_incidents'][] = [
                            'staff_id'  => $sid,
                            'name'      => $sData['name'],
                            'overtime'  => ($history->hours_worked - $shiftEnd->diffInHours($shiftStart)),
                            'scheduled_date' => $schedule->date
                        ];
                    }                    
                }
            }
        }


        /*
        ###############################################################
        late Schedule
        ##############################################################
        */
        if($this->staffReport?->late_schedule){
            $data['is_late_schedule'] = true;
            $data['late_schedules'] = [];
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
                            // ->whereRaw('TIMESTAMPDIFF(HOUR, logged_in_at, logged_out_at) > ?', [$shiftEnd->diffInHours($shiftStart)])
                            ->orderBy('logged_in_at', 'desc')
                            ->first();

                    
                    if ($history) {
                        $data['late_schedules'][] = [
                            'staff_id'  => $sid,
                            'name'      => $sData['name'],                            
                            'worktime'   => $shiftEnd->diffInHours($shiftStart),
                            'real_worktime'  => $history->hours_worked,
                            'scheduled_date' => $schedule->date,
                            'start_time' => $shiftStart,
                            'end_time'   => $shiftEnd,
                        ];
                    }                    
                }
            } 
        }
        
        
        $data['staffScheduleList'] = $staffScheduleList;

        $data['range_due']  = $this->staffReport->range_sdate." ~ ".$this->staffReport->range_edate;

        $receiverEmail = $this->staffReport->email;

        $this->doSendEmail($data, $receiverEmail);        
    }

    public function doSendEmail($data, $receiverEmail){
        Mail::send('email.staffReportNotify', ['data' => $data], function($message) use($receiverEmail){

            $message->to($receiverEmail);

            $message->subject('Staff Report Notification');

        });

        //update with reported
        $this->staffReport->update([
            'reported_date' => now(),
        ]); 
    }
}
