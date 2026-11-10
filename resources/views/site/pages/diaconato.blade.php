@php
    // ------------------------------------------------------------------
    // Ciclo 2026 — Habilitação ao Diaconato.
    // Fonte: DADOS-OFICIAIS.md (Issue #23). NÃO editar sem uma nova
    // Issue com fonte institucional confirmada. Este bloco é somente
    // conteúdo estático de exibição — nenhuma gravação no banco.
    // ------------------------------------------------------------------

    $cicloAno = 2026;

    $cronogramaCiclo = [
        ['rotulo' => 'Aula inaugural',       'data' => '28/03/2026', 'ic' => 'calendar'],
        ['rotulo' => '1ª aula',              'data' => '25/04/2026', 'ic' => 'book'],
        ['rotulo' => '2ª aula',              'data' => '23/05/2026', 'ic' => 'book'],
        ['rotulo' => '3ª aula',              'data' => '27/06/2026', 'ic' => 'bookopen'],
        ['rotulo' => '4ª aula',              'data' => '25/07/2026', 'ic' => 'bookopen'],
        ['rotulo' => '5ª aula',              'data' => '22/08/2026', 'ic' => 'book'],
        ['rotulo' => '6ª aula',              'data' => '26/09/2026', 'ic' => 'book'],
        ['rotulo' => 'Formatura (previsão)', 'data' => '24/10/2026', 'ic' => 'cap'],
    ];

    // Aulas confirmadas com professor/tema/horário (DADOS-OFICIAIS.md).
    $aulasDetalhadas = [
        '27/06/2026' => [
            'tema'      => 'Introdução à Bibliologia',
            'professor' => 'Pr. Otávio Sobrinho',
            'local'     => 'Templo Matriz',
            'horario'   => '09h00 às 13h00',
            'almoco'    => '13h10',
            'intervalos'=> null,
        ],
        '25/07/2026' => [
            'tema'      => 'Fundamentos bíblicos, históricos e teológicos das dimensões do diaconato',
            'professor' => 'Pr. Alexandre Santos',
            'local'     => 'Igreja Matriz – Templo',
            'horario'   => '09h00 às 16h00',
            'almoco'    => '13h10 às 14h10',
            'intervalos'=> '10h40 às 11h00 · 15h00 às 15h15',
        ],
    ];

    // Vencimentos do 2º semestre de 2026 — sem preço, apenas datas.
    $vencimentos2sem = [
        ['mes' => 'Julho',    'data' => '25/07/2026'],
        ['mes' => 'Agosto',   'data' => '22/08/2026'],
        ['mes' => 'Setembro', 'data' => '26/09/2026'],
        ['mes' => 'Outubro',  'data' => '24/10/2026'],
        ['mes' => 'Novembro', 'data' => '28/11/2026'],
        ['mes' => 'Dezembro', 'data' => '26/12/2026'],
    ];
@endphp
<div class="page" data-page="diaconato">
  <div class="crumb"><div class="wrap"><a data-go="home">INÍCIO</a><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg><a data-go="home">CURSOS</a><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg><span class="cur">HABILITAÇÃO AO DIACONATO</span></div></div>
  <section class="hero"><div class="watermark-faces" aria-hidden="true"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&q=60" alt=""><img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&q=60" alt=""></div>
    <div class="wrap"><div><span class="eyebrow">Curso de <svg data-ic="crown" viewBox="0 0 24 24"></svg></span><h1 class="serif">HABILITAÇÃO AO<span class="l2 gold-text">DIACONATO</span></h1><p class="lead">Prepare-se para servir com excelência na obra do Senhor e viver o chamado bíblico para o ministério diaconal — aberto a homens e mulheres.</p>
      <div class="hero-info"><div class="hi"><span class="ic" data-ic="calendar"></span><div><small>Ciclo</small><b>{{ $cicloAno }}</b></div></div><div class="hi"><span class="ic" data-ic="monitor"></span><div><small>Modalidade</small><b>HÍBRIDA</b></div></div><div class="hi"><span class="ic" data-ic="award"></span><div><small>Certificação</small><b>Reconhecida pelo IEADP</b></div></div></div>
      <div class="hero-actions"><button class="btn btn-navy btn-lg" data-go="inscricao">QUERO ME INSCREVER</button></div></div>
      <div class="hero-media hero-curve"><div class="ph" data-ic="hand"></div><img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?w=800&q=80" alt="Mãos sobre a Bíblia" onerror="this.style.display='none'"><div class="quote-chip"><span class="qmark">“</span><span class="q">Os diáconos sejam maridos de uma só mulher, governem bem seus filhos e suas próprias casas.</span><cite>1 Timóteo 3:12</cite></div></div></div>
  </section>
  <div class="tabs-wrap wrap"><div class="tabs" data-tabset="dia"><button class="active" data-tab="dia-sobre" data-ic="book">SOBRE O CURSO</button><button data-tab="dia-crono" data-ic="calendar">CRONOGRAMA {{ $cicloAno }}</button><button data-tab="dia-disc" data-ic="bookopen">AULAS CONFIRMADAS</button><button data-tab="dia-inv" data-ic="dollar">VENCIMENTOS</button><button data-tab="dia-insc" data-ic="edit">INSCRIÇÃO</button></div></div>
  <div class="wrap">
    <section class="tabpane active" data-pane="dia-sobre"><div class="detail-grid">
      <div><h2 class="sec-title">SOBRE O CURSO</h2><svg class="crown-left" data-ic="crown" viewBox="0 0 24 24"></svg><p style="margin-top:14px">O Curso de Habilitação ao Diaconato do CETSADP tem como objetivo preparar homens e mulheres para servir com excelência na igreja, segundo os princípios bíblicos, desenvolvendo caráter, liderança, conhecimento teológico e habilidades práticas para o ministério diaconal.</p><p>O curso aborda temas como o papel do diácono e da diaconisa na igreja, ética cristã, aconselhamento, administração, visitação, ação social e muito mais.</p><ul class="check"><li data-ic="check">Formação bíblica e prática para o serviço cristão</li><li data-ic="check">Professores experientes e comprometidos</li><li data-ic="check">Integração entre teoria e prática ministerial</li><li data-ic="check">Ambiente de aprendizado acolhedor</li><li data-ic="check">Certificação reconhecida pelo IEADP</li></ul><div class="detail-photo" style="margin-top:24px"><div class="ph" data-ic="cross"></div><img src="https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=700&q=70" alt="Cálice e pão" onerror="this.style.display='none'"></div></div>
      <div>
        <div class="info-card">
          <div class="top-ic" data-ic="hand"></div>
          <h3>CICLO {{ $cicloAno }}</h3>
          <div class="row"><span class="ic" data-ic="monitor"></span><div><small>Modalidade deste ciclo:</small><b>Híbrida (online e presencial)</b></div></div>
          <div class="row"><span class="ic" data-ic="calendar"></span><div><small>Aula inaugural:</small><b>28/03/2026</b></div></div>
          <div class="row"><span class="ic" data-ic="calendar"></span><div><small>Encontros mensais:</small><b>Quarto sábado de cada mês</b></div></div>
          <div class="row"><span class="ic" data-ic="cap"></span><div><small>Formatura (previsão):</small><b>24/10/2026</b></div></div>
          <div class="row"><span class="ic" data-ic="award"></span><div><small>Certificação:</small><b>Reconhecida pelo IEADP</b></div></div>
          <div class="row"><span class="ic" data-ic="users"></span><div><small>Público-alvo:</small><b>Membros da igreja com chamado para o diaconato</b></div></div>
        </div>
        <div class="mini-card"><div class="hd"><span class="ic" data-ic="users"></span><h4>PARA QUEM É ESTE CURSO?</h4></div><p>Homens e mulheres que desejam se preparar para servir como diáconos e diaconisas, desenvolvendo seu caráter, conhecimentos e habilidades para o ministério com excelência e integridade.</p></div>
      </div>
    </div></section>

    <section class="tabpane" data-pane="dia-crono">
      <h2 class="sec-title" style="text-align:center">CRONOGRAMA — CICLO {{ $cicloAno }}</h2>
      <div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div>
      <p style="text-align:center;color:var(--navy);opacity:.85;margin-top:12px">
        Oferta <b>híbrida (online e presencial)</b>. Aulas mensais no <b>quarto sábado</b> de cada mês.<br>
        <small>Cronograma referente exclusivamente ao ciclo de {{ $cicloAno }}.</small>
      </p>
      <div class="mod-grid" style="margin-top:34px">
        @foreach ($cronogramaCiclo as $i => $ev)
          <div class="mod-card @if ($i >= 4) offset @endif">
            <div class="hd">
              <span class="ic" data-ic="{{ $ev['ic'] }}"></span>
              <b>{{ mb_strtoupper($ev['rotulo']) }}</b>
              <span>Ciclo {{ $cicloAno }}</span>
            </div>
            <div class="ch" style="font-size:1.1rem">{{ $ev['data'] }}</div>
            @if (isset($aulasDetalhadas[$ev['data']]))
              @php $det = $aulasDetalhadas[$ev['data']]; @endphp
              <ul>
                <li><b>Tema:</b> {{ $det['tema'] }}</li>
                <li><b>Professor:</b> {{ $det['professor'] }}</li>
                <li><b>Local:</b> {{ $det['local'] }}</li>
                <li><b>Horário:</b> {{ $det['horario'] }}</li>
                <li><b>Almoço:</b> {{ $det['almoco'] }}</li>
                @if (! empty($det['intervalos']))
                  <li><b>Intervalos:</b> {{ $det['intervalos'] }}</li>
                @endif
              </ul>
            @endif
          </div>
        @endforeach
      </div>
    </section>

    <section class="tabpane" data-pane="dia-disc">
      <h2 class="sec-title" style="text-align:center">AULAS CONFIRMADAS — CICLO {{ $cicloAno }}</h2>
      <div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div>
      <p style="text-align:center;color:var(--navy);opacity:.85;margin-top:12px">
        As demais aulas do ciclo terão tema e professor divulgados pela secretaria em cada mês.
      </p>
      <div class="disc-grid" style="margin-top:30px">
        @foreach ($aulasDetalhadas as $data => $det)
          <div class="disc-item" style="align-items:flex-start">
            <span class="n">{{ \Illuminate\Support\Str::of($data)->substr(0, 2) }}/{{ \Illuminate\Support\Str::of($data)->substr(3, 2) }}</span>
            <div>
              <b>{{ $det['tema'] }}</b>
              <small>{{ $det['professor'] }} · {{ $det['local'] }}</small>
              <small>{{ $det['horario'] }} · almoço {{ $det['almoco'] }}@if (! empty($det['intervalos'])) · intervalos {{ $det['intervalos'] }} @endif</small>
              <small style="color:var(--gold-deep);font-weight:700">Data: {{ $data }}</small>
            </div>
          </div>
        @endforeach
      </div>
    </section>

    <section class="tabpane" data-pane="dia-inv">
      <h2 class="sec-title" style="text-align:center">CALENDÁRIO DE VENCIMENTOS — 2º SEMESTRE DE {{ $cicloAno }}</h2>
      <div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div>
      <p style="text-align:center;color:var(--navy);opacity:.85;margin-top:12px">
        Referente exclusivamente ao segundo semestre de {{ $cicloAno }}. <b>Vencimento no quarto sábado de cada mês.</b><br>
        <small>Valores devem ser consultados diretamente na secretaria.</small>
      </p>
      <div class="disc-grid" style="margin-top:30px">
        @foreach ($vencimentos2sem as $venc)
          <div class="disc-item">
            <span class="n">{{ \Illuminate\Support\Str::of($venc['data'])->substr(3, 2) }}</span>
            <div>
              <b>{{ $venc['mes'] }}/{{ $cicloAno }}</b>
              <small>Vencimento: {{ $venc['data'] }}</small>
            </div>
          </div>
        @endforeach
      </div>
      <p style="text-align:center;color:var(--navy);opacity:.75;margin-top:18px;font-size:.9rem">
        Observação: vencimento no quarto sábado de cada mês.
      </p>
    </section>

    <section class="tabpane" data-pane="dia-insc"><div class="center-cta"><button class="btn btn-navy btn-lg" data-go="inscricao">FAZER MINHA INSCRIÇÃO AGORA</button></div></section>
  </div>
  <div class="cta-band left" style="margin-top:50px"><div class="photo"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=70" alt="" onerror="this.parentElement.style.display='none'"></div><div class="wrap split"><div class="inner col"><h3>DÊ O PRÓXIMO PASSO NO SEU CHAMADO</h3><p>Transforme sua vida e a vida de outros através do serviço. Inscreva-se agora e faça parte desta jornada de propósito e excelência.</p></div><div class="act"><button class="btn btn-gold" data-go="inscricao">FAZER MINHA INSCRIÇÃO</button><span class="lim" data-icm="users">Vagas limitadas!</span></div></div></div>
</div>
