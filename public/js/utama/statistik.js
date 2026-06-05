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
        wilayah_id: qs('stat-filter-wilayah')?.value || ''
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

// Mode tampilan data statistik (global, frontend-only)
// Default: "Hanya data valid" => unknown disembunyikan.
let __showUnknownStats = false;

function isUnknownLabel(label) {
    if (label === null || label === undefined) return true;
    let s = String(label).trim().toLowerCase();
    if (!s) return true;

    // Normalisasi sederhana untuk varian penulisan
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
    // Dashboard berisi banyak chart: matikan animasi untuk menghindari freeze.
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
    const empty = document.querySelector(`.stat-empty[data-empty-for="${canvasId}"]`);
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
    const baseHue = 206; // theme biru
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
                        label: function (ctx2) {
                            const v = ctx2.parsed;
                            const showPct = opts?.showPercent === true;
                            const denom = Number(opts?.percentDenominator ?? total) || total;
                            const pct = showPct && denom > 0 ? ` (${Math.round((v / denom) * 100)}%)` : '';
                            return `${ctx2.label}: ${formatNumber(v)}${pct}`;
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

        const wrap = qs(canvasId)?.closest('.stat-wilayah-chart-wrap');
        if (wrap) {
            const count = Array.isArray(labels) ? labels.length : 0;
            const computed = Math.max(360, 96 + (count * 34));
            wrap.style.height = `${Math.min(computed, 920)}px`;
            wrap.style.overflowY = computed > 720 ? 'auto' : 'hidden';
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
    const isSalary = key === 'salary_distribution';
    const baseBekerja = Number(__lastPayload?.charts?.top_company?.base?.bekerja ?? 0) || 0;
    const truncate = (s, max) => {
        const t = String(s ?? '');
        if (!max || t.length <= max) return t;
        return t.slice(0, Math.max(0, max - 1)) + '…';
    };

    const makeHorizontalTickCallback = () => function (value) {
        const idx = typeof value === 'number' ? value : Number(value);
        const chartLabels = this?.chart?.data?.labels || [];
        const labelFromScale = Number.isFinite(idx) && typeof this?.getLabelForValue === 'function'
            ? this.getLabelForValue(idx)
            : null;
        const label = labelFromScale || chartLabels[idx] || value;
        return truncate(label, key === 'top_wilayah' ? 32 : (isCompany ? 28 : 24));
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
                    grid: { color: 'rgba(0, 74, 135, 0.06)' },
                    beginAtZero: true,
                    ticks: {
                        font: { weight: '700' },
                        autoSkip: true,
                        maxRotation: 0,
                        ...(isCompany ? { stepSize: 1, precision: 0 } : {})
                    },
                    title: key === 'top_wilayah'
                        ? { display: true, text: 'Jumlah Alumni Bekerja', font: { weight: '900' } }
                        : { display: false }
                },
                y: {
                    grid: { color: 'rgba(0, 74, 135, 0.06)' },
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { weight: '700' },
                        callback: horizontal ? makeHorizontalTickCallback() : undefined
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: key === 'top_wilayah' || isCompany || isTopBidang || isSalary
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
                                if (isTopBidang || isSalary) return `${formatNumber(count)} alumni`;
                                if (!isCompany) return `Jumlah: ${formatNumber(count)}`;
                                const pct = baseBekerja > 0 ? ` (${Math.round((count / baseBekerja) * 100)}%)` : '';
                                return `Jumlah: ${formatNumber(count)}${pct}`;
                            }
                        }
                    }
                    : undefined,
                valueLabelPlugin: enableValueLabels ? { enabled: true } : { enabled: false }
            }
        }
    };

    if (charts[key]) {
        charts[key].data.labels = safeLabels;
        charts[key].data.datasets[0].data = data;
        charts[key].options.indexAxis = horizontal ? 'y' : 'x';
        charts[key].options.scales.y.ticks.callback = horizontal ? makeHorizontalTickCallback() : undefined;
        charts[key].resize();
        if (key === 'top_wilayah') {
            charts[key].data.datasets[0].backgroundColor = wilayahColors;
            charts[key].options.scales.x.title = { display: true, text: 'Jumlah Alumni Bekerja', font: { weight: '900' } };
            charts[key].options.plugins.tooltip = config.options.plugins.tooltip;
            charts[key].options.plugins.valueLabelPlugin = { enabled: true };
        } else if (enableValueLabels) {
            charts[key].options.plugins.valueLabelPlugin = { enabled: true };
            charts[key].options.plugins.tooltip = config.options.plugins.tooltip;
        }
        charts[key].update();
        return;
    }

    charts[key] = new Chart(ctx, config);
    // Pastikan canvas tidak mengikuti ukuran yang salah dari render sebelumnya.
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
    setText(
        'chart-top-wilayah-subtitle',
        c.top_wilayah?.subtitle || payload?.meta?.top_wilayah_subtitle || 'Distribusi wilayah kerja seluruh alumni yang bekerja'
    );

    safeRenderChart('Status Alumni', () => {
        const labels = normalizeLabels(c.status?.labels || []);
        const filtered = filterLabelsData(labels, c.status?.data || [], (label) => !String(label || '').toLowerCase().includes('wirausaha'));
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
        const defaultLabels = ['< Rp1 juta', 'Rp1–3 juta', 'Rp3–5 juta', 'Rp5–10 juta', '> Rp10 juta', 'Tidak diketahui'];
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
                foot.textContent = `Data gaji valid: ${formatNumber(valid)} alumni â€¢ Tidak diketahui: ${formatNumber(unknown)} alumni`;
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
        { label: 'Total Alumni', data: c.tren_angkatan?.total || [], borderColor: '#004a87', backgroundColor: 'rgba(0, 74, 135, 0.12)', fill: true, tension: 0.35, pointRadius: 3 }
    ]));

    safeRenderChart('Tren Keterserapan', () => updateStackedBarChart('tren_serap', 'chart-tren-serap', c.tren_angkatan?.labels || [], [
        { label: 'Bekerja', data: c.tren_angkatan?.bekerja || [], backgroundColor: 'rgba(37, 99, 235, 0.85)' },
        { label: 'Belum Bekerja', data: c.tren_angkatan?.belum_bekerja || [], backgroundColor: 'rgba(220, 38, 38, 0.85)' }
    ]));

    renderInsights(payload);
    // Heatmap section dinonaktifkan di halaman public (jika container tidak ada, jangan jalankan apa pun).
    const heatEl = qs('heatmap-map');
    if (heatEl) {
        // Heatmap tetap lazy karena paling berat (Leaflet + heat layer).
        observeAndRender('heatmap-map', () => safeRenderChart('Heatmap', () => updateHeatmap(payload)));

        // Fallback aman: jika IntersectionObserver tidak terpanggil (layout/scroll tertentu),
        // tetap render heatmap sekali setelah data tersedia.
        try {
            if (__heatFallbackTimer) clearTimeout(__heatFallbackTimer);
            __heatFallbackTimer = setTimeout(() => {
                try {
                    const el = qs('heatmap-map');
                    if (!el) return;
                    if (!__heatMap) {
                        heatDebug('fallback heatmap render triggered');
                        safeRenderChart('Heatmap (fallback)', () => updateHeatmap(payload));
                    }
                } catch (_) {}
            }, 350);
        } catch (_) {}
    } else {
        try { if (__heatFallbackTimer) clearTimeout(__heatFallbackTimer); } catch (_) {}
    }
}

let __heatMap = null;
let __heatLayer = null;
let __heatMode = 'domisili';
let __kalselPolygonLayer = null;
let __kalselPolygonLoading = false;
let __kalselPolygonLoaded = false;
let __heatDebugLayer = null;
let __heatFitDone = false;
let __heatLastModeKey = null;
let __heatFallbackTimer = null;
let __heatStatusText = '';
let __heatStatusKind = null;

// Enable from console: window.DEBUG_STAT_HEATMAP = true
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

function polyDebug(...args) {
    if (window.DEBUG_STAT_HEATMAP === true) {
        console.log('[STAT-POLYGON]', ...args);
    }
}

function polyWarn(...args) {
    console.warn('[STAT-POLYGON]', ...args);
}

function polyError(...args) {
    console.error('[STAT-POLYGON]', ...args);
}

function ensureHeatStatusEl() {
    const mapEl = qs('heatmap-map');
    if (!mapEl) return null;

    // Prefer foot area (below map) so tidak mengganggu peta.
    const card = mapEl.closest('.heatmap-stat-card') || mapEl.closest('.stat-card') || null;
    const foot = card ? card.querySelector('.stat-heatmap-foot') : null;
    const host = foot || mapEl.parentElement || card || document.body;
    if (!host) return null;

    let el = document.getElementById('heatmap-status');
    if (!el) {
        el = document.createElement('div');
        el.id = 'heatmap-status';
        el.className = 'stat-heatmap-status';
        el.setAttribute('role', 'status');
        el.hidden = true;
        host.appendChild(el);
    }
    return el;
}

function setHeatStatus(message, kind) {
    const el = ensureHeatStatusEl();
    if (!el) return;

    const text = String(message || '').trim();
    if (!text) {
        el.hidden = true;
        el.textContent = '';
        el.classList.remove('is-info', 'is-warn', 'is-error', 'is-success');
        return;
    }

    el.hidden = false;
    el.textContent = text;
    el.classList.remove('is-info', 'is-warn', 'is-error', 'is-success');
    if (kind) el.classList.add(`is-${kind}`);
}

function appendHeatStatus(message, kind) {
    const el = ensureHeatStatusEl();
    if (!el) return;
    const current = String(el.textContent || '').trim();
    const next = String(message || '').trim();
    if (!next) return;
    if (!current) {
        setHeatStatus(next, kind);
        return;
    }
    // Jangan duplikasi kalimat yang sama.
    if (current.includes(next)) return;
    setHeatStatus(`${current} ${next}`, kind);
}

function syncHeatStatus() {
    const parts = [];
    if (__kalselPolygonLoaded) {
        parts.push('Polygon dimuat.');
    } else if (__kalselPolygonLoading) {
        parts.push('Memuat polygon Kalimantan Selatan...');
    }
    if (__heatStatusText) {
        parts.push(__heatStatusText);
    }

    const text = parts.join(' ').trim();
    setHeatStatus(text, __heatStatusKind || 'info');
}

function getStatPolygonStyle() {
    return {
        // Lebih kontras agar batas Kalsel jelas terlihat,
        // tapi fill tetap tipis supaya heatmap tidak tertutup.
        color: '#0057b8',
        weight: 2.5,
        opacity: 0.95,
        fillColor: '#38bdf8',
        fillOpacity: 0.06
    };
}

function ensureHeatPanes() {
    if (!__heatMap || typeof __heatMap.getPane !== 'function') return;
    try {
        if (!__heatMap.getPane('polygonPane')) {
            __heatMap.createPane('polygonPane');
        }
        const polygonPane = __heatMap.getPane('polygonPane');
        if (polygonPane) {
            polygonPane.style.zIndex = '430';
            polygonPane.style.pointerEvents = 'none';
        }

        if (!__heatMap.getPane('heatPane')) {
            __heatMap.createPane('heatPane');
        }
        const heatPane = __heatMap.getPane('heatPane');
        if (heatPane) {
            heatPane.style.zIndex = '650';
            heatPane.style.pointerEvents = 'none';
        }

        if (window.DEBUG_STAT_HEATMAP === true) {
            heatDebug('polygonPane zIndex:', polygonPane ? window.getComputedStyle(polygonPane).zIndex : null);
            heatDebug('heatPane zIndex:', heatPane ? window.getComputedStyle(heatPane).zIndex : null);
        }
    } catch (_) {}
}

function sampleHeatCanvasPixels(canvas) {
    try {
        const ctx = canvas?.getContext?.('2d');
        if (!ctx) return null;
        const w = canvas.width || 0;
        const h = canvas.height || 0;
        if (!w || !h) return null;

        const step = 12;
        const img = ctx.getImageData(0, 0, w, h).data;
        let nonTransparent = 0;
        let colored = 0;
        for (let y = 0; y < h; y += step) {
            for (let x = 0; x < w; x += step) {
                const idx = (y * w + x) * 4;
                const r = img[idx], g = img[idx + 1], b = img[idx + 2], a = img[idx + 3];
                if (a > 0) {
                    nonTransparent += 1;
                    if (r !== 0 || g !== 0 || b !== 0) colored += 1;
                }
            }
        }
        return { nonTransparent, colored, w, h, step };
    } catch (_) {
        return null;
    }
}

function ensureHeatCanvasInHeatPane() {
    if (!__heatMap) return null;
    try {
        const heatPane = __heatMap.getPane?.('heatPane');
        const overlayPane = __heatMap.getPane?.('overlayPane');
        if (window.DEBUG_STAT_HEATMAP === true) {
            heatDebug('heatPane exists:', !!heatPane);
        }

        // Prefer canvas yang sudah berada di heatPane.
        let canvas = heatPane?.querySelector?.('canvas.leaflet-heatmap-layer') || null;

        // Fallback: cari di overlayPane atau container map.
        if (!canvas) {
            canvas = overlayPane?.querySelector?.('canvas.leaflet-heatmap-layer') || null;
        }
        if (!canvas) {
            const cont = qs('heatmap-map');
            canvas = cont?.querySelector?.('canvas.leaflet-heatmap-layer') || null;
        }

        if (!canvas) return null;

        // Pindahkan ke heatPane jika belum di sana.
        if (heatPane && canvas.parentElement !== heatPane) {
            heatWarn('heat canvas tidak berada di heatPane, memindahkan canvas ke heatPane. parent=', canvas.parentElement?.className);
            heatPane.appendChild(canvas);
        }

        // Pastikan styling kuat.
        canvas.style.position = 'absolute';
        canvas.style.zIndex = '650';
        canvas.style.opacity = '1';
        canvas.style.visibility = 'visible';
        canvas.style.mixBlendMode = 'normal';

        if (window.DEBUG_STAT_HEATMAP === true) {
            const parentCls = canvas.parentElement?.className;
            const z = window.getComputedStyle(canvas).zIndex;
            const op = window.getComputedStyle(canvas).opacity;
            heatDebug('heat canvas in heatPane:', canvas.parentElement === heatPane);
            heatDebug('heat canvas parent className:', parentCls);
            heatDebug('heat canvas zIndex:', z);
            heatDebug('heat canvas opacity:', op);
            const sample = sampleHeatCanvasPixels(canvas);
            if (sample) {
                heatDebug('heat canvas pixel sample:', sample);
            }
        }

        return canvas;
    } catch (_) {
        return null;
    }
}

function normalizeHeatmapPoints(points, mode) {
    const source = Array.isArray(points) ? points : [];
    const normalized = [];

    let invalidNumeric = 0;
    let invalidRange = 0;
    let swappedCount = 0;
    let zeroWeightFixed = 0;

    source.forEach((point, index) => {
        let lat = Number(point?.lat ?? point?.latitude ?? point?.[0]);
        let lng = Number(point?.lng ?? point?.longitude ?? point?.lon ?? point?.[1]);
        let weight = Number(point?.weight ?? point?.intensity ?? point?.[2] ?? 1);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            invalidNumeric += 1;
            if (window.DEBUG_STAT_HEATMAP === true && index < 10) {
                heatWarn(`${mode}: invalid numeric at index ${index}`, point, { lat, lng, weight });
            }
            return;
        }

        // Deteksi lat/lng tertukar (heuristik Kalsel)
        const looksSwapped = lat >= 95 && lat <= 130 && lng >= -10 && lng <= 10;
        if (looksSwapped) {
            const tmp = lat;
            lat = lng;
            lng = tmp;
            swappedCount += 1;
        }

        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            invalidRange += 1;
            if (window.DEBUG_STAT_HEATMAP === true && index < 10) {
                heatWarn(`${mode}: invalid range at index ${index}`, point, { lat, lng, weight });
            }
            return;
        }

        if (!Number.isFinite(weight) || weight <= 0) {
            zeroWeightFixed += 1;
            weight = 1;
        }

        normalized.push([lat, lng, weight]);
    });

    if (window.DEBUG_STAT_HEATMAP === true) {
        heatDebug(`${mode}: raw points length`, source.length);
        heatDebug(`${mode}: normalized length`, normalized.length);
        heatDebug(`${mode}: invalid numeric`, invalidNumeric);
        heatDebug(`${mode}: invalid range`, invalidRange);
        heatDebug(`${mode}: swapped lat/lng count`, swappedCount);
        heatDebug(`${mode}: zero/invalid weight fixed`, zeroWeightFixed);
        heatDebug(`${mode}: first 10 normalized`, normalized.slice(0, 10));
        if (normalized.length) {
            const lats = normalized.map((p) => p[0]);
            const lngs = normalized.map((p) => p[1]);
            const ws = normalized.map((p) => p[2]);
            heatDebug(`${mode}: lat range`, Math.min(...lats), Math.max(...lats));
            heatDebug(`${mode}: lng range`, Math.min(...lngs), Math.max(...lngs));
            heatDebug(`${mode}: weight range`, Math.min(...ws), Math.max(...ws));
        }
    }

    return normalized;
}

function ensureKalselPolygonLayer() {
    if (!__heatMap || __kalselPolygonLayer || __kalselPolygonLoading || typeof L === 'undefined') return;
    __kalselPolygonLoading = true;
    __kalselPolygonLoaded = false;

    const url = '/data/data_kalsel_simplified.geojson';
    polyDebug('loading geojson:', url);
    syncHeatStatus();

    (async () => {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            polyDebug('geojson response status:', res.status);
            if (!res.ok) {
                throw new Error(`GeoJSON gagal dimuat: HTTP ${res.status}`);
            }

            const geojson = await res.json();
            const type = String(geojson?.type || '');
            const features = Array.isArray(geojson?.features) ? geojson.features : null;
            const featureCount = features ? features.length : 0;

            if (!type || (type !== 'FeatureCollection' && type !== 'Feature')) {
                polyWarn('geojson type tidak valid:', type, geojson);
            }
            if (type === 'FeatureCollection' && !features) {
                polyWarn('geojson FeatureCollection tanpa features array:', geojson);
            }
            if (type === 'FeatureCollection' && featureCount === 0) {
                polyWarn('geojson features kosong:', geojson);
            }

            polyDebug('geojson loaded. type:', type, 'featureCount:', featureCount);
            if (!__heatMap) return;

            const style = getStatPolygonStyle();
            __kalselPolygonLayer = L.geoJSON(geojson, {
                style,
                interactive: false,
                pane: 'polygonPane'
            }).addTo(__heatMap);

            window.statPolygonLayer = __kalselPolygonLayer;
            polyDebug('layer added:', __kalselPolygonLayer);
            if (window.DEBUG_STAT_HEATMAP === true) {
                try { polyDebug('style applied:', style); } catch (_) {}
            }

            try {
                const b = __kalselPolygonLayer.getBounds?.();
                if (b && b.isValid && b.isValid()) {
                    polyDebug('bounds:', b);
                    // Fit ringan ke Kalsel hanya sekali, agar polygon terlihat.
                    __heatMap.fitBounds(b, { padding: [12, 12] });
                } else {
                    polyWarn('bounds tidak valid');
                }
            } catch (e) {
                polyWarn('getBounds/fitBounds gagal:', e);
            }

            // Jangan agresif bringToBack; cukup pastikan heat overlay di atas polygon via zIndex pane.
            // Jangan menimpa status heatmap; gabungkan saja.
            __kalselPolygonLoaded = true;
            __heatStatusKind = __heatStatusKind || 'success';
            syncHeatStatus();
        } catch (e) {
            polyError('gagal load polygon kalsel:', e);
            __heatStatusKind = 'error';
            __heatStatusText = __heatStatusText || '';
            setHeatStatus('Polygon Kalimantan Selatan gagal dimuat.' + (__heatStatusText ? ` ${__heatStatusText}` : ''), 'error');
        } finally {
            __kalselPolygonLoading = false;
            syncHeatStatus();
        }
    })();
}

function setHeatEmpty(isEmpty) {
    const empty = document.querySelector('.stat-empty[data-empty-for="heatmap-map"]');
    if (empty) empty.hidden = !isEmpty;
}

function setHeatTab(mode) {
    __heatMode = mode;
    const btnDom = qs('heatmap-tab-domisili');
    const btnKerja = qs('heatmap-tab-kerja');
    if (btnDom && btnKerja) {
        const isDom = mode === 'domisili';
        btnDom.classList.toggle('stat-btn-primary', isDom);
        btnKerja.classList.toggle('stat-btn-primary', !isDom);
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
    heatDebug('Leaflet L available:', typeof L !== 'undefined');
    heatDebug('L.map available:', typeof L?.map);
    heatDebug('L.heatLayer available:', typeof L?.heatLayer);
    heatDebug('mode:', modeKey);
    heatDebug('raw sample:', rawPoints?.slice?.(0, 10));
    heatDebug('normalized sample:', heatPoints.slice(0, 10));
}

function countPointsInKalselBounds(heatPoints) {
    return (heatPoints || []).filter(([lat, lng]) => lat >= -5.0 && lat <= -1.0 && lng >= 113.0 && lng <= 117.5).length;
}

function initHeatmapUi() {
    const domBtn = qs('heatmap-tab-domisili');
    const kerjaBtn = qs('heatmap-tab-kerja');
    if (domBtn) domBtn.addEventListener('click', () => {
        heatDebug('toggle clicked:', 'domisili');
        setHeatTab('domisili');
        updateHeatmap(__lastPayload);
    });
    if (kerjaBtn) kerjaBtn.addEventListener('click', () => {
        heatDebug('toggle clicked:', 'lokasi_kerja');
        setHeatTab('lokasi_kerja');
        updateHeatmap(__lastPayload);
    });
}

function updateHeatmap(payload) {
    const container = qs('heatmap-map');
    if (!container) return;
    if (typeof L === 'undefined' || !L?.map) return;

    heatDebug('init/update started');
    heatDebug('Map container:', container);

    if (typeof L?.heatLayer !== 'function') {
        heatError('leaflet.heat belum ter-load. L.heatLayer bukan function.');
        setHeatStatus('Heatmap tidak dapat ditampilkan karena plugin leaflet-heat belum termuat.', 'error');
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
    } catch (_) {}

    logHeatDebug(rawPoints, points, modeKey);
    const inKalsel = countPointsInKalselBounds(points);
    heatDebug(`${modeKey}: points inside Kalsel bbox`, inKalsel, '/', points.length);
    if (points.length > 0 && inKalsel === 0) {
        heatWarn('titik valid ada, tetapi tidak berada dalam bounds Kalimantan Selatan. Cek koordinat/mode data.');
        __heatStatusText = 'Titik valid ada, tetapi tidak berada dalam bounds Kalimantan Selatan. Cek koordinat atau mode data.';
        __heatStatusKind = 'warn';
        syncHeatStatus();
    } else if (points.length > 0) {
        __heatStatusText = `Heatmap siap: ${formatNumber(points.length)} titik valid (${formatNumber(inKalsel)} dalam bounds Kalsel).`;
        __heatStatusKind = 'info';
        syncHeatStatus();
    } else {
        __heatStatusText = '';
        __heatStatusKind = null;
        syncHeatStatus();
    }

    if (window.DEBUG_STAT_HEATMAP === true && points.length) {
        try {
            const bounds = L.latLngBounds(points.map((p) => [p[0], p[1]]));
            if (bounds?.isValid?.()) {
                heatDebug('heat bounds:', bounds.toBBoxString?.() || bounds);
            }
        } catch (_) {}
    }

    if (!__heatMap) {
        __heatMap = L.map('heatmap-map', { zoomControl: true }).setView([-3.316694, 114.590111], 8);
        window.statHeatmapMap = __heatMap;

        // Pane layering: polygon di bawah heatmap.
        try {
            ensureHeatPanes();
        } catch (_) { }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
            // Sedikit "soft" agar heatmap lebih kontras tanpa mengubah data/layer lain.
            opacity: 0.85
        }).addTo(__heatMap);

        ensureKalselPolygonLayer();
        initHeatmapUi();
        setHeatTab('domisili');
        setTimeout(() => { try { __heatMap?.invalidateSize(); } catch (_) {} }, 200);
    }

    // Pastikan polygon sudah dimuat (sekali) dan tidak dihapus saat toggle.
    ensureKalselPolygonLayer();
    window.statPolygonLayer = __kalselPolygonLayer;

    if (!points.length || !L.heatLayer) {
        setHeatEmpty(true);
        if (__heatLayer) {
            heatDebug('existing heat layer before remove:', __heatLayer, __heatMap.hasLayer(__heatLayer));
            try { __heatMap.removeLayer(__heatLayer); } catch (_) { }
            __heatLayer = null;
            window.statHeatmapLayer = null;
            heatDebug('heat layer removed');
        }
    } else {
        setHeatEmpty(false);

        // Fit map saat pertama kali tampil dan saat ganti mode.
        if (!__heatFitDone || __heatLastModeKey !== modeKey) {
            __heatFitDone = true;
            __heatLastModeKey = modeKey;
            try {
                const bounds = L.latLngBounds(points.map((p) => [p[0], p[1]]));
                if (bounds.isValid()) {
                    __heatMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
                }
            } catch (_) { }
        }

        if (__heatLayer && typeof __heatLayer.setLatLngs === 'function') {
            heatDebug('updating existing heat layer. hasLayer:', __heatMap.hasLayer(__heatLayer));
            __heatLayer.setLatLngs(points);
            try { __heatLayer.redraw(); } catch (_) { }
        } else {
            heatDebug(`${modeKey}: creating heat layer with points`, points.length);
            try {
                __heatLayer = L.heatLayer(points, {
                    radius: 60,
                    blur: 32,
                    maxZoom: 18,
                    minOpacity: 0.85,
                    max: 0.3,
                    gradient: {
                        0.1: '#0000ff',
                        0.3: '#00ff00',
                        0.5: '#ffff00',
                        0.7: '#ff9900',
                        1.0: '#ff0000'
                    },
                    // Coba paksa heatmap masuk pane khusus.
                    pane: 'heatPane'
                }).addTo(__heatMap);

                window.statHeatmapLayer = __heatLayer;
                heatDebug(`${modeKey}: heat layer created`, __heatLayer);
                heatDebug(`${modeKey}: map has heat layer`, __heatMap.hasLayer(__heatLayer));
                try { __heatLayer.redraw?.(); heatDebug(`${modeKey}: heat layer redraw called`); } catch (_) { }
            } catch (e) {
                heatError('gagal membuat heat layer:', e);
                setHeatStatus('Heatmap gagal dibuat (lihat console untuk detail).', 'error');
            }
        }

        // Pastikan heatmap berada di heatPane dan berada di atas polygon.
        try {
            ensureHeatPanes();
            ensureHeatCanvasInHeatPane();
        } catch (_) {}
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

    // Extra invalidate & redraw untuk layout card yang kadang resize setelah render.
    try {
        setTimeout(() => { try { __heatMap?.invalidateSize(); } catch (_) {} }, 300);
        setTimeout(() => { try { __heatMap?.invalidateSize(); } catch (_) {} }, 700);
    } catch (_) {}

    // Force redraw setelah pane/canvas fix.
    try {
        const doRedraw = () => {
            try { __heatLayer?.setLatLngs?.(points); } catch (_) {}
            try { __heatLayer?.redraw?.(); } catch (_) {}
            ensureHeatCanvasInHeatPane();
        };
        setTimeout(doRedraw, 100);
        setTimeout(doRedraw, 300);
        setTimeout(doRedraw, 700);
    } catch (_) {}

    if (window.DEBUG_STAT_HEATMAP === true && __heatMap) {
        setTimeout(() => {
            try {
                __heatMap.invalidateSize();
                heatDebug('map size after invalidate:', __heatMap.getSize());
                heatDebug('map bounds after invalidate:', __heatMap.getBounds());

                const overlayPane = __heatMap.getPane('overlayPane');
                const overlayCanvases = overlayPane ? overlayPane.querySelectorAll('canvas') : [];
                const allLeafletCanvases = container.querySelectorAll('canvas, .leaflet-overlay-pane canvas');

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

function normalizeCompareText(value) {
    return String(value ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
}

function renderInsights(payload) {
    const wrap = qs('stat-insight');
    const list = qs('stat-insight-list');
    if (!wrap || !list) return;

    const k = payload?.kpis || {};
    const c = payload?.charts || {};
    const meta = payload?.meta || {};
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
    if (topWilayah?.label) {
        insights.push(`Wilayah kerja terbanyak adalah ${topWilayah.label}.`);

        const filterWilayah = String(meta.wilayah_filter_label || '').trim();
        if (filterWilayah) {
            if (normalizeCompareText(topWilayah.label) === normalizeCompareText(filterWilayah)) {
                insights.push(`Mayoritas alumni terkait ${filterWilayah} memang bekerja di wilayah tersebut.`);
            } else {
                insights.push(`Catatan: sebagian alumni terkait ${filterWilayah} bekerja di luar ${filterWilayah}, dengan konsentrasi terbesar di ${topWilayah.label}.`);
            }
        }
    }

    const masa = Number(k.rata_masa_tunggu);
    if (Number.isFinite(masa)) insights.push(`Rata-rata masa tunggu kerja sekitar ${formatDecimal(masa, 1)} bulan.`);

    list.innerHTML = '';
    const finalInsights = insights.filter(Boolean).slice(0, 5);
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

function populateWilayahFilter() {
    const select = qs('stat-filter-wilayah');
    if (!select || select.options.length > 1) return Promise.resolve();

    const initialId = String(select.dataset.initialWilayahId || '').trim();

    return fetch('/api/wilayah-kalsel', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!Array.isArray(data)) return;
            data.forEach(function (wilayah) {
                const option = document.createElement('option');
                option.value = wilayah.id;
                option.textContent = wilayah.display;
                if (initialId !== '' && String(wilayah.id) === initialId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        })
        .catch(function (err) {
            console.warn('Gagal memuat daftar wilayah Kalsel:', err);
        });
}

function resetFilters() {
    ['stat-filter-angkatan', 'stat-filter-tahun-lulus', 'stat-filter-jenis-kelamin', 'stat-filter-status-alumni', 'stat-filter-bidang', 'stat-filter-wilayah']
        .forEach((id) => { const el = qs(id); if (el) el.value = ''; });

    const mode = qs('stat-filter-data-mode');
    if (mode) mode.value = 'valid';
    __showUnknownStats = false;
}

async function refresh() {
    const payload = await fetchStatistik(getFiltersFromUi());
    applyData(payload);
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
    if (window.__GIS_STATISTIK_INIT__) return;
    window.__GIS_STATISTIK_INIT__ = true;
    applyChartDefaults();
    if (typeof Chart !== 'undefined' && Chart?.register) {
        Chart.register(valueLabelPlugin);
    }
    initFilterToggle();
    initDataModeControl();
    qs('stat-apply')?.addEventListener('click', refresh);
    qs('stat-reset')?.addEventListener('click', async function () {
        resetFilters();
        await refresh();
    });
    qs('stat-wilayah-sort')?.addEventListener('change', function () {
        const c = __lastPayload?.charts || {};
        observeAndRender('chart-top-wilayah', () => updateBarChart('top_wilayah', 'chart-top-wilayah', c.top_wilayah?.labels || [], c.top_wilayah?.data || [], true));
    });
    Promise.resolve(populateWilayahFilter()).finally(() => refresh().catch(() => {}));
});
