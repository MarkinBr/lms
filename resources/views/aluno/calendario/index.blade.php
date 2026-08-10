@extends('layouts.aluno')

@section('title', 'Calendário Acadêmico')
@section('header_title', 'Calendário')
@section('header_subtitle', 'Aulas ao vivo, avisos e eventos lançados pela secretaria')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<style>
    .fc {
        --fc-border-color: #e2e8f0;
        --fc-button-bg-color: #003366;
        --fc-button-border-color: #003366;
        --fc-button-hover-bg-color: #00264d;
        --fc-button-hover-border-color: #00264d;
        --fc-button-active-bg-color: #1d4ed8;
        --fc-button-active-border-color: #1d4ed8;
        --fc-today-bg-color: rgba(37, 99, 235, 0.08);
        font-family: Inter, sans-serif;
    }
    .fc .fc-toolbar-title {
        font-size: 1.125rem;
        font-weight: 800;
        color: #0f172a;
    }
    .fc .fc-button {
        border-radius: 0.9rem;
        font-weight: 700;
        box-shadow: none !important;
    }
    .fc .fc-daygrid-event,
    .fc .fc-timegrid-event {
        border: none;
        border-radius: 0.75rem;
        padding: 2px 4px;
    }
    .fc-theme-standard td,
    .fc-theme-standard th {
        border-color: #e2e8f0;
    }
    @media (max-width: 768px) {
        .fc .fc-toolbar {
            flex-direction: column;
            gap: 0.75rem;
            align-items: stretch;
        }
        .fc .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
        }
        .fc .fc-button {
            padding: 0.45rem 0.7rem;
            font-size: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $proximosEventos = collect($eventos)
        ->filter(fn ($evento) => !empty($evento['start']) && \Carbon\Carbon::parse($evento['start'])->greaterThanOrEqualTo(now()->startOfDay()))
        ->sortBy('start')
        ->take(6)
        ->values();
@endphp

<div class="space-y-6">
    <section class="bg-gradient-to-r from-[#003366] via-blue-800 to-indigo-700 rounded-3xl p-6 md:p-8 text-white shadow-2xl overflow-hidden relative">
        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/10"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 rounded-full bg-white/5"></div>
        <div class="relative z-10 flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div class="max-w-2xl">
                <p class="text-blue-200 text-xs font-black uppercase tracking-[0.35em] mb-3">Agenda do aluno</p>
                <h1 class="text-3xl md:text-4xl font-black mb-3">Tudo que importa para a sua turma em um só calendário</h1>
                <p class="text-blue-100 text-sm md:text-base">Agora o calendário mistura aulas ao vivo, avisos com data e eventos cadastrados pela secretaria para o seu curso ou turma.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full xl:w-auto">
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[125px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Eventos</p>
                    <p class="text-3xl font-black mt-1">{{ count($eventos) }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[125px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Aulas ao vivo</p>
                    <p class="text-3xl font-black mt-1">{{ $aulasAoVivo->count() }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[125px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Secretaria</p>
                    <p class="text-3xl font-black mt-1">{{ $eventosSecretaria->count() }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[125px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Matrículas</p>
                    <p class="text-3xl font-black mt-1">{{ $matriculas->count() }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6">
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-4 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-800">Calendário acadêmico</h2>
                    <p class="text-sm text-slate-500">Clique em qualquer evento para ver mais detalhes.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-bold">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>Aulas ao vivo</span>
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-violet-50 text-violet-700"><span class="w-2.5 h-2.5 rounded-full bg-violet-600"></span>Secretaria</span>
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 text-amber-700"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Avisos</span>
                </div>
            </div>
            <div id="calendar"></div>
        </section>

        <aside class="space-y-6">
            <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-800">Próximos compromissos</h3>
                        <p class="text-sm text-slate-500">Os próximos itens da sua agenda.</p>
                    </div>
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400">{{ $proximosEventos->count() }} itens</span>
                </div>
                <div class="space-y-3">
                    @forelse($proximosEventos as $evento)
                        @php
                            $inicio = \Carbon\Carbon::parse($evento['start']);
                            $tipoInfo = match($evento['type']) {
                                'aula_vivo' => ['label' => 'Aula ao vivo', 'badge' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-600'],
                                'secretaria' => ['label' => 'Secretaria', 'badge' => 'bg-violet-50 text-violet-700', 'dot' => 'bg-violet-600'],
                                default => ['label' => 'Aviso', 'badge' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
                            };
                        @endphp
                        <article class="rounded-2xl border border-slate-200 p-4 hover:border-blue-200 hover:shadow-md transition-all">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[11px] font-black {{ $tipoInfo['badge'] }}">
                                    <span class="w-2 h-2 rounded-full {{ $tipoInfo['dot'] }}"></span>{{ $tipoInfo['label'] }}
                                </span>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">{{ $inicio->format('d/m') }}</span>
                            </div>
                            <h4 class="font-black text-slate-800 leading-tight">{{ $evento['title'] }}</h4>
                            <p class="text-sm text-slate-500 mt-2">{{ $inicio->format('d/m/Y H:i') }}</p>
                            @if(!empty($evento['curso']) || !empty($evento['turma']))
                                <p class="text-xs text-slate-400 mt-2">
                                    {{ $evento['curso'] ?? 'Curso' }}@if(!empty($evento['curso']) && !empty($evento['turma'])) • @endif{{ $evento['turma'] ?? '' }}
                                </p>
                            @endif
                            @if(!empty($evento['url']))
                                <a href="{{ $evento['url'] }}" target="_blank" class="mt-3 inline-flex items-center text-sm font-bold text-blue-700 hover:text-blue-900">
                                    Acessar sala <i class="fa-solid fa-arrow-up-right-from-square ml-2 text-xs"></i>
                                </a>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">
                            Nenhum compromisso futuro cadastrado.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-800">Próximas aulas ao vivo</h3>
                        <p class="text-sm text-slate-500">Links diretos para entrar na sala.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @forelse($aulasAoVivo->take(4) as $aula)
                        <article class="rounded-2xl bg-blue-50/70 border border-blue-100 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-widest text-blue-600 font-black">Aula ao vivo</p>
                                    <h4 class="font-black text-slate-800 mt-1">{{ $aula->titulo }}</h4>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black bg-white text-blue-700 border border-blue-100">{{ optional($aula->data_hora)->format('d/m') }}</span>
                            </div>
                            <p class="text-sm text-slate-600 mt-3">{{ optional($aula->data_hora)->format('d/m/Y \à\s H:i') }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $aula->curso->titulo ?? 'Curso' }} @if($aula->turma) • {{ $aula->turma->nome }} @endif</p>
                            @if($aula->link_transmissao)
                                <a href="{{ $aula->link_transmissao }}" target="_blank" class="mt-3 inline-flex items-center text-sm font-bold text-blue-700 hover:text-blue-900">
                                    Entrar na sala <i class="fa-solid fa-arrow-up-right-from-square ml-2 text-xs"></i>
                                </a>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">
                            Nenhuma aula ao vivo agendada.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-800">Avisos com data</h3>
                        <p class="text-sm text-slate-500">Avisos importantes que também aparecem no calendário.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @forelse($avisos->take(4) as $aviso)
                        <article class="rounded-2xl bg-amber-50/80 border border-amber-100 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-black text-slate-800">{{ $aviso->titulo }}</h4>
                                    <p class="text-sm text-slate-600 mt-2">{{ \Illuminate\Support\Str::limit($aviso->mensagem, 110) }}</p>
                                </div>
                                @if($aviso->data_evento)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black bg-white text-amber-700 border border-amber-100">{{ $aviso->data_evento->format('d/m') }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-3">
                                Publicado {{ optional($aviso->data_publicacao ?? $aviso->created_at)->diffForHumans() }}
                            </p>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-slate-500">
                            Nenhum aviso com data cadastrada.
                        </div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>

<div id="calendar-modal" class="hidden fixed inset-0 z-50 px-4 py-8">
    <div class="absolute inset-0 bg-slate-950/60"></div>
    <div class="relative max-w-xl mx-auto mt-10 bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div>
                <p id="calendar-modal-type" class="text-xs uppercase tracking-widest font-black text-blue-600">Evento</p>
                <h3 id="calendar-modal-title" class="text-xl font-black text-slate-800 mt-1">Detalhes</h3>
            </div>
            <button type="button" id="calendar-modal-close" class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Quando</p>
                    <p id="calendar-modal-date" class="text-slate-700 font-semibold"></p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Vínculo</p>
                    <p id="calendar-modal-context" class="text-slate-700 font-semibold">-</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Descrição</p>
                <p id="calendar-modal-description" class="text-slate-700 leading-relaxed">Sem descrição.</p>
            </div>
            <div class="flex justify-end">
                <a id="calendar-modal-link" href="#" target="_blank" class="hidden inline-flex items-center justify-center rounded-2xl bg-[#003366] text-white px-5 py-3 text-sm font-black hover:bg-[#00264d] transition-colors">
                    Acessar sala <i class="fa-solid fa-arrow-up-right-from-square ml-2 text-xs"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/pt-br.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const modal = document.getElementById('calendar-modal');
    const closeButton = document.getElementById('calendar-modal-close');
    const overlay = modal.querySelector('.absolute.inset-0');
    const linkEl = document.getElementById('calendar-modal-link');
    const eventos = @json($eventos);

    const typeMap = {
        aula_vivo: 'Aula ao vivo',
        secretaria: 'Evento da secretaria',
        aviso: 'Aviso importante',
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        linkEl.classList.add('hidden');
        linkEl.setAttribute('href', '#');
    };

    closeButton.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: eventos,
        eventClick: function(info) {
            info.jsEvent.preventDefault();

            const event = info.event;
            const start = event.start ? event.start.toLocaleString('pt-BR') : 'Sem data';
            const end = event.end ? event.end.toLocaleString('pt-BR') : null;
            const context = [event.extendedProps.curso, event.extendedProps.turma].filter(Boolean).join(' • ') || 'Evento geral';

            document.getElementById('calendar-modal-type').textContent = typeMap[event.extendedProps.type] || 'Evento';
            document.getElementById('calendar-modal-title').textContent = event.title;
            document.getElementById('calendar-modal-date').textContent = end ? `${start} até ${end}` : start;
            document.getElementById('calendar-modal-context').textContent = context;
            document.getElementById('calendar-modal-description').textContent = event.extendedProps.description || 'Sem descrição informada.';

            if (event.url) {
                linkEl.setAttribute('href', event.url);
                linkEl.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        },
        eventDidMount: function(info) {
            info.el.style.boxShadow = '0 8px 18px rgba(15, 23, 42, 0.08)';
        }
    });

    calendar.render();
});
</script>
@endpush
