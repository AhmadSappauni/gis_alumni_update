function switchView(viewType) {
    const cardWrapper = document.getElementById('card-view-wrapper');
    const listView = document.getElementById('list-view');
    const btnCard = document.getElementById('btn-card');
    const btnList = document.getElementById('btn-list');

    if (viewType === 'list') {
        cardWrapper.style.display = 'none';
        listView.style.display = 'block';
        btnList.classList.add('active');
        btnCard.classList.remove('active');
        localStorage.setItem('alumniViewPref', 'list');
    } else {
        cardWrapper.style.display = 'flex';
        listView.style.display = 'none';
        btnCard.classList.add('active');
        btnList.classList.remove('active');
        localStorage.setItem('alumniViewPref', 'card');
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const savedPref = localStorage.getItem("alumniViewPref") || 'list';
    switchView(savedPref);

    initLiveServerSearch();
});

function normalizeFilterValue(value) {
    return (value || "").toString().trim().toLowerCase();
}

function initLiveServerSearch() {
    const input = document.getElementById("alumniSearch");
    const form = document.getElementById("alumniSearchForm");
    const results = document.getElementById("alumniResults");
    const resetLink = document.getElementById("resetSearchLink");

    if (!input || !results) return;

    let debounceTimer = null;
    let activeController = null;
    let requestSeq = 0;

    function setLoading(isLoading) {
        if (isLoading) {
            results.style.opacity = "0.6";
            results.style.pointerEvents = "none";
        } else {
            results.style.opacity = "";
            results.style.pointerEvents = "";
        }
    }

    function updateSearchUI(keyword) {
        const trimmed = (keyword || "").trim();
        if (trimmed) {
            if (resetLink) resetLink.style.display = "";
        } else {
            if (resetLink) resetLink.style.display = "none";
        }
    }

    async function fetchAndRender(url, { pushState = false } = {}) {
        const currentSeq = ++requestSeq;

        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();

        setLoading(true);

        try {
            const res = await fetch(url, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                signal: activeController.signal,
            });

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const data = await res.json();
            if (currentSeq !== requestSeq) return; // abaikan response lama

            const viewPref = window.localStorage ? window.localStorage.getItem("alumniViewPref") : null;

            if (data && typeof data.html === "string") {
                results.innerHTML = data.html;
            }

            if (viewPref) {
                switchView(viewPref);
            }

            const nextUrl = (typeof url === "string") ? url : window.location.href;
            if (pushState) {
                window.history.pushState({}, "", nextUrl);
            } else {
                window.history.replaceState({}, "", nextUrl);
            }

            // re-init fitur yang bergantung ke DOM hasil
            if (typeof window.initBulkDeleteAlumni === "function") {
                window.initBulkDeleteAlumni();
            }
            if (typeof window.initModalHandlers === "function") {
                window.initModalHandlers();
            }
        } catch (e) {
            if (e && e.name === "AbortError") return;
            // fallback: kalau error, biar user tetap bisa jalan via full reload
            window.location.assign(url);
        } finally {
            setLoading(false);
        }
    }

    function triggerSearch() {
        const keyword = (input.value || "").trim();
        updateSearchUI(keyword);

        const url = new URL(window.location.href);
        if (keyword) url.searchParams.set("search", keyword);
        else url.searchParams.delete("search");

        // keyword berubah => reset page
        url.searchParams.delete("page");

        fetchAndRender(url.toString());
    }

    // expose untuk applyFilters()/resetFilters() (filter popup)
    window.alumniFetchAndRender = function (url, opts) {
        return fetchAndRender(url, opts || {});
    };

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") e.preventDefault();
    });

    input.addEventListener("input", function () {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(triggerSearch, 200);
    });

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
        });
    }

    if (resetLink) {
        resetLink.addEventListener("click", function (e) {
            e.preventDefault();
            input.value = "";
            updateSearchUI("");

            const url = new URL(window.location.href);
            url.searchParams.delete("search");
            url.searchParams.delete("page");

            fetchAndRender(url.toString(), { pushState: true });
            input.focus();
        });
    }

    // pagination AJAX (delegate) + per_page change
    results.addEventListener("click", function (e) {
        const link = e.target.closest("a");
        if (!link) return;

        // khusus link pagination di area hasil
        if (link.closest(".pagination-links") || link.closest(".pagination-wrapper") || link.closest(".pagination-card-container")) {
            const href = link.getAttribute("href");
            if (!href || href === "#") return;
            e.preventDefault();
            fetchAndRender(href, { pushState: true });
        }
    });

    results.addEventListener("change", function (e) {
        const select = e.target;
        if (!select || !select.classList || !select.classList.contains("per-page-select")) return;

        const url = new URL(window.location.href);
        url.searchParams.set("per_page", select.value);
        url.searchParams.delete("page");
        fetchAndRender(url.toString(), { pushState: true });
    });

    // handle back/forward
    window.addEventListener("popstate", function () {
        fetchAndRender(window.location.href);
        const url = new URL(window.location.href);
        const keyword = url.searchParams.get("search") || "";
        input.value = keyword;
        updateSearchUI(keyword);
    });
}

function applyFilters() {
    const angkatanEl = document.getElementById("filterAngkatan");
    const tahunEl = document.getElementById("filterTahun");
    const linearEl = document.getElementById("filterLinear");
    const bidangEl = document.getElementById("filterBidang");
    const kelengkapanEl = document.getElementById("filterKelengkapan");
    const kelengkapanBagianEl = document.getElementById("filterKelengkapanBagian");

    const angkatan = angkatanEl ? angkatanEl.value : "";
    const tahunLulus = tahunEl ? tahunEl.value : "";
    const linearitas = linearEl ? linearEl.value : "";
    const bidang = bidangEl ? bidangEl.value : "";
    const kelengkapan = kelengkapanEl ? kelengkapanEl.value : "";
    const kelengkapanBagian = kelengkapanBagianEl ? kelengkapanBagianEl.value : "";

    const url = new URL(window.location.href);

    if (angkatan) url.searchParams.set("angkatan", angkatan);
    else url.searchParams.delete("angkatan");

    if (tahunLulus) url.searchParams.set("tahun_lulus", tahunLulus);
    else url.searchParams.delete("tahun_lulus");

    if (linearitas) url.searchParams.set("linearitas", linearitas);
    else url.searchParams.delete("linearitas");

    if (bidang) url.searchParams.set("bidang_pekerjaan", bidang);
    else url.searchParams.delete("bidang_pekerjaan");

    if (kelengkapan) url.searchParams.set("kelengkapan", kelengkapan);
    else url.searchParams.delete("kelengkapan");

    if (kelengkapanBagian) url.searchParams.set("kelengkapan_bagian", kelengkapanBagian);
    else url.searchParams.delete("kelengkapan_bagian");

    url.searchParams.delete("page");

    if (typeof window.alumniFetchAndRender === "function") {
        window.alumniFetchAndRender(url.toString(), { pushState: true });
    } else {
        window.location.assign(url.toString());
    }
}

function toggleFilterMenu() {
    const menu = document.getElementById("filterMenu");
    const btn = document.querySelector(".filter-btn");
    const isVisible = menu.style.display === "block";
    
    menu.style.display = isVisible ? "none" : "block";
    isVisible ? btn.classList.remove("active") : btn.classList.add("active");
}

window.onclick = function (event) {
    if (!event.target.closest(".filter-dropdown")) {
        const menu = document.getElementById("filterMenu");
        const btn = document.querySelector(".filter-btn");
        if (menu && menu.style.display === "block") {
            menu.style.display = "none";
            btn.classList.remove("active");
        }
    }
};

function resetFilters() {
    if (document.getElementById("filterAngkatan")) {
        document.getElementById("filterAngkatan").value = "";
    }
    document.getElementById("filterTahun").value = "";
    document.getElementById("filterLinear").value = "";
    document.getElementById("filterBidang").value = "";
    if (document.getElementById("filterKelengkapan")) {
        document.getElementById("filterKelengkapan").value = "";
    }
    if (document.getElementById("filterKelengkapanBagian")) {
        document.getElementById("filterKelengkapanBagian").value = "";
    }
    applyFilters();
}
