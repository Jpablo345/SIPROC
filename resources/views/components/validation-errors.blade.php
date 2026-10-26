@props(['bag'])

@if ($bag->any())
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700']) }}>
        <p class="font-semibold">Revisa los siguientes campos:</p>
        <ul class="mt-1 list-disc space-y-0.5 ps-5">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
