/* global Chart */

function qs(id) {
    return document.getElementById(id);
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
    const empty = document.querySelector(`.stat-empty[data-empty-for="${canvasId}"]`);
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
                        label: function (ctx2) {
                            const v = ctx2.parsed;
                            return `${ctx2.label}: ${formatNumber(v)}`;
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

    const config = {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: '',
                data,
                backgroundColor: 'rgba(0, 74, 135, 0.85)',
                borderRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            scales: {
                x: { grid: { color: 'rgba(0, 74, 135, 0.06)' }, beginAtZero: true },
                y: { grid: { color: 'rgba(0, 74, 135, 0.06)' } }
            },
            plugins: {
                legend: { display: false }
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

    const config = {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                x: { grid: { color: 'rgba(0, 74, 135, 0.06)' } },
                y: { grid: { color: 'rgba(0, 74, 135, 0.06)' }, beginAtZero: true }
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

async function fetchStatistik(params) {
    const endpoint = window.__STATISTIK_ENDPOINT__ || '';
    const qsText = buildQuery(params);
    const url = endpoint + (qsText ? `?${qsText}` : '');

    setLoading(true);
    try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
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

    const c = payload?.charts || {};
    updateDoughnutChart('status', 'chart-status', c.status?.labels || [], c.status?.data || [], ['#004a87', '#ef4444', '#7c3aed', '#fdb813']);
    updateDoughnutChart('linearitas', 'chart-linearitas', c.linearitas?.labels || [], c.linearitas?.data || [], ['#16a34a', '#22c55e', '#0ea5e9', '#f59e0b', '#ef4444', '#94a3b8']);

    updateBarChart('top_bidang', 'chart-top-bidang', c.top_bidang?.labels || [], c.top_bidang?.data || [], true);
    updateBarChart('top_wilayah', 'chart-top-wilayah', c.top_wilayah?.labels || [], c.top_wilayah?.data || [], true);
    updateBarChart('masa_tunggu', 'chart-masa-tunggu', c.masa_tunggu?.labels || [], c.masa_tunggu?.data || [], false);
    updateBarChart('studi_jenjang', 'chart-studi-jenjang', c.studi_jenjang?.labels || [], c.studi_jenjang?.data || [], false);
    updateBarChart('top_kampus', 'chart-top-kampus', c.top_kampus?.labels || [], c.top_kampus?.data || [], true);

    updateLineChart('tren_angkatan', 'chart-tren-angkatan', c.tren_angkatan?.labels || [], [
        { label: 'Total Alumni', data: c.tren_angkatan?.total || [], borderColor: '#004a87', backgroundColor: 'rgba(0, 74, 135, 0.12)', fill: true, tension: 0.35, pointRadius: 3 }
    ]);

    updateLineChart('tren_serap', 'chart-tren-serap', c.tren_angkatan?.labels || [], [
        { label: 'Bekerja', data: c.tren_angkatan?.bekerja || [], borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, 0.10)', fill: true, tension: 0.35, pointRadius: 3 },
        { label: 'Belum Bekerja', data: c.tren_angkatan?.belum_bekerja || [], borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.10)', fill: true, tension: 0.35, pointRadius: 3 }
    ]);
}

function resetFilters() {
    ['stat-filter-angkatan', 'stat-filter-tahun-lulus', 'stat-filter-jenis-kelamin', 'stat-filter-status-alumni', 'stat-filter-bidang', 'stat-filter-wilayah']
        .forEach((id) => { const el = qs(id); if (el) el.value = ''; });
}

async function refresh() {
    const payload = await fetchStatistik(getFiltersFromUi());
    applyData(payload);
}

document.addEventListener('DOMContentLoaded', function () {
    qs('stat-apply')?.addEventListener('click', refresh);
    qs('stat-reset')?.addEventListener('click', async function () {
        resetFilters();
        await refresh();
    });
    refresh().catch(() => {});
});

