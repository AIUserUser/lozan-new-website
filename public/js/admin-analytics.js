(function () {
  var el = document.getElementById('analytics-data');
  if (!el || typeof Chart === 'undefined') return;
  var data = JSON.parse(el.textContent);

  // Validated two-slot categorical palette + recessive chrome.
  var SERIES_1 = '#2a78d6';
  var SERIES_2 = '#eb6834';
  var GRID = '#e1e0d9';
  var AXIS = '#c3c2b7';
  var MUTED = '#898781';
  var INK = '#1a1a1a';

  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.font.size = 12;
  Chart.defaults.color = MUTED;
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.plugins.tooltip.backgroundColor = INK;
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.boxPadding = 4;
  Chart.defaults.plugins.tooltip.rtl = data.rtl;
  Chart.defaults.plugins.legend.rtl = data.rtl;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 8;
  Chart.defaults.plugins.legend.labels.boxHeight = 8;
  Chart.defaults.plugins.legend.labels.color = '#52514e';

  function valueAxis() {
    return {
      beginAtZero: true,
      border: { display: false },
      grid: { color: GRID },
      ticks: { precision: 0 },
    };
  }

  function categoryAxis(extra) {
    return Object.assign({
      border: { color: AXIS },
      grid: { display: false },
    }, extra || {});
  }

  function shortDay(iso) {
    var parts = iso.split('-');
    return parts[2] + '/' + parts[1];
  }

  var trend = document.getElementById('chart-trend');
  if (trend) {
    new Chart(trend, {
      type: 'line',
      data: {
        labels: data.daily.labels.map(shortDay),
        datasets: [
          { label: data.labels.visitors, data: data.daily.visitors, borderColor: SERIES_1, backgroundColor: SERIES_1 },
          { label: data.labels.productViews, data: data.daily.productViews, borderColor: SERIES_2, backgroundColor: SERIES_2 },
        ],
      },
      options: {
        interaction: { mode: 'index', intersect: false },
        elements: {
          line: { borderWidth: 2, tension: 0.3 },
          point: { radius: data.daily.labels.length > 31 ? 0 : 2.5, hoverRadius: 5, hitRadius: 12 },
        },
        plugins: { legend: { position: 'top', align: 'end' } },
        scales: {
          x: categoryAxis({ ticks: { maxRotation: 0, autoSkipPadding: 16 } }),
          y: valueAxis(),
        },
      },
    });
  }

  var products = document.getElementById('chart-products');
  if (products && data.products.length) {
    new Chart(products, {
      type: 'bar',
      data: {
        labels: data.products.map(function (p) { return p.name.length > 34 ? p.name.slice(0, 33) + '…' : p.name; }),
        datasets: [{ label: data.labels.productViewsSingle, data: data.products.map(function (p) { return p.views; }), backgroundColor: SERIES_1, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 18 }],
      },
      options: {
        indexAxis: 'y',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { title: function (items) { return data.products[items[0].dataIndex].name; } } },
        },
        scales: {
          x: Object.assign(valueAxis(), { reverse: data.rtl }),
          y: categoryAxis({ position: data.rtl ? 'right' : 'left', ticks: { color: '#52514e' } }),
        },
      },
    });
  }

  var hours = document.getElementById('chart-hours');
  if (hours) {
    new Chart(hours, {
      type: 'bar',
      data: {
        labels: data.hours.map(function (_, h) { return (h < 10 ? '0' : '') + h; }),
        datasets: [{ label: data.labels.views, data: data.hours, backgroundColor: SERIES_1, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 16 }],
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { title: function (items) { return items[0].label + ':00'; } } },
        },
        scales: {
          x: categoryAxis({ ticks: { maxRotation: 0, autoSkipPadding: 8 } }),
          y: valueAxis(),
        },
      },
    });
  }
})();
