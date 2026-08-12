@props(['disabled' => false])

{{-- min-h-[44px]: alvo mínimo de toque no celular, onde o sistema é mais usado --}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-[44px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
