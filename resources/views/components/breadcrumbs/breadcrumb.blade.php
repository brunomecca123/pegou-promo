@php
    use Illuminate\Support\Facades\Request;

    $segments = Request::segments();
    $url = '';
    $customLabels = [
        'dashboard' => 'Painel de Controle',
        'usuarios' => 'Usuários',
        'editar' => 'Editar',
        'configuracoes' => 'Configurações',
    ];
@endphp

<div class="ml-8 px-4 pt-4">
    <nav class="text-gray-700 text-sm mb-4" aria-label="Breadcrumb">
        <ol class="list-none p-0 inline-flex flex-wrap space-x-2">
            <li class="inline-flex items-center">
                <a href="#" class="text-primary text-xl hover:underline">Início</a>
            </li>

            @foreach ($segments as $index => $segment)
                @php
                    $url .= '/' . $segment;
                    $isLast = $index === array_key_last($segments);
                    $label = $customLabels[$segment] ?? ucwords(str_replace('-', ' ', $segment));
                @endphp

                <li>
                    <span class="mx-2 text-xl">/</span>
                </li>

                <li class="inline-flex items-center">
                    @if ($isLast)
                        <span class="text-gray-500 text-xl">{{ $label }}</span>
                    @else
                        <a href="{{ url($url) }}" class="text-primary hover:underline">{{ $label }}</a>
                    @endif
                </li>
            @endforeach

            {{-- Slot opcional para customizações extras --}}
            {{ $slot }}
        </ol>
    </nav>
</div>
