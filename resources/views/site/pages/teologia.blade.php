@php
    /**
     * $matrizTeologia (opcional): Collection de módulos ordenados por `ordem`,
     * cada um com `disciplinas` também ordenadas. Fonte: MatrizCurricularTeologiaSeeder.
     * Se ausente (seeder ainda não rodou), a página degrada exibindo apenas o resumo.
     */
    $matrizTeologia = $matrizTeologia ?? null;
    $totalModulos = $matrizTeologia?->count() ?? 6;
    $totalDisciplinas = $matrizTeologia?->sum(fn ($m) => $m->disciplinas->count()) ?? 40;
    $totalHoras = $matrizTeologia?->sum(fn ($m) => (int) $m->disciplinas->sum('carga_horaria')) ?? 960;
@endphp
<div class="page" data-page="teologia">
  <div class="crumb"><div class="wrap"><a data-go="home">INÍCIO</a><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg><a data-go="home">CURSOS</a><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg><span class="cur">CURSO MÉDIO EM TEOLOGIA</span></div></div>
  <section class="hero"><div class="watermark-faces" aria-hidden="true"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&q=60" alt=""><img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&q=60" alt=""></div>
    <div class="wrap"><div><span class="eyebrow">Formação que transforma vidas</span><h1 class="serif">CURSO MÉDIO<span class="l2 gold-text">EM TEOLOGIA</span></h1><p class="lead">Formação bíblica completa para quem deseja aprofundar seus conhecimentos nas Escrituras e servir com excelência na obra do Senhor.</p>
      <div class="hero-info"><div class="hi"><span class="ic" data-ic="calendar"></span><div><small>Duração</small><b>3 ANOS</b></div></div><div class="hi"><span class="ic" data-ic="monitor"></span><div><small>Modalidade</small><b>HÍBRIDA</b></div></div><div class="hi"><span class="ic" data-ic="clock"></span><div><small>Carga horária</small><b>{{ $totalHoras }} HORAS</b></div></div></div>
      <div class="hero-actions"><button class="btn btn-navy btn-lg" data-go="inscricao">QUERO ME INSCREVER</button></div></div>
      <div class="hero-media hero-curve"><div class="ph" data-ic="book"></div><img src="https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=800&q=80" alt="Bíblia aberta" onerror="this.style.display='none'"></div></div>
  </section>
  <div class="tabs-wrap wrap"><div class="tabs" data-tabset="teo"><button class="active" data-tab="teo-sobre" data-ic="book">SOBRE O CURSO</button><button data-tab="teo-crono" data-ic="calendar">CRONOGRAMA</button><button data-tab="teo-disc" data-ic="bookopen">DISCIPLINAS</button><button data-tab="teo-inv" data-ic="dollar">INVESTIMENTO</button><button data-tab="teo-insc" data-ic="edit">INSCRIÇÃO</button></div></div>
  <div class="wrap">
    <section class="tabpane active" data-pane="teo-sobre"><div class="detail-grid">
      <div class="text"><h2 class="sec-title">SOBRE O CURSO</h2><svg class="crown-left" data-ic="crown" viewBox="0 0 24 24"></svg><p style="margin-top:14px">O Curso Médio em Teologia do CETSADP oferece uma formação sólida e equilibrada, unindo conhecimento bíblico, teológico e prático para preparar o aluno para o ministério e para a vida cristã.</p><p>Mais que um curso, é um chamado para crescer, compreender as Escrituras e impactar vidas com a verdade do Evangelho.</p>
        <p style="margin-top:14px"><b>Estrutura oficial:</b> 3 anos · {{ $totalModulos }} módulos · {{ $totalDisciplinas }} disciplinas · {{ $totalHoras }} horas.</p>
        <ul class="check"><li data-ic="check">Formação bíblica e teológica completa</li><li data-ic="check">Professores qualificados e experientes</li><li data-ic="check">Ambiente de aprendizado acolhedor</li><li data-ic="check">Foco prático para o ministério</li><li data-ic="check">Certificação reconhecida pelo IEADP</li></ul></div>
      <div><div class="info-card"><h3 style="border:none;padding:0;margin-bottom:6px">INSCREVA-SE AGORA</h3><p style="text-align:center;color:#c4cfe6;font-size:.9rem;margin-bottom:18px">Dê o próximo passo no seu chamado!</p><div class="row"><span class="ic" data-ic="edit"></span><b>Preencha o formulário de inscrição</b></div><div class="row"><span class="ic" data-ic="monitor"></span><b>Modalidade híbrida: online e presencial</b></div><div class="row"><span class="ic" data-ic="phone"></span><b>Aguarde o contato da secretaria</b></div><button class="btn btn-gold btn-block" style="margin-top:18px" data-go="inscricao">FAZER MINHA INSCRIÇÃO</button></div><div style="text-align:center;margin-top:20px"><p class="serif" style="font-style:italic;color:var(--navy);font-size:1.05rem"><span style="font-family:var(--serif);font-size:2rem;color:var(--gold);line-height:0;vertical-align:-.4em">“</span> Ensina a verdade e prepara o obreiro para toda boa obra.”</p><cite style="color:var(--gold-deep);font-weight:800;font-size:.85rem;letter-spacing:1px">2 Timóteo 2:15</cite></div></div>
    </div></section>
    <section class="tabpane" data-pane="teo-crono"><div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div><h2 class="sec-title serif" style="text-align:center;font-size:1.8rem">CRONOGRAMA</h2>
      <p style="text-align:center;color:var(--navy);opacity:.85;margin-top:14px">Curso de 3 anos, distribuído em {{ $totalModulos }} módulos curriculares e {{ $totalDisciplinas }} disciplinas, totalizando {{ $totalHoras }} horas.<br><small>Datas de início, período letivo e formatura serão divulgadas pela secretaria.</small></p>
      <div class="crono-grid" style="margin-top:36px">
        <div class="crono-card"><span class="ic" data-ic="calendar"></span><h4>DURAÇÃO</h4><div class="date">3 anos</div><p>Formação bíblico-teológica completa em regime modular.</p></div>
        <div class="crono-card"><span class="ic" data-ic="book"></span><h4>MÓDULOS</h4><div class="date">{{ $totalModulos }} módulos</div><p>Distribuídos ao longo dos 3 anos, agrupando as disciplinas por período.</p></div>
        <div class="crono-card"><span class="ic" data-ic="bookopen"></span><h4>DISCIPLINAS</h4><div class="date">{{ $totalDisciplinas }} disciplinas</div><p>Matriz curricular oficial cadastrada pela secretaria.</p></div>
        <div class="crono-card"><span class="ic" data-ic="clock"></span><h4>CARGA HORÁRIA</h4><div class="date">{{ $totalHoras }} horas</div><p>Total do curso, somando todas as disciplinas dos {{ $totalModulos }} módulos.</p></div>
      </div>
    </section>
    <section class="tabpane" data-pane="teo-disc"><h2 class="sec-title" style="text-align:center">MATRIZ CURRICULAR</h2><div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div>
      <p style="text-align:center;color:var(--navy);opacity:.85;margin-top:12px">{{ $totalModulos }} módulos · {{ $totalDisciplinas }} disciplinas · {{ $totalHoras }} horas.</p>
      @if ($matrizTeologia && $matrizTeologia->isNotEmpty())
        @foreach ($matrizTeologia as $modulo)
          <div class="mod-block" style="margin-top:28px">
            <h3 class="serif" style="color:var(--navy);font-size:1.25rem;margin-bottom:6px">{{ $modulo->titulo }}</h3>
            <small style="color:var(--gold-deep);font-weight:700;letter-spacing:1px">
              {{ $modulo->disciplinas->count() }} disciplinas · {{ (int) $modulo->disciplinas->sum('carga_horaria') }} horas
            </small>
            <div class="disc-grid" style="margin-top:14px">
              @foreach ($modulo->disciplinas as $disciplina)
                <div class="disc-item">
                  <span class="n">{{ str_pad((string) $disciplina->ordem, 2, '0', STR_PAD_LEFT) }}</span>
                  <div>
                    <b>{{ $disciplina->nome }}</b>
                    <small>{{ (int) $disciplina->carga_horaria }} horas</small>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      @else
        <p style="text-align:center;color:var(--navy);margin-top:24px">
          A matriz curricular detalhada estará disponível em breve. Entre em contato com a secretaria para receber a grade completa.
        </p>
      @endif
    </section>
    <section class="tabpane" data-pane="teo-inv"><h2 class="sec-title" style="text-align:center">INVESTIMENTO</h2><div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div>
      <div class="inv-grid" style="margin-top:30px">
        <div class="inv-card feat" style="grid-column:1/-1;max-width:520px;margin:0 auto">
          <h4>VALORES E MODALIDADE</h4>
          <div class="price">Consulte<small>a secretaria</small></div>
          <ul>
            <li data-ic="check">Matriz oficial de {{ $totalModulos }} módulos e {{ $totalDisciplinas }} disciplinas</li>
            <li data-ic="check">{{ $totalHoras }} horas de formação</li>
            <li data-ic="check">Certificação reconhecida pelo IEADP</li>
          </ul>
          <button class="btn btn-gold btn-block" data-go="inscricao">INSCREVER-SE</button>
        </div>
      </div>
    </section>
    <section class="tabpane" data-pane="teo-insc"><div class="center-cta"><button class="btn btn-navy btn-lg" data-go="inscricao">FAZER MINHA INSCRIÇÃO AGORA</button></div></section>
  </div>
  <div class="cta-band right" style="margin-top:50px"><div class="photo"><img src="https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=600&q=70" alt="" onerror="this.parentElement.style.display='none'"></div><div class="wrap"><div class="inner"><span class="ic" data-ic="cap"></span><div class="txt"><span class="eyebrow" style="color:var(--gold-3)">Comece hoje sua</span><h3>JORNADA TEOLÓGICA</h3><p>Invista no seu chamado e transforme conhecimento em propósito.</p><div style="margin-top:14px"><button class="btn btn-outline" data-go="inscricao">FAZER MINHA INSCRIÇÃO</button></div></div></div></div></div>
</div>
