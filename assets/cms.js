(() => {
  const payload = window.__FOURCUS_CMS__;
  if (!payload) return;

  const lang = (localStorage.getItem('lang') || document.documentElement.lang || 'ru').toLowerCase().startsWith('en') ? 'en' : 'ru';
  const cssEscape = window.CSS && CSS.escape ? CSS.escape : (s => String(s).replace(/"/g, '\\"'));

  Object.entries(payload.content || {}).forEach(([key, values]) => {
    const value = (values && (values[lang] || values.ru || values.en)) || '';
    if (!value) return;
    document.querySelectorAll('[data-i18n="' + cssEscape(key) + '"]').forEach(el => {
      el.textContent = value;
    });
  });

  const blockMap = {
    hero: '.hero',
    team: '#team',
    cases: '#cases',
    about: '#about',
    why: '.why',
    process: '.process',
    services: '#services',
    socials: '#socials',
    contact: '#contact'
  };
  Object.entries(payload.blocks || {}).forEach(([slug, state]) => {
    const selector = blockMap[slug];
    if (!selector) return;
    const el = document.querySelector(selector);
    if (el && state.enabled === false) el.hidden = true;
  });

  const gallery = payload.gallery || [];
  if (gallery.length) {
    const section = document.createElement('section');
    section.className = 'cms-gallery section-pad';
    section.id = 'gallery';
    section.innerHTML = `
      <div class="section-head reveal visible">
        <div><div class="eyebrow">Портфолио</div><h2>Галерея проектов</h2></div>
        <p>Работы и визуальные материалы команды 4cus.</p>
      </div>
      <div class="cms-gallery-grid"></div>`;
    const grid = section.querySelector('.cms-gallery-grid');
    gallery.forEach(item => {
      const card = document.createElement(item.link_url ? 'a' : 'article');
      card.className = 'cms-gallery-card';
      if (item.link_url) {
        card.href = item.link_url;
        card.target = '_blank';
        card.rel = 'noopener';
      }
      card.innerHTML = `
        <div class="cms-gallery-image">${item.image_url ? '<img src="' + item.image_url.replace(/"/g, '&quot;') + '" alt="">' : '<div class="cms-gallery-placeholder">4cus.</div>'}</div>
        <div class="cms-gallery-copy">
          <small>${item.category || 'PROJECT'}</small>
          <h3>${item.title || ''}</h3>
          <p>${item.description || ''}</p>
        </div>`;
      grid.appendChild(card);
    });
    const contact = document.querySelector('#contact');
    if (contact) contact.before(section);
    else document.querySelector('main')?.appendChild(section);
  }
})();