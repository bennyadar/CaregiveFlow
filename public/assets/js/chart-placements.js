document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('placementsMonthly');
  if (!el || typeof Chart === 'undefined') return;

  // מקבל מה-View: window.placementsChartData = { labels: [...], active: [...] }
  var payload = window.placementsChartData || { labels: [], active: [] };
  var labels  = Array.isArray(payload.labels) ? payload.labels : [];
  var data    = Array.isArray(payload.active) ? payload.active : [];

  var rootStyles = getComputedStyle(document.documentElement);
  var primary = (rootStyles.getPropertyValue('--cf-primary') || '#2F9E7E').trim();
  var secondary = (rootStyles.getPropertyValue('--cf-secondary') || '#5CC3A6').trim();
  var border = (rootStyles.getPropertyValue('--cf-border') || '#D6E3DF').trim();
  var textMuted = (rootStyles.getPropertyValue('--cf-text-muted') || '#667085').trim();

  var ctx = el.getContext('2d');
  var gradient = ctx.createLinearGradient(0, 0, 0, el.height || 260);
  gradient.addColorStop(0, 'rgba(47, 158, 126, 0.18)');
  gradient.addColorStop(1, 'rgba(47, 158, 126, 0.02)');

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'שיבוצים פעילים',
        data: data,
        borderColor: primary,
        backgroundColor: gradient,
        pointBackgroundColor: primary,
        pointBorderColor: primary,
        pointHoverBackgroundColor: secondary,
        pointHoverBorderColor: primary,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 4,
        fill: true,
        tension: 0.32
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: true,
          labels: {
            color: textMuted,
            boxWidth: 36,
            boxHeight: 8,
            useBorderRadius: true,
            borderRadius: 6
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0, color: textMuted },
          grid: { color: border, borderDash: [4, 4] }
        },
        x: {
          ticks: { color: textMuted },
          grid: { color: 'rgba(214, 227, 223, 0.65)' }
        }
      }
    }
  });
});
