<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-teal-600/20 transition-all focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 outline-none'
]) }}>
    {{ $slot }}
</button>