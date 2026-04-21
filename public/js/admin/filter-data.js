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
    const savedPref = localStorage.getItem("alumniViewPref");
    if (savedPref) {
        switchView(savedPref);
    }

    initDynamicFilterOptions();
});

function normalizeFilterValue(value) {
    return (value || "").toString().trim().toLowerCase();
}

function populateSelectOptions(selectId, values, defaultLabel) {
    const select = document.getElementById(selectId);

    if (!select) {
        return;
    }

    select.innerHTML = `<option value="">${defaultLabel}</option>`;

    values.forEach(value => {
        const option = document.createElement("option");
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    });
}

function initDynamicFilterOptions() {
    const rows = Array.from(document.querySelectorAll("#main-alumni-data tr"));

    const tahunSet = new Set();
    const bidangSet = new Set();

    rows.forEach(row => {
        const tahun = row.dataset.tahun || "";
        const bidang = row.dataset.bidang || "";

        if (tahun) {
            tahunSet.add(tahun);
        }

        if (bidang) {
            bidangSet.add(bidang);
        }
    });

    const tahunList = Array.from(tahunSet).sort((a, b) => Number(b) - Number(a));
    const bidangList = Array.from(bidangSet).sort((a, b) => a.localeCompare(b, 'id'));

    populateSelectOptions("filterTahun", tahunList, "Semua Tahun");
    populateSelectOptions("filterBidang", bidangList, "Semua Bidang");
}

function applyFilters() {
    const searchText = normalizeFilterValue(document.getElementById("alumniSearch").value);
    const filterTahun = document.getElementById("filterTahun").value;
    const filterLinear = normalizeFilterValue(document.getElementById("filterLinear").value);
    const filterBidang = normalizeFilterValue(document.getElementById("filterBidang").value);

    const paginations = document.querySelectorAll('.pagination-wrapper, .pagination-card-container');
    const isFiltering =
        searchText.length > 0 ||
        filterTahun !== "" ||
        filterLinear !== "" ||
        filterBidang !== "";
    
    paginations.forEach(p => p.style.display = isFiltering ? 'none' : 'flex');

    let visibleCards = 0;
    document.querySelectorAll(".data-card").forEach(card => {
        const fullText = normalizeFilterValue(card.innerText);
        const cardTahun = card.dataset.tahun || "";
        const cardLinear = normalizeFilterValue(card.dataset.linearitas);
        const cardBidang = normalizeFilterValue(card.dataset.bidang);

        const textMatch = fullText.includes(searchText);
        const tahunMatch = filterTahun === "" || cardTahun === filterTahun;
        const linearMatch = filterLinear === "" || cardLinear === filterLinear;
        const bidangMatch = filterBidang === "" || cardBidang === filterBidang;

        if (textMatch && tahunMatch && linearMatch && bidangMatch) {
            card.style.display = "";
            visibleCards++;
        } else {
            card.style.display = "none";
        }
    });

    let visibleRows = 0;
    document.querySelectorAll("#main-alumni-data tr").forEach(row => {
        const fullText = normalizeFilterValue(row.innerText);
        const rowTahun = row.dataset.tahun || "";
        const rowLinear = normalizeFilterValue(row.dataset.linearitas);
        const rowBidang = normalizeFilterValue(row.dataset.bidang);

        const textMatch = fullText.includes(searchText);
        const tahunMatch = filterTahun === "" || rowTahun === filterTahun;
        const linearMatch = filterLinear === "" || rowLinear === filterLinear;
        const bidangMatch = filterBidang === "" || rowBidang === filterBidang;

        if (textMatch && tahunMatch && linearMatch && bidangMatch) {
            row.style.display = "";
            visibleRows++;
        } else {
            row.style.display = "none";
        }
    });

    // --- PERBAIKAN LOGIKA EMPTY STATE ---
    const cardEmpty = document.getElementById("card-empty");
    const listEmpty = document.getElementById("list-empty");
    const mainTableBody = document.getElementById("main-alumni-data"); // Gunakan ID baru

    if (cardEmpty) {
        cardEmpty.style.display = (visibleCards === 0) ? "block" : "none";
    }

    if (listEmpty && mainTableBody) {
        // Jika sedang mencari DAN tidak ada hasil
        if (isFiltering && visibleRows === 0) {
            mainTableBody.style.display = "none";
            listEmpty.style.display = "table-row-group"; 
        } 
        // Jika sedang mencari DAN ada hasil, atau sedang tidak mencari
        else {
            mainTableBody.style.display = "table-row-group";
            listEmpty.style.display = "none";
        }
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
    document.getElementById("alumniSearch").value = "";
    document.getElementById("filterTahun").value = "";
    document.getElementById("filterLinear").value = "";
    document.getElementById("filterBidang").value = "";
    applyFilters();
}
