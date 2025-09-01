// Frontend logic for search, favorites, and small UI helpers

// Helpers
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

// Favorite toggle (stores locally and sends to server if logged in)
function toggleFavorite(el) {
  const pid = el.dataset.pid;
  const on = !el.classList.contains('on');
  el.classList.toggle('on', on);
  el.setAttribute('aria-pressed', String(on));
  el.textContent = on ? '❤ Saved' : '❤ Save';
  // optimistic UI; try to sync with server
  const base = (window.APP_ROOT || '').replace(/\/+$/, '/') || '';
  fetch(base + 'controllers/favorite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ property_id: pid, action: on ? 'add' : 'remove' })
  }).catch(() => {/* no-op */});
}

window.addEventListener('DOMContentLoaded', () => {
  // Hook favorite buttons
  $$('.fav-btn').forEach(btn => {
    btn.addEventListener('click', () => toggleFavorite(btn));
  });

  // Search form submit -> navigate to properties.php with query params (base-path aware)
  const sf = $('#searchForm');
  if (sf) {
    sf.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = new URLSearchParams(new FormData(sf)).toString();
      const base = (window.APP_ROOT || '').replace(/\/+$/, '/') || '';
      const action = sf.getAttribute('action') || 'properties.php';
      let target = action;
      // If action is relative (no scheme and not starting with /), prefix with base
      if (!/^([a-z]+:)?\//i.test(action)) {
        target = base + action.replace(/^\/+/, '');
      }
      window.location.href = `${target}?${q}`;
    });
  }

  // Contact form ajax (optional progressive)
  const cf = $('#contactForm');
  if (cf) {
    cf.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(cf);
  const base = (window.APP_ROOT || '').replace(/\/+$/, '/') || '';
  const res = await fetch(base + 'controllers/contact.php', { method: 'POST', body: formData });
      const json = await res.json().catch(() => ({}));
      alert(json.message || 'Inquiry sent!');
      if (json.ok) cf.reset();
    });
  }
});
