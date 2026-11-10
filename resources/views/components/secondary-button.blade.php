<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-secondary inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
