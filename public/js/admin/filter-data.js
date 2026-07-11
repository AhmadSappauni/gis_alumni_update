let currentAlumniView = "list";

function switchView(viewType) {
    const cardWrapper = document.getElementById('card-view-wrapper');
    const listView = document.getElementById('list-view');
    const btnCard = document.getElementById('btn-card');
    const btnList = document.getElementById('btn-list');

    if (!cardWrapper || !listView || !btnCard || !btnList) return;

    if (viewType === 'list') {
        cardWrapper.style.display = 'none';
        listView.style.display = 'block';
        btnList.classList.add('active');
        btnCard.classList.remove('active');
        currentAlumniView = 'list';
    } else {
        cardWrapper.style.display = 'flex';
        listView.style.display = 'none';
        btnCard.classList.add('active');
        btnList.classList.remove('active');
        currentAlumniView = 'card';
    }
}

function openProfilModal(id) {
    const modal = document.getElementById(`modal-profil-${id}`);
    if (modal) modal.style.display = "flex";
}

function closeProfilModal(id) {
    const modal = document.getElementById(`modal-profil-${id}`);
    if (modal) modal.style.display = "none";
}

document.addEventListener("DOMContentLoaded", function () {
    switchView('list');

    initClientAlumniSearch();
});

function normalizeFilterValue(value) {
    const normalized = (value || "").toString().trim().toLowerCase();

    return typeof normalized.normalize === "function"
        ? normalized.normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        : normalized;
}

function initClientAlumniSearch() {
    const input = document.getElementById("alumniSearch");
    const form = document.getElementById("alumniSearchForm");
    const results = document.getElementById("alumniResults");
    const resetLink = document.getElementById("resetSearchLink");

    if (!input || !results) return;

    let activeController = null;
    let requestSeq = 0;
    let isComposing = false;

    function getFilterSignature(urlValue) {
        const url = new URL(urlValue, window.location.origin);
        url.searchParams.delete("search");
        url.searchParams.delete("page");
        url.searchParams.delete("per_page");

        return Array.from(url.searchParams.entries())
            .sort(([keyA, valueA], [keyB, valueB]) => {
                return keyA.localeCompare(keyB) || valueA.localeCompare(valueB);
            })
            .map(([key, value]) => `${key}=${value}`)
            .join("&");
    }

    let loadedFilterSignature = getFilterSignature(window.location.href);

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
        if (!resetLink) return;

        resetLink.style.display = trimmed ? "" : "none";

        const resetUrl = new URL(window.location.href);
        resetUrl.searchParams.delete("search");
        resetUrl.searchParams.delete("page");
        resetLink.href = resetUrl.toString();
    }

    function getPerPage() {
        const requested = Number(new URL(window.location.href).searchParams.get("per_page") || 40);

        return [40, 60, 80, 100].includes(requested) ? requested : 40;
    }

    function getRequestedPage() {
        const requested = Number(new URL(window.location.href).searchParams.get("page") || 1);

        return Number.isInteger(requested) && requested > 0 ? requested : 1;
    }

    function matchesKeyword(element, keyword) {
        if (!keyword) return true;

        const name = normalizeFilterValue(element.dataset.alumniName);
        const nim = normalizeFilterValue(element.dataset.alumniNim);

        return name.includes(keyword) || nim.includes(keyword);
    }

    function getPaginationTokens(currentPage, totalPages) {
        if (totalPages <= 7) {
            return Array.from({ length: totalPages }, (_, index) => index + 1);
        }

        const pages = new Set([1, totalPages, currentPage - 1, currentPage, currentPage + 1]);
        const sortedPages = Array.from(pages)
            .filter(page => page >= 1 && page <= totalPages)
            .sort((a, b) => a - b);
        const tokens = [];

        sortedPages.forEach((page, index) => {
            const previous = sortedPages[index - 1];
            if (previous && page - previous > 1) {
                tokens.push("...");
            }
            tokens.push(page);
        });

        return tokens;
    }

    function renderPagination(container, currentPage, totalPages) {
        if (!container || totalPages <= 1) {
            if (container) container.innerHTML = "";
            return;
        }

        const previousDisabled = currentPage === 1;
        const nextDisabled = currentPage === totalPages;
        const tokens = getPaginationTokens(currentPage, totalPages);
        const pageItems = tokens.map(token => {
            if (token === "...") {
                return '<li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>';
            }

            if (token === currentPage) {
                return `<li class="page-item active" aria-current="page"><span class="page-link">${token}</span></li>`;
            }

            return `<li class="page-item"><button type="button" class="page-link" data-alumni-page="${token}">${token}</button></li>`;
        }).join("");

        container.innerHTML = `
            <nav class="pagination-nav" role="navigation" aria-label="Navigasi halaman alumni">
                <ul class="pagination pagination-compact mb-0">
                    <li class="page-item${previousDisabled ? " disabled" : ""}"${previousDisabled ? ' aria-disabled="true"' : ""}>
                        ${previousDisabled
                            ? '<span class="page-link" aria-hidden="true">&lsaquo;</span>'
                            : `<button type="button" class="page-link" data-alumni-page="${currentPage - 1}" aria-label="Halaman sebelumnya">&lsaquo;</button>`}
                    </li>
                    ${pageItems}
                    <li class="page-item${nextDisabled ? " disabled" : ""}"${nextDisabled ? ' aria-disabled="true"' : ""}>
                        ${nextDisabled
                            ? '<span class="page-link" aria-hidden="true">&rsaquo;</span>'
                            : `<button type="button" class="page-link" data-alumni-page="${currentPage + 1}" aria-label="Halaman berikutnya">&rsaquo;</button>`}
                    </li>
                </ul>
            </nav>
        `;
    }

    function updateEmptyState(keyword, totalMatches) {
        const url = new URL(window.location.href);
        const filterKeys = [
            "angkatan",
            "tahun_lulus",
            "linearitas",
            "bidang_pekerjaan",
            "kelengkapan",
            "kelengkapan_bagian",
        ];
        const hasFilter = filterKeys.some(key => url.searchParams.has(key));
        let message = "Belum ada data alumni.";

        if (keyword && hasFilter) {
            message = "Tidak ada alumni yang cocok dengan pencarian dan filter saat ini.";
        } else if (keyword) {
            message = "Tidak ada alumni yang cocok dengan pencarian.";
        } else if (hasFilter) {
            message = "Tidak ada alumni untuk filter yang dipilih.";
        }

        const cardEmpty = results.querySelector("#card-empty");
        const listEmpty = results.querySelector("#list-empty");

        if (cardEmpty) {
            cardEmpty.style.display = totalMatches === 0 ? "block" : "none";
            const text = cardEmpty.querySelector("p");
            if (text) text.textContent = message;
        }

        if (listEmpty) {
            listEmpty.style.display = totalMatches === 0 ? "table-row-group" : "none";
            const text = listEmpty.querySelector("p");
            if (text) text.textContent = message;
        }
    }

    function syncClientUrl(keyword, page, perPage, pushState) {
        const url = new URL(window.location.href);

        if (keyword) url.searchParams.set("search", input.value.trim());
        else url.searchParams.delete("search");

        if (page > 1) url.searchParams.set("page", String(page));
        else url.searchParams.delete("page");

        url.searchParams.set("per_page", String(perPage));

        const method = pushState ? "pushState" : "replaceState";
        window.history[method]({}, "", url.toString());
        updateSearchUI(keyword);
    }

    function renderClientResults({
        requestedPage = null,
        resetPage = false,
        syncUrl = false,
        pushState = false,
        clearBulkSelection = true,
    } = {}) {
        const cards = Array.from(results.querySelectorAll("#card-view > .data-card"));
        const rows = Array.from(results.querySelectorAll("#main-alumni-data > tr"));
        const sourceItems = cards.length ? cards : rows;
        const keyword = normalizeFilterValue(input.value);
        const matchedItems = sourceItems.filter(item => matchesKeyword(item, keyword));
        const matchedIds = new Set(matchedItems.map(item => item.dataset.alumniId));
        const perPage = getPerPage();
        const totalMatches = matchedItems.length;
        const totalPages = Math.max(1, Math.ceil(totalMatches / perPage));
        const rawPage = resetPage
            ? 1
            : (requestedPage === null ? getRequestedPage() : Number(requestedPage));
        const currentPage = Math.min(Math.max(1, rawPage || 1), totalPages);
        const startIndex = (currentPage - 1) * perPage;
        const visibleIds = new Set(
            matchedItems
                .slice(startIndex, startIndex + perPage)
                .map(item => item.dataset.alumniId)
        );

        cards.forEach(card => {
            const isMatch = matchedIds.has(card.dataset.alumniId);
            card.dataset.clientSearchMatch = isMatch ? "true" : "false";
            card.hidden = !visibleIds.has(card.dataset.alumniId);
        });

        rows.forEach(row => {
            const isMatch = matchedIds.has(row.dataset.alumniId);
            row.dataset.clientSearchMatch = isMatch ? "true" : "false";
            row.hidden = !visibleIds.has(row.dataset.alumniId);
        });

        const from = totalMatches > 0 ? startIndex + 1 : 0;
        const to = totalMatches > 0 ? Math.min(startIndex + perPage, totalMatches) : 0;

        results.querySelectorAll(".pagination-showing").forEach(element => {
            element.textContent = `Showing ${from} to ${to} of ${totalMatches} rows`;
        });

        results.querySelectorAll(".per-page-select").forEach(select => {
            select.value = String(perPage);
        });

        results.querySelectorAll("[data-client-pagination]").forEach(container => {
            renderPagination(container, currentPage, totalPages);
        });

        const bulkActionBar = results.querySelector("#bulk-action-bar");
        if (bulkActionBar) {
            bulkActionBar.dataset.total = String(totalMatches);
        }

        updateEmptyState(keyword, totalMatches);

        if (syncUrl) {
            syncClientUrl(keyword, currentPage, perPage, pushState);
        } else {
            updateSearchUI(keyword);
        }

        if (clearBulkSelection && typeof window.refreshBulkDeleteAlumni === "function") {
            window.refreshBulkDeleteAlumni({ clearSelection: true });
        }
    }

    async function fetchAndRender(url, { pushState = false, throwOnError = false } = {}) {
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

            if (data && typeof data.html === "string") {
                results.innerHTML = data.html;
            }

            const nextUrl = new URL(
                (typeof url === "string") ? url : window.location.href,
                window.location.origin
            );
            const liveKeyword = input.value.trim();
            if (liveKeyword) nextUrl.searchParams.set("search", liveKeyword);
            else nextUrl.searchParams.delete("search");

            if (pushState) {
                window.history.pushState({}, "", nextUrl.toString());
            } else {
                window.history.replaceState({}, "", nextUrl.toString());
            }
            loadedFilterSignature = getFilterSignature(nextUrl.toString());

            switchView(currentAlumniView);

            renderClientResults({
                requestedPage: Number(nextUrl.searchParams.get("page") || 1),
                clearBulkSelection: false,
            });

            // re-init fitur yang bergantung ke DOM hasil
            if (typeof window.initBulkDeleteAlumni === "function") {
                window.initBulkDeleteAlumni();
            }
            if (typeof window.initModalHandlers === "function") {
                window.initModalHandlers();
            }
        } catch (e) {
            if (throwOnError) {
                throw e;
            }
            if (e && e.name === "AbortError") return;
            // fallback: kalau error, biar user tetap bisa jalan via full reload
            window.location.assign(url);
        } finally {
            setLoading(false);
        }
    }

    function triggerClientSearch() {
        renderClientResults({
            resetPage: true,
            syncUrl: true,
        });
    }

    // expose untuk applyFilters()/resetFilters() (filter popup)
    window.alumniFetchAndRender = function (url, opts) {
        return fetchAndRender(url, opts || {});
    };

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") e.preventDefault();
    });

    input.addEventListener("input", function () {
        if (!isComposing) triggerClientSearch();
    });

    input.addEventListener("compositionstart", function () {
        isComposing = true;
    });

    input.addEventListener("compositionend", function () {
        isComposing = false;
        triggerClientSearch();
    });

    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            triggerClientSearch();
        });

        form.querySelector('button[type="button"]')?.addEventListener("click", triggerClientSearch);
    }

    if (resetLink) {
        resetLink.addEventListener("click", function (e) {
            e.preventDefault();
            input.value = "";
            renderClientResults({
                resetPage: true,
                syncUrl: true,
                pushState: true,
            });
            input.focus();
        });
    }

    // Pagination dan jumlah baris diproses dari data yang sudah ada di browser.
    results.addEventListener("click", function (e) {
        const pageButton = e.target.closest("[data-alumni-page]");
        if (!pageButton) return;

        renderClientResults({
            requestedPage: Number(pageButton.dataset.alumniPage),
            syncUrl: true,
            pushState: true,
        });
    });

    results.addEventListener("change", function (e) {
        const select = e.target;
        if (!select || !select.classList || !select.classList.contains("per-page-select")) return;

        const url = new URL(window.location.href);
        url.searchParams.set("per_page", select.value);
        url.searchParams.delete("page");
        window.history.pushState({}, "", url.toString());

        renderClientResults({
            requestedPage: 1,
            clearBulkSelection: true,
        });
    });

    // handle back/forward
    window.addEventListener("popstate", function () {
        const url = new URL(window.location.href);
        const keyword = url.searchParams.get("search") || "";
        input.value = keyword;
        updateSearchUI(keyword);

        if (getFilterSignature(url.toString()) === loadedFilterSignature) {
            renderClientResults({
                requestedPage: Number(url.searchParams.get("page") || 1),
            });
            return;
        }

        fetchAndRender(url.toString());
    });

    renderClientResults({ clearBulkSelection: false });
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
