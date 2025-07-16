@php
    $syncId = 'sync-loading-' . $findRequestSentToday->id;
    $syncCountId = 'sync-count-' . $findRequestSentToday->id;

    $data = $findRequestSentToday?->created_at;
    $countImportedStudents = $findRequestSentToday?->moodleZeroGrades->where('status', 'IMPORTED')->count();
    $errorStudents = $findRequestSentToday?->moodleZeroGrades->where('status', 'ERROR');
    $countErrorStudents = $errorStudents->count();
    $countAllStudents = $findRequestSentToday?->moodleZeroGrades->count();

    $isZeroGrade = $findRequestSentToday?->source === 'send_zero_grade';
    $isProcessing = $findRequestSentToday?->status === 'PROCESSING';
    $isSuccess = $findRequestSentToday?->status === 'IMPORTED';
    $isError = $findRequestSentToday?->status === 'ERROR';

    $statusColor = $isSuccess ? 'text-green-700 dark:text-green-400' :
        ($isError ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400');

    $statusBg = $isSuccess ? 'bg-green-100 dark:bg-green-900/40' :
        ($isError ? 'bg-red-100 dark:bg-red-900/40' : 'bg-amber-100 dark:bg-amber-900/40');

    $statusText = match (true) {
        $isSuccess => 'Sincronização concluída com sucesso',
        $isProcessing => 'Sincronizando notas',
        $isError => 'Falha na sincronização',
        default => 'Status indefinido'
    };

    // Novo texto para o tipo de sincronização
    $sourceText = match ($findRequestSentToday?->source) {
        'sync_manual_moodle_jacad' => 'Sincronização manual de notas',
        'auto_sync_jacad' => 'Sincronização automática de notas',
        'send_zero_grade' => 'Notas 0',
        default => 'Notas Gerais'
    };

    $errorNames = $errorStudents->pluck('name')->filter()->implode(', ');
@endphp

<div id="{{ $syncId }}"
    class="flex items-start mb-4 p-4 rounded-lg shadow-sm border {{ $statusBg }} border-gray-300 dark:border-gray-600">
    <svg class="animate-spin h-5 w-5 mr-3 mt-1
        @if($isSuccess) text-green-600 dark:text-green-400
        @elseif($isError) text-red-600 dark:text-red-400
            @else text-amber-600 dark:text-amber-400
        @endif" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z" />
    </svg>

    <div class="text-sm">
        <div class="font-semibold {{ $statusColor }}">
            {{ $statusText }} — <span class="font-normal">{{ $sourceText }}</span>
        </div>

        <div class="text-gray-700 dark:text-gray-300 mt-1">
            <strong>{{ $countImportedStudents }}</strong> de {{ $countAllStudents }} alunos importados

            @if($data)
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                    ({{ \Carbon\Carbon::parse($data)->format('d/m/Y H:i') }})
                </span>
            @endif
        </div>

        @if($countErrorStudents > 0)
            <div class="text-sm mt-1">
                <span class="text-red-700 dark:text-red-400 underline hover:cursor-help" title="{{ $errorNames }}">
                    <strong>{{ $countErrorStudents }}</strong> aluno{{ $countErrorStudents > 1 ? 's' : '' }} com erro na
                    importação
                </span>
            </div>
        @endif

        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Criado por: {{ $findRequestSentToday?->user?->name ?? 'Desconhecido' }} <br>
            <span class="text-gray-500 dark:text-gray-400">
                ID da Requisição: {{ $findRequestSentToday?->id }}
            </span>
        </div>
    </div>
</div>