(() => {
  'use strict';
  const menu = document.getElementById('csMenu');
  const toggle = document.getElementById('csMenuToggle');
  if (menu && toggle) {
    const close = () => { menu.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); toggle.setAttribute('aria-label', 'Abrir menu de navegação'); };
    toggle.addEventListener('click', () => {
      const open = menu.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Fechar menu de navegação' : 'Abrir menu de navegação');
    });
    menu.addEventListener('click', event => { if (event.target.closest('a')) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    document.addEventListener('click', event => { if (!menu.contains(event.target) && !toggle.contains(event.target)) close(); });
  }
  const checkin = document.getElementById('csCheckin');
  const checkout = document.getElementById('csCheckout');
  if (checkin && checkout) {
    const datePlusDay = dateString => { const d = new Date(dateString + 'T12:00:00'); d.setDate(d.getDate() + 1); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; };
    const validate = () => {
      if (!checkin.value) return;
      const min = datePlusDay(checkin.value);
      checkout.min = min;
      if (!checkout.value || checkout.value < min) checkout.value = min;
    };
    checkin.addEventListener('change', validate);
    validate();
  }
})();
