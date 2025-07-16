@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge([
    'class' => 'h-10 px-3 py-2 border-gray-300 dark:border-gray-700 dark:bg-bodybg2 dark:text-gray-300 focus:border-primary dark:focus:border-primary focus:ring-primary dark:focus:ring-primary rounded-md shadow-sm'
]) }}>
    {{ $slot }}
</select>