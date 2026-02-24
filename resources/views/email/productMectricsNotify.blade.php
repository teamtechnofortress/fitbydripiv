<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Product Metrics Contents.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?> </h4>          

        @if(isset($data['ivList']))
            <h3 style="margin-top:50px;">
                IV Product List (Count: <?= count($data['ivList']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['ivList']) > 0)
                <?php foreach ($data['ivList'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif    

        @if(isset($data['injectablesList']))
            <h3 style="margin-top:50px;">
                Injectable Product List (Count: <?= count($data['injectablesList']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['injectablesList']) > 0)
                <?php foreach ($data['injectablesList'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif       
        
        
        @if(isset($data['peptideList']))
            <h3 style="margin-top:50px;">
                Peptide Product List (Count: <?= count($data['peptideList']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['peptideList']) > 0)
                <?php foreach ($data['peptideList'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif   

        @if(isset($data['otherList']))
            <h3 style="margin-top:50px;">
                Other Product List (Count: <?= count($data['otherList']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['otherList']) > 0)
                <?php foreach ($data['otherList'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif        

        @if(isset($data['semaglutideList']))
            <h3 style="margin-top:50px;">
                Semaglutide Product List (Count: <?= count($data['semaglutideList']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['semaglutideList']) > 0)
                <?php foreach ($data['semaglutideList'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 

        @if(isset($data['tirzepatideList']))
            <h3 style="margin-top:50px;">
                Tirzepatide Product List (Count: <?= count($data['tirzepatideList']);?>)
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Name</th>                    
                    <th>Dosage</th>                    
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['tirzepatideList']) > 0)
                <?php foreach ($data['tirzepatideList'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>                        
                        <td style="text-align: center">{{ $row['enc_dosage'] }} {{ $row['unit'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
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
