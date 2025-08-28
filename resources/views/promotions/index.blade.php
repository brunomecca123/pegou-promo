<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header com estatísticas -->
            <x-cards.card>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">Gerenciamento de Promoções</h2>
                        <button id="scrapeBtn" class="bg-info hover:bg-infoHover text-white font-bold py-2 px-4 rounded">
                            🔍 Buscar Novas Promoções
                        </button>
                    </div>

                    <!-- Estatísticas -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-100 dark:bg-blue-900 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-200">{{ $stats['total'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Total</div>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-200">{{ $stats['pending'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Pendentes</div>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-700 dark:text-green-200">{{ $stats['posted'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Publicadas</div>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-200">{{ $stats['today'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Hoje</div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="border border-gray-500 p-4 rounded-lg mb-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">Todos</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Rascunho</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Aprovado</option>
                                    <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Publicado</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeitado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium ">Loja</label>
                                <input type="text" name="store" value="{{ request('store') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                       placeholder="Nome da loja">
                            </div>
                            <div>
                                <label class="block text-sm font-medium ">Categoria</label>
                                <select name="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">Todas</option>
                                    <option value="eletrônicos" {{ request('category') == 'eletrônicos' ? 'selected' : '' }}>Eletrônicos</option>
                                    <option value="casa" {{ request('category') == 'casa' ? 'selected' : '' }}>Casa</option>
                                    <option value="moda" {{ request('category') == 'moda' ? 'selected' : '' }}>Moda</option>
                                    <option value="saúde" {{ request('category') == 'saúde' ? 'selected' : '' }}>Saúde</option>
                                    <option value="livros" {{ request('category') == 'livros' ? 'selected' : '' }}>Livros</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="!bg-success hover:!bg-successHover text-white font-bold py-2 px-4 rounded mr-2">
                                    Filtrar
                                </button>
                                <a href="{{ route('promotions.index') }}" class="!bg-danger hover:!bg-dangerHover text-white font-bold py-2 px-4 rounded">
                                    Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </x-cards.card>

            <!-- Lista de Promoções -->
            <x-cards.card class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($promotions->count() > 0)
                        <div class="space-y-4">
                            @foreach($promotions as $promotion)
                                <div class="border rounded-lg p-4 promotion-card" data-promotion-id="{{ $promotion->id }}">
                                    <div class="flex items-start space-x-4">
                                        <!-- Imagem -->
                                        @if($promotion->image)
                                            <img src="{{ $promotion->image }}" alt="{{ $promotion->title }}"
                                                 class="w-20 h-20 object-cover rounded">
                                        @else
                                            <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                                <span class="text-gray-400 text-xs">Sem imagem</span>
                                            </div>
                                        @endif

                                        <!-- Conteúdo -->
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-semibold text-lg">{{ $promotion->title }}</h3>
                                                    <div class="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                                                        @if($promotion->store)
                                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ $promotion->store }}</span>
                                                        @endif
                                                        @if($promotion->category)
                                                            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded">{{ $promotion->category }}</span>
                                                        @endif
                                                        <span class="bg-{{ $promotion->status == 'pending' ? 'yellow' : ($promotion->status == 'approved' ? 'green' : ($promotion->status == 'posted' ? 'blue' : 'gray')) }}-100 text-{{ $promotion->status == 'pending' ? 'yellow' : ($promotion->status == 'approved' ? 'green' : ($promotion->status == 'posted' ? 'blue' : 'gray')) }}-800 px-2 py-1 rounded">
                                                            {{ ucfirst($promotion->status) }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Preços -->
                                                <div class="text-right">
                                                    @if($promotion->original_price && $promotion->discounted_price)
                                                        <div class="text-sm text-gray-500 line-through">{{ $promotion->formatted_original_price }}</div>
                                                        <div class="text-lg font-bold text-green-600">{{ $promotion->formatted_discounted_price }}</div>
                                                        @if($promotion->discount_percentage)
                                                            <div class="text-sm text-green-600 font-semibold">{{ $promotion->discount_percentage }}% OFF</div>
                                                        @endif
                                                    @elseif($promotion->discounted_price)
                                                        <div class="text-lg font-bold text-green-600">{{ $promotion->formatted_discounted_price }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Post gerado -->
                                            @if($promotion->gemini_generated_post)
                                                <div class="mt-3 p-3 border border-gray-200 rounded">
                                                    <h4 class="font-semibold text-sm mb-2">Post Gerado pela IA:</h4>
                                                    <div class="text-sm whitespace-pre-wrap">{{ $promotion->gemini_generated_post }}</div>
                                                </div>
                                            @endif

                                            <!-- Ações -->
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                @if($promotion->status == 'pending')
                                                    <button class="approve-btn bg-green-500 hover:bg-green-700 text-white text-sm font-bold py-1 px-3 rounded"
                                                            data-promotion-id="{{ $promotion->id }}">
                                                        ✅ Aprovar e Enviar
                                                    </button>
                                                    <button class="reject-btn bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-1 px-3 rounded"
                                                            data-promotion-id="{{ $promotion->id }}">
                                                        ❌ Rejeitar
                                                    </button>
                                                @endif

                                                <button class="regenerate-btn bg-info hover:bg-infoHover text-white text-sm font-bold py-1 px-3 rounded"
                                                        data-promotion-id="{{ $promotion->id }}">
                                                    🔄 Regenerar Post
                                                </button>

                                                <button class="variations-btn bg-purple-500 hover:bg-purple-700 text-white text-sm font-bold py-1 px-3 rounded"
                                                        data-promotion-id="{{ $promotion->id }}">
                                                    🎯 Gerar Variações
                                                </button>

                                                <a href="{{ route('promotions.show', $promotion) }}"
                                                   class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-1 px-3 rounded">
                                                    👁️ Ver Detalhes
                                                </a>

                                                <button class="delete-btn bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-1 px-3 rounded"
                                                        data-promotion-id="{{ $promotion->id }}">
                                                    🗑️ Excluir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Paginação -->
                        <div class="mt-6">
                            {{ $promotions->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-lg">Nenhuma promoção encontrada.</p>
                            <button id="scrapeBtn2" class="mt-4 bg-info hover:bg-infoHover text-white font-bold py-2 px-4 rounded">
                                🔍 Buscar Promoções
                            </button>
                        </div>
                    @endif
                </div>
            </x-cards.card>
        </div>
    </div>

    <!-- Modal para configurações de scrapping -->
    <div id="scrapeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 m-4 max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">Configurar Busca</h3>
                <form id="scrapeForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Loja</label>
                        <select name="store" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Todas as lojas</option>
                            <option value="amazon">Amazon</option>
                            <option value="mercado-livre">Mercado Livre</option>
                            <option value="americanas">Americanas</option>
                            <option value="magazine-luiza">Magazine Luiza</option>
                            <option value="casas-bahia">Casas Bahia</option>
                            <option value="extra">Extra</option>
                            <option value="shopee">Shopee</option>
                            <option value="aliexpress">AliExpress</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Limite de promoções</label>
                        <select name="limit" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="5">5 promoções</option>
                            <option value="10" selected>10 promoções</option>
                            <option value="20">20 promoções</option>
                            <option value="50">50 promoções</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancelScrape" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-info hover:bg-infoHover text-white font-bold py-2 px-4 rounded">
                            Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Modal de scrapping
        const scrapeModal = document.getElementById('scrapeModal');
        const scrapeForm = document.getElementById('scrapeForm');

        document.getElementById('scrapeBtn').addEventListener('click', () => {
            scrapeModal.classList.remove('hidden');
        });

        document.getElementById('scrapeBtn2')?.addEventListener('click', () => {
            scrapeModal.classList.remove('hidden');
        });

        document.getElementById('cancelScrape').addEventListener('click', () => {
            scrapeModal.classList.add('hidden');
        });

        scrapeForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(scrapeForm);
            const button = e.target.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Buscando...';

            try {
                const response = await fetch('{{ route("promotions.scrape") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                alert('Erro na comunicação: ' + error.message);
            } finally {
                button.disabled = false;
                button.textContent = 'Buscar';
                scrapeModal.classList.add('hidden');
            }
        });

        // Função genérica para ações AJAX
        async function performAction(url, method = 'POST', data = {}) {
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                alert('Erro na comunicação: ' + error.message);
            }
        }

        // Event listeners para botões
        document.addEventListener('click', (e) => {
            const promotionId = e.target.dataset.promotionId;

            if (e.target.classList.contains('approve-btn')) {
                if (confirm('Aprovar e enviar esta promoção para o Telegram?')) {
                    performAction(`/promotions/${promotionId}/approve`);
                }
            } else if (e.target.classList.contains('reject-btn')) {
                if (confirm('Rejeitar esta promoção?')) {
                    performAction(`/promotions/${promotionId}/reject`);
                }
            } else if (e.target.classList.contains('regenerate-btn')) {
                if (confirm('Regenerar o post desta promoção?')) {
                    performAction(`/promotions/${promotionId}/regenerate-post`);
                }
            } else if (e.target.classList.contains('variations-btn')) {
                performAction(`/promotions/${promotionId}/generate-variations`);
            } else if (e.target.classList.contains('delete-btn')) {
                if (confirm('Excluir esta promoção permanentemente?')) {
                    performAction(`/promotions/${promotionId}`, 'DELETE');
                }
            }
        });
    </script>
</x-app-layout>
