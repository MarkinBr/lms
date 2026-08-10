/* =========================================================================
   CETSADP · motion.js
   Utilitário pequeno e sem dependências. NÃO reimplementa o observer de
   `.reveal` (já existe em app.js) — só adiciona o que faltava: loading de
   botão e troca de skeleton por conteúdo real.
   Ver docs/MOTION_GUIDELINES.md.
   ========================================================================= */
(function () {
  'use strict';

  /**
   * Liga/desliga o estado de loading num botão de forma segura, mesmo que
   * o botão ainda não tenha o <span class="cs-btn-loading__label">: na
   * primeira chamada, envolve o conteúdo atual automaticamente.
   */
  function setButtonLoading(btn, isLoading) {
    if (!btn) return;
    if (!btn.querySelector('.cs-btn-loading__label')) {
      const span = document.createElement('span');
      span.className = 'cs-btn-loading__label';
      span.innerHTML = btn.innerHTML;
      btn.innerHTML = '';
      btn.appendChild(span);
    }
    btn.classList.toggle('cs-btn-loading', !!isLoading);
    btn.disabled = !!isLoading;
    btn.setAttribute('aria-busy', isLoading ? 'true' : 'false');
  }

  /**
   * Troca um skeleton pelo conteúdo real. `renderFn` recebe o elemento de
   * destino e escreve o conteúdo nele. Existe só para padronizar o padrão
   * "skeleton -> conteúdo", não é obrigatório usar isso especificamente.
   *
   * Exemplo:
   *   CSMotion.replaceSkeleton(
   *     document.getElementById('listaAlunos'),
   *     el => { el.innerHTML = htmlReal; }
   *   );
   */
  function replaceSkeleton(skeletonEl, renderFn) {
    if (!skeletonEl) return;
    skeletonEl.classList.remove('cs-skeleton', 'cs-skeleton--linha', 'cs-skeleton--titulo', 'cs-skeleton--card', 'cs-skeleton--avatar');
    skeletonEl.removeAttribute('aria-hidden');
    renderFn(skeletonEl);
    skeletonEl.classList.add('reveal');
    // reaproveita o observer que já existe em app.js: só precisa da classe .reveal
    requestAnimationFrame(() => skeletonEl.classList.add('in'));
  }

  window.CSMotion = { setButtonLoading, replaceSkeleton };
})();
