/* Costa Serena · operational UI. Only client-side presentation; business rules remain on the server. */
(() => {
  'use strict';
  const root = document.querySelector('.ux-page');
  if (!root) return;
  const $ = (selector, scope = root) => scope.querySelector(selector);
  const $$ = (selector, scope = root) => [...scope.querySelectorAll(selector)];

  $$("[data-ux-tabs]").forEach((shell) => {
    const tabs = $$("[data-ux-tab]", shell);
    const panels = $$("[data-ux-panel]", shell);
    const activate = (tab, focus = false) => {
      tabs.forEach((current) => {
        const active = current === tab;
        current.classList.toggle('is-active', active);
        current.setAttribute('aria-selected', String(active));
        current.tabIndex = active ? 0 : -1;
      });
      panels.forEach((panel) => { panel.hidden = panel.dataset.uxPanel !== tab.dataset.uxTab; });
      if (focus) tab.focus();
    };
    tabs.forEach((tab, index) => {
      const panel = panels.find(item => item.dataset.uxPanel === tab.dataset.uxTab);
      if (!panel) return;
      const tabId = `ux-tab-${tab.dataset.uxTab}-${index}`;
      const panelId = `ux-panel-${tab.dataset.uxTab}-${index}`;
      tab.id = tabId;
      tab.setAttribute('aria-controls', panelId);
      panel.id = panelId;
      panel.setAttribute('aria-labelledby', tabId);
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', event => {
        let next = -1;
        if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
        if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = tabs.length - 1;
        if (next !== -1) { event.preventDefault(); activate(tabs[next], true); }
      });
    });
    if (tabs.length) activate(tabs.find(t => t.classList.contains('is-active')) || tabs[0]);
  });

  $$('[data-ux-filter]').forEach(input => {
    const table = document.getElementById(input.dataset.uxFilter);
    if (!table) return;
    const rows = $$("tbody tr", table).filter(row => row.querySelectorAll('td').length > 1);
    const empty = $$('[data-ux-empty]').find(element => element.dataset.uxEmpty === table.id);
    const normalize = value => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    const filter = () => {
      const query = normalize(input.value);
      let shown = 0;
      rows.forEach(row => {
        const visible = normalize(row.textContent).includes(query);
        row.hidden = !visible;
        if (visible) shown++;
      });
      if (empty) empty.hidden = !query || shown !== 0;
    };
    input.addEventListener('input', filter);
  });

  $$('[data-ux-open]').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const dialog = document.getElementById(trigger.dataset.uxOpen);
      if (dialog?.showModal) dialog.showModal();
      else if (dialog) dialog.setAttribute('open', '');
    });
  });
  $$('[data-ux-close]').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const dialog = trigger.closest('dialog');
      if (dialog?.close) dialog.close();
      else dialog?.removeAttribute('open');
    });
  });
  $$('dialog.ux-dialog').forEach(dialog => {
    dialog.addEventListener('click', event => {
      if (event.target === dialog) dialog.close();
    });
  });
  $$('button[data-ux-confirm]').forEach(button => {
    button.closest('form')?.addEventListener('submit', event => {
      if (!window.confirm(button.dataset.uxConfirm || 'Confirmar esta ação?')) event.preventDefault();
    });
  });
  const reservationForm = $('[data-ux-reservation-form]');
  if (reservationForm) {
    const from = $('[name="check_in"]', reservationForm), to = $('[name="check_out"]', reservationForm);
    const sync = () => {
      if (!from?.value || !to) return;
      const [y, m, d] = from.value.split('-').map(Number);
      const minimumDate = new Date(Date.UTC(y, m - 1, d + 1)).toISOString().slice(0, 10);
      to.min = minimumDate;
      if (to.value && to.value < minimumDate) to.value = '';
    };
    from?.addEventListener('change', sync); sync();
  }
  const dirtyForm = $('[data-ux-dirty-form]');
  if (dirtyForm) {
    let modified = false;
    const indicator = $('[data-ux-dirty-indicator]', dirtyForm);
    const changed = () => { modified = true; if (indicator) indicator.textContent = 'Há alterações ainda não salvas.'; };
    dirtyForm.addEventListener('input', changed);
    dirtyForm.addEventListener('change', changed);
    dirtyForm.addEventListener('submit', () => { modified = false; });
    window.addEventListener('beforeunload', event => { if (!modified) return; event.preventDefault(); event.returnValue = ''; });
  }
  $$('details.ux-create-pop').forEach(details => {
    document.addEventListener('click', event => { if (details.open && !details.contains(event.target)) details.open = false; });
    details.addEventListener('keydown', event => { if (event.key === 'Escape') details.open = false; });
  });
})();
