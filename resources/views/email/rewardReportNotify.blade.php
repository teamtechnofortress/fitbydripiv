<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Reward Report.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?></h4>

        @if(isset($data['totalRewardPurchases']))
            <h3 style="margin-top:20px;">Total Reward Program Purchases</h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Reward Amount (USD)</th>
                </tr>                
                @if($data['totalRewardPurchasesCount'] > 0)
                <?php 
                    $total = 0;
                    foreach ($data['totalRewardPurchases'] as $row) {  
                        $total += $row->totalPrice;
                ?>
                    <tr>
                        <td style="text-align: center">#00{{ $row->id }}</td>
                        <td style="text-align: center">{{ $row->first_name." ".$row->middle_name." ".$row->last_name }}</td>
                        <td style="text-align: center">{{ number_format($row->totalPrice, 2) }}</td>
                    </tr>
                <?php } ?>
                    <tr>
                        <td style="text-align: right" colspan="2"><strong>Total</strong></td>
                        <td style="text-align: center"><strong>$ {{ number_format($total, 2) }}</strong></td>
                    </tr>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 

        @if(isset($data['rewardGold']))
            <h3 style="margin-top:50px;">Reward Gold Purchases</h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Reward Amount (USD)</th>
                </tr>                
                @if(count($data['rewardGold']) > 0)
                <?php 
                    $total = 0;
                    foreach ($data['rewardGold'] as $row) {  
                        $total += $row->totalPrice;
                ?>
                    <tr>
                        <td style="text-align: center">#00{{ $row->id }}</td>
                        <td style="text-align: center">{{ $row->first_name." ".$row->middle_name." ".$row->last_name }}</td>
                        <td style="text-align: center">{{ number_format($row->totalPrice, 2) }}</td>
                    </tr>
                <?php } ?>
                    <tr>
                        <td style="text-align: right" colspan="2"><strong>Total</strong></td>
                        <td style="text-align: center"><strong>$ {{ number_format($total, 2) }}</strong></td>
                    </tr>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 

        @if(isset($data['rewardSilver']))
            <h3 style="margin-top:50px;">Reward Silver Purchases</h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Reward Amount (USD)</th>
                </tr>                
                @if(count($data['rewardSilver']) > 0)
                <?php 
                    $total = 0;
                    foreach ($data['rewardSilver'] as $row) {  
                        $total += $row->totalPrice;
                ?>
                    <tr>
                        <td style="text-align: center">#00{{ $row->id }}</td>
                        <td style="text-align: center">{{ $row->first_name." ".$row->middle_name." ".$row->last_name }}</td>
                        <td style="text-align: center">{{ number_format($row->totalPrice, 2) }}</td>
                    </tr>
                <?php } ?>
                    <tr>
                        <td style="text-align: right" colspan="2"><strong>Total</strong></td>
                        <td style="text-align: center"><strong>$ {{ number_format($total, 2) }}</strong></td>
                    </tr>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 

        @if(isset($data['rewardBronze']))
            <h3 style="margin-top:50px;">Reward Bronze Purchases</h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Reward Amount (USD)</th>
                </tr>                
                @if(count($data['rewardBronze']) > 0)
                <?php 
                    $total = 0;
                    foreach ($data['rewardBronze'] as $row) {  
                        $total += $row->totalPrice;
                ?>
                    <tr>
                        <td style="text-align: center">#00{{ $row->id }}</td>
                        <td style="text-align: center">{{ $row->first_name." ".$row->middle_name." ".$row->last_name }}</td>
                        <td style="text-align: center">{{ number_format($row->totalPrice, 2) }}</td>
                    </tr>
                <?php } ?>
                    <tr>
                        <td style="text-align: right" colspan="2"><strong>Total</strong></td>
                        <td style="text-align: center"><strong>$ {{ number_format($total, 2) }}</strong></td>
                    </tr>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 

        @if(isset($data['rewardDiscount']))
            <h3 style="margin-top:50px;">Reward Discounts</h3>
            <table border="1" cellpadding="8" cellspacing="0" width="75%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Discounts Amount (USD)</th>
                </tr>                
                @if(count($data['rewardDiscount']) > 0)
                <?php 
                    $total = 0;
                    foreach ($data['rewardDiscount'] as $row) {  
                        $total += $row->totalTip;
                ?>
                    <tr>
                        <td style="text-align: center">#00{{ $row->patient->id }}</td>
                        <td style="text-align: center">{{ $row->patient->first_name." ".$row->patient->last_name }}</td>
                        <td style="text-align: center">{{ number_format($row->totalTip, 2) }}</td>
                    </tr>
                <?php } ?>
                    <tr>
                        <td style="text-align: right" colspan="2"><strong>Total</strong></td>
                        <td style="text-align: center"><strong>$ {{ number_format($total, 2) }}</strong></td>
                    </tr>
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
