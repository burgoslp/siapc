@props(['disabled' => false])

<input @disabled($disabled)
       {{ $attributes->merge([
            'class' => 'w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-siapc-blue focus:border-siapc-blue outline-none transition-all'
       ]) }}>