<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Appointment Contents.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?></h4>

        @if(isset($data['onlineAppointment']))
            <h3 style="margin-top:20px;">
                Online Appointment History
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Goal</th>
                    <th>Therapy Name</th>
                    <th>Appointment Date</th>                    
                    <th>Created at</th>
                </tr>
                @if(count($data['onlineAppointment']) > 0)
                <?php foreach ($data['onlineAppointment'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['patient']['first_name']." ".$row['patient']['middle_name']." ".$row['patient']['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['goal'] }}</td>
                        <td style="text-align: center">{{ $row['therapy'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i', strtotime($row['start'])) }} ~ {{ date('Y-m-d H:i', strtotime($row['end'])) }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif    

        @if(isset($data['phoneInAppointment']))
            <h3 style="margin-top:50px;">
                Phone In Appointment History
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Goal</th>
                    <th>Therapy Name</th>
                    <th>Appointment Date</th>                    
                    <th>Created at</th>
                </tr>
                @if(count($data['phoneInAppointment']) > 0)
                <?php foreach ($data['phoneInAppointment'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['patient']['first_name']." ".$row['patient']['middle_name']." ".$row['patient']['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['goal'] }}</td>
                        <td style="text-align: center">{{ $row['therapy'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i', strtotime($row['start'])) }} ~ {{ date('Y-m-d H:i', strtotime($row['end'])) }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif  

        @if(isset($data['walkInAppointment']))
            <h3 style="margin-top:50px;">
                Walk-In Appointment History
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Goal</th>
                    <th>Therapy Name</th>
                    <th>Appointment Date</th>                    
                    <th>Created at</th>
                </tr>
                @if(count($data['walkInAppointment']) > 0)
                <?php foreach ($data['walkInAppointment'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['patient']['first_name']." ".$row['patient']['middle_name']." ".$row['patient']['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['goal'] }}</td>
                        <td style="text-align: center">{{ $row['therapy'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i', strtotime($row['start'])) }} ~ {{ date('Y-m-d H:i', strtotime($row['end'])) }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif    

        @if(isset($data['noShowAppointment']))
            <h3 style="margin-top:50px;">
                No Show Appointment History
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Goal</th>
                    <th>Therapy Name</th>
                    <th>Appointment Date</th>                    
                    <th>Created at</th>
                </tr>
                @if(count($data['noShowAppointment']) > 0)
                <?php foreach ($data['noShowAppointment'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['patient']['first_name']." ".$row['patient']['middle_name']." ".$row['patient']['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['goal'] }}</td>
                        <td style="text-align: center">{{ $row['therapy'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i', strtotime($row['start'])) }} ~ {{ date('Y-m-d H:i', strtotime($row['end'])) }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
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
