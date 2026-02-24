<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Staff Report.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?></h4>

        @if(isset($data['is_late_checkin']) && $data['is_late_checkin'])
            <h3 style="margin-top:20px;">
                Late Checkin Staffs
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Staff Name</th>
                    <th>Scheduled Time</th>                    
                </tr>
                @if(count($data['late_checkins']) > 0)
                <?php foreach ($data['late_checkins'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['name'] }}</td>
                        <td style="text-align: center">{{ $row['scheduled_date'].' '.$row['scheduled_time'] }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif               

        @if(isset($data['is_early_checkin']) && $data['is_early_checkin'])
            <h3 style="margin-top:50px;">
                Early Checkin Staffs
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Staff Name</th>
                    <th>Scheduled Time</th>                    
                </tr>
                @if(count($data['early_checkins']) > 0)
                <?php foreach ($data['early_checkins'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['name'] }}</td>
                        <td style="text-align: center">{{ $row['scheduled_date'].' '.$row['scheduled_time'] }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif   

        @if(isset($data['is_overtime_incident']) && $data['is_overtime_incident'])
            <h3 style="margin-top:50px;">
                Overtime Incident Staffs
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Staff Name</th>
                    <th>Scheduled Date</th>
                    <th>Overtime Hours</th>
                </tr>
                @if(count($data['overtime_incidents']) > 0)
                <?php foreach ($data['overtime_incidents'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['name'] }}</td>
                        <td style="text-align: center">{{ $row['scheduled_date'] }}</td>
                        <td style="text-align: center">{{ $row['overtime'] }} Hrs</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif

        @if(isset($data['is_late_schedule']) && $data['is_late_schedule'])
            <h3 style="margin-top:50px;">
                Late Schedule Staffs
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Staff Name</th>
                    <th>Scheduled Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Work Time</th>
                    <th>Real Work</th>
                </tr>
                @if(count($data['late_schedules']) > 0)
                <?php foreach ($data['late_schedules'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['name'] }}</td>
                        <td style="text-align: center">{{ $row['scheduled_date'] }}</td>
                        <td style="text-align: center">{{ $row['start_time'] }}</td>
                        <td style="text-align: center">{{ $row['end_time'] }}</td>
                        <td style="text-align: center">{{ $row['worktime'] }} Hrs</td>
                        <td style="text-align: center">{{ $row['real_worktime'] }} Hrs</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif               

    </div>
    <br>    
</x-guest-layout>
