<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('INTAKE NOTIFICATION!') }}
    </div>

    <div class="mt-4 flex items-center justify-between">       
        {!! nl2br(e($data)) !!}
    </div>
</x-guest-layout>
