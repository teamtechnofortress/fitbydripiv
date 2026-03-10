<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Chart History Contents.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">        
        <h3 style="margin-top:20px;">
            Patient Name: <?= $data['patient']['first_name'].' '.$data['patient']['middle_name'].' '.$data['patient']['last_name'] ?>
            ( <?= $data['due'];?> )
        </h3>
        
        @if($data['isEncounters'])
        <h3 style="margin-top:20px;">Encounters:</h3>
        <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
            <tr style="background-color:#f2f2f2;">
                <th>Type</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Encountered Date</th>
            </tr>
            @if(count($data['encounters']) > 0)
            <?php foreach ($data['encounters'] as $encounter) { ?>
                <?php 
                    $inventory = $encounter['inventory'];
                ?>
                <tr>
                    <td style="text-align: center">{{ $inventory['type'] ?? '' }}</td>
                    <td style="text-align: center">{{ $inventory['name'] ?? '' }}</td>
                    <td style="text-align: center">{{ $encounter['quantity'] ?? '' }}</td>                    
                    <td style="text-align: center">{{ date('Y-m-d H:i', strtotime($encounter['created_at'])) }}</td>
                </tr>
            <?php } ?>
            @else
                <tr>
                    <td style="text-align: center" colspan="7">~ No Data ~</td>
                </tr>
            @endif
        </table>
        @endif

        @if($data['isProducts'])
        <h3 style="margin-top:20px;">Products:</h3>
        <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
            <tr style="background-color:#f2f2f2;">
                <th>Product Type</th>
                <th>Product Name</th>
                <th>Dosage</th>
                <th>Ingredients</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Purchased Date</th>
            </tr>
            @if(count($data['encounters']) > 0)
            <?php foreach ($data['encounters'] as $encounter) { ?>
                <?php 
                    $inventory = $encounter['inventory'];
                ?>
                <tr>
                    <td style="text-align: center">{{ $inventory['type'] ?? '' }}</td>
                    <td style="text-align: center">{{ $inventory['name'] ?? '' }}</td>
                    <td style="text-align: center">{{ $inventory['inject_dosage'] ?? '' }}</td>
                    <td style="text-align: center">{{ $inventory['ingredients'] ?? '' }}</td>
                    <td style="text-align: center">{{ $encounter['quantity'] ?? '' }}</td>                    
                    <td style="text-align: center">{{number_format($inventory['price'], 2)}}</td>
                    <td style="text-align: center">{{ date('Y-m-d H:i', strtotime($encounter['created_at'])) }}</td>
                </tr>
            <?php } ?>
            @else
                <tr>
                    <td style="text-align: center" colspan="7">~ No Data ~</td>
                </tr>
            @endif
        </table>
        @endif

        @if($data['hasNotes'])
        <h3 style="margin-top:20px;">Notes From Encounter:</h3>                
        <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
            <tr style="background-color:#f2f2f2;">
                <th>Note</th>                
                <th>Noted Date</th>
            </tr>
            @if(count($data['notes']) > 0)
                @foreach($data['notes'] as $note)
                    <tr>
                        <td>{!! nl2br(e($note['notes'])) !!}</td>
                        <td>{{ date('Y-m-d', strtotime($note->created_at)) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td style="text-align: center" colspan="7">~ No Data ~</td>
                </tr>
            @endif
        </table>
        @endif       
    </div>
    <br>    
</x-guest-layout>
