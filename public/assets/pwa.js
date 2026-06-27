(() => {
  const menuButton = document.querySelector('.mobile-menu-button');
  const menu = document.querySelector('#app-menu');
  const overlay = document.querySelector('.app-menu-overlay');
  const closeMenu = document.querySelector('.app-menu-close');
  const setMenu = open => {
    document.body.classList.toggle('menu-open', open);
    if (menuButton) menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (menu) menu.setAttribute('aria-hidden', open ? 'false' : 'true');
  };
  if (menuButton) menuButton.addEventListener('click', () => setMenu(!document.body.classList.contains('menu-open')));
  if (overlay) overlay.addEventListener('click', () => setMenu(false));
  if (closeMenu) closeMenu.addEventListener('click', () => setMenu(false));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') setMenu(false); });
  document.querySelectorAll('#app-menu a').forEach(link => link.addEventListener('click', () => setMenu(false)));

  const banner = document.querySelector('.pwa-banner');
  const title = document.querySelector('.pwa-banner-title');
  const text = document.querySelector('.pwa-banner-text');
  const action = document.querySelector('.pwa-banner-action');
  const close = document.querySelector('.pwa-banner-close');
  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  let installPrompt = null;
  let waitingWorker = null;

  const showBanner = (mode) => {
    if (!banner || !action || !title || !text) return;
    banner.dataset.mode = mode;
    if (mode === 'update') {
      title.textContent = 'Atualização disponível';
      text.textContent = 'Toque para carregar a versão mais nova.';
      action.textContent = 'Atualizar';
    } else {
      title.textContent = 'Instalar aplicativo';
      text.textContent = 'Acesse mais rápido pela tela inicial.';
      action.textContent = 'Instalar';
    }
    banner.hidden = false;
  };
  const hideBanner = () => { if (banner) banner.hidden = true; };
  if (close) close.addEventListener('click', hideBanner);

  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    if (!standalone) showBanner('install');
  });
  window.addEventListener('appinstalled', hideBanner);

  if (action) action.addEventListener('click', async () => {
    if (banner && banner.dataset.mode === 'update' && waitingWorker) {
      waitingWorker.postMessage({type: 'SKIP_WAITING'});
      return;
    }
    if (!installPrompt) return hideBanner();
    installPrompt.prompt();
    await installPrompt.userChoice.catch(() => null);
    installPrompt = null;
    hideBanner();
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
      const registration = await navigator.serviceWorker.register('./sw.js');
      const watchWorker = worker => {
        if (!worker) return;
        worker.addEventListener('statechange', () => {
          if (worker.state === 'installed' && navigator.serviceWorker.controller) {
            waitingWorker = worker;
            showBanner('update');
          }
        });
      };
      watchWorker(registration.installing);
      if (registration.waiting && navigator.serviceWorker.controller) {
        waitingWorker = registration.waiting;
        showBanner('update');
      }
      registration.addEventListener('updatefound', () => watchWorker(registration.installing));
    });
    navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload());
  }
})();
