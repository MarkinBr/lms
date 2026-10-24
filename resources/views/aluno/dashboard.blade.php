@extends('layouts.aluno')
@section('title', 'Início')
@section('header_title', 'Início')
@section('header_subtitle', 'Bem-vindo ao seu portal acadêmico')
@section('content')
@php
    $documentosInscricao = app(\App\Support\ApplicationDocuments\DocumentosInscricaoService::class)->resumoDo(Auth::user());
    $portalContinuar = $cursos->first(fn ($curso) => ($curso->progresso ?? 0) > 0 && ($curso->progresso ?? 0) < 100) ?? $cursos->first();
    $portalPercentual = max(0, min(100, (float) $percentualGeral));
@endphp
<div class="portal-dashboard">
    <section class="portal-welcome" aria-labelledby="portal-saudacao">
        <div class="portal-welcome-copy"><p>Bem-vindo de volta,</p><h2 id="portal-saudacao">{{ Auth::user()->name ?? 'Estudante' }}</h2><span class="portal-gold-rule" aria-hidden="true"></span><p>Que Deus te conceda sabedoria e força nesta jornada!</p></div>
        <blockquote class="portal-verse"><span aria-hidden="true">“</span><div><p>Ensina a criança no caminho em que deve andar, e ainda quando for velho não se desviará dele.</p><cite>Provérbios 22:6</cite></div></blockquote>
    </section>
    @if(($documentosInscricao['tem_inscricao'] ?? false) && (($documentosInscricao['pendentes'] ?? 0) > 0 || ($documentosInscricao['rejeitados'] ?? 0) > 0))
            <section class="relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-orange-50 p-6 shadow-sm" aria-labelledby="documentos-pendentes-titulo">
                <div class="absolute -right-10 -top-12 h-36 w-36 rounded-full bg-amber-200/30"></div>
                <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20">
                            <i class="fa-solid fa-file-circle-exclamation text-xl" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h2 id="documentos-pendentes-titulo" class="text-lg font-black text-slate-800">Documentos da inscrição</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                @if(($documentosInscricao['rejeitados'] ?? 0) > 0)
                                    Há documentos que precisam ser reenviados pela Central.
                                @else
                                    Sua inscrição tem {{ $documentosInscricao['pendentes'] }} documento(s) pendente(s) de envio.
                                @endif
                            </p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">{{ $documentosInscricao['enviados'] }} de {{ $documentosInscricao['total'] }} documentos enviados</p>
                        </div>
                    </div>
                    <a href="{{ route('aluno.documentos-inscricao') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#061a30] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#041322] focus:outline-none focus:ring-2 focus:ring-[#061a30] focus:ring-offset-2">
                        Enviar documentos
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </section>
        @endif
    <section class="portal-stats" aria-label="Resumo acadêmico">
        <a href="{{ route('aluno.cursos') }}" class="portal-stat"><span class="portal-stat-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span><div><h3>Cursos matriculados</h3><strong>{{ $cursosAtivos }}</strong><p>Ativos no momento</p></div></a>
        <a href="{{ route('aluno.cursos') }}" class="portal-stat"><span class="portal-stat-icon portal-stat-icon--gold"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span><div><h3>Aulas concluídas</h3><strong>{{ $totalAssistidasGeral }}</strong><p>De {{ $totalAulasGeral }} aulas</p></div><div class="portal-stat-progress"><div class="portal-progress" role="progressbar" aria-label="Aulas concluídas" aria-valuenow="{{ $portalPercentual }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $portalPercentual }}%"></span></div><b>{{ $portalPercentual }}%</b></div></a>
        <a href="{{ route('aluno.cursos') }}" class="portal-stat"><span class="portal-stat-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span><div><h3>Aulas pendentes</h3><strong>{{ $proximasAulas }}</strong><p>Continue aprendendo</p></div></a>
        <a href="{{ route('aluno.financeiro') }}" class="portal-stat"><span class="portal-stat-icon portal-stat-icon--gold"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span><div><h3>Financeiro</h3><strong class="portal-stat-status">{{ $statusFinanceiro }}</strong><p>{{ $statusFinanceiro === 'Em dia' ? 'Sem pendências vencidas' : 'Consulte suas mensalidades' }}</p></div></a>
    </section>
    <div class="portal-dashboard-grid">
        <section class="portal-panel portal-panel-courses">
            <div class="portal-panel-heading"><h2><i class="fa-solid fa-book-open" aria-hidden="true"></i>Meus cursos</h2><a href="{{ route('aluno.cursos') }}">Ver todos <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
            <div class="portal-course-list">@forelse($cursos as $curso)<x-course-card-desktop :course="$curso" />@empty<div class="portal-empty"><i class="fa-solid fa-book-open" aria-hidden="true"></i><h3>Seu próximo capítulo começa aqui</h3><p>Quando sua matrícula estiver ativa, seus cursos aparecerão neste espaço.</p><a href="{{ route('aluno.secretaria') }}" class="portal-text-link">Falar com a Secretaria</a></div>@endforelse</div>
        </section>
        <section class="portal-panel portal-panel-agenda" data-portal-agenda="{{ route('aluno.calendario.proximos') }}" data-agenda-url="{{ route('aluno.calendario') }}">
            <div class="portal-panel-heading"><h2><i class="fa-regular fa-calendar-days" aria-hidden="true"></i>Próximas aulas e eventos</h2><a href="{{ route('aluno.calendario') }}">Ver calendário <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
            <p class="portal-panel-note">Sua agenda para os próximos 7 dias</p>
            <div data-portal-events aria-live="polite" aria-busy="true"><div class="portal-skeleton-row"><span></span><div></div></div><div class="portal-skeleton-row"><span></span><div></div></div><div class="portal-skeleton-row"><span></span><div></div></div></div>
            <noscript><p class="portal-empty">Consulte suas aulas e eventos no <a href="{{ route('aluno.calendario') }}">calendário acadêmico</a>.</p></noscript>
            <a class="portal-panel-footer" href="{{ route('aluno.calendario') }}">Ver toda a agenda <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
        </section>
        <section class="portal-panel portal-panel-continue">
            <div class="portal-panel-heading"><h2><i class="fa-solid fa-feather-pointed" aria-hidden="true"></i>Continue seus estudos</h2></div>
            @if($portalContinuar)
                <div class="portal-continue-content">
                    <div class="portal-continue-cover"><img src="{{ asset('assets/portal/biblia-capa.webp') }}" alt="Bíblia aberta" width="132" height="88" loading="lazy"></div>
                    <div class="portal-continue-copy"><h3>{{ $portalContinuar->titulo }}</h3><p>{{ $portalContinuar->aulas_assistidas ?? 0 }} de {{ $portalContinuar->total_aulas ?? 0 }} aulas concluídas</p><div class="portal-progress-line"><div class="portal-progress" role="progressbar" aria-label="Progresso do curso" aria-valuenow="{{ max(0, min(100, (float) ($portalContinuar->progresso ?? 0))) }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ max(0, min(100, (float) ($portalContinuar->progresso ?? 0))) }}%"></span></div><b>{{ $portalContinuar->progresso ?? 0 }}%</b></div><div class="portal-continue-actions"><a href="{{ route('aluno.curso.show', $portalContinuar->id) }}" class="portal-button"><i class="fa-solid fa-play" aria-hidden="true"></i>Continuar curso</a><a href="{{ route('aluno.cursos') }}" class="portal-text-link">Ver meus cursos</a></div></div>
                </div>
            @else
                <div class="portal-empty portal-empty--compact"><p>Seus cursos e seu progresso aparecerão aqui assim que estiverem disponíveis.</p></div>
            @endif
        </section>
        <section class="portal-panel portal-panel-calendar">
            <div class="portal-panel-heading"><h2><i class="fa-regular fa-calendar-days" aria-hidden="true"></i>Calendário acadêmico</h2><a href="{{ route('aluno.calendario') }}">Ver completo</a></div>
            <div class="portal-mini-calendar" data-portal-calendar><div class="portal-month-nav"><button type="button" data-calendar-prev aria-label="Mês anterior"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button><h3 data-calendar-title aria-live="polite"></h3><button type="button" data-calendar-next aria-label="Próximo mês"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button></div><div class="portal-calendar-week" aria-hidden="true"><span>DOM</span><span>SEG</span><span>TER</span><span>QUA</span><span>QUI</span><span>SEX</span><span>SÁB</span></div><div class="portal-calendar-days" data-calendar-days></div></div>
        </section>
    </div>
    <section class="portal-inspiration"><span class="portal-inspiration-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span><h2>Cresça no conhecimento.<br>Transforme vidas.</h2><blockquote>Seja diligente em apresentar-te a Deus aprovado, como obreiro que não tem de que se envergonhar, que maneja bem a palavra da verdade.<cite>2 Timóteo 2:15</cite></blockquote></section>
    <div class="portal-dashboard-secondary">
        <section class="portal-panel"><div class="portal-panel-heading"><h2><i class="fa-regular fa-bell" aria-hidden="true"></i>Mural de avisos</h2><a href="{{ route('aluno.comunicacao') }}">Ver todos</a></div><div class="portal-notice-list">@forelse($avisos as $aviso)<article class="portal-notice"><span aria-hidden="true"></span><div><h3>{{ $aviso->titulo }}</h3><p>{{ \Illuminate\Support\Str::limit(strip_tags($aviso->mensagem), 130) }}</p><small>{{ \Carbon\Carbon::parse($aviso->created_at)->diffForHumans() }}</small></div></article>@empty<div class="portal-empty portal-empty--compact"><i class="fa-regular fa-bell-slash" aria-hidden="true"></i><p>Nenhum aviso no momento. Tudo em ordem por aqui.</p></div>@endforelse</div></section>
        <section class="portal-panel"><div class="portal-panel-heading"><h2><i class="fa-solid fa-compass" aria-hidden="true"></i>Acesso rápido</h2></div><div class="portal-shortcuts">@foreach([['aluno.notas', 'fa-clipboard-check', 'Minhas notas', 'Acompanhe seu desempenho'], ['aluno.certificados', 'fa-award', 'Certificados', 'Suas conquistas acadêmicas'], ['aluno.documentos-inscricao', 'fa-folder-open', 'Documentos da inscrição', 'Envios e análise da Secretaria'], ['aluno.financeiro', 'fa-wallet', 'Financeiro', 'Mensalidades e pagamentos']] as [$rota, $icone, $titulo, $descricao])<a href="{{ route($rota) }}"><i class="fa-solid {{ $icone }}" aria-hidden="true"></i><div><h3>{{ $titulo }}</h3><p>{{ $descricao }}</p></div><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endforeach</div></section>
    </div>
</div>
@endsection
