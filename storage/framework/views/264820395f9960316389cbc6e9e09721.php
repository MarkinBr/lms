<div class="page" data-page="contato">
  <div class="crumb"><div class="wrap"><a data-go="home">INÍCIO</a><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg><span class="cur">CONTATO</span></div></div>
  <section class="hero" style="padding-bottom:24px"><div class="watermark-faces" aria-hidden="true"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&q=60" alt=""><img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&q=60" alt=""></div>
    <div class="wrap" style="grid-template-columns:1fr;text-align:center"><div style="max-width:760px;margin-inline:auto"><span class="eyebrow" style="justify-content:center"><span class="ln"></span>Fale conosco<span class="ln"></span></span><h1 class="serif">ENTRE EM<span class="gold-text"> CONTATO</span></h1><p class="lead" style="margin-inline:auto">Estamos prontos para atender você. Tire suas dúvidas, solicite informações ou envie sua mensagem — nossa equipe responde com carinho e agilidade.</p></div></div>
  </section>

  <section class="sec" style="padding-top:10px"><div class="wrap">
    <div class="contact-grid">
      <div class="contact-form reveal">
        <h2>Envie sua mensagem</h2>
        <p class="lead">Preencha o formulário e retornaremos em até 24 horas úteis.</p>
        <form id="contactForm" method="POST" action="<?php echo e(route('site.contato')); ?>"><?php echo csrf_field(); ?>
          <div class="fgrid">
            <div class="field"><label>Nome completo <span class="req">*</span></label><input type="text" name="nome" placeholder="Seu nome" required></div>
            <div class="field"><label>E-mail <span class="req">*</span></label><input type="email" name="email" placeholder="seu@email.com" required></div>
            <div class="field"><label>Telefone / WhatsApp</label><input type="tel" name="telefone" placeholder="(21) 99999-9999"></div>
            <div class="field"><label>Assunto <span class="req">*</span></label><select required><option value="">Selecione o assunto</option><option>Dúvidas sobre cursos</option><option>Matrículas e inscrições</option><option>Secretaria e documentos</option><option>Financeiro</option><option>Suporte ao portal</option><option>Parcerias e outros</option></select></div>
            <div class="field full"><label>Mensagem <span class="req">*</span></label><textarea name="mensagem" placeholder="Escreva sua mensagem..." required></textarea></div>
          </div>
          <label class="terms"><input type="checkbox" required> Autorizo o CETSADP a utilizar meus dados para responder a este contato, conforme a política de privacidade. <span class="req">*</span></label>
          <button type="submit" class="btn btn-navy btn-block btn-lg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg> ENVIAR MENSAGEM</button>
          <div class="form-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Suas informações estão seguras e não serão compartilhadas.</div>
        </form>
      </div>

      <div class="contact-stack reveal">
        <div class="ci-card"><span class="ic" data-ic="pin"></span><div><b>Endereço</b><div class="v">Rua Afonso Franco, 15<br>Centro - Paracambi/RJ<br>CEP: 26600-000</div></div></div>
        <div class="ci-card"><span class="ic" data-ic="phone"></span><div><b>Telefone &amp; WhatsApp</b><div class="v"><a href="tel:2126833271">(21) 2683-3271</a><br><a href="#">(21) 99999-0000 (WhatsApp)</a></div></div></div>
        <div class="ci-card"><span class="ic" data-ic="mail"></span><div><b>E-mail</b><div class="v"><a href="mailto:contato@cetsadp.com.br">contato@cetsadp.com.br</a><br><a href="mailto:secretaria@cetsadp.com.br">secretaria@cetsadp.com.br</a></div></div></div>
        <div class="ci-card"><span class="ic" data-ic="clock"></span><div><b>Horário de atendimento</b><div class="v">Segunda a Sexta · 08h às 17h<br>Sábado · 08h às 12h</div></div></div>
        <div class="map-box"><iframe loading="lazy" title="Mapa CETSADP - Paracambi" src="https://www.openstreetmap.org/export/embed.html?bbox=-43.7300%2C-22.6260%2C-43.6960%2C-22.6000&amp;layer=mapnik&amp;marker=-22.6130%2C-43.7130"></iframe><div class="maptag"><span class="ping"></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> CETSADP · Paracambi</div></div>
        <div><div style="font-family:var(--serif);color:var(--navy);font-size:1.1rem;font-weight:700;margin-bottom:12px">Siga-nos</div>
          <div class="social-row">
            <a href="#" data-toast="Abrindo Facebook..."><span class="ic" style="background:#1877f2" data-ic="fb"></span><span>Facebook</span></a>
            <a href="#" data-toast="Abrindo Instagram..."><span class="ic" style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)" data-ic="ig"></span><span>Instagram</span></a>
            <a href="#" data-toast="Abrindo YouTube..."><span class="ic" style="background:#ff0000" data-ic="yt"></span><span>YouTube</span></a>
          </div></div>
      </div>
    </div>
  </div></section>

  <section class="sec" style="padding-top:0"><div class="wrap" style="max-width:860px">
    <div class="sec-head reveal"><span class="eyebrow"><span class="ln"></span>Dúvidas frequentes<span class="ln"></span></span><h2>PERGUNTAS FREQUENTES</h2></div>
    <div class="faq-list reveal">
      <div class="faq-item"><button class="faq-q">Como faço minha matrícula?<span class="pm" data-ic="plus"></span></button><div class="faq-a"><p>Você pode se inscrever diretamente pelo nosso formulário de inscrição no site, escolhendo o curso e a modalidade desejados. Em seguida, a secretaria entra em contato para finalizar o processo e enviar as orientações de pagamento e documentação.</p></div></div>
      <div class="faq-item"><button class="faq-q">Quais modalidades de ensino estão disponíveis?<span class="pm" data-ic="plus"></span></button><div class="faq-a"><p>Oferecemos as modalidades Presencial, Híbrida (encontros ao vivo + plataforma) e EAD (100% online, no seu tempo). A disponibilidade varia conforme o curso — confira a página de cada curso para mais detalhes.</p></div></div>
      <div class="faq-item"><button class="faq-q">O certificado é reconhecido?<span class="pm" data-ic="plus"></span></button><div class="faq-a"><p>Sim. Os certificados emitidos pelo CETSADP são reconhecidos pelo IEADP e pela Igreja Evangélica Assembleia de Deus, com validade para fins eclesiásticos e ministeriais.</p></div></div>
      <div class="faq-item"><button class="faq-q">O curso de Diaconato é apenas para homens?<span class="pm" data-ic="plus"></span></button><div class="faq-a"><p>Não. A Habilitação ao Diaconato do CETSADP é aberta a homens e mulheres que desejam se preparar para servir como diáconos e diaconisas, com excelência e integridade.</p></div></div>
      <div class="faq-item"><button class="faq-q">Como solicito um documento ou declaração?<span class="pm" data-ic="plus"></span></button><div class="faq-a"><p>Documentos como declarações de matrícula, históricos e certificados podem ser solicitados pela secretaria, pelo portal do aluno ou por este formulário de contato, na opção “Secretaria e documentos”.</p></div></div>
      <div class="faq-item"><button class="faq-q">Esqueci minha senha do portal. E agora?<span class="pm" data-ic="plus"></span></button><div class="faq-a"><p>Na tela de acesso do portal, utilize a opção “Esqueceu sua senha?” para receber um link de recuperação no seu e-mail cadastrado. Se precisar de ajuda, fale com o suporte técnico pelo WhatsApp.</p></div></div>
    </div>
  </div></section>

  <section class="verse"><div class="wrap" style="grid-template-columns:1fr"><div class="vtxt reveal" style="grid-column:1/-1"><p><span class="qmark">“</span>Sejam vossas palavras sempre agradáveis, temperadas com sal, para que saibais como responder a cada um.</p><cite>Colossenses 4:6</cite><div class="crown-rule"><span class="ln"></span><svg data-ic="crown" viewBox="0 0 24 24"></svg><span class="ln"></span></div></div></div></section>
</div>
<?php /**PATH C:\Users\Marcos\Downloads\cetsadp-lms-completo\lms\resources\views/site/pages/contato.blade.php ENDPATH**/ ?>