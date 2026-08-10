{{-- Card de Curso para Desktop --}}
@props(['course'])

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-lg hover:border-blue-200 transition-all group cursor-pointer">
    {{-- Thumbnail --}}
    <div class="relative h-48 bg-gradient-to-br from-blue-500 to-blue-700 overflow-hidden">
        @if(isset($course->imagem) && $course->imagem)
            <img src="{{ $course->imagem }}" alt="{{ $course->titulo }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fa-solid fa-book-open text-white/20 text-6xl"></i>
            </div>
        @endif
        
        {{-- Badge de Status --}}
        <div class="absolute top-4 right-4">
            @if($course->progresso >= 100)
                <span class="px-3 py-1 bg-green-500 text-white text-[10px] font-black rounded-full uppercase tracking-widest shadow-lg">
                    <i class="fa-solid fa-check mr-1"></i> Concluído
                </span>
            @elseif($course->progresso > 0)
                <span class="px-3 py-1 bg-blue-500 text-white text-[10px] font-black rounded-full uppercase tracking-widest shadow-lg">
                    Em Progresso
                </span>
            @else
                <span class="px-3 py-1 bg-slate-700 text-white text-[10px] font-black rounded-full uppercase tracking-widest shadow-lg">
                    Novo
                </span>
            @endif
        </div>
        
        {{-- Progresso Visual --}}
        @if($course->progresso > 0 && $course->progresso < 100)
        <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-black/20">
            <div class="h-full bg-yellow-400 transition-all duration-500" style="width: {{ $course->progresso }}%"></div>
        </div>
        @endif
    </div>
    
    {{-- Conteúdo --}}
    <div class="p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
                <h3 class="text-lg font-black text-slate-800 leading-tight group-hover:text-[#003366] transition-colors">
                    {{ $course->titulo }}
                </h3>
                <div class="flex items-center space-x-2 mt-2">
                    <i class="fa-solid fa-user-tie text-slate-400 text-xs"></i>
                    <p class="text-xs text-slate-500 font-bold">{{ $course->instrutor ?? 'Professor' }}</p>
                </div>
            </div>
        </div>
        
        {{-- Estatísticas --}}
        <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100">
            <div class="text-center">
                <p class="text-2xl font-black text-[#003366]">{{ $course->aulas_assistidas ?? 0 }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Aulas</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-black text-blue-600">{{ $course->progresso ?? 0 }}%</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Progresso</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-black text-green-600">{{ $course->nota ?? '-' }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Nota</p>
            </div>
        </div>
        
        {{-- Ação --}}
        <div class="mt-4">
            <a href="{{ route('aluno.curso.show', $course->id) }}" 
               class="block w-full bg-[#003366] text-white py-3 rounded-xl text-sm font-black text-center uppercase tracking-widest hover:bg-[#002244] active:scale-95 transition-all shadow-md">
                @if($course->progresso > 0)
                    Continuar Estudando
                @else
                    Iniciar Curso
                @endif
            </a>
        </div>
    </div>
</div>
