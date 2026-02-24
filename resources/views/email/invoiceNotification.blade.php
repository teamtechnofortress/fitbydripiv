<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {!! nl2br(e(__($data['invoice_intro_text']))) !!}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <?php foreach ($data['content'] as $serviceType => $encounters){  ?>
            <h3 style="margin-top:20px;">Service Type: <?= $serviceType?></h3>                
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>
                    <th><?= $serviceType == 'IV' ? 'Ingredients' : 'Dosage'?></th>
                    <th><?= $serviceType == 'IV' ? 'Dosage' : 'Quantity'?></th>
                    <th>Price</th>
                    <th>Discount (%)</th>
                    <th>Net Price</th>
                </tr>
                <?php $subTotal = 0; ?>
                <?php foreach ($encounters as $encounter) { ?>
                    <?php 
                        $price = floatval($encounter['inventory']['price'] ?? 0);
                        $discount = floatval($encounter['inventory']['discount'] ?? 0);
                        $net = $price * (1 - $discount / 100);
                        $subTotal = $subTotal + $net;
                    ?>
                    <tr>
                        <td style="text-align: center">{{ $encounter['name'] ?? '' }}</td>
                        @if($serviceType=='IV')
                            <td style="text-align: center">{{ $encounter['ingredients'] ?? '' }}</td>
                            <td style="text-align: center">{{ $encounter['dosage'] ?? '' }}</td>
                        @else
                            <td style="text-align: center">{{ $encounter['dosage'] ?? '' }}</td>
                            <td style="text-align: center">{{ $encounter['quantity'] ?? '' }}</td> 
                        @endif
                        <td style="text-align: center">{{number_format($price, 2)}}</td>
                        <td style="text-align: center">{{number_format($discount, 2)}}</td>
                        <td style="text-align: center">{{number_format($net, 2)}}</td>
                    </tr>
                <?php } ?>            
            </table>
        <?php } ?> 
        @if(!empty($data['risk_note']))
        <h3 style="margin-top:20px;">Risk Note:</h3>        
        <div>{!! nl2br(e($data['risk_note'])) !!}</div>
        @endif            
    <br>            
    Tip: {{ $data['tip'] }} USD, Tax: {{ number_format($subTotal * $data['tax']/100, 2) }} USD<br>
    Total: {{ $data['totalPrice'] }} USD
    </div> 
</x-guest-layout>
