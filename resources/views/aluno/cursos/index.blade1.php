<x-desktop-layout>
    <x-slot name="pageTitle">Meus Cursos</x-slot>
    <x-slot name="pageSubtitle">Gerencie todos os seus cursos</x-slot>
    
    <div class="space-y-6">
        {{-- Filtros e Busca --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                {{-- Busca --}}
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="Buscar curso..." 
                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm font-medium"
                            wire:model.live="busca"
                        >
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>
                
                {{-- Filtros --}}
                <div class="flex items-center gap-3">
                    <select class="px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm font-bold text-slate-700" wire:model.live="filtroStatus">
                        <option value="todos">Todos os Status</option>
                        <option value="em_andamento">Em Andamento</option>
                        <option value="concluido">Concluídos</option>
                        <option value="nao_iniciado">Não Iniciados</option>
                    </select>
                    
                    <select class="px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm font-bold text-slate-700" wire:model.live="ordenacao">
                        <option value="recente">Mais Recentes</option>
                        <option value="alfabetica">A-Z</option>
                        <option value="progresso">Maior Progresso</option>
                    </select>
                    
                    <button class="w-10 h-10 rounded-xl border border-slate-200 hover:bg-slate-50 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-sliders text-slate-600"></i>
                    </button>
                </div>
            </div>
            
            {{-- Tags de Filtro Ativo --}}
            <div class="flex items-center gap-2 mt-4">
                <span class="text-xs font-bold text-slate-500">Filtros ativos:</span>
                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold flex items-center gap-2">
                    Em Andamento
                    <button class="hover:text-blue-900"><i class="fa-solid fa-xmark text-xs"></i></button>
                </span>
            </div>
        </div>
        
        {{-- Grid de Cursos --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse($cursos ?? [] as $curso)
                <x-course-card-desktop :course="$curso" />
            @empty
                {{-- Mock Data --}}
                @foreach([
                    ['id' => 1, 'titulo' => 'Teologia Sistemática I', 'instrutor' => 'Pr. João Silva', 'progresso' => 65, 'aulas_assistidas' => 13, 'nota' => 9.2],
                    ['id' => 2, 'titulo' => 'Hermenêutica Bíblica', 'instrutor' => 'Pr. Maria Santos', 'progresso' => 45, 'aulas_assistidas' => 9, 'nota' => 8.8],
                    ['id' => 3, 'titulo' => 'História da Igreja', 'instrutor' => 'Pr. Pedro Costa', 'progresso' => 30, 'aulas_assistidas' => 6, 'nota' => 9.5],
                    ['id' => 4, 'titulo' => 'Liderança Cristã', 'instrutor' => 'Pr. Ana Oliveira', 'progresso' => 80, 'aulas_assistidas' => 16, 'nota' => 9.0],
                    ['id' => 5, 'titulo' => 'Homilética', 'instrutor' => 'Pr. Carlos Mendes', 'progresso' => 0, 'aulas_assistidas' => 0, 'nota' => null],
                    ['id' => 6, 'titulo' => 'Pentateuco', 'instrutor' => 'Pr. João Silva', 'progresso' => 100, 'aulas_assistidas' => 20, 'nota' => 9.8],
                ] as $mockCurso)
                    <x-course-card-desktop :course="(object)$mockCurso" />
                @endforeach
            @endforelse
        </div>
        
        {{-- Paginação --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500 font-medium">Exibindo <span class="font-bold text-slate-800">6</span> de <span class="font-bold text-slate-800">12</span> cursos</p>
            
            <div class="flex items-center space-x-2">
                <button class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-bold text-slate-400 hover:bg-slate-50 disabled:opacity-50" disabled>
                    Anterior
                </button>
                <button class="w-10 h-10 rounded-lg bg-[#003366] text-white font-bold text-sm">1</button>
                <button class="w-10 h-10 rounded-lg border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50">2</button>
                <button class="w-10 h-10 rounded-lg border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50">3</button>
                <button class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Próximo
                </button>
            </div>
        </div>
    </div>
</x-desktop-layout>
