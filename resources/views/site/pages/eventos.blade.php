<div class="page" data-page="eventos">
  <div class="crumb"><div class="wrap"><a data-go="home">INÍCIO</a><span class="cur">EVENTOS</span></div></div>
  <section class="hero" style="padding-bottom:30px"><div class="wrap" style="grid-template-columns:1fr;text-align:center"><div style="max-width:780px;margin-inline:auto"><span class="eyebrow">Agenda</span><h1 class="serif">EVENTOS &amp; <span class="gold-text">PROGRAMAS</span></h1><p class="lead">Aulas especiais, seminários, cultos e encontros que fortalecem a comunhão. Confira nossa programação.</p></div></div></section>
  @if($eventosPublicos->isNotEmpty())
  @php($destaque = $eventosPublicos->first())
  <section class="sec" style="padding-top:10px"><div class="wrap"><div class="ev-hero-grid reveal">
    <div class="ev-feature"><div class="bg">@if($destaque->imagem_url)<img src="{{ $destaque->imagem_url }}" alt="">@endif</div><div class="pad">
      <div class="tagrow"><span class="live">Próximo evento</span><span class="cat-pill2 acad">{{ $destaque->categoria_label }}</span></div>
      <h2>{{ $destaque->titulo }}</h2>
      <div class="meta"><span data-icm="calendar">{{ $destaque->data_inicio->format('d/m/Y') }}</span><span data-icm="clock">{{ $destaque->data_inicio->format('H:i') }}</span><span data-icm="pin">{{ $destaque->local ?: 'Local a confirmar' }}</span></div>
      <p style="color:#dbe3f3;max-width:560px;margin-bottom:18px">{{ $destaque->descricao }}</p>
      <div class="countdown" id="cd" data-target="{{ $destaque->data_inicio->toIso8601String() }}"><div class="cd-box"><b>00</b><small>Dias</small></div><div class="cd-box"><b>00</b><small>Horas</small></div><div class="cd-box"><b>00</b><small>Min</small></div><div class="cd-box"><b>00</b><small>Seg</small></div></div>
      @if($destaque->inscricao_url)<a class="btn btn-gold" href="{{ $destaque->inscricao_url }}" target="_blank" rel="noopener noreferrer">INSCREVER-SE</a>@endif
    </div></div>
    <div class="ev-next"><h3>Próximos encontros</h3><div class="sub">Eventos da agenda institucional</div><div class="list">
      @foreach($eventosPublicos->take(5) as $evento)
      <div class="act-row"><div class="date-box"><small>{{ mb_strtoupper($evento->data_inicio->translatedFormat('M')) }}</small><b>{{ $evento->data_inicio->format('d') }}</b></div><div class="info"><b>{{ $evento->titulo }}</b><small>{{ $evento->categoria_label }} · {{ $evento->data_inicio->format('H:i') }}</small><div class="hr">{{ $evento->local ?: 'Local a confirmar' }}</div></div></div>
      @endforeach
    </div><a class="see-all" data-scroll="evgrade">Ver agenda completa</a></div>
  </div></div></section>
  <section class="sec" id="evgrade" style="padding-top:0"><div class="wrap"><div class="sec-head reveal"><span class="eyebrow">Programação</span><h2>TODOS OS EVENTOS</h2></div>
    <div class="chip-filter reveal" id="evFilter"><button class="active" data-cat="all">Todos</button>
      @foreach($categoriasPublicas as $categoria => $label)<button data-cat="{{ $categoria }}">{{ $label }}</button>@endforeach
    </div>
    <div class="ev-grid">
      @foreach($eventosPublicos as $evento)
      <article class="evcard reveal" data-cat="{{ $evento->categoria }}"><div class="thumb">
        @if($evento->imagem_url)<img src="{{ $evento->imagem_url }}" alt="" loading="lazy">@endif
        <div class="dbadge"><small>{{ mb_strtoupper($evento->data_inicio->translatedFormat('M')) }}</small><b>{{ $evento->data_inicio->format('d') }}</b></div><span class="tbadge cat-pill2 acad">{{ $evento->categoria_label }}</span>
      </div><div class="body"><h3>{{ $evento->titulo }}</h3><p class="desc">{{ $evento->descricao }}</p>
        <div class="emeta"><span data-icm="calendar">{{ $evento->data_inicio->format('d/m/Y') }}</span><span data-icm="clock">{{ $evento->data_inicio->format('H:i') }}@if($evento->data_fim) – {{ $evento->data_fim->format('H:i') }}@endif</span><span data-icm="pin">{{ $evento->local ?: 'Local a confirmar' }}</span>@if($evento->vagas)<span data-icm="users">{{ $evento->vagas }} vagas</span>@endif</div>
        <div class="ebar"><span class="st ok">Programado</span>@if($evento->inscricao_url)<a class="btn btn-outline" href="{{ $evento->inscricao_url }}" target="_blank" rel="noopener noreferrer">Inscrever-se</a>@else<span class="st info">Consulte a secretaria</span>@endif</div>
      </div></article>
      @endforeach
    </div>
  </div></section>
  <section class="sec" style="padding-top:0"><div class="wrap"><div class="sec-head reveal"><span class="eyebrow">Calendário</span><h2>{{ mb_strtoupper($destaque->data_inicio->translatedFormat('F Y')) }}</h2></div>
    <div class="cal-big-wrap reveal"><div class="cal-big-head"><b>{{ $destaque->data_inicio->translatedFormat('F Y') }}</b></div><div class="cal-big" id="evCal" data-year="{{ $destaque->data_inicio->year }}" data-month="{{ $destaque->data_inicio->month - 1 }}" data-events="{{ $eventosPublicos->filter(fn ($evento) => $evento->data_inicio->format('Y-m') === $destaque->data_inicio->format('Y-m'))->pluck('data_inicio')->map(fn ($data) => $data->day)->unique()->implode(',') }}"></div><div class="cal-legend"><span><i style="background:var(--gold)"></i>Dia com evento</span><span><i style="background:var(--navy)"></i>Hoje</span></div></div>
  </div></section>
  @else
  <section class="sec"><div class="wrap" style="max-width:760px;text-align:center"><h2 class="serif">Programação em preparação</h2><p class="lead">No momento não há novos eventos publicados. Acompanhe nossos canais para novidades.</p></div></section>
  @endif
  <div class="cta-band right"><div class="wrap"><div class="inner"><div class="txt"><h3>FIQUE POR DENTRO</h3><p>Entre em contato com a secretaria para saber mais sobre a programação.</p><button class="btn btn-gold" data-go="contato">FALE CONOSCO</button></div></div></div></div>
</div>