<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Payroll Report.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?></h4>

        @if(isset($data['is_iv_solutions']) && $data['is_iv_solutions'])
            <h3 style="margin-top:20px;">
                IV Solutions Report
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Ingredients</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['iv_solutions']) > 0)
                <?php 
                    foreach ($data['iv_solutions'] as $row) { 
                        $patient = $row['patient'];
                ?>
                    <tr>
                        <td style="text-align: center">{{ $patient['first_name']." ".$patient['middle_name']." ".$patient['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['inventory']['name'] }}</td>
                        <td style="text-align: center">{{ $row['dosage'] }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>
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

        @if(isset($data['is_injectable']) && $data['is_injectable'])
            <h3 style="margin-top:20px;">
                Injectable Report
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Ingredients</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['injectable']) > 0)
                <?php 
                    foreach ($data['injectable'] as $row) { 
                        $patient = $row['patient'];
                ?>
                    <tr>
                        <td style="text-align: center">{{ $patient['first_name']." ".$patient['middle_name']." ".$patient['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['inventory']['name'] }}</td>
                        <td style="text-align: center">{{ $row['dosage'] }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>
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

        @if(isset($data['is_peptides']) && $data['is_peptides'])
            <h3 style="margin-top:20px;">
                Peptides Report
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Ingredients</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['peptides']) > 0)
                <?php 
                    foreach ($data['peptides'] as $row) { 
                        $patient = $row['patient'];
                ?>
                    <tr>
                        <td style="text-align: center">{{ $patient['first_name']." ".$patient['middle_name']." ".$patient['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['inventory']['name'] }}</td>
                        <td style="text-align: center">{{ $row['dosage'] }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>
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

        @if(isset($data['is_consumables']) && $data['is_consumables'])
            <h3 style="margin-top:20px;">
                Consumables Report
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Ingredients</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['consumables']) > 0)
                <?php 
                    foreach ($data['consumables'] as $row) { 
                        $patient = $row['patient'];
                ?>
                    <tr>
                        <td style="text-align: center">{{ $patient['first_name']." ".$patient['middle_name']." ".$patient['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['inventory']['name'] }}</td>
                        <td style="text-align: center">{{ $row['dosage'] }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>
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

        @if(isset($data['is_on_hand']) && $data['is_on_hand'])
            <h3 style="margin-top:20px;">
                On Hand Report
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Ingredients</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['on_hand']) > 0)
                <?php 
                    foreach ($data['on_hand'] as $row) { 
                        $patient = $row['patient'];
                ?>
                    <tr>
                        <td style="text-align: center">{{ $patient['first_name']." ".$patient['middle_name']." ".$patient['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['inventory']['name'] }}</td>
                        <td style="text-align: center">{{ $row['dosage'] }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>
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

        @if(isset($data['is_sold_invoice']) && $data['is_sold_invoice'])
            <h3 style="margin-top:20px;">
                Sold Invoice Report
            </h3>
            <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
                <tr style="background-color:#f2f2f2;">
                    <th>Patient Name</th>
                    <th>Ingredients</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Created at</th>
                </tr>
                @if(count($data['sold_invoice']) > 0)
                <?php 
                    foreach ($data['sold_invoice'] as $row) { 
                        $patient = $row['patient'];
                ?>
                    <tr>
                        <td style="text-align: center">{{ $patient['first_name']." ".$patient['middle_name']." ".$patient['last_name'] }}</td>
                        <td style="text-align: center">{{ $row['inventory']['name'] }}</td>
                        <td style="text-align: center">{{ $row['dosage'] }}</td>
                        <td style="text-align: center">{{ $row['quantity'] }}</td>
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
