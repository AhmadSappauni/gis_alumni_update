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

function getDataModeFromUi() {
    const v = qs('stat-filter-data-mode')?.value || 'valid';
    return String(v || 'valid').toLowerCase() === 'all' ? 'all' : 'valid';
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

// Mode tampilan data statistik (global, frontend-only)
// Default: "Hanya data valid" => unknown disembunyikan.
let __showUnknownStats = false;

function isUnknownLabel(label) {
    if (label === null || label === undefined) return true;
    let s = String(label).trim().toLowerCase();
    if (!s) return true;

    s = s.replace(/\s+/g, ' ');
    s = s.replace(/[–—]/g, '-');

    if (s === '-' || s === 'null' || s === 'n/a' || s === 'na' || s === 'none') return true;
    if (s === 'unknown' || s === 'tidak diketahui') return true;
    if (s.includes('tidak diketahui')) return true;
    if (s.includes('belum diketahui')) return true;
    if (s.includes('belum diisi')) return true;
    if (s.includes('kosong')) return true;
    return false;
}

function filterUnknownCategory(labels, data) {
    const arrLabels = Array.isArray(labels) ? labels : [];
    const arrData = Array.isArray(data) ? data : [];
    const len = Math.min(arrLabels.length, arrData.length);

    if (__showUnknownStats) {
        return { labels: arrLabels.slice(0, len), data: arrData.slice(0, len) };
    }

    const outLabels = [];
    const outData = [];
    for (let i = 0; i < len; i++) {
        const l = arrLabels[i];
        if (isUnknownLabel(l)) continue;
        outLabels.push(l);
        outData.push(arrData[i]);
    }
    return { labels: outLabels, data: outData };
}

function colorsForStatus(labels) {
    const key = (s) => String(s || '').toLowerCase().replace(/\s+/g, '_');
    return (labels || []).map((l) => {
        const k = key(l);
        if (k.includes('belum')) return '#dc2626';
        if (k === 'bekerja' || k.includes('bekerja')) return '#2563eb';
        if (k.includes('studi')) return '#7c3aed';
        if (k.includes('wirausaha')) return '#f59e0b';
        if (k.includes('tidak') || k.includes('unknown')) return '#94a3b8';
        return '#005a9c';
    });
}

function colorsForGender(labels) {
    const key = (s) => String(s || '').toLowerCase();
    return (labels || []).map((l) => {
        const k = key(l);
        if (k.includes('laki')) return '#2563eb';
        if (k.includes('perem')) return '#ec4899';
        if (k.includes('tidak') || k.includes('unknown')) return '#94a3b8';
        return '#005a9c';
    });
}

function filterLabelsData(labels, data, predicate) {
    const outLabels = [];
    const outData = [];
    const arrLabels = Array.isArray(labels) ? labels : [];
    const arrData = Array.isArray(data) ? data : [];
    const len = Math.min(arrLabels.length, arrData.length);

    for (let i = 0; i < len; i++) {
        const l = arrLabels[i];
        const d = arrData[i];
        if (predicate(l, d, i) === false) continue;
        outLabels.push(l);
        outData.push(d);
    }

    return { labels: outLabels, data: outData };
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
    Chart.defaults.animation = false;
    if (Chart.defaults.transitions?.active?.animation) {
        Chart.defaults.transitions.active.animation.duration = 0;
    }
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

function safeNumber(value) {
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
}

function toNumberArray(values) {
    return (Array.isArray(values) ? values : []).map(safeNumber);
}

function safeRenderChart(name, callback) {
    try {
        callback();
    } catch (error) {
        console.error(`Gagal render chart: ${name}`, error);
    }
}

function setEmptyState(canvasId, isEmpty) {
    const canvas = qs(canvasId);
    const empty = document.querySelector(`.chart-empty[data-empty-for="${canvasId}"]`);
    if (canvas) canvas.style.display = isEmpty ? 'none' : 'block';
    if (empty) empty.hidden = !isEmpty;
}

const charts = {};
let __lastPayload = null;
let __lazyObserver = null;
const __lazyRendered = new Set();
const __lazyCallbacks = new Map();

function ensureLazyObserver() {
    if (__lazyObserver || typeof IntersectionObserver === 'undefined') return;
    __lazyObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const id = entry.target?.id;
            if (!id) return;
            const cb = __lazyCallbacks.get(id);
            if (cb) {
                __lazyRendered.add(id);
                try { cb(); } catch (_) { }
                __lazyCallbacks.delete(id);
            }
            try { __lazyObserver.unobserve(entry.target); } catch (_) { }
        });
    }, { rootMargin: '200px' });
}

function observeAndRender(elementId, renderCallback) {
    const el = qs(elementId);
    if (!el) return;
    if (__lazyRendered.has(elementId)) {
        renderCallback();
        return;
    }
    ensureLazyObserver();
    if (!__lazyObserver) {
        renderCallback();
        __lazyRendered.add(elementId);
        return;
    }
    __lazyCallbacks.set(elementId, renderCallback);
    __lazyObserver.observe(el);
}

function isWilayahSortEnabled() {
    return qs('stat-wilayah-sort')?.checked === true;
}

function sortLabelsData(labels, data, byCountDesc) {
    const pairs = [];
    const arrLabels = Array.isArray(labels) ? labels : [];
    const arrData = Array.isArray(data) ? data : [];
    const len = Math.min(arrLabels.length, arrData.length);

    for (let i = 0; i < len; i++) {
        pairs.push({ label: arrLabels[i], value: Number(arrData[i]) || 0 });
    }

    if (byCountDesc) {
        pairs.sort((a, b) => b.value - a.value || String(a.label).localeCompare(String(b.label), 'id'));
    } else {
        pairs.sort((a, b) => String(a.label).localeCompare(String(b.label), 'id') || (b.value - a.value));
    }

    return {
        labels: pairs.map((p) => p.label),
        data: pairs.map((p) => p.value)
    };
}

function colorsForWilayahBars(count) {
    const n = Math.max(0, Number(count) || 0);
    const colors = [];
    const baseHue = 206;
    const step = 360 / Math.max(12, n);
    for (let i = 0; i < n; i++) {
        const hue = Math.round((baseHue + (i * step)) % 360);
        const sat = 78;
        const light = 44 + (i % 2) * 6;
        colors.push(`hsla(${hue}, ${sat}%, ${light}%, 0.88)`);
    }
    return colors;
}

const valueLabelPlugin = {
    id: 'valueLabelPlugin',
    afterDatasetsDraw(chart, _args, pluginOptions) {
        if (pluginOptions?.enabled === false) return;
        const ctx = chart?.ctx;
        if (!ctx) return;
        const meta = chart.getDatasetMeta(0);
        if (!meta || meta.hidden) return;
        const data = chart.data?.datasets?.[0]?.data || [];

        ctx.save();
        ctx.font = '800 12px Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
        ctx.fillStyle = '#0f172a';
        ctx.textBaseline = 'middle';

        meta.data.forEach((bar, i) => {
            const v = Number(data[i]);
            if (!Number.isFinite(v)) return;
            const label = formatNumber(v);

            const x = bar.x;
            const y = bar.y;
            const textWidth = ctx.measureText(label).width;
            const padding = 8;
            const rightEdge = chart.chartArea?.right ?? x;
            const drawInside = x + textWidth + padding > rightEdge;
            const tx = drawInside ? Math.max((bar.base ?? 0) + padding, x - textWidth - padding) : x + padding;

            ctx.fillText(label, tx, y);
        });

        ctx.restore();
    }
};

function destroyChart(key) {
    if (charts[key]) {
        try { charts[key].destroy(); } catch (_) { }
        delete charts[key];
    }
}

function updateDoughnutChart(key, canvasId, labels, data, colors, opts) {
    data = toNumberArray(data);
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
            normalized: true,
            plugins: {
                valueLabelPlugin: { enabled: false },
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, usePointStyle: true, pointStyle: 'circle' }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const v = ctx.parsed;
                            const showPct = opts?.showPercent === true;
                            const denom = Number(opts?.percentDenominator ?? total) || total;
                            const pct = showPct && denom > 0 ? ` (${Math.round((v / denom) * 100)}%)` : '';
                            return `${ctx.label}: ${formatNumber(v)}${pct}`;
                        }
                    }
                }
            },
            cutout: '62%'
        }
    });
}

function updateBarChart(key, canvasId, labels, data, horizontal) {
    data = toNumberArray(data);
    if (key === 'top_wilayah') {
        const sorted = sortLabelsData(labels, data, isWilayahSortEnabled());
        labels = sorted.labels;
        data = sorted.data;

        const wrap = qs(canvasId)?.closest('.chart-wilayah-chart-wrap');
        if (wrap) {
            const count = Array.isArray(labels) ? labels.length : 0;
            const computed = Math.max(320, 80 + (count * 28));
            wrap.style.height = `${Math.min(computed, 900)}px`;
            wrap.style.overflowY = computed > 520 ? 'auto' : 'hidden';
        }
    }

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
        studi_jenjang: 'rgba(124, 58, 237, 0.80)',
        toefl_dist: 'rgba(2, 132, 199, 0.86)',
        salary_distribution: 'rgba(16, 185, 129, 0.82)',
        top_company: 'rgba(0, 74, 135, 0.85)'
    };

    const safeLabels = normalizeLabels(labels);
    const wilayahColors = key === 'top_wilayah'
        ? colorsForWilayahBars(safeLabels.length)
        : null;

    const enableValueLabels = key === 'top_wilayah' || key === 'top_company';
    const isCompany = key === 'top_company';
    const isTopBidang = key === 'top_bidang';
    const baseBekerja = Number(__lastPayload?.charts?.top_company?.base?.bekerja ?? 0) || 0;
    const truncate = (s, max) => {
        const t = String(s ?? '');
        if (!max || t.length <= max) return t;
        return t.slice(0, Math.max(0, max - 1)) + '…';
    };

    const config = {
        type: 'bar',
        data: {
            labels: safeLabels,
            datasets: [{
                label: '',
                data,
                backgroundColor: wilayahColors || colorByKey[key] || 'rgba(0, 74, 135, 0.85)',
                borderRadius: 10,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            normalized: true,
            indexAxis: horizontal ? 'y' : 'x',
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    ticks: {
                        color: '#334155',
                        font: { weight: '700' },
                        ...(isCompany ? { stepSize: 1, precision: 0 } : {})
                    },
                    grid: { color: 'rgba(0, 74, 135, 0.06)' },
                    beginAtZero: true,
                    title: key === 'top_wilayah'
                        ? { display: true, text: 'Jumlah Alumni Bekerja', font: { weight: '900' } }
                        : { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#334155',
                        font: { weight: '700' },
                        precision: 0,
                        callback: horizontal ? function (value) {
                            const idx = typeof value === 'number' ? value : Number(value);
                            const label = safeLabels?.[idx] ?? value;
                            return truncate(label, isCompany ? 28 : 24);
                        } : undefined
                    },
                    grid: { color: 'rgba(0, 74, 135, 0.06)' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: key === 'top_wilayah' || isCompany || isTopBidang
                    ? {
                        callbacks: {
                            title: (items) => {
                                const item = items?.[0];
                                const rawLabel = item?.label;
                                if (!horizontal) return rawLabel || '';
                                const idx = typeof rawLabel === 'number' ? rawLabel : Number(rawLabel);
                                return safeLabels?.[idx] ?? rawLabel ?? '';
                            },
                            label: (item) => {
                                const count = Number(item?.raw ?? 0) || 0;
                                if (isTopBidang) return `${formatNumber(count)} alumni`;
                                if (!isCompany) return `Jumlah: ${formatNumber(count)}`;
                                const pct = baseBekerja > 0 ? ` (${Math.round((count / baseBekerja) * 100)}%)` : '';
                                return `Jumlah: ${formatNumber(count)}${pct}`;
                            }
                        }
                    }
                    : {
                        callbacks: {
                            label: function (ctx2) {
                                return `${formatNumber(ctx2.parsed[horizontal ? 'x' : 'y'] ?? ctx2.parsed)} alumni`;
                            }
                        }
                    },
                valueLabelPlugin: enableValueLabels ? { enabled: true } : { enabled: false }
            }
        }
    };

    if (charts[key]) {
        charts[key].data.labels = safeLabels;
        charts[key].data.datasets[0].data = data;
        charts[key].options.indexAxis = horizontal ? 'y' : 'x';
        charts[key].resize();
        if (key === 'top_wilayah') {
            charts[key].data.datasets[0].backgroundColor = wilayahColors;
            charts[key].options.scales.x.title = { display: true, text: 'Jumlah Alumni Bekerja', font: { weight: '900' } };
            charts[key].options.plugins.tooltip = config.options.plugins.tooltip;
            charts[key].options.plugins.valueLabelPlugin = { enabled: true };
        } else if (enableValueLabels) {
            charts[key].options.plugins.tooltip = config.options.plugins.tooltip;
            charts[key].options.plugins.valueLabelPlugin = { enabled: true };
        }
        charts[key].update();
        return;
    }

    charts[key] = new Chart(ctx, config);
    try { charts[key].resize(); } catch (_) {}
}

function updateLineChart(key, canvasId, labels, datasets) {
    datasets = (datasets || []).map((ds) => ({ ...ds, data: toNumberArray(ds.data) }));
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
            normalized: true,
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
    datasets = (datasets || []).map((ds) => ({ ...ds, data: toNumberArray(ds.data) }));
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
            normalized: true,
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
    __lastPayload = payload;
    const k = payload?.kpis || {};

    setKpi('kpi-total', formatNumber(k.total_alumni ?? 0));
    setKpi('kpi-bekerja', formatNumber(k.bekerja ?? 0));
    setKpi('kpi-belum', formatNumber(k.belum_bekerja ?? 0));
    setKpi('kpi-studi', formatNumber(k.studi_lanjut ?? 0));
    setKpi('kpi-multi', formatNumber(k.multi_job ?? 0));
    setKpi('kpi-masatunggu', k.rata_masa_tunggu === null ? '-' : formatDecimal(k.rata_masa_tunggu, 1));

    const toeflValid = Number(k.toefl_valid_count ?? 0) || 0;
    const avgToefl = Number(k.rata_toefl);
    if (toeflValid > 0 && Number.isFinite(avgToefl)) {
        setKpi('kpi-toefl', formatNumber(Math.round(avgToefl)));
        setText('kpi-toefl-sub', 'Berdasarkan alumni dengan data TOEFL');
        setText('kpi-toefl-meta', `Data tersedia: ${formatNumber(toeflValid)} alumni`);
        const metaEl = qs('kpi-toefl-meta');
        if (metaEl) metaEl.style.display = '';
    } else {
        setKpi('kpi-toefl', '-');
        setText('kpi-toefl-sub', 'Data TOEFL belum tersedia');
        const metaEl = qs('kpi-toefl-meta');
        if (metaEl) metaEl.style.display = 'none';
    }

    const total = Number(k.total_alumni ?? 0) || 0;
    const bekerja = Number(k.bekerja ?? 0) || 0;
    const pctBekerja = total > 0 ? Math.round((bekerja / total) * 100) : null;
    setText('kpi-bekerja-sub', pctBekerja === null ? '' : `${pctBekerja}% dari total alumni`);

    const c = payload?.charts || {};

    safeRenderChart('Status Alumni', () => {
        const labels = normalizeLabels(c.status?.labels || []);
        const filteredNoWirausaha = filterLabelsData(labels, c.status?.data || [], (label) => !String(label || '').toLowerCase().includes('wirausaha'));
        const filtered = filterUnknownCategory(filteredNoWirausaha.labels, filteredNoWirausaha.data);
        updateDoughnutChart('status', 'chart-status', filtered.labels, filtered.data, colorsForStatus(filtered.labels), { showPercent: false });
    });

    safeRenderChart('Jenis Kelamin', () => {
        const labels = normalizeLabels(c.gender?.labels || []);
        if (qs('chart-gender')) {
            const filtered = filterUnknownCategory(labels, c.gender?.data || []);
            updateDoughnutChart('gender', 'chart-gender', filtered.labels, filtered.data, colorsForGender(filtered.labels), { showPercent: true });
        }
    });

    safeRenderChart('Linearitas', () => {
        const labels = normalizeLabels(c.linearitas?.labels || []);
        const filtered = filterUnknownCategory(labels, c.linearitas?.data || []);
        updateDoughnutChart('linearitas', 'chart-linearitas', filtered.labels, filtered.data, colorsForLinearitas(filtered.labels), { showPercent: false });
    });

    // Render langsung: dataset kecil (5 kategori), tidak perlu lazy.
    if (qs('chart-toefl-dist')) {
        const toeflKeys = ['< 400', '400-449', '450-499', '>= 500', 'Tidak diketahui'];
        const toeflLabels = ['< 400', '400–449', '450–499', '≥ 500', 'Tidak diketahui'];
        const dist = c.toefl_dist?.distribution || {};
        const values = toeflKeys.map((k2) => safeNumber(dist?.[k2] ?? 0));

        // Debug sementara (hapus kalau sudah stabil)
        // console.log('TOEFL distribution raw:', dist);
        // console.log('TOEFL chart values:', values);
        // console.log('TOEFL valid count:', c.toefl_dist?.valid_count);

        const filtered = filterUnknownCategory(toeflLabels, values);
        safeRenderChart('Distribusi TOEFL', () => updateBarChart('toefl_dist', 'chart-toefl-dist', filtered.labels, filtered.data, false));
        const foot = qs('toefl-dist-footnote');
        if (foot) {
            if ((Number(c.toefl_dist?.valid_count ?? 0) || 0) > 0) {
                foot.textContent = `Data tersedia: ${formatNumber(c.toefl_dist?.valid_count ?? 0)} alumni`;
                foot.style.display = '';
            } else {
                foot.style.display = 'none';
            }
        }
    }

    if (qs('chart-salary-dist')) {
        const defaultLabels = ['< Rp1 juta', 'Rp1-3 juta', 'Rp3-5 juta', 'Rp5-10 juta', '> Rp10 juta', 'Tidak diketahui'];
        const sd = c.salary_distribution || {};
        const labels = Array.isArray(sd.labels) && sd.labels.length ? sd.labels : defaultLabels;
        const data = Array.isArray(sd.data) ? sd.data : [];
        const filtered = filterUnknownCategory(labels, data);
        safeRenderChart('Distribusi Rentang Gaji', () => updateBarChart('salary_distribution', 'chart-salary-dist', filtered.labels, filtered.data, false));

        const foot = qs('salary-dist-footnote');
        if (foot) {
            const valid = Number(sd.total_valid ?? 0) || 0;
            const unknown = Number(sd.total_unknown ?? 0) || 0;
            const total = valid + unknown;
            if (total > 0) {
                foot.textContent = `Data gaji valid: ${formatNumber(valid)} alumni • Tidak diketahui: ${formatNumber(unknown)} alumni`;
                foot.style.display = '';
            } else {
                foot.style.display = 'none';
            }
        }
    }

    safeRenderChart('Top Bidang', () => {
        const filtered = filterUnknownCategory(c.top_bidang?.labels || [], c.top_bidang?.data || []);
        updateBarChart('top_bidang', 'chart-top-bidang', filtered.labels, filtered.data, true);
    });
    safeRenderChart('Top Wilayah', () => {
        const filtered = filterUnknownCategory(c.top_wilayah?.labels || [], c.top_wilayah?.data || []);
        updateBarChart('top_wilayah', 'chart-top-wilayah', filtered.labels, filtered.data, true);
    });
    safeRenderChart('Masa Tunggu', () => {
        const filtered = filterUnknownCategory(c.masa_tunggu?.labels || [], c.masa_tunggu?.data || []);
        updateBarChart('masa_tunggu', 'chart-masa-tunggu', filtered.labels, filtered.data, false);
    });

    safeRenderChart('Jenjang Studi', () => {
        const filtered = filterUnknownCategory(c.studi_jenjang?.labels || [], c.studi_jenjang?.data || []);
        updateBarChart('studi_jenjang', 'chart-studi-jenjang', filtered.labels, filtered.data, false);
    });
    safeRenderChart('Top Kampus', () => {
        const filtered = filterUnknownCategory(c.top_kampus?.labels || [], c.top_kampus?.data || []);
        updateBarChart('top_kampus', 'chart-top-kampus', filtered.labels, filtered.data, true);
    });
    safeRenderChart('Top Instansi', () => {
        const filtered = filterUnknownCategory(c.top_company?.labels || [], c.top_company?.data || []);
        updateBarChart('top_company', 'chart-top-company', filtered.labels, filtered.data, true);
    });

    safeRenderChart('Tren Angkatan', () => updateLineChart('tren_angkatan', 'chart-tren-angkatan', c.tren_angkatan?.labels || [], [
        {
            label: 'Total Alumni',
            data: c.tren_angkatan?.total || [],
            borderColor: '#004a87',
            backgroundColor: 'rgba(0, 74, 135, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3
        }
    ]));

    safeRenderChart('Tren Keterserapan', () => updateStackedBarChart('tren_serap', 'chart-tren-serap', c.tren_angkatan?.labels || [], [
        { label: 'Bekerja', data: c.tren_angkatan?.bekerja || [], backgroundColor: 'rgba(37, 99, 235, 0.85)' },
        { label: 'Belum Bekerja', data: c.tren_angkatan?.belum_bekerja || [], backgroundColor: 'rgba(220, 38, 38, 0.85)' }
    ]));

    renderInsights(payload);

    // Heatmap bisa dinonaktifkan di Blade (jika container tidak ada, jangan jalankan apa pun).
    const heatEl = qs('heatmap-map');
    if (heatEl) {
        observeAndRender('heatmap-map', () => safeRenderChart('Heatmap', () => updateHeatmap(payload)));
    }
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

let __heatMap = null;
let __heatLayer = null;
let __heatMode = 'domisili';
let __kalselPolygonLayer = null;
let __kalselPolygonLoading = false;
let __heatDebugLayer = null;
let __heatFitDone = false;
let __heatLastModeKey = null;

// Diagnostic logging (enable from console): window.DEBUG_STAT_HEATMAP = true
if (typeof window.DEBUG_STAT_HEATMAP === 'undefined') {
    window.DEBUG_STAT_HEATMAP = false;
}
function heatDebug(...args) {
    if (window.DEBUG_STAT_HEATMAP === true) {
        console.log('[STAT-HEATMAP]', ...args);
    }
}
function heatWarn(...args) {
    if (window.DEBUG_STAT_HEATMAP === true) {
        console.warn('[STAT-HEATMAP]', ...args);
    }
}
function heatError(...args) {
    console.error('[STAT-HEATMAP]', ...args);
}

function getStatPolygonStyle() {
    return {
        color: '#cbd5e1',
        weight: 1,
        opacity: 0.9,
        fillColor: '#ffffff',
        fillOpacity: 0.15
    };
}

function normalizeHeatmapPoints(points, mode) {
    const source = Array.isArray(points) ? points : [];

    heatDebug(`${mode}: raw points length`, source.length);

    let invalidNumeric = 0;
    let invalidRange = 0;
    let swappedCount = 0;
    let zeroWeightCount = 0;

    const normalized = source.map((point, index) => {
        let lat = Number(point?.lat ?? point?.latitude ?? point?.[0]);
        let lng = Number(point?.lng ?? point?.longitude ?? point?.lon ?? point?.[1]);
        let weight = Number(point?.weight ?? point?.intensity ?? point?.[2] ?? 1);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            invalidNumeric++;
            if (window.DEBUG_STAT_HEATMAP === true && index < 10) {
                heatWarn(`${mode}: invalid numeric at index ${index}`, point, { lat, lng, weight });
            }
            return null;
        }

        // Deteksi lat/lng tertukar (Kalsel: lat ~ -5..-1, lng ~ 113..117.5)
        const looksSwapped = lat >= 95 && lat <= 130 && lng >= -10 && lng <= 10;
        if (looksSwapped) {
            const tmp = lat;
            lat = lng;
            lng = tmp;
            swappedCount++;
        }

        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            invalidRange++;
            if (window.DEBUG_STAT_HEATMAP === true && index < 10) {
                heatWarn(`${mode}: invalid range at index ${index}`, point, { lat, lng, weight });
            }
            return null;
        }

        if (!Number.isFinite(weight) || weight <= 0) {
            zeroWeightCount++;
            weight = 1;
        }

        return [lat, lng, weight];
    }).filter(Boolean);

    heatDebug(`${mode}: normalized length`, normalized.length);
    heatDebug(`${mode}: invalid numeric`, invalidNumeric);
    heatDebug(`${mode}: invalid range`, invalidRange);
    heatDebug(`${mode}: swapped lat/lng count`, swappedCount);
    heatDebug(`${mode}: zero/invalid weight fixed`, zeroWeightCount);
    heatDebug(`${mode}: first 10 normalized`, normalized.slice(0, 10));

    if (window.DEBUG_STAT_HEATMAP === true && normalized.length > 0) {
        const lats = normalized.map((p) => p[0]);
        const lngs = normalized.map((p) => p[1]);
        const weights = normalized.map((p) => p[2]);
        heatDebug(`${mode}: lat range`, Math.min(...lats), Math.max(...lats));
        heatDebug(`${mode}: lng range`, Math.min(...lngs), Math.max(...lngs));
        heatDebug(`${mode}: weight range`, Math.min(...weights), Math.max(...weights));
    }

    return normalized;
}

function ensureKalselPolygonLayer() {
    if (!__heatMap || __kalselPolygonLayer || __kalselPolygonLoading || typeof L === 'undefined') return;
    __kalselPolygonLoading = true;

    fetch('/data/data_kalsel.geojson')
        .then((r) => r.json())
        .then((geojson) => {
            if (!__heatMap) return;
            const style = getStatPolygonStyle();
            __kalselPolygonLayer = L.geoJSON(geojson, {
                style,
                interactive: false,
                pane: 'polygonPane'
            }).addTo(__heatMap);
            heatDebug('polygon loaded:', __kalselPolygonLayer);
            window.statPolygonLayer = __kalselPolygonLayer;

            try {
                if (typeof __kalselPolygonLayer.bringToBack === 'function') {
                    __kalselPolygonLayer.bringToBack();
                }
            } catch (_) {}

            try {
                const b = __kalselPolygonLayer.getBounds?.();
                if (b && b.isValid && b.isValid()) {
                    __heatMap.fitBounds(b, { padding: [12, 12] });
                }
            } catch (_) {}
        })
        .catch((e) => {
            console.error('Gagal load polygon Kalsel:', e);
        })
        .finally(() => {
            __kalselPolygonLoading = false;
        });
}

function setHeatEmpty(isEmpty) {
    const empty = document.querySelector('.chart-empty[data-empty-for="heatmap-map"]');
    if (empty) empty.hidden = !isEmpty;
}

function setHeatTab(mode) {
    __heatMode = mode;
    const btnDom = qs('heatmap-tab-domisili');
    const btnKerja = qs('heatmap-tab-kerja');
    if (btnDom && btnKerja) {
        const isDom = mode === 'domisili';
        btnDom.classList.toggle('btn-primary', isDom);
        btnDom.classList.toggle('btn-outline-primary', !isDom);
        btnKerja.classList.toggle('btn-primary', !isDom);
        btnKerja.classList.toggle('btn-outline-primary', isDom);
        btnDom.setAttribute('aria-selected', isDom ? 'true' : 'false');
        btnKerja.setAttribute('aria-selected', !isDom ? 'true' : 'false');
    }

    const sub = qs('heatmap-subtitle');
    if (sub) {
        sub.textContent = mode === 'domisili'
            ? 'Menampilkan kepadatan domisili alumni di Kalsel'
            : 'Menampilkan kepadatan lokasi kerja alumni di Kalsel';
    }
}

function buildHeatLayerPoints(points) {
    const arr = Array.isArray(points) ? points : [];
    return arr
        .map((p) => [Number(p?.lat), Number(p?.lng), Number(p?.weight ?? 1)])
        .filter((p) => Number.isFinite(p[0]) && Number.isFinite(p[1]) && Number.isFinite(p[2]));
}

function logHeatDebug(rawPoints, heatPoints, modeKey) {
    if (window.DEBUG_STAT_HEATMAP !== true) return;
    heatDebug('init/update started');
    heatDebug('Leaflet L available:', typeof L !== 'undefined');
    heatDebug('L.map available:', typeof L?.map);
    heatDebug('L.heatLayer available:', typeof L?.heatLayer);
    heatDebug('mode:', modeKey);
    heatDebug('raw sample:', rawPoints?.slice?.(0, 10));
    heatDebug('normalized sample:', heatPoints.slice(0, 10));

    if (heatPoints.length) {
        const lats = heatPoints.map((p) => p[0]);
        const lngs = heatPoints.map((p) => p[1]);
        const ws = heatPoints.map((p) => p[2]);
        heatDebug('lat range:', Math.min(...lats), Math.max(...lats));
        heatDebug('lng range:', Math.min(...lngs), Math.max(...lngs));
        heatDebug('weight range:', Math.min(...ws), Math.max(...ws));
    }
}

function initHeatmapUi() {
    const domBtn = qs('heatmap-tab-domisili');
    const kerjaBtn = qs('heatmap-tab-kerja');
    if (domBtn) domBtn.addEventListener('click', () => { setHeatTab('domisili'); updateHeatmap(__lastPayload); });
    if (kerjaBtn) kerjaBtn.addEventListener('click', () => { setHeatTab('lokasi_kerja'); updateHeatmap(__lastPayload); });
}

function updateHeatmap(payload) {
    const container = qs('heatmap-map');
    if (!container) return;
    if (typeof L === 'undefined' || !L?.map) return;

    if (window.DEBUG_STAT_HEATMAP === true) {
        heatDebug('Map container:', container);
    }

    if (typeof L?.heatLayer !== 'function') {
        heatError('leaflet.heat belum ter-load. L.heatLayer bukan function.');
        return;
    }

    const heat = payload?.heatmaps || {};
    const modeKey = __heatMode || 'domisili';
    const current = heat?.[modeKey] || {};
    const rawPoints = current?.points || [];
    const points = normalizeHeatmapPoints(rawPoints, modeKey);

    // expose raw data (untuk inspeksi console)
    try {
        window.domicileHeatmapPoints = heat?.domisili?.points || [];
        window.workHeatmapPoints = heat?.lokasi_kerja?.points || [];
        if (window.DEBUG_STAT_HEATMAP === true) {
            heatDebug('window.domicileHeatmapPoints raw length:', Array.isArray(window.domicileHeatmapPoints) ? window.domicileHeatmapPoints.length : 'not array');
            heatDebug('window.workHeatmapPoints raw length:', Array.isArray(window.workHeatmapPoints) ? window.workHeatmapPoints.length : 'not array');
            heatDebug('domicile first 10:', Array.isArray(window.domicileHeatmapPoints) ? window.domicileHeatmapPoints.slice(0, 10) : window.domicileHeatmapPoints);
            heatDebug('work first 10:', Array.isArray(window.workHeatmapPoints) ? window.workHeatmapPoints.slice(0, 10) : window.workHeatmapPoints);
        }
    } catch (_) {}

    logHeatDebug(rawPoints, points, modeKey);

    if (!__heatMap) {
        __heatMap = L.map('heatmap-map', { zoomControl: true }).setView([-3.316694, 114.590111], 8);
        window.statHeatmapMap = __heatMap;

        try {
            if (!__heatMap.getPane('polygonPane')) {
                __heatMap.createPane('polygonPane');
                __heatMap.getPane('polygonPane').style.zIndex = 350;
            }
            if (!__heatMap.getPane('heatPane')) {
                __heatMap.createPane('heatPane');
                __heatMap.getPane('heatPane').style.zIndex = 450;
            }
        } catch (_) { }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(__heatMap);

        ensureKalselPolygonLayer();
        initHeatmapUi();
        setHeatTab('domisili');
        setTimeout(() => { try { __heatMap?.invalidateSize(); } catch (_) {} }, 200);
    }

    ensureKalselPolygonLayer();

    if (!points.length || !L.heatLayer) {
        setHeatEmpty(true);
        if (__heatLayer) {
            try { __heatMap.removeLayer(__heatLayer); } catch (_) { }
            __heatLayer = null;
        }
    } else {
        setHeatEmpty(false);

        if (!__heatFitDone || __heatLastModeKey !== modeKey) {
            __heatFitDone = true;
            __heatLastModeKey = modeKey;
            try {
                const bounds = L.latLngBounds(points.map((p) => [p[0], p[1]]));
                if (bounds.isValid()) {
                    __heatMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 9 });
                }
            } catch (_) { }
        }

        if (__heatLayer && typeof __heatLayer.setLatLngs === 'function') {
            __heatLayer.setLatLngs(points);
            try { __heatLayer.redraw(); } catch (_) { }
        } else {
            try {
                __heatLayer = L.heatLayer(points, {
                    pane: 'heatPane',
                    radius: 40,
                    blur: 28,
                    maxZoom: 12,
                    minOpacity: 0.55,
                    max: 1,
                    gradient: {
                        0.2: '#3b82f6',
                        0.4: '#22c55e',
                        0.6: '#facc15',
                        0.8: '#f97316',
                        1.0: '#ef4444'
                    }
                }).addTo(__heatMap);
                window.statHeatmapLayer = __heatLayer;
                heatDebug(`${modeKey}: heat layer created`, __heatLayer);
                heatDebug(`${modeKey}: map has heat layer`, __heatMap.hasLayer(__heatLayer));
                try { __heatLayer.redraw?.(); heatDebug(`${modeKey}: heat layer redraw called`); } catch (_) { }
            } catch (error) {
                heatError(`${modeKey}: gagal membuat heat layer`, error);
            }
        }

        try {
            const heatCanvas = container.querySelector('canvas.leaflet-heatmap-layer');
            if (heatCanvas) heatCanvas.style.zIndex = '600';
        } catch (_) {}

        if (window.DEBUG_STAT_HEATMAP === true && __heatMap) {
            setTimeout(() => {
                try {
                    const overlayPane = __heatMap.getPane('overlayPane');
                    const overlayCanvases = overlayPane ? overlayPane.querySelectorAll('canvas') : [];
                    const allLeafletCanvases = document.querySelectorAll('#heatmap-map canvas, .leaflet-overlay-pane canvas');

                    heatDebug('overlayPane:', overlayPane);
                    heatDebug('overlayPane canvas count:', overlayCanvases.length);
                    heatDebug('all leaflet canvas count:', allLeafletCanvases.length);

                    allLeafletCanvases.forEach((canvas, index) => {
                        const rect = canvas.getBoundingClientRect();
                        const style = window.getComputedStyle(canvas);
                        heatDebug(`canvas ${index}`, {
                            width: canvas.width,
                            height: canvas.height,
                            clientWidth: canvas.clientWidth,
                            clientHeight: canvas.clientHeight,
                            rect,
                            display: style.display,
                            opacity: style.opacity,
                            zIndex: style.zIndex,
                            position: style.position,
                            visibility: style.visibility
                        });
                    });

                    ['tilePane', 'overlayPane', 'shadowPane', 'markerPane', 'tooltipPane', 'popupPane'].forEach((paneName) => {
                        const pane = __heatMap.getPane(paneName);
                        if (!pane) return;
                        const s = window.getComputedStyle(pane);
                        heatDebug(`pane ${paneName}`, { zIndex: s.zIndex, childCount: pane.children.length, display: s.display, opacity: s.opacity });
                    });
                } catch (e) {
                    heatError('diagnostic failed', e);
                }
            }, 500);

            if (typeof window.testStatHeatmap !== 'function') {
                window.testStatHeatmap = function () {
                    if (!window.statHeatmapMap) {
                        heatError('statHeatmapMap tidak tersedia');
                        return;
                    }

                    const testPoints = [
                        [-3.3194, 114.5908, 1],
                        [-3.3150, 114.6000, 1],
                        [-3.3300, 114.5800, 1],
                        [-3.4420, 114.8320, 1],
                        [-3.4500, 114.8400, 1]
                    ];

                    if (window.statHeatmapTestLayer) {
                        try { window.statHeatmapMap.removeLayer(window.statHeatmapTestLayer); } catch (_) { }
                    }

                    try {
                        window.statHeatmapTestLayer = L.heatLayer(testPoints, {
                            radius: 45,
                            blur: 25,
                            minOpacity: 0.7,
                            max: 1,
                            gradient: {
                                0.2: '#3b82f6',
                                0.4: '#22c55e',
                                0.6: '#facc15',
                                0.8: '#f97316',
                                1.0: '#ef4444'
                            }
                        }).addTo(window.statHeatmapMap);

                        window.statHeatmapMap.setView([-3.3194, 114.5908], 10);
                        heatDebug('test heatmap added');
                    } catch (e) {
                        heatError('gagal membuat test heatmap', e);
                    }
                };
                heatDebug('test function registered: testStatHeatmap()');
            }
        }
    }

    if (window.DEBUG_STAT_HEATMAP === true && __heatMap) {
        try {
            if (!__heatDebugLayer) {
                __heatDebugLayer = L.layerGroup().addTo(__heatMap);
            }
            __heatDebugLayer.clearLayers();
            points.slice(0, 30).forEach((p) => {
                const lat = p[0], lng = p[1];
                L.circleMarker([lat, lng], { radius: 4, color: '#0f172a', weight: 1, fillColor: '#ffffff', fillOpacity: 0.9 })
                    .addTo(__heatDebugLayer);
            });
        } catch (_) { }
    }

    const metaEl = qs('heatmap-meta');
    const meta = current?.meta || {};
    if (metaEl) {
        const valid = points.length;
        const noCoord = Number(meta?.no_coord ?? 0) || 0;
        metaEl.textContent = `${formatNumber(valid)} titik valid • ${formatNumber(noCoord)} data tanpa koordinat`;
    }

    try { __heatMap.invalidateSize(); } catch (_) {}
}

async function refresh() {
    const params = getFiltersFromUi();
    const payload = await fetchStatistik(params);
    applyData(payload);
}

function exportPdf() {
    const endpoint = window.__STATISTIK_EXPORT_PDF__ || '';
    if (!endpoint) return;

    const params = {
        ...getFiltersFromUi(),
        data_mode: getDataModeFromUi()
    };

    const url = endpoint + (buildQuery(params) ? `?${buildQuery(params)}` : '');

    // Trigger download (hindari membuka tab baru / preview HTML)
    window.location.href = url;
}

function exportExcel() {
    const endpoint = window.__STATISTIK_EXPORT_EXCEL__ || '';
    if (!endpoint) return;

    const params = {
        ...getFiltersFromUi(),
        data_mode: getDataModeFromUi()
    };

    const url = endpoint + (buildQuery(params) ? `?${buildQuery(params)}` : '');
    window.location.href = url;
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

    const mode = qs('stat-filter-data-mode');
    if (mode) mode.value = 'valid';
    __showUnknownStats = false;
}

function initDataModeControl() {
    const el = qs('stat-filter-data-mode');
    if (!el) return;

    const applyMode = () => {
        const v = String(el.value || 'valid').toLowerCase();
        __showUnknownStats = v === 'all';
    };

    applyMode();
    el.addEventListener('change', function () {
        applyMode();
        if (__lastPayload) {
            applyData(__lastPayload);
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.__GIS_ADMIN_STATISTIK_INIT__) return;
    window.__GIS_ADMIN_STATISTIK_INIT__ = true;
    applyChartDefaults();
    if (typeof Chart !== 'undefined' && Chart?.register) {
        Chart.register(valueLabelPlugin);
    }
    initFilterToggle();
    initDataModeControl();
    qs('stat-apply')?.addEventListener('click', refresh);
    qs('stat-export-pdf')?.addEventListener('click', exportPdf);
    qs('stat-export-excel')?.addEventListener('click', exportExcel);
    qs('stat-reset')?.addEventListener('click', async function () {
        resetFilters();
        await refresh();
    });
    qs('stat-wilayah-sort')?.addEventListener('change', function () {
        const c = __lastPayload?.charts || {};
        observeAndRender('chart-top-wilayah', () => updateBarChart('top_wilayah', 'chart-top-wilayah', c.top_wilayah?.labels || [], c.top_wilayah?.data || [], true));
    });

    refresh().catch(() => {
        // Silent: empty state handled by UI. Errors will show in console.
    });
});
