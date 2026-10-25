<div class="page" data-page="professores">

  {{-- ============================================================
       BREADCRUMB
  ============================================================ --}}
  <div class="crumb">
    <div class="wrap">
      <a data-go="home">INÍCIO</a>

      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 18l6-6-6-6"/>
      </svg>

      <span class="cur">PROFESSORES</span>
    </div>
  </div>


  {{-- ============================================================
       HERO
  ============================================================ --}}
  <section class="hero" style="padding-bottom:30px">

    <div class="watermark-faces" aria-hidden="true">

      <img
        src="https://cetsadp.com.br/images/PR-JORGE2.png?w=300&q=60"
        alt=""
      >

      <img
        src="https://cetsadp.com.br/images/PR-OTAVIO.png?w=300&q=60"
        alt=""
      >

    </div>

    <div
      class="wrap"
      style="grid-template-columns:1fr;text-align:center"
    >

      <div style="max-width:760px;margin-inline:auto">

        <span
          class="eyebrow"
          style="justify-content:center"
        >
          <span class="ln"></span>

          Corpo docente

          <span class="ln"></span>
        </span>

        <h1 class="serif">
          NOSSOS
          <span class="gold-text">
            PROFESSORES
          </span>
        </h1>

        <p
          class="lead"
          style="margin-inline:auto"
        >
          Uma equipe de pastores, mestres e servos preparados
          para ensinar, discipular e inspirar — unindo
          profundidade bíblica, experiência ministerial
          e amor pelo aluno.
        </p>

      </div>

    </div>

  </section>


  {{-- ============================================================
       PROFESSOR EM DESTAQUE
  ============================================================ --}}
  <section
    class="sec"
    style="padding-top:10px"
  >

    <div class="wrap">

      <div class="prof2-feat reveal">

        <div class="pic">

          <div
            class="ph"
            data-ic="user"
          ></div>

          <img
            src="https://cetsadp.com.br/images/PR-JORGE2.png?w=600&q=80"
            alt="Pr. Jorge Coutinho"
            onerror="this.style.display='none'"
          >

        </div>


        <div>

          <span class="eyebrow">

            <span class="ln"></span>

            Em destaque

            <span class="ln"></span>

          </span>

          <span class="role">
            PRESIDENTE · TEOLOGIA SISTEMÁTICA
          </span>

          <h2>
            Pr. Jorge Coutinho
          </h2>

          <p>
            Presidente da instituição e professor na área de
            Teologia Sistemática, contribuindo para a formação
            bíblica, doutrinária e ministerial dos alunos do CETSADP.
          </p>

          <div
            class="subj"
            style="justify-content:flex-start"
          >

            <span>
              Teologia Sistemática
            </span>

            <span>
              Doutrina
            </span>

            <span>
              Liderança
            </span>

          </div>

        </div>

      </div>


      {{-- ========================================================
           FILTROS
      ======================================================== --}}
      <div
        class="prof-filter reveal"
        id="profFilter"
      >

        <button
          class="active"
          data-area="all"
        >
          Todos
        </button>

        <button
          data-area="teologia"
        >
          Teologia
        </button>

        <button
          data-area="biblica"
        >
          Estudos Bíblicos
        </button>

        <button
          data-area="diaconato"
        >
          Diaconato
        </button>

        <button
          data-area="pastoral"
        >
          Pastoral
        </button>

      </div>


      {{-- ========================================================
           GRID DE PROFESSORES
      ======================================================== --}}
      <div
        class="prof2-grid"
        id="profGrid"
      >


        {{-- ======================================================
             PR. JORGE COUTINHO
        ====================================================== --}}
        <article
          class="prof2-card reveal"
          data-area="teologia"
        >

          <div class="pic">

            <span class="area-tag">
              TEOLOGIA
            </span>

            <div
              class="ph"
              data-ic="user"
            ></div>

            <img
              src="https://cetsadp.com.br/images/PR-JORGE2.png?w=500&q=80"
              alt="Pr. Jorge Coutinho"
              onerror="this.style.display='none'"
            >

          </div>


          <div class="body">

            <h3>
              Pr. Jorge Coutinho
            </h3>

            <div class="role">
              PRESIDENTE · TEOLOGIA SISTEMÁTICA
            </div>

            <p>
              Presidente da instituição e professor na área
              de Teologia Sistemática.
            </p>

            <div class="subj">

              <span>
                Teologia Sistemática
              </span>

              <span>
                Doutrina
              </span>

            </div>

          </div>

        </article>


        {{-- ======================================================
             PR. OTÁVIO SOBRINHO
        ====================================================== --}}
        <article
          class="prof2-card reveal"
          data-area="biblica"
        >

          <div class="pic">

            <span class="area-tag">
              ESTUDOS BÍBLICOS
            </span>

            <div
              class="ph"
              data-ic="user"
            ></div>

            <img
              src="https://cetsadp.com.br/images/PR-OTAVIO.png?w=500&q=80"
              alt="Pr. Otávio Sobrinho"
              onerror="this.style.display='none'"
            >

          </div>


          <div class="body">

            <h3>
              Pr. Otávio Sobrinho
            </h3>

            <div class="role">
              BIBLIOLOGIA · EXEGESE
            </div>

            <p>
              Professor nas áreas de Bibliologia e Exegese,
              contribuindo para o aprofundamento no estudo
              e interpretação das Escrituras.
            </p>

            <div class="subj">

              <span>
                Bibliologia
              </span>

              <span>
                Exegese
              </span>

            </div>

          </div>

        </article>


        {{-- ======================================================
             PR. ALEXANDRE SANTOS
        ====================================================== --}}
        <article
          class="prof2-card reveal"
          data-area="diaconato"
        >

          <div class="pic">

            <span class="area-tag">
              DIACONATO
            </span>

            <div
              class="ph"
              data-ic="user"
            ></div>

            <img
              src="{{ asset('assets/images/professores/pr-alexandre-santos.png') }}"
              alt="Pr. Alexandre Santos"
              onerror="this.style.display='none'"
            >

          </div>


          <div class="body">

            <h3>
              Pr. Alexandre Santos
            </h3>

            <div class="role">
              FUNDAMENTOS DO DIACONATO
            </div>

            <p>
              Compreendendo os fundamentos bíblicos,
              históricos e teológicos no tocante às
              dimensões do diaconato.
            </p>

            <div class="subj">

              <span>
                Diaconato
              </span>

              <span>
                Fundamentos Bíblicos
              </span>

              <span>
                Teologia
              </span>

            </div>

          </div>

        </article>


        {{-- ======================================================
             PR. MISAEL FIGUEIREDO
        ====================================================== --}}
        <article
          class="prof2-card reveal"
          data-area="pastoral"
        >

          <div class="pic">

            <span class="area-tag">
              PASTORAL
            </span>

            <div
              class="ph"
              data-ic="user"
            ></div>

            <img
              src="{{ asset('assets/images/professores/pr-misael-figueiredo.png') }}"
              alt="Pr. Misael Figueiredo"
              onerror="this.style.display='none'"
            >

          </div>


          <div class="body">

            <h3>
              Pr. Misael Figueiredo
            </h3>

            <div class="role">
              TEOLOGIA PASTORAL · LIDERANÇA CRISTÃ
            </div>

            <p>
              Professor nas áreas de Teologia Pastoral
              e Liderança Cristã, contribuindo para a
              formação ministerial e o desenvolvimento
              de líderes cristãos.
            </p>

            <div class="subj">

              <span>
                Teologia Pastoral
              </span>

              <span>
                Liderança Cristã
              </span>

            </div>

          </div>

        </article>

      </div>


      {{-- ========================================================
           CTA
      ======================================================== --}}
      <div class="center-cta reveal">

        <button
          class="btn btn-navy btn-lg"
          data-go="inscricao"
        >
          ESTUDE COM NOSSOS PROFESSORES
        </button>

      </div>

    </div>

  </section>


  {{-- ============================================================
       VERSÍCULO
  ============================================================ --}}
  <section class="verse">

    <div
      class="wrap"
      style="grid-template-columns:1fr"
    >

      <div
        class="vtxt reveal"
        style="grid-column:1/-1"
      >

        <p>

          <span class="qmark">
            “
          </span>

          E ele mesmo deu uns para apóstolos,
          outros para profetas,
          outros para evangelistas,
          e outros para pastores e mestres.

        </p>

        <cite>
          Efésios 4:11
        </cite>

        <div class="crown-rule">

          <span class="ln"></span>

          <svg
            data-ic="crown"
            viewBox="0 0 24 24"
          ></svg>

          <span class="ln"></span>

        </div>

      </div>

    </div>

  </section>

</div>