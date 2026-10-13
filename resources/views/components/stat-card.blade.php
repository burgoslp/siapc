@props(['title', 'value', 'change', 'isPositive' => true, 'iconBg' => 'bg-teal-50 text-teal-600'])

<div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
    <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $title }}</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $value }}</p>
        <div class="flex items-center mt-2 text-xs {{ $isPositive ? 'text-emerald-600' : 'text-rose-600' }}">
            <span class="font-semibold">{{ $change }}</span>
            <span class="text-slate-400 ml-1.5">vs mes anterior</span>
        </div>
    </div>
    <div class="p-3.5 {{ $iconBg }} rounded-2xl">
        {{ $slot }}
    </div>
</div>