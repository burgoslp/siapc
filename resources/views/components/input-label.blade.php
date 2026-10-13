@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2']) }}>
    {{ $value ?? $slot }}
</label>