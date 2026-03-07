<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {!! nl2br(e($data['invoice_intro_text'] ?? '')) !!}
    </div>

    <h3 style="margin-top:20px;">
       Payment Summary
    </h3>
    <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <th>Payment Type</th>
            <th>Number of Used</th>
            <th>Paid Amount ($)</th>                
        </tr>   
        @if(isset($data['creditCardPayment']))
            <tr>
                <td style="text-align: center">Credit Card</td>
                <td style="text-align: center">{{ $data['creditCardPayment']->cardCount }}</td>
                <td style="text-align: center">{{ number_format($data['creditCardPayment']->paidAmount, 2) }}</td>
            </tr>
        @endif         
        @if(isset($data['cashPayment']))
            <tr>
                <td style="text-align: center">Cash</td>
                <td style="text-align: center">{{ $data['cashPayment']->cardCount }}</td>
                <td style="text-align: center">{{ number_format($data['cashPayment']->paidAmount, 2) }}</td>
            </tr>
        @endif               
        @if(isset($data['paypalPayment']))
            <tr>
                <td style="text-align: center">Paypal</td>
                <td style="text-align: center">{{ $data['paypalPayment']->cardCount }}</td>
                <td style="text-align: center">{{ number_format($data['paypalPayment']->paidAmount, 2) }}</td>
            </tr>
        @endif                                     
        @if(isset($data['venmoPayment']))
            <tr>
                <td style="text-align: center">Venmo</td>
                <td style="text-align: center">{{ $data['venmoPayment']->cardCount }}</td>
                <td style="text-align: center">{{ number_format($data['venmoPayment']->paidAmount, 2) }}</td>
            </tr>
        @endif               
        @if(isset($data['cashappPayment']))
            <tr>
                <td style="text-align: center">Cashapp</td>
                <td style="text-align: center">{{ $data['cashappPayment']->cardCount }}</td>
                <td style="text-align: center">{{ number_format($data['cashappPayment']->paidAmount, 2) }}</td>
            </tr>
        @endif               
        @if(isset($data['cryptoPayment']))
            <tr>
                <td style="text-align: center">Crypto</td>
                <td style="text-align: center">{{ $data['cryptoPayment']->cardCount }}</td>
                <td style="text-align: center">{{ number_format($data['cryptoPayment']->paidAmount, 2) }}</td>
            </tr>
        @endif        
    </table>                

    <div class="mt-4">
        @if(isset($data['salesDetail']))            
            <h3 style="margin-top: 50px;">
                Payment Details (Count: <?= count($data['salesDetail']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Therapy Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Purchased Date</th>
                    <th>Sub Price(US$)</th>
                </tr>
                @if(count($data['salesDetail']) > 0)
                <?php 
                    $totalPrice = 0;
                    foreach ($data['salesDetail'] as $row) { 
                        $totalPrice = $totalPrice + $row['sub_price'];
                ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                    </tr>
                <?php } ?>
                    <tr>
                        <td style="text-align: center" colspan="5">Total Price</td>
                        <td style="text-align: center" colspan="6">{{ number_format($totalPrice, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>   
        @endif

        @if(isset($data['salesTaxes']))            
            <h3 style="margin-top: 50px;">
                Sales Tax Collected (Count: <?= count($data['salesTaxes']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>                    
                    <th>Tax (%)</th>                    
                    <th>Date</th>                    
                    <th>Tax Price(US$)</th>                    
                </tr>
                @if(count($data['salesTaxes']) > 0)
                <?php   
                    $totalTax = 0;                  
                    foreach ($data['salesTaxes'] as $row) {         
                        $totalTax = $totalTax + $row->tax;              
                ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row->patient->first_name.' '.$row->patient->last_name }}</td>                        
                        <td style="text-align: center">{{ $row->tax }}</td>                        
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>                        
                        <td style="text-align: center">{{ number_format($row->totalPrice*$row->tax/100, 2) }}</td>                        
                    </tr>
                <?php } ?>    
                <tr>
                    <td style="text-align: center" colspan="3">Total</td>
                    <td style="text-align: center" colspan="1">{{ number_format($totalTax, 2) }}</td>
                </tr>               
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>   
        @endif
        
    </div>
</x-guest-layout>
