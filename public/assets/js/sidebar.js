// CaregiveFlow – Sidebar enhancements (AdminLTE-inspired)
// פונקציונליות: חיפוש בתפריט (client-side) + הסתרת פריטים שלא תואמים.

(function () {
  const body = document.body;
  const sidebar = document.querySelector('[data-cgf-sidebar]');
  const toggles = Array.from(document.querySelectorAll('[data-cgf-sidebar-toggle]'));

  // -----------------------------
  // Sidebar Mini (Collapse) – Persisted in localStorage
  // -----------------------------
  const STORAGE_KEY = 'cgf_sidebar_mini';

  const applyMini = (on) => {
    if (on) body.classList.add('cf-sidebar-mini');
    else body.classList.remove('cf-sidebar-mini');

    toggles.forEach((btn) => {
      try { btn.setAttribute('aria-pressed', on ? 'true' : 'false'); } catch (e) {}
    });

    // במצב Mini השדה חיפוש מוסתר – מנקים פילטר כדי שלא יישאר "תקוע".
    if (on && sidebar) {
      const input = sidebar.querySelector('[data-cgf-sidebar-search]');
      const items = Array.from(sidebar.querySelectorAll('[data-cgf-sidebar-item]'));
      if (input) input.value = '';
      items.forEach((a) => { a.style.display = ''; });
    }
  };

  // restore
  try {
    const saved = window.localStorage.getItem(STORAGE_KEY);
    if (saved === '1') applyMini(true);
  } catch (e) {}

  // bind toggles
  if (toggles.length) {
    toggles.forEach((btn) => {
      btn.addEventListener('click', function () {
        const next = !body.classList.contains('cf-sidebar-mini');
        applyMini(next);
        try { window.localStorage.setItem(STORAGE_KEY, next ? '1' : '0'); } catch (e) {}

        // אם חזרנו מ-mini -> נוח להתמקד בשדה החיפוש
        if (!next && sidebar) {
          const input = sidebar.querySelector('[data-cgf-sidebar-search]');
          if (input) input.focus();
        }
      });
    });
  }

  // -----------------------------
  // Sidebar Search (client-side)
  // -----------------------------
  if (!sidebar) return;
  const input = sidebar.querySelector('[data-cgf-sidebar-search]');
  const items = Array.from(sidebar.querySelectorAll('[data-cgf-sidebar-item]'));
  if (!input || items.length === 0) return;

  const normalize = (s) => (s || '').toString().trim().toLowerCase();

  input.addEventListener('input', function () {
    const q = normalize(input.value);
    items.forEach((a) => {
      const text = normalize(a.textContent);
      const show = (q.length === 0) || text.includes(q);
      a.style.display = show ? '' : 'none';
    });
  });
})();
