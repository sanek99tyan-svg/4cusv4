(() => {
  const payload = window.__FOURCUS_CMS__;
  if (!payload) return;

  const content = payload.content || {};
  const langFromPage = () => (document.documentElement.lang || 'ru').toLowerCase().startsWith('en') ? 'en' : 'ru';
  const applyCmsText = (lang = langFromPage()) => {
    Object.entries(content).forEach(([key, values]) => {
      const value = values ? ((lang === 'en' ? values.en : values.ru) || values.ru || values.en || '') : '';
      if (!value) return;
      document.querySelectorAll('[data-i18n="' + (window.CSS?.escape ? CSS.escape(key) : key) + '"]').forEach(el => el.textContent = value);
      document.querySelectorAll('[data-i18n-placeholder="' + (window.CSS?.escape ? CSS.escape(key) : key) + '"]').forEach(el => el.placeholder = value);
    });
  };

  // Подмешиваем CMS-значения в штатный словарь сайта.
  // Благодаря этому переключатель RU/EN продолжает использовать отредактированные в админке тексты.
  try {
    if (typeof translations !== 'undefined') {
      Object.entries(content).forEach(([key, values]) => {
        if (values?.ru) translations.ru[key] = values.ru;
        if (values?.en) translations.en[key] = values.en;
      });
    }
  } catch (_) {}

  applyCmsText();

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
      .map(([slug, state]) => ({slug, order: Number(state.sort_order || 0), el: document.querySelector(blockMap[slug] || '')}))
      .filter(x => x.el && x.slug !== 'hero')
      .sort((a,b) => a.order - b.order);
    const anchor = document.querySelector('.canvas-section');
    sortable.forEach(x => main.insertBefore(x.el, anchor || null));
  }

  const settings = payload.settings || {};
  if (settings.contact_email) {
    document.querySelectorAll('a[href^="mailto:"]').forEach(a => a.href = 'mailto:' + settings.contact_email);
  }

  const gallery = payload.gallery || [];
  if (gallery.length) {
    const section = document.createElement('section');
    section.className = 'cms-gallery section-pad';
    section.id = 'gallery';
    section.innerHTML = '<div class="section-head reveal visible"><div><div class="eyebrow">Портфолио</div><h2>Галерея проектов</h2></div><p>Работы и визуальные материалы команды 4cus.</p></div><div class="cms-gallery-grid"></div>';
    const grid = section.querySelector('.cms-gallery-grid');

    gallery.forEach(item => {
      const card = document.createElement(item.link_url ? 'a' : 'article');
      card.className = 'cms-gallery-card';
      if (item.link_url) {
        card.href = item.link_url;
        card.target = '_blank';
        card.rel = 'noopener';
      }

      const imageWrap = document.createElement('div');
      imageWrap.className = 'cms-gallery-image';
      if (item.image_url) {
        const img = document.createElement('img');
        img.src = item.image_url;
        img.alt = item.title || '';
        img.loading = 'lazy';
        imageWrap.appendChild(img);
      } else {
        const placeholder = document.createElement('div');
        placeholder.className = 'cms-gallery-placeholder';
        placeholder.textContent = '4cus.';
        imageWrap.appendChild(placeholder);
      }

      const copy = document.createElement('div');
      copy.className = 'cms-gallery-copy';
      const small = document.createElement('small');
      small.textContent = item.category || 'PROJECT';
      const h3 = document.createElement('h3');
      h3.textContent = item.title || '';
      const p = document.createElement('p');
      p.textContent = item.description || '';
      copy.append(small,h3,p);
      card.append(imageWrap,copy);
      grid.appendChild(card);
    });

    const contact = document.querySelector('#contact');
    if (contact) contact.before(section); else main?.appendChild(section);
  }

  // Перехватываем существующую mailto-форму и сохраняем заявки в БД.
  const form = document.querySelector('#projectForm');
  if (form) {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      event.stopImmediatePropagation();

      const button = form.querySelector('button[type="submit"]');
      const original = button ? button.innerHTML : '';
      if (button) { button.disabled = true; button.textContent = langFromPage() === 'en' ? 'Sending…' : 'Отправляем…'; }

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
        if (button) button.textContent = langFromPage() === 'en' ? 'Sent ✓' : 'Заявка отправлена ✓';
        setTimeout(() => { if (button) button.innerHTML = original; }, 2600);
      } catch (e) {
        alert(e.message || 'Не удалось отправить заявку');
        if (button) button.innerHTML = original;
      } finally {
        if (button) button.disabled = false;
      }
    }, true);
  }

  // Повторно применяем CMS после переключения языка штатной кнопкой.
  document.querySelector('#langSwitch')?.addEventListener('click', () => {
    setTimeout(() => applyCmsText(langFromPage()), 0);
  });
})();