var dates = JSON.parse(document.querySelector('[data-json-dates]')?.getAttribute('data-json-dates') || 'null');
var callsData = JSON.parse(document.querySelector('[data-json-callsdata]')?.getAttribute('data-json-callsdata') || 'null');
var tokensData = JSON.parse(document.querySelector('[data-json-tokensdata]')?.getAttribute('data-json-tokensdata') || 'null');
var scenarioStats = JSON.parse(document.querySelector('[data-json-scenariostats]')?.getAttribute('data-json-scenariostats') || 'null');

var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

new ApexCharts(document.getElementById('callsChart'), {
    chart: { type: 'area', height: 250, background: 'transparent', toolbar: { show: false } },
    series: [{ name: '调用次数', data: callsData }],
    xaxis: { categories: dates, labels: { style: { fontSize: '10px' } } },
    yaxis: { labels: { style: { fontSize: '10px' } } },
    stroke: { width: 2, curve: 'smooth' },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    colors: ['#206bc4'],
    dataLabels: { enabled: false },
    theme: { mode: isDark ? 'dark' : 'light' }
}).render();

new ApexCharts(document.getElementById('tokensChart'), {
    chart: { type: 'bar', height: 250, background: 'transparent', toolbar: { show: false } },
    series: [{ name: 'Token 消耗', data: tokensData }],
    xaxis: { categories: dates, labels: { style: { fontSize: '10px' } } },
    yaxis: { labels: { style: { fontSize: '10px' } } },
    colors: ['#4299e1'],
    plotOptions: { bar: { borderRadius: 4 } },
    dataLabels: { enabled: false },
    theme: { mode: isDark ? 'dark' : 'light' }
}).render();

new ApexCharts(document.getElementById('scenarioChart'), {
    chart: { type: 'donut', height: 250, background: 'transparent' },
    series: scenarioStats.map(function(s) { return s.count; }),
    labels: scenarioStats.map(function(s) { return s.scenario; }),
    colors: ['#206bc4', '#4299e1', '#17a2b8', '#5eba00', '#fab005', '#fa5252'],
    legend: { position: 'bottom' },
    theme: { mode: isDark ? 'dark' : 'light' }
}).render();
