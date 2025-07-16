<!-- Main modal -->
<div id="show-modal-{{$log->id}}" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">

    <!-- Overlay personalizado -->
    <div class="fixed inset-0 bg-[
    #1a1a2a]/70 backdrop-blur-sm z-[-1]"></div>

    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <!-- Modal content -->
        <div class="relative bg-[#f5f5f5] rounded-lg shadow-lg dark:bg-bodybg2">
            <!-- Modal header -->
            <div
                class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Log do aluno - {{ $log->name }}
                </h3>
                <button type="button"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                    data-modal-hide="show-modal-{{$log->id}}">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-6 space-y-6">
                <div class="p-4 rounded-lg shadow-inner border-2 
                    @if($log->status === \App\Enums\MoodleGradeStatus::IMPORTED)
                        border-green-500 bg-green-50 dark:bg-green-900/20 dark:border-green-400
                    @elseif($log->status === \App\Enums\MoodleGradeStatus::PENDING)
                        border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-400
                    @elseif($log->status === \App\Enums\MoodleGradeStatus::ERROR)
                        border-red-500 bg-red-50 dark:bg-red-900/20 dark:border-red-400
                    @else
                        border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-600
                    @endif">

                    <div class="flex items-center gap-2 mb-4">
                        @if($log->status === \App\Enums\MoodleGradeStatus::IMPORTED)
                            <svg class="w-6 h-6 text-green-500 dark:text-green-300" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="text-green-700 dark:text-green-300 font-bold">Status: IMPORTADO</span>
                        @elseif($log->status === \App\Enums\MoodleGradeStatus::PENDING)
                            <svg class="w-6 h-6 text-yellow-500 dark:text-yellow-300" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="text-yellow-700 dark:text-yellow-300 font-bold">Status: PENDENTE</span>
                        @elseif($log->status === \App\Enums\MoodleGradeStatus::ERROR)
                            <svg class="w-6 h-6 text-red-500 dark:text-red-300" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="text-red-700 dark:text-red-300 font-bold">Status: ERRO</span>
                        @endif
                    </div>

                    <p class="text-sm text-gray-800 dark:text-gray-200 space-y-1">
                        <strong>Nome:</strong> {{ $log->name }}<br>
                        <strong>ID Aluno Moodle:</strong> {{ $log->moodle_user_id }}<br>
                        <strong>ID Curso Moodle:</strong> {{ $log->moodle_course_id }}<br>
                        <strong>Atividade:</strong> {{ $log->moodle_atividade }}<br>
                        <strong>ID Requisição:</strong> {{ $log->request_sent_id }}<br>
                        <strong>Tipo de Requisição:</strong> {{ __($log->requestSent->source) }}<br>
                        <strong>Data da Importação</strong> {{($log->requestSent->created_at)->format('d/m/Y H:i ')}}<br>
                        <strong>Realizado por:</strong> {{ $log->requestSent->user->name ?? 'N/A' }}<br>
                    </p>
                </div>
            </div>



            <!-- Modal footer -->
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
                {{-- <button data-modal-hide="show-modal-{{$log->id}}" type="button"
                    class="text-white !bg-primary hover:!bg-primaryHover focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary dark:hover:bg-primaryHover dark:focus:ring-primaryHover">I
                    accept
                </button> --}}
                <button data-modal-hide="show-modal-{{$log->id}}" type="button"
                    class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Fechar</button>
            </div>
        </div>
    </div>
</div>