@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold text-siapc-navy uppercase tracking-wider mb-2']) }}>
    {{ $value ?? $slot }}
</label>