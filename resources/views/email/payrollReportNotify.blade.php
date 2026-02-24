<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Payroll Report.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?></h4>

        @if(isset($data['is_hours_worked']) && $data['is_hours_worked'])
            <h3 style="margin-top:20px;">
                Hours Worked
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Payrolled Staff Name</th>
                    <th>Staff Email</th>
                    <th>Worked Date</th>
                    <th>Worked Hours</th>
                    <th>Pay Rate</th>
                    <th>Withholding</th>
                </tr>
                @if(count($data['hours_worked']) > 0)
                <?php foreach ($data['hours_worked'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['staff']['firstName'] }} {{ $row['staff']['middleName'] }} {{ $row['staff']['lastName'] }}</td>
                        <td style="text-align: center">{{ $row['staff']['email'] }}</td>
                        <td style="text-align: center">{{ $row['worked_date'] }}</td>
                        <td style="text-align: center">{{ $row['worked_hrs'] }}</td>
                        <td style="text-align: center">{{ $row['staff']['staffpayroll']['payrate'] ?? "N/A" }}</td>
                        <td style="text-align: center">{{ $row['staff']['staffpayroll']['withholding'] ?? "N/A" }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif               

        @if(isset($data['is_salary']) && $data['is_salary'])
            <h3 style="margin-top:20px;">
                Salary
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Payrolled Staff Name</th>
                    <th>Staff Email</th>
                    <th>Worked Date</th>
                    <th>Worked Hours</th>
                    <th>Pay Rate</th>
                    <th>Withholding</th>
                </tr>
                @if(count($data['salary']) > 0)
                <?php foreach ($data['salary'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['staff']['firstName'] }} {{ $row['staff']['middleName'] }} {{ $row['staff']['lastName'] }}</td>
                        <td style="text-align: center">{{ $row['staff']['email'] }}</td>
                        <td style="text-align: center">{{ $row['worked_date'] }}</td>
                        <td style="text-align: center">{{ $row['worked_hrs'] }}</td>
                        <td style="text-align: center">{{ $row['staff']['staffpayroll']['payrate'] ?? "N/A" }}</td>
                        <td style="text-align: center">{{ $row['staff']['staffpayroll']['withholding'] ?? "N/A" }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>
        @endif

        @if(isset($data['is_calculated_overtime']) && $data['is_calculated_overtime'])
            <h3 style="margin-top:20px;">
                Calculated Overtime
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Payrolled Staff Name</th>
                    <th>Staff Email</th>
                    <th>Total Worked Hours</th>
                    <th>Pay Rate</th>
                    <th>Withholding</th>
                </tr>
                @if(count($data['hours_worked_summary']) > 0)
                <?php foreach ($data['hours_worked_summary'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['staff']['firstName'] }} {{ $row['staff']['middleName'] }} {{ $row['staff']['lastName'] }}</td>
                        <td style="text-align: center">{{ $row['staff']['email'] }}</td>                        
                        <td style="text-align: center">{{ $row['total_worked_hours'] }}</td>
                        <td style="text-align: center">{{ $row['staff']['staffpayroll']['payrate'] ?? "N/A" }}</td>
                        <td style="text-align: center">{{ $row['staff']['staffpayroll']['withholding'] ?? "N/A" }}</td>
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
