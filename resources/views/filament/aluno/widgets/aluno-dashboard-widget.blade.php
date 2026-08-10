<x-filament-widgets::widget>
    <div class="space-y-6">
        {{-- Header de Boas-vindas --}}
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">
                        Olá, {{ $this->getData()['aluno']->name }}! 👋
                    </h1>
                    <p class="text-green-100">Bem-vindo de volta ao seu portal de estudos</p>
                </div>
                <div class="text-right">
                    <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-2">
                        <p class="text-sm text-green-100">Seu Progresso Geral</p>
                        <p class="text-4xl font-bold">{{ $this->getData()['progresso'] }}%</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alerta de Mensalidade --}}
        @if($mensalidade = $this->getData()['mensalidadePendente'])
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-yellow-700">
                        <strong>Atenção!</strong> Você tem uma mensalidade vencendo em 
                        {{ \Carbon\Carbon::parse($mensalidade->vencimento)->diffForHumans() }}
                    </p>
                </div>
                <div class="ml-4">
                    <a href="/aluno/financeiro" class="text-sm font-medium text-yellow-700 hover:text-yellow-600">
                        Ver detalhes →
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Grid Principal --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            {{-- Card: Continuar de onde parou --}}
            @if($ultimaAula = $this->getData()['ultimaAula'])
            <div class="col-span-full lg:col-span-2">
                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02]">
                    <div class="p-6 text-white">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-purple-100 text-sm mb-1">Continue de onde parou</p>
                                <h3 class="text-2xl font-bold">{{ $ultimaAula->titulo }}</h3>
                                <p class="text-purple-100 mt-1">{{ $ultimaAula->curso }} • {{ $ultimaAula->modulo }}</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <a href="/aluno/sala-de-aula?aula={{ $ultimaAula->id }}" 
                           class="inline-flex items-center bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-purple-50 transition-colors">
                            <svg class="w-5 h-5" mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                            </svg>
                            Continuar Assistindo
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Card: Próximas Aulas ao Vivo --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">🔴 Aulas ao Vivo</h3>
                    <a href="/aluno/aulas-ao-vivo" class="text-sm text-green-600 hover:text-green-700">Ver todas</a>
                </div>
                
                @forelse($this->getData()['proximasAulas'] as $live)
                <div class="border-l-4 border-red-500 bg-red-50 p-3 rounded mb-3">
                    <p class="font-semibold text-sm text-gray-900">{{ $live->titulo }}</p>
                    <p class="text-xs text-gray-600 mt-1">{{ $live->curso }}</p>
                    <p class="text-xs text-red-600 mt-1 font-medium">
                        📅 {{ \Carbon\Carbon::parse($live->data_hora)->format('d/m/Y à\s H:i') }}
                    </p>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">Nenhuma aula ao vivo agendada</p>
                @endforelse
            </div>
        </div>

        {{-- Meus Cursos --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">📚 Meus Cursos</h2>
                <a href="/aluno/meus-cursos" class="text-green-600 hover:text-green-700 font-medium">Ver todos →</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($this->getData()['cursosAtivos'] as $curso)
                <a href="/aluno/curso/{{ $curso->id }}" 
                   class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.03]">
                    
                    {{-- Capa do Curso --}}
                    <div class="relative h-48 bg-gradient-to-br from-green-400 to-blue-500">
                        @if($curso->capa)
                        <img src="{{ Storage::url($curso->capa) }}" alt="{{ $curso->titulo }}" class="w-full h-full object-cover">
                        @else
                        <div class="flex items-center justify-center h-full">
                            <svg class="w-20 h-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        @endif
                        
                        {{-- Badge de Status --}}
                        @if($curso->progresso == 100)
                        <div class="absolute top-3 right-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                            ✓ Concluído
                        </div>
                        @else
                        <div class="absolute top-3 right-3 bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                            Em andamento
                        </div>
                        @endif
                    </div>

                    {{-- Informações do Curso --}}
                    <div class="p-5">
                        <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2">{{ $curso->titulo }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ $curso->aulas_assistidas }}/{{ $curso->total_aulas }} aulas</p>
                        
                        {{-- Barra de Progresso --}}
                        <div class="mb-3">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>Progresso</span>
                                <span class="font-semibold">{{ $curso->progresso }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full transition-all duration-500" 
                                     style="width: {{ $curso->progresso }}%"></div>
                            </div>
                        </div>

                        <button class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                            Continuar Curso →
                        </button>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Avisos Importantes --}}
        @if($avisos = $this->getData()['avisos'])
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">📢 Avisos Importantes</h3>
            @foreach($avisos as $aviso)
            <div class="bg-white rounded-lg p-4 mb-3 last:mb-0">
                <h4 class="font-semibold text-gray-900">{{ $aviso->titulo }}</h4>
                <p class="text-sm text-gray-600 mt-1">{{ $aviso->mensagem }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-filament-widgets::widget>