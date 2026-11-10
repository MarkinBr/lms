(() => {
  const canvas = document.getElementById('canvas-assinatura');
  const form = document.getElementById('form-assinatura');
  if (!canvas || !form) return;
  const ctx = canvas.getContext('2d', { willReadFrequently: false });
  const arquivo = document.getElementById('as-imagem');
  const desenho = document.getElementById('as-desenho');
  let desenhando = false;
  let temTraco = false;
  const inicio = e => {
    if (arquivo.files.length) return;
    desenhando = true;
    canvas.setPointerCapture(e.pointerId);
    const box = canvas.getBoundingClientRect();
    const x = (e.clientX - box.left) * canvas.width / box.width;
    const y = (e.clientY - box.top) * canvas.height / box.height;
    ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + .2, y + .2); ctx.stroke();
    temTraco = true;
  };
  const move = e => {
    if (!desenhando) return;
    const box = canvas.getBoundingClientRect();
    const x = (e.clientX - box.left) * canvas.width / box.width;
    const y = (e.clientY - box.top) * canvas.height / box.height;
    ctx.lineTo(x, y); ctx.stroke();
  };
  const fim = () => { desenhando = false; };
  ctx.strokeStyle = '#102f50';
  ctx.lineWidth = 2.7;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  canvas.addEventListener('pointerdown', inicio);
  canvas.addEventListener('pointermove', move);
  canvas.addEventListener('pointerup', fim);
  canvas.addEventListener('pointercancel', fim);
  document.getElementById('limpar-assinatura').addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    temTraco = false;
    desenho.value = '';
  });
  arquivo.addEventListener('change', () => {
    if (arquivo.files.length) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      temTraco = false;
    }
  });
  form.addEventListener('submit', event => {
    if (arquivo.files.length && temTraco) {
      event.preventDefault();
      window.alert('Selecione apenas arquivo ou desenho.'); return;
    }
    if (!arquivo.files.length && !temTraco) {
      event.preventDefault();
      window.alert('Envie uma imagem ou desenhe sua assinatura.'); return;
    }
    desenho.value = arquivo.files.length ? '' : canvas.toDataURL('image/png');
  });
})();
