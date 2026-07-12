document.addEventListener('DOMContentLoaded', function() {
  const city = document.querySelector('[data-city-select]');
  const street = document.querySelector('[data-street-select]');
  if (!city || !street) return;
  city.addEventListener('change', async function() {
    const city_code = this.value || 0;
    street.innerHTML = '<option value="">-- בחר/י רחוב --</option>';
    if (!city_code) return;
    try {
      const res = await fetch('ajax/streets.php?city_code=' + encodeURIComponent(city_code));
      const rows = await res.json();
      rows.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.street_code;
        opt.textContent = r.street_name_he;
        street.appendChild(opt);
      });
    } catch (e) { console.error(e); }
  });
});