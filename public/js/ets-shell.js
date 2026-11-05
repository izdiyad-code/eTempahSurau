(function () {
  const byId = (id) => document.getElementById(id);
  const drawer = byId('etsDrawer');
  const backdrop = byId('etsDrawerBackdrop');
  const menuToggle = byId('etsMenuToggle');
  const menuClose = byId('etsMenuClose');
  const languageToggle = byId('etsLanguageToggle');
  const languageMenu = byId('etsLanguageMenu');

  function closeDrawer() {
    if (!drawer || !backdrop) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.inert = true;
    backdrop.hidden = true;
    menuToggle?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('ets-drawer-open');
  }
  function openDrawer() {
    if (!drawer || !backdrop) return;
    closeLanguageMenu();
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    drawer.inert = false;
    backdrop.hidden = false;
    menuToggle?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('ets-drawer-open');
    menuClose?.focus();
  }
  function closeLanguageMenu() {
    if (!languageMenu || !languageToggle) return;
    languageMenu.hidden = true;
    languageToggle.setAttribute('aria-expanded', 'false');
  }
  function toggleLanguageMenu() {
    if (!languageMenu || !languageToggle) return;
    const willOpen = languageMenu.hidden;
    languageMenu.hidden = !willOpen;
    languageToggle.setAttribute('aria-expanded', String(willOpen));
    if (willOpen) closeDrawer();
  }

  menuToggle?.addEventListener('click', () => drawer?.classList.contains('is-open') ? closeDrawer() : openDrawer());
  menuClose?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);
  languageToggle?.addEventListener('click', toggleLanguageMenu);

  document.addEventListener('click', (event) => {
    if (languageMenu && !languageMenu.hidden && !event.target.closest('.ets-language-wrap')) closeLanguageMenu();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') { closeDrawer(); closeLanguageMenu(); }
  });
  document.querySelectorAll('.ets-drawer a').forEach((link) => link.addEventListener('click', closeDrawer));
})();
