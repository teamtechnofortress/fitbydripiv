<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Email/Text Marketing Reward Report.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?></h4>

        @if(isset($data['emailTextRewardReport']['email_sent']) && $data['emailTextRewardReport']['email_sent'])
            <h3 style="margin-top:20px;">
                Email Marketing Sent
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Email</th>
                    <th>Patient Name</th>
                    <th>Email Content</th>
                    <th>Send Date</th>                    
                </tr>
                @if(count($data['emailMarketingSent']) > 0)
                <?php foreach ($data['emailMarketingSent'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['email'] }}</td>
                        <td style="text-align: center">{{ $row['first_name'] }} {{ $row['middle_name'] }} {{ $row['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['content'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['send_date'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif          

        @if(isset($data['emailTextRewardReport']['text_sent']) && $data['emailTextRewardReport']['text_sent'])
            <h3 style="margin-top:20px;">
                Text Marketing Sent
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Email</th>
                    <th>Patient Name</th>
                    <th>Text Message</th>
                    <th>Send Date</th>                    
                </tr>
                @if(count($data['textMarketingSent']) > 0)
                <?php foreach ($data['textMarketingSent'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['email'] }}</td>
                        <td style="text-align: center">{{ $row['first_name'] }} {{ $row['middle_name'] }} {{ $row['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['message'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['send_date'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif    
                   
        @if(isset($data['emailTextRewardReport']['reward_sent']) && $data['emailTextRewardReport']['reward_sent'])
            <h3 style="margin-top:20px;">
                Reward Marketing Sent
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Email</th>
                    <th>Patient Name</th>
                    <th>Reward Content</th>
                    <th>Send Date</th>                    
                </tr>
                @if(count($data['rewardMarketingSent']) > 0)
                <?php foreach ($data['rewardMarketingSent'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['email'] }}</td>
                        <td style="text-align: center">{{ $row['first_name'] }} {{ $row['middle_name'] }} {{ $row['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['content'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['send_date'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif               

        @if(isset($data['emailTextRewardReport']['birth_sent']) && $data['emailTextRewardReport']['birth_sent'])
            <h3 style="margin-top:20px;">
                Birthday Marketing Sent
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Email</th>
                    <th>Patient Name</th>
                    <th>Birthday</th>
                    <th>Message</th>
                    <th>Send Date</th>                    
                </tr>
                @if(count($data['birthdayMarketingSent']) > 0)
                <?php foreach ($data['birthdayMarketingSent'] as $row) { ?>
                    <tr>
                        <td style="text-align: center">{{ $row['email'] }}</td>
                        <td style="text-align: center">{{ $row['first_name'] }} {{ $row['middle_name'] }} {{ $row['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['birthday'] }}</td>
                        <td style="text-align: center">{{ $row['content'] }} {{ $row['first_name'] }} {{ $row['middle_name'] }} {{ $row['last_name'] }}</td>
                        <td style="text-align: center">{{ date('Y-m-d H:i:s', strtotime($row['send_date'])) }}</td>
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
