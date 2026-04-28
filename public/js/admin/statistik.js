/* global Chart */

function qs(id) {
    return document.getElementById(id);
}

function initFilterToggle() {
    const btn = qs('stat-filter-toggle');
    const panel = qs('stat-filter-panel');
    if (!btn || !panel) return;

    function setExpanded(isExpanded) {
        btn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        panel.hidden = !isExpanded;
    }

    btn.addEventListener('click', function () {
        const current = btn.getAttribute('aria-expanded') === 'true';
        setExpanded(!current);
    });
}

function getFiltersFromUi() {
    return {
        angkatan: qs('stat-filter-angkatan')?.value || '',
        tahun_lulus: qs('stat-filter-tahun-lulus')?.value || '',
        jenis_kelamin: qs('stat-filter-jenis-kelamin')?.value || '',
        status_alumni: qs('stat-filter-status-alumni')?.value || '',
        bidang_pekerjaan: qs('stat-filter-bidang')?.value || '',
        wilayah: qs('stat-filter-wilayah')?.value || ''
    };
}

function buildQuery(params) {
    const q = new URLSearchParams();
    Object.keys(params || {}).forEach((k) => {
        const v = params[k];
        if (v === null || v === undefined) return;
        const s = String(v).trim();
        if (s === '') return;
        q.set(k, s);
    });
    return q.toString();
}

function normalizeUnknownLabel(v) {
    const s = String(v ?? '').trim();
    if (!s || s === '-' || s.toLowerCase() === 'null') return 'Tidak diketahui';
    return s;
}

function normalizeLabels(labels) {
    return (Array.isArray(labels) ? labels : []).map(normalizeUnknownLabel);
}

function colorsForStatus(labels) {
    const key = (s) => String(s || '').toLowerCase().replace(/\s+/g, '_');
    return (labels || []).map((l) => {
        const k = key(l);
        if (k.includes('bekerja')) return '#2563eb';
        if (k.includes('belum')) return '#dc2626';
        if (k.includes('studi')) return '#7c3aed';
        if (k.includes('wirausaha')) return '#f59e0b';
        if (k.includes('tidak') || k.includes('unknown')) return '#94a3b8';
        return '#005a9c';
    });
}

function colorsForLinearitas(labels) {
    const key = (s) => String(s || '').toLowerCase();
    return (labels || []).map((l) => {
        const k = key(l);
        if (k.includes('sangat') && k.includes('erat')) return '#16a34a';
        if (k === 'erat') return '#22c55e';
        if (k.includes('cukup')) return '#0ea5e9';
        if (k.includes('kurang')) return '#f59e0b';
        if (k.includes('tidak') && k.includes('erat')) return '#dc2626';
        if (k.includes('tidak') || k.includes('unknown')) return '#94a3b8';
        return '#005a9c';
    });
}

function setLoading(isLoading) {
    const el = qs('stat-loading');
    const btnApply = qs('stat-apply');
    const btnReset = qs('stat-reset');

    if (el) el.style.display = isLoading ? 'block' : 'none';
    if (btnApply) btnApply.disabled = isLoading;
    if (btnReset) btnReset.disabled = isLoading;
}

function setKpi(id, value) {
    const el = qs(id);
    if (!el) return;
    el.textContent = value;
}

function setText(id, text) {
    const el = qs(id);
    if (!el) return;
    const t = String(text ?? '').trim();
    el.textContent = t;
    el.style.display = t ? '' : 'none';
}

function applyChartDefaults() {
    if (typeof Chart === 'undefined' || !Chart?.defaults) return;
    if (Chart.__GIS_ALUMNI_DEFAULTS_APPLIED__) return;
    Chart.__GIS_ALUMNI_DEFAULTS_APPLIED__ = true;

    Chart.defaults.color = '#334155';
    Chart.defaults.font.family = 'Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
    Chart.defaults.font.weight = '600';
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.legend.labels.padding = 12;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.92)';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255, 255, 255, 0.14)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.titleFont = { weight: '800' };
    Chart.defaults.plugins.tooltip.bodyFont = { weight: '700' };
    Chart.defaults.plugins.tooltip.displayColors = true;
}

function formatNumber(n) {
    const num = Number(n);
    if (!Number.isFinite(num)) return '0';
    return new Intl.NumberFormat('id-ID').format(num);
}

function formatDecimal(n, decimals) {
    const num = Number(n);
    if (!Number.isFinite(num)) return '-';
    return num.toFixed(decimals);
}

function setEmptyState(canvasId, isEmpty) {
    const canvas = qs(canvasId);
    const empty = document.querySelector(`.chart-empty[data-empty-for="${canvasId}"]`);
    if (canvas) canvas.style.display = isEmpty ? 'none' : 'block';
    if (empty) empty.hidden = !isEmpty;
}

const charts = {};

function destroyChart(key) {
    if (charts[key]) {
        try { charts[key].destroy(); } catch (_) { }
        delete charts[key];
    }
}

function updateDoughnutChart(key, canvasId, labels, data, colors) {
    const total = (data || []).reduce((a, b) => a + (Number(b) || 0), 0);
    const empty = !labels?.length || !data?.length || total === 0;
    setEmptyState(canvasId, empty);
    if (empty) {
        destroyChart(key);
        return;
    }

    const ctx = qs(canvasId)?.getContext('2d');
    if (!ctx) return;

    if (charts[key]) {
        charts[key].data.labels = labels;
        charts[key].data.datasets[0].data = data;
        charts[key].update();
        return;
    }

    charts[key] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderColor: 'rgba(255,255,255,0.9)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const v = ctx.parsed;
                            return `${ctx.label}: ${formatNumber(v)}`;
                        }
                    }
                }
            },
            cutout: '62%'
        }
    });
}

function updateBarChart(key, canvasId, labels, data, horizontal) {
    const total = (data || []).reduce((a, b) => a + (Number(b) || 0), 0);
    const empty = !labels?.length || !data?.length || total === 0;
    setEmptyState(canvasId, empty);
    if (empty) {
        destroyChart(key);
        return;
    }

    const ctx = qs(canvasId)?.getContext('2d');
    if (!ctx) return;

    const colorByKey = {
        top_bidang: 'rgba(0, 74, 135, 0.85)',
        top_wilayah: 'rgba(0, 74, 135, 0.85)',
        top_kampus: 'rgba(0, 74, 135, 0.85)',
        masa_tunggu: 'rgba(253, 184, 19, 0.92)',
        studi_jenjang: 'rgba(124, 58, 237, 0.80)'
    };

    const safeLabels = normalizeLabels(labels);
    const config = {
        type: 'bar',
        data: {
            labels: safeLabels,
            datasets: [{
                label: '',
                data,
                backgroundColor: colorByKey[key] || 'rgba(0, 74, 135, 0.85)',
                borderRadius: 10,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    ticks: { color: '#334155', font: { weight: '700' } },
                    grid: { color: 'rgba(0, 74, 135, 0.06)' }
                },
                y: {
                    ticks: { color: '#334155', font: { weight: '700' } },
                    grid: { color: 'rgba(0, 74, 135, 0.06)' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx2) {
                            return `${formatNumber(ctx2.parsed[horizontal ? 'x' : 'y'] ?? ctx2.parsed)} alumni`;
                        }
                    }
                }
            }
        }
    };

    if (charts[key]) {
        charts[key].data.labels = labels;
        charts[key].data.datasets[0].data = data;
        charts[key].options.indexAxis = horizontal ? 'y' : 'x';
        charts[key].update();
        return;
    }

    charts[key] = new Chart(ctx, config);
}

function updateLineChart(key, canvasId, labels, datasets) {
    const allData = (datasets || []).flatMap(ds => ds.data || []);
    const total = allData.reduce((a, b) => a + (Number(b) || 0), 0);
    const empty = !labels?.length || total === 0;
    setEmptyState(canvasId, empty);
    if (empty) {
        destroyChart(key);
        return;
    }

    const ctx = qs(canvasId)?.getContext('2d');
    if (!ctx) return;

    const safeLabels = normalizeLabels(labels);
    const config = {
        type: 'line',
        data: { labels: safeLabels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11, weight: '700' } }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(0, 74, 135, 0.06)' }, ticks: { font: { weight: '700' } } },
                y: { grid: { color: 'rgba(0, 74, 135, 0.06)' }, beginAtZero: true, ticks: { font: { weight: '700' } } }
            }
        }
    };

    if (charts[key]) {
        charts[key].data.labels = labels;
        charts[key].data.datasets = datasets;
        charts[key].update();
        return;
    }

    charts[key] = new Chart(ctx, config);
}

function updateStackedBarChart(key, canvasId, labels, datasets) {
    const safeLabels = normalizeLabels(labels);
    const allData = (datasets || []).flatMap(ds => ds.data || []);
    const total = allData.reduce((a, b) => a + (Number(b) || 0), 0);
    const empty = !safeLabels?.length || total === 0;
    setEmptyState(canvasId, empty);
    if (empty) {
        destroyChart(key);
        return;
    }

    const ctx = qs(canvasId)?.getContext('2d');
    if (!ctx) return;

    const config = {
        type: 'bar',
        data: {
            labels: safeLabels,
            datasets: (datasets || []).map((ds) => ({
                ...ds,
                borderRadius: 10,
                borderSkipped: false
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 11, weight: '700' } }
                }
            },
            scales: {
                x: { stacked: true, grid: { color: 'rgba(0, 74, 135, 0.06)' }, ticks: { font: { weight: '700' } } },
                y: { stacked: true, grid: { color: 'rgba(0, 74, 135, 0.06)' }, beginAtZero: true, ticks: { font: { weight: '700' } } }
            }
        }
    };

    if (charts[key]) {
        charts[key].data.labels = safeLabels;
        charts[key].data.datasets = config.data.datasets;
        charts[key].update();
        return;
    }

    charts[key] = new Chart(ctx, config);
}

async function fetchStatistik(params) {
    const endpoint = window.__STATISTIK_ENDPOINT__ || '';
    const url = endpoint + (buildQuery(params) ? `?${buildQuery(params)}` : '');

    setLoading(true);
    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return await res.json();
    } finally {
        setLoading(false);
    }
}

function applyData(payload) {
    const k = payload?.kpis || {};

    setKpi('kpi-total', formatNumber(k.total_alumni ?? 0));
    setKpi('kpi-bekerja', formatNumber(k.bekerja ?? 0));
    setKpi('kpi-belum', formatNumber(k.belum_bekerja ?? 0));
    setKpi('kpi-studi', formatNumber(k.studi_lanjut ?? 0));
    setKpi('kpi-multi', formatNumber(k.multi_job ?? 0));
    setKpi('kpi-masatunggu', k.rata_masa_tunggu === null ? '-' : formatDecimal(k.rata_masa_tunggu, 1));

    const total = Number(k.total_alumni ?? 0) || 0;
    const bekerja = Number(k.bekerja ?? 0) || 0;
    const pctBekerja = total > 0 ? Math.round((bekerja / total) * 100) : null;
    setText('kpi-bekerja-sub', pctBekerja === null ? '' : `${pctBekerja}% dari total alumni`);

    const c = payload?.charts || {};

    {
        const labels = normalizeLabels(c.status?.labels || []);
        updateDoughnutChart('status', 'chart-status', labels, c.status?.data || [], colorsForStatus(labels));
    }

    {
        const labels = normalizeLabels(c.linearitas?.labels || []);
        updateDoughnutChart('linearitas', 'chart-linearitas', labels, c.linearitas?.data || [], colorsForLinearitas(labels));
    }

    updateBarChart('top_bidang', 'chart-top-bidang', c.top_bidang?.labels || [], c.top_bidang?.data || [], true);
    updateBarChart('top_wilayah', 'chart-top-wilayah', c.top_wilayah?.labels || [], c.top_wilayah?.data || [], true);
    updateBarChart('masa_tunggu', 'chart-masa-tunggu', c.masa_tunggu?.labels || [], c.masa_tunggu?.data || [], false);

    updateBarChart('studi_jenjang', 'chart-studi-jenjang', c.studi_jenjang?.labels || [], c.studi_jenjang?.data || [], false);
    updateBarChart('top_kampus', 'chart-top-kampus', c.top_kampus?.labels || [], c.top_kampus?.data || [], true);

    updateLineChart('tren_angkatan', 'chart-tren-angkatan', c.tren_angkatan?.labels || [], [
        {
            label: 'Total Alumni',
            data: c.tren_angkatan?.total || [],
            borderColor: '#004a87',
            backgroundColor: 'rgba(0, 74, 135, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3
        }
    ]);

    updateStackedBarChart('tren_serap', 'chart-tren-serap', c.tren_angkatan?.labels || [], [
        { label: 'Bekerja', data: c.tren_angkatan?.bekerja || [], backgroundColor: 'rgba(37, 99, 235, 0.85)' },
        { label: 'Belum Bekerja', data: c.tren_angkatan?.belum_bekerja || [], backgroundColor: 'rgba(220, 38, 38, 0.85)' }
    ]);

    renderInsights(payload);
}

function findTop(labels, data) {
    const arrLabels = Array.isArray(labels) ? labels : [];
    const arrData = Array.isArray(data) ? data : [];
    if (!arrLabels.length || !arrData.length) return null;
    let bestIdx = -1;
    let bestVal = -Infinity;
    for (let i = 0; i < Math.min(arrLabels.length, arrData.length); i++) {
        const v = Number(arrData[i]);
        if (!Number.isFinite(v)) continue;
        if (v > bestVal) {
            bestVal = v;
            bestIdx = i;
        }
    }
    if (bestIdx < 0 || bestVal <= 0) return null;
    return { label: String(arrLabels[bestIdx] ?? '').trim(), value: bestVal };
}

function renderInsights(payload) {
    const wrap = qs('stat-insight');
    const list = qs('stat-insight-list');
    if (!wrap || !list) return;

    const k = payload?.kpis || {};
    const c = payload?.charts || {};
    const insights = [];

    const total = Number(k.total_alumni ?? 0) || 0;
    const bekerja = Number(k.bekerja ?? 0) || 0;
    const belum = Number(k.belum_bekerja ?? 0) || 0;
    const studi = Number(k.studi_lanjut ?? 0) || 0;

    if (total > 0) {
        const pct = Math.round((bekerja / total) * 100);
        if (pct >= 50) insights.push('Mayoritas alumni sudah bekerja.');
        else if (Math.round((belum / total) * 100) >= 50) insights.push('Mayoritas alumni belum bekerja.');
        else if (Math.round((studi / total) * 100) >= 50) insights.push('Mayoritas alumni sedang studi lanjut.');
    }

    const topBidang = findTop(c.top_bidang?.labels, c.top_bidang?.data);
    if (topBidang?.label) insights.push(`Bidang pekerjaan terbanyak adalah ${topBidang.label}.`);

    const topWilayah = findTop(c.top_wilayah?.labels, c.top_wilayah?.data);
    if (topWilayah?.label) insights.push(`Wilayah kerja terbanyak adalah ${topWilayah.label}.`);

    const masa = Number(k.rata_masa_tunggu);
    if (Number.isFinite(masa)) insights.push(`Rata-rata masa tunggu kerja sekitar ${formatDecimal(masa, 1)} bulan.`);

    list.innerHTML = '';
    const finalInsights = insights.filter(Boolean).slice(0, 4);
    if (!finalInsights.length) {
        wrap.hidden = true;
        return;
    }

    finalInsights.forEach((text) => {
        const li = document.createElement('li');
        li.textContent = text;
        list.appendChild(li);
    });
    wrap.hidden = false;
}

async function refresh() {
    const params = getFiltersFromUi();
    const payload = await fetchStatistik(params);
    applyData(payload);
}

function resetFilters() {
    const ids = [
        'stat-filter-angkatan',
        'stat-filter-tahun-lulus',
        'stat-filter-jenis-kelamin',
        'stat-filter-status-alumni',
        'stat-filter-bidang',
        'stat-filter-wilayah'
    ];
    ids.forEach((id) => {
        const el = qs(id);
        if (el) el.value = '';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    applyChartDefaults();
    initFilterToggle();
    qs('stat-apply')?.addEventListener('click', refresh);
    qs('stat-reset')?.addEventListener('click', async function () {
        resetFilters();
        await refresh();
    });

    refresh().catch(() => {
        // Silent: empty state handled by UI. Errors will show in console.
    });
});
