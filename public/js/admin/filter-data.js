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
});

// GABUNGKAN SEMUA FILTER DI SINI
function applyFilters() {
    const searchText = document.getElementById("alumniSearch").value.toLowerCase();
    const filterTahun = document.getElementById("filterTahun").value;
    const filterLinear = document.getElementById("filterLinear").value.toLowerCase();

    const paginations = document.querySelectorAll('.pagination-wrapper, .pagination-card-container');
    const isFiltering = searchText.length > 0 || filterTahun !== "" || filterLinear !== "";
    
    paginations.forEach(p => p.style.display = isFiltering ? 'none' : 'flex');

    // 1. Filter Card (Logika sudah benar)
    let visibleCards = 0;
    document.querySelectorAll(".data-card").forEach(card => {
        const fullText = card.innerText.toLowerCase();
        const textMatch = fullText.includes(searchText);
        const tahunMatch = filterTahun === "" || fullText.includes(filterTahun);
        const linearMatch = filterLinear === "" || fullText.includes(filterLinear);

        if (textMatch && tahunMatch && linearMatch) {
            card.style.display = "";
            visibleCards++;
        } else {
            card.style.display = "none";
        }
    });

    // 2. Filter Table (Logika sudah benar)
    let visibleRows = 0;
    document.querySelectorAll("#main-alumni-data tr").forEach(row => {
        const fullText = row.innerText.toLowerCase();
        const textMatch = fullText.includes(searchText);
        const tahunMatch = filterTahun === "" || fullText.includes(filterTahun);
        const linearMatch = filterLinear === "" || fullText.includes(filterLinear);

        if (textMatch && tahunMatch && linearMatch) {
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
    applyFilters();
}