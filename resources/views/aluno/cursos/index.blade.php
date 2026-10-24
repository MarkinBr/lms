@extends('layouts.aluno')
@section('title', 'Meus Cursos')
@section('header_title', 'Meus Cursos')
@section('header_subtitle', 'Sua jornada de conhecimento')
@section('content')
<div class="portal-courses-page" x-data="{ filtro: 'todos' }">
    <section class="portal-page-intro"><span class="portal-eyebrow">Área acadêmica</span><h2>Meus cursos</h2><p>Conhecimento que fortalece a fé e transforma o seu ministério.</p><div class="portal-course-summary"><span><b>{{ $resumo['total_cursos'] }}</b> cursos</span><span><b>{{ $resumo['ativos'] }}</b> ativos</span><span><b>{{ $resumo['concluidos'] }}</b> concluídos</span><span><b>{{ $resumo['progresso_medio'] }}%</b> de progresso médio</span></div></section>
    <div class="portal-filter-bar" role="group" aria-label="Filtrar os cursos desta página">
        <button type="button" @click="filtro = 'todos'" :class="{ 'is-active': filtro === 'todos' }" :aria-pressed="filtro === 'todos'">Todos</button>
        <button type="button" @click="filtro = 'andamento'" :class="{ 'is-active': filtro === 'andamento' }" :aria-pressed="filtro === 'andamento'">Em andamento</button>
        <button type="button" @click="filtro = 'concluidos'" :class="{ 'is-active': filtro === 'concluidos' }" :aria-pressed="filtro === 'concluidos'">Concluídos</button>
        <small>Filtrar esta página</small>
    </div>
    @if($matriculas->count())
        <section class="portal-enrolled-list" aria-label="Cursos matriculados">
            @foreach($matriculas as $matricula)
                @php
                    $curso = $matricula->curso;
                    $progresso = max(0, min(100, (float) ($matricula->progresso ?? 0)));
                    $status = strtolower((string) $matricula->status);
                    $concluido = $status === 'concluido';
                @endphp
                <article class="portal-enrolled-card" x-show="filtro === 'todos' || filtro === '{{ $concluido ? 'concluidos' : 'andamento' }}'">
                    <div class="portal-enrolled-main"><a href="{{ route('aluno.curso.show', $curso->id) }}" class="portal-course-cover portal-enrolled-cover" aria-label="Acessar {{ $curso->titulo ?? 'curso' }}">
                        @if(!empty($curso?->capa))<img src="{{ asset('storage/' . $curso->capa) }}" alt="" loading="lazy" width="160" height="110">@else<img src="{{ asset('assets/portal/biblia-banner.webp') }}" alt="" loading="lazy" width="160" height="110"><span>{{ $curso->titulo ?? 'Curso' }}</span>@endif
                    </a><div class="portal-enrolled-copy"><div class="portal-enrolled-labels"><span class="portal-pill {{ $concluido ? 'portal-pill--blue' : '' }}">{{ $concluido ? 'Concluído' : 'Em andamento' }}</span>@if($curso?->categoria)<small>{{ $curso->categoria }}</small>@endif</div><h2><a href="{{ route('aluno.curso.show', $curso->id) }}">{{ $curso->titulo ?? 'Curso sem título' }}</a></h2><p>{{ \Illuminate\Support\Str::limit(strip_tags($curso->descricao ?? 'Curso vinculado à sua matrícula.'), 130) }}</p><div class="portal-progress-line"><div class="portal-progress" role="progressbar" aria-label="Progresso de {{ $curso->titulo ?? 'curso' }}" aria-valuenow="{{ $progresso }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $progresso }}%"></span></div><b>{{ $progresso }}%</b></div><small>{{ $matricula->aulas_concluidas ?? 0 }} de {{ $matricula->total_aulas ?? 0 }} aulas concluídas</small></div></div>
                    <div class="portal-enrolled-footer"><div class="portal-enrolled-meta"><span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ $curso?->modulos?->count() ?? 0 }} módulos</span><span><i class="fa-regular fa-circle-play" aria-hidden="true"></i>{{ $matricula->total_aulas ?? 0 }} aulas</span><span><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ max(1, (int) ceil(($matricula->tempo_total_minutos ?? 0) / 60)) }}h</span></div><div class="portal-enrolled-actions"><a href="{{ route('aluno.calendario') }}" class="portal-text-link">Ver agenda</a><a href="{{ route('aluno.curso.show', $curso->id) }}" class="portal-button"><i class="fa-solid fa-play" aria-hidden="true"></i>{{ $progresso > 0 ? 'Continuar curso' : 'Acessar curso' }}</a></div></div>
                </article>
            @endforeach
            <div class="portal-empty portal-panel" x-cloak x-show="(filtro === 'concluidos' && {{ $matriculas->filter(fn ($m) => strtolower((string) $m->status) === 'concluido')->count() }} === 0) || (filtro === 'andamento' && {{ $matriculas->filter(fn ($m) => strtolower((string) $m->status) !== 'concluido')->count() }} === 0)"><i class="fa-solid fa-book-open" aria-hidden="true"></i><h3>Nenhum curso neste filtro</h3><p>Não há cursos com este status nesta página. Selecione Todos para voltar à lista.</p></div>
        </section>
        @if($matriculas->hasPages())<div class="portal-panel p-4">{{ $matriculas->links() }}</div>@endif
    @else
        <section class="portal-panel portal-empty"><i class="fa-solid fa-book-open" aria-hidden="true"></i><h3>Seu próximo capítulo começa aqui</h3><p>Quando sua matrícula estiver ativa, seus cursos aparecerão aqui com acesso direto ao conteúdo, progresso e calendário acadêmico.</p></section>
    @endif
        @if($proximasAulasAoVivo->isNotEmpty())
        <section class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-800">Próximas aulas ao vivo</h2>
                    <p class="text-sm text-slate-500">Eventos próximos vinculados aos seus cursos.</p>
                </div>
                <a href="{{ route('aluno.calendario') }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Abrir calendário</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach($proximasAulasAoVivo as $aula)
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-blue-600 font-black">Aula ao vivo</p>
                                <h3 class="font-bold text-slate-800 leading-tight mt-1">{{ $aula->titulo }}</h3>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black bg-white text-blue-700 border border-blue-100">{{ optional($aula->data_hora)->format('d/m') }}</span>
                        </div>
                        <div class="space-y-2 text-sm text-slate-600">
                            <p><i class="fa-regular fa-clock w-4 mr-1 text-blue-600"></i>{{ optional($aula->data_hora)->format('d/m/Y \à\s H:i') }}</p>
                            <p><i class="fa-solid fa-book w-4 mr-1 text-blue-600"></i>{{ $aula->curso->titulo ?? 'Curso' }}</p>
                            @if($aula->turma)
                                <p><i class="fa-solid fa-users w-4 mr-1 text-blue-600"></i>{{ $aula->turma->nome }}</p>
                            @endif
                        </div>
                        @if($aula->link_transmissao)
                            <a href="{{ $aula->link_transmissao }}" target="_blank" class="mt-4 inline-flex items-center justify-center w-full rounded-xl bg-[#061a30] text-white px-4 py-2.5 text-sm font-bold hover:bg-[#102d47] transition-colors">
                                Entrar na sala
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif


</div>
@endsection
