document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('placementsMonthly');
  if (!el || typeof Chart === 'undefined') return;

  // מקבל מה-View: window.placementsChartData = { labels: [...], active: [...] }
  var payload = window.placementsChartData || { labels: [], active: [] };
  var labels  = Array.isArray(payload.labels) ? payload.labels : [];
  var data    = Array.isArray(payload.active) ? payload.active : [];

  var ctx = el.getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'שיבוצים פעילים',
        data: data,
        borderWidth: 2,
        fill: false,
        tension: 0.2
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
});
