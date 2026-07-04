// =====================================================================
// LOGIKA SIDEBAR MENU (Statistik & Mahasiswa)
// =====================================================================
const btnOpenSidebar = document.getElementById("open-sidebar");
const btnCloseSidebar = document.getElementById("close-sidebar");
const mainSidebar = document.getElementById("main-sidebar");
const sidebarOverlay = document.getElementById("sidebar-overlay");

// Fungsi membuka sidebar
function bukaSidebar() {
    if (!mainSidebar || !sidebarOverlay) {
        return;
    }

    mainSidebar.classList.add("active");
    sidebarOverlay.classList.add("active");
    btnOpenSidebar?.classList.add("active");
}

// Fungsi menutup sidebar
function tutupSidebar() {
    if (!mainSidebar || !sidebarOverlay) {
        return;
    }

    mainSidebar.classList.remove("active");
    sidebarOverlay.classList.remove("active");
    btnOpenSidebar?.classList.remove("active");
}

// Pemicu tombol
btnOpenSidebar?.addEventListener("click", bukaSidebar);
btnCloseSidebar?.addEventListener("click", tutupSidebar);

// Klik area gelap untuk menutup
sidebarOverlay?.addEventListener("click", tutupSidebar);

// Konfirmasi logout untuk user maupun admin yang sedang membuka halaman peta
const sidebarLogoutForm = document.querySelector(".sidebar-logout-form");

sidebarLogoutForm?.addEventListener("submit", function (e) {
    e.preventDefault();

    const submitLogout = () =>
        HTMLFormElement.prototype.submit.call(sidebarLogoutForm);
    const isAdmin = window.appAuth?.isAdmin === true;
    const confirmationText = isAdmin
        ? "Anda akan keluar dari sesi admin."
        : "Anda akan keluar dari akun Anda.";

    // Tetap sediakan konfirmasi bawaan browser jika SweetAlert gagal dimuat
    if (typeof Swal === "undefined") {
        if (window.confirm("Apakah Anda yakin ingin logout?")) {
            submitLogout();
        }
        return;
    }

    Swal.fire({
        icon: "warning",
        title: "Yakin ingin logout?",
        text: confirmationText,
        showCancelButton: true,
        confirmButtonText: "Ya, logout",
        cancelButtonText: "Batal",
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#64748b",
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            submitLogout();
        }
    });
});

// Esc untuk menutup
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        tutupSidebar();
    }
});
