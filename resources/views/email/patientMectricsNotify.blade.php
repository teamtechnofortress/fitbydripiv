<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Patient Metrics Contents.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Report Type: <?= $data['patient_report_type'];?> </h4>
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?> </h4>        

        @if(isset($data['rewards']))
            <h3 style="margin-top:20px;">
                Reward Patient List
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>                    
                    <th>Bought</th>
                    <th>Purchased Date</th>
                </tr>
                @if(count($data['rewards']) > 0)
                    <?php foreach ($data['rewards'] as $row) { ?>
                        <tr>
                            <td style="text-align: center;">#00{{ $row->patient_id }}</td>
                            <td style="text-align: center;">{{ $row->first_name. ' ' . $row->last_name }}</td>                            
                            <td style="text-align: center;">{{  number_format($row->totalPrice, 2) }}</td>
                            <td style="text-align: center;">{{  date('Y-m-d H:i', strtotime($row->created_at)) }}</td>
                        </tr>
                    <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="6">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 
        

        @if(isset($data['add_on_purchase']))
            <h3 style="margin-top:20px;">
                Add-On Purchase
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Add-On Name</th>
                    <th>Type</th>
                    <th>Dosage</th>
                    <th>Ingredients</th>
                    <th>Price(US$)</th>
                    <th>Quantity</th>                    
                    <th>Sub Price(US$)</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['add_on_purchase']) > 0)
                <?php foreach ($data['add_on_purchase'] as $row) { ?>                      
                    <tr>
                        <td style="text-align: center">{{ $row['patient_name'] }}</td>
                        <td style="text-align: center">{{ $row['enc_name'] }}</td>
                        <td style="text-align: center">{{ $row['enc_type'] }}</td>
                        <td style="text-align: center">{{ $row['enc_dosage'] }}</td>
                        <td style="text-align: center">{{ $row['enc_ingredients'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['inv_price'], 2) }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>                        
                        <td style="text-align: center">{{ number_format($row['sub_price'], 2) }}</td>
                        <td style="text-align: center">{{  date('Y-m-d H:i:s', strtotime($row['created_at'])) }}</td>
                    </tr>
                <?php } ?>
                @else
                    <tr>
                        <td style="text-align: center" colspan="9">~ No Data ~</td>
                    </tr>
                @endif
            </table>            
        @endif 
        
        @if(isset($data['utilized_discounts']))
            <h3 style="margin-top:20px;">
                Utilized Discount
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient ID</th>
                    <th>Patient Name</th>                    
                    <th>Discount</th>
                    <th>Utilized Date</th>
                </tr>
                @if(count($data['utilized_discounts']) > 0)
                    <?php foreach ($data['utilized_discounts'] as $row) { ?>
                        <tr>
                            <td style="text-align: center;">#00{{ $row->patient_id }}</td>
                            <td style="text-align: center;">{{ $row->patient->first_name. ' ' . $row->patient->last_name }}</td>
                            <td style="text-align: center;">{{  number_format($row->tip, 2) }}</td>
                            <td style="text-align: center;">{{  date('Y-m-d H:i', strtotime($row->created_at)) }}</td>
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
