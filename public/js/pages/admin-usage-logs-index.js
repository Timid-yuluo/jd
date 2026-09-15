document.addEventListener('DOMContentLoaded', function() {
    @php $trendLabels = collect($dailyTrend)->pluck('label')->toJson(); @endphp
    @php $trendCalls = collect($dailyTrend)->pluck('calls')->toJson(); @endphp
    @php $trendCosts = collect($dailyTrend)->map(fn($r) => round($r['cost_micros'] / 1000000, 4))->toJson(); @endphp

    var options = {
        chart: { type: 'bar', height: 160, toolbar: { show: false } },
        series: [
            { name: '调用次数', type: 'column', data: document.querySelector('[data-var-trendcalls]')?.getAttribute('data-var-trendcalls') || '' },
            { name: '费用(¥)', type: 'line', data: document.querySelector('[data-var-trendcosts]')?.getAttribute('data-var-trendcosts') || '' },
        ],
        xaxis: { categories: document.querySelector('[data-var-trendlabels]')?.getAttribute('data-var-trendlabels') || '' },
        yaxis: [
            { title: { text: '调用' }, labels: { style: { fontSize: '10px' } } },
            { opposite: true, title: { text: '¥' }, labels: { style: { fontSize: '10px' } } },
        ],
        colors: ['#206bc4', '#2fb344'],
        stroke: { width: [0, 2] },
        plotOptions: { bar: { borderRadius: 3 } },
        dataLabels: { enabled: false },
        legend: { position: 'top', fontSize: '11px' },
        tooltip: { shared: true, intersect: false },
    };

    var el = document.getElementById('trend-chart');
    if (el) new ApexCharts(el, options).render();
});
