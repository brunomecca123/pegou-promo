<x-app-layout>
    <x-cards.card>
        @foreach ($requestSents as $requestSent)
            @include('logs.components.loading', ['findRequestSentToday' => $requestSent])
        @endforeach

        <form>
            @php
                $selectedType = request('type', 'moodle_grades');
            @endphp
            <ul
                class="flex flex-wrap text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 mb-4">
                <li class="me-2">
                    <button type="submit" name="type" value="moodle_grades"
                        class="inline-block p-4 rounded-t-lg focus:outline-none
                            {{ $selectedType === 'moodle_grades' ? 'text-white hover:!bg-primaryHover !bg-primary active dark:bg-primary dark:text-white' : 'hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300' }}">
                        Sincronização de Notas
                    </button>
                </li>
                <li class="me-2">
                    <button type="submit" name="type" value="moodle_zero_grades"
                        class="inline-block p-4 rounded-t-lg focus:outline-none
                            {{ $selectedType === 'moodle_zero_grades' ? 'text-white !bg-primary hover:!bg-primaryHover active dark:bg-primary dark:text-white' : 'hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300' }}">
                        Sincronização de Zeros
                    </button>
                </li>
            </ul>

            <div class="grid grid-cols-1 md:grid-cols-8 lg:grid-cols-12 gap-4 mb-4">
                <div class="col-span-6">
                    <x-input-label for="student_name" value="Nome do Estudante" />
                    <x-text-input id="student_name" name="student_name" value="{{ request('student_name') }}"
                        type="text" class="mt-1 block w-full" placeholder="Pesquisar estudantes..." />
                </div>
                <div class="col-span-6">
                    <x-input-label for="created_by" value="Importação criada por" />
                    <x-select id="created_by" name="created_by" class="mt-1 block w-full">
                        <option value="">Nenhum</option>
                        @foreach ($getUsersToRequestSents as $getUserToRequestSent)
                            <option value="{{ $getUserToRequestSent['id'] }}"
                                @if(request('created_by') == $getUserToRequestSent['id']) selected @endif>
                                {{ $getUserToRequestSent['name'] }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="col-span-3">
                    <x-input-label for="status" value="Status" />
                    <x-select id="status" name="status" class="mt-1 block w-full">
                        <option value="">Todos</option>
                        <option value="{{ \App\Enums\MoodleGradeStatus::IMPORTED->value }}"
                            @if(request('status') == \App\Enums\MoodleGradeStatus::IMPORTED->value) selected @endif>
                            Importados</option>
                        <option value="{{ \App\Enums\MoodleGradeStatus::ERROR->value }}"
                            @if(request('status') == \App\Enums\MoodleGradeStatus::ERROR->value) selected @endif>Erro
                        </option>
                        <option value="{{ \App\Enums\MoodleGradeStatus::PENDING->value }}"
                            @if(request('status') == \App\Enums\MoodleGradeStatus::PENDING->value) selected @endif>
                            Pendente</option>
                    </x-select>
                </div>
                <div class="col-span-3">
                    <x-input-label for="date_request_sent" value="Data da requisição" />
                    <x-text-input id="date_request_sent" type="date" name="date_request_sent" class="mt-1 block w-full"
                        value="{{ request('date_request_sent') }}" />
                </div>
                <div class="col-span-3">
                    <x-input-label for="student_moodle_id" value="Id do Aluno Moodle" />
                    <x-text-input id="student_moodle_id" name="student_moodle_id" type="number"
                        class="mt-1 block w-full" value="{{ request('student_moodle_id') }}" />
                </div>
                <div class="col-span-3">
                    <x-input-label for="course_moodle_id" value="Id do Curso Moodle" />
                    <x-text-input id="course_moodle_id" name="course_moodle_id" type="number" class="mt-1 block w-full"
                        value="{{ request('course_moodle_id') }}" />
                </div>
            </div>
            <div class="mb-4 flex justify-end gap-2">
                <x-danger-button type="button" class="mt-4" onclick="window.location.href='{{ route('logs.index') }}'">
                    <span class="material-symbols-outlined">
                        clear
                    </span>
                    Limpar Filtros
                </x-danger-button>
                <x-primary-button class="mt-4">
                    <span class="material-symbols-outlined">
                        search
                    </span>
                    Pesquisar
                </x-primary-button>
            </div>
        </form>

        <x-table>
            <thead class="bg-gray-100 dark:bg-bodybg2">
                <tr>
                    <th
                        class="text-center px-6 py-3 text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        {{-- <x-text-input type="checkbox" id="select-all" /> --}}
                        ID
                    </th>
                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Nome</th>
                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        ID Aluno Moodle
                    </th>
                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        ID Curso Moodle
                    </th>
                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Atividade
                    </th>

                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Status
                    </th>
                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Data da Importação
                    </th>
                    <th
                        class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Ações</th>
                </tr>
            </thead>


            @foreach ($logs as $log)
                <tr class="border border-gray-200 dark:border-[#444448]">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{-- <x-text-input type="checkbox" class="row-checkbox" /> --}}
                        {{ $log->id }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $log->name }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $log->moodle_user_id }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $log->moodle_course_id }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $log->moodle_atividade }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ __($log->status->name) }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ $log->requestSent->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="text-center px-6 py-4 whitespace-nowrap text-sm">
                        <a href="#" class="text-green-600 hover:text-green-400" data-modal-target="show-modal-{{$log->id}}"
                            data-modal-toggle="show-modal-{{$log->id}}">
                            <span class="material-symbols-outlined">
                                visibility
                            </span>
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-table>
        @foreach ($logs as $log)
            @include('logs.modals.show', ['log' => $log])
        @endforeach

        <div>
            {{ $logs->links() }}
        </div>
    </x-cards.card>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.row-checkbox');

        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    });

    function showSyncLoading(requestId, count) {
        const loadingElement = document.getElementById(`sync-loading-${requestId}`);
        const countElement = document.getElementById(`sync-count-${requestId}`);
        if (loadingElement) {
            loadingElement.style.display = 'flex';
        }
        if (countElement) {
            countElement.textContent = count;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        @foreach ($requestSents as $requestSent)
            @if ($requestSent->status === 'PROCESSING')
                showSyncLoading({{ $requestSent->id }}, {{ $requestSent->moodleZeroGrades->where('status', 'IMPORTED')->count() }});
            @endif
        @endforeach
    });
</script>