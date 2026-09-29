(() => {
  const payload = window.__FOURCUS_CMS__;
  if (!payload) return;

  const lang = (localStorage.getItem('lang') || document.documentElement.lang || 'ru').toLowerCase().startsWith('en') ? 'en' : 'ru';
  const cssEscape = window.CSS && CSS.escape ? CSS.escape : (s => String(s).replace(/"/g, '\\"'));

  Object.entries(payload.content || {}).forEach(([key, values]) => {
    const value = values ? (lang === 'en' ? values.en : values.ru) : '';
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

  const main = document.querySelector('main');
  if (main) {
    const sortable = Object.entries(payload.blocks || {})
      .map(([slug, state]) => ({ slug, order: Number(state.sort_order || 0), el: document.querySelector(blockMap[slug] || '') }))
      .filter(x => x.el && x.slug !== 'hero')
      .sort((a,b) => a.order - b.order);
    const anchor = document.querySelector('.canvas-section');
    sortable.forEach(x => main.insertBefore(x.el, anchor || null));
  }

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
      const safeUrl = String(item.image_url || '').replace(/"/g, '&quot;');
      card.innerHTML = `
        <div class="cms-gallery-image">${safeUrl ? '<img src="' + safeUrl + '" alt="">' : '<div class="cms-gallery-placeholder">4cus.</div>'}</div>
        <div class="cms-gallery-copy">
          <small>${item.category || 'PROJECT'}</small>
          <h3>${item.title || ''}</h3>
          <p>${item.description || ''}</p>
        </div>`;
      grid.appendChild(card);
    });
    const contact = document.querySelector('#contact');
    if (contact) contact.before(section);
    else main?.appendChild(section);
  }

  const form = document.querySelector('#projectForm');
  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
      const button = form.querySelector('button[type="submit"]');
      const original = button ? button.innerHTML : '';
      if (button) { button.disabled = true; button.textContent = 'Отправляем…'; }
      try {
        const response = await fetch('/api/contact.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({
            name: document.querySelector('#contactName')?.value || '',
            contact: document.querySelector('#contactWay')?.value || '',
            task: document.querySelector('#contactTask')?.value || ''
          })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'Ошибка отправки');
        form.reset();
        if (button) button.textContent = 'Заявка отправлена ✓';
        setTimeout(() => { if (button) button.innerHTML = original; }, 2600);
      } catch (e) {
        alert(e.message || 'Не удалось отправить заявку');
        if (button) button.innerHTML = original;
      } finally {
        if (button) button.disabled = false;
      }
    }, true);
  }
})();