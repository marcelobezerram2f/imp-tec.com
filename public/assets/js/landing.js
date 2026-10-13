(() => {
  const toggle = document.querySelector('.mobile-menu-button');
  const menu = document.querySelector('#mobile-menu');
  const closeMenu = () => { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.querySelector('i').className = 'ri-menu-line'; };
  toggle?.addEventListener('click', () => {
    menu.hidden = !menu.hidden;
    toggle.setAttribute('aria-expanded', String(!menu.hidden));
    toggle.querySelector('i').className = menu.hidden ? 'ri-menu-line' : 'ri-close-line';
  });
  menu?.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && menu && toggle) closeMenu(); });
  const cards = [...document.querySelectorAll('[data-testimonial]')];
  const dots = [...document.querySelectorAll('[data-slide]')];
  let active = 0;
  const show = n => {
    active = (n + cards.length) % cards.length;
    cards.forEach((card, i) => { card.hidden = ![0, 1, 2].some(offset => (active + offset) % cards.length === i); card.style.order = (i - active + cards.length) % cards.length; });
    dots.forEach((dot, i) => { dot.classList.toggle('active', i === active); dot.setAttribute('aria-pressed', String(i === active)); });
  };
  if (cards.length) {
    show(0);
    dots.forEach(dot => dot.addEventListener('click', () => show(Number(dot.dataset.slide))));
    document.querySelectorAll('[data-carousel]').forEach(button => button.addEventListener('click', () => show(active + (button.dataset.carousel === 'next' ? 1 : -1))));
  }
  const form = document.querySelector('#lead-form');
  let sending = false;
  form?.addEventListener('submit', async e => {
    e.preventDefault();
    if (sending || !form.reportValidity()) return;
    sending = true;
    const button = form.querySelector('button[type=submit]');
    const status = document.querySelector('#form-status');
    button.disabled = true; button.querySelector('span').textContent = 'Enviando...';
    status.textContent = ''; status.className = '';
    try {
      const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Não foi possível enviar. Tente novamente.');
      status.className = 'success'; status.textContent = result.message; form.reset();
    } catch (error) {
      status.className = 'error';
      status.textContent = error instanceof SyntaxError || error instanceof TypeError ? 'Não foi possível confirmar o envio. Verifique sua conexão ou fale pelo WhatsApp.' : error.message;
    } finally {
      sending = false; button.disabled = false; button.querySelector('span').textContent = 'Enviar projeto';
    }
  });
})();
