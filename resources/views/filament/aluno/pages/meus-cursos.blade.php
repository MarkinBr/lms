<x-filament-panels::page>
    <div class="space-y-6">
        
        {{-- Header com Estatísticas --}}
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-6 text-white">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-green-100 text-sm mb-1">Total de Cursos</p>
                    <p class="text-4xl font-bold">{{ count($cursos) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-green-100 text-sm mb-1">Em Andamento</p>
                    <p class="text-4xl font-bold">
                        {{ collect($cursos)->where('status_matricula', 'ativo')->whereNull('certificado_disponivel')->count() }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-green-100 text-sm mb-1">Concluídos</p>
                    <p class="text-4xl font-bold">
                        {{ collect($cursos)->where('progresso', 100)->count() }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-green-100 text-sm mb-1">Progresso Médio</p>
                    <p class="text-4xl font-bold">
                        {{ count($cursos) > 0 ? round(collect($cursos)->avg('progresso')) : 0 }}%
                    </p>
                </div>
            </div>
        </div>

        {{-- Filtros e Busca --}}
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex flex-wrap items-center gap-4">
                {{-- Busca --}}
                <div class="flex-1 min-w-[200px]">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="busca"
                        placeholder="Buscar curso..."
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>

                {{-- Filtro por Status --}}
                <select 
                    wire:model.live="filtroStatus"
                    class="border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                    <option value="todos">Todos os Status</option>
                    <option value="ativo">Ativos</option>
                    <option value="concluido">Concluídos</option>
                    <option value="expirado">Expirados</option>
                </select>

                {{-- Ordenação --}}
                <select 
                    wire:model.live="ordenacao"
                    class="border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
                    <option value="recente">Mais Recentes</option>
                    <option value="alfabetica">Ordem Alfabética</option>
                    <option value="progresso">Maior Progresso</option>
                </select>
            </div>
        </div>

        {{-- Grid de Cursos --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($cursos as $curso)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02] group">
                
                {{-- Capa do Curso --}}
                <div class="relative h-48 overflow-hidden">
                    @if($curso->capa)
                    <img src="{{ Storage::url($curso->capa) }}" 
                         alt="{{ $curso->titulo }}" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    @else
                    <div class="w-full h-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                        <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    @endif
                    
                    {{-- Badge de Status --}}
                    @if($curso->progresso == 100)
                    <div class="absolute top-3 right-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                        ✓ Concluído
                    </div>
                    @elseif($curso->status_matricula == 'expirado')
                    <div class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                        ⚠ Expirado
                    </div>
                    @else
                    <div class="absolute top-3 right-3 bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                        Em andamento
                    </div>
                    @endif

                    {{-- Overlay com Botão ao Hover --}}
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center">
                        <a href="/aluno/sala-de-aula?aula={{ $curso->ultima_aula->id ?? '' }}" 
                           class="opacity-0 group-hover:opacity-100 bg-white text-green-600 font-semibold px-6 py-3 rounded-lg transform scale-75 group-hover:scale-100 transition-all duration-300 shadow-xl">
                            @if($curso->ultima_aula)
                                Continuar Assistindo →
                            @else
                                Começar Curso →
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Informações do Curso --}}
                <div class="p-5">
                    <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2 min-h-[56px]">
                        {{ $curso->titulo }}
                    </h3>
                    
                    @if($curso->descricao)
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $curso->descricao }}</p>
                    @endif

                    {{-- Estatísticas --}}
                    <div class="flex items-center gap-4 text-xs text-gray-500 mb-3">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $curso->total_aulas }} aulas
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $curso->duracao_total ?? 'N/A' }}
                        </span>
                    </div>

                    {{-- Progresso --}}
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Progresso</span>
                            <span class="font-semibold">{{ $curso->progresso }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $curso->progresso }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $curso->aulas_assistidas }}/{{ $curso->total_aulas }} aulas assistidas
                        </p>
                    </div>

                    {{-- Ações --}}
                    <div class="flex gap-2">
                        <a href="/aluno/sala-de-aula?aula={{ $curso->ultima_aula->id ?? '' }}" 
                           class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors text-center">
                            @if($curso->ultima_aula)
                                Continuar
                            @else
                                Iniciar
                            @endif
                        </a>
                        
                        @if($curso->certificado_disponivel)
                        <a href="/aluno/certificado/{{ $curso->id }}" 
                           class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Nenhum curso encontrado</h3>
                <p class="text-gray-500">
                    @if(!empty($busca))
                        Tente buscar com outras palavras-chave
                    @else
                        Você ainda não está matriculado em nenhum curso
                    @endif
                </p>
            </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>