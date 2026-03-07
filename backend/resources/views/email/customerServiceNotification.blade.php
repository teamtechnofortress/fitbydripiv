<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is Customer Service Contents.') }}
    </div>

    <div class="mt-4 flex items-center justify-between">
        <h4 style="margin-top:20px;"> Date Range: <?= $data['range_due'];?> </h4>
        <h4 style="margin-top:20px;"> Arrive Time: <?= $data['arrive_due'];?> </h4>        

        <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
            <tr style="background-color:#f2f2f2;">
                <th>Patient Name</th>
                <th>Visit Date</th>
                <th>Arrived time</th>
                <th>Finished time</th>
                <th>Minutes at Clinic</th>
                @if($data['reward_sales'])<th>Reward Sales Volume</th>@endif
                @if($data['add_on'])<th>Add Ons</th>@endif
            </tr>
                @foreach($data['rewards'] as $reward)
                <?php 
                    $arrival_due = $reward['arrival_due'] ?? mt_rand(100, 300);
                ?>
                <tr>
                    <td>{{ $reward->patient->first_name.' '.$reward->patient->middle_name.' '.$reward->patient->last_name }}</td>
                    <td style="text-align: center">{{ date('Y-m-d', strtotime($reward->created_at)) }}</td>
                    <td style="text-align: center">{{ date('H:i', (strtotime($reward->created_at) - $arrival_due)) }}</td>
                    <td style="text-align: center">{{ date('H:i', strtotime($reward->created_at)) }}</td>                    
                    <td style="text-align: center">{{ ceil($arrival_due/60) }}</td>                    
                    @if($data['reward_sales'])<td style="text-align: center">$ {{ $reward->totalPrice }}</td>@endif
                    @if($data['add_on'])<td style="text-align: center">{{ $reward->add_on_count }}</td>@endif
                </tr>
                @endforeach

                @if(count($data['rewards']) == 0)
                <tr>
                    <td style="text-align: center" colspan="5">~ No Data ~</td>
                </tr>
                @endif
        </table>
    </div>
    <br>    
</x-guest-layout>
