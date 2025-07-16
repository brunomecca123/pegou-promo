<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 !bg-primary dark:bg-primary border border-transparent rounded-md font-semibold text-xs text-white dark:text-white uppercase tracking-widest hover:!bg-primaryHover dark:hover:bg-primaryHover focus:outline-none transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>