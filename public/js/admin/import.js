let excelRows = [];

const fileInput = document.getElementById("file-input");
const tableWrapper = document.getElementById("table-wrapper");
const table = document.getElementById("preview-table");
const tbody = table.querySelector("tbody");
const importBtn = document.getElementById("btn-import");
const fileNameDisplay = document.getElementById("file-name-display");
const dropArea = document.getElementById("drop-area");

// FUNGSI UTAMA: Preview Excel
function previewFile(file) {
    if (!file) return;

    // Update UI Nama File
    fileNameDisplay.innerText = file.name;
    fileNameDisplay.style.background = '#e0f2fe';

    let formData = new FormData();
    formData.append("file", file);

    // Tampilkan loading sederhana (opsional)
    fileNameDisplay.innerText = "Membaca file...";

    fetch("/admin/alumni/import-preview", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        },
        body: formData,
    })
    .then((res) => {
        if (!res.ok) throw new Error("Server error (500)");
        return res.json();
    })
    .then((data) => {
        excelRows = data;
        tbody.innerHTML = "";
        fileNameDisplay.innerText = file.name; // kembalikan nama file

        data.forEach((row) => {
            let tr = document.createElement("tr");
            row.forEach((col) => {
                let td = document.createElement("td");
                td.innerText = col ?? '-'; // antisipasi kolom kosong
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        // Tampilkan tabel dan tombol
        tableWrapper.style.display = "block";
        importBtn.style.display = "flex";
    })
    .catch((err) => {
        console.error(err);
        alert("Gagal membaca file Excel. Pastikan format benar.");
        fileNameDisplay.innerText = "Pilih File Alumni";
    });
}

// Event 1: Lewat Klik Input
fileInput.addEventListener("change", function () {
    if (this.files.length > 0) {
        previewFile(this.files[0]);
    }
});

// Event 2: Lewat Drag & Drop
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, (e) => e.preventDefault(), false);
});

dropArea.addEventListener('dragover', () => dropArea.classList.add('highlight'));
dropArea.addEventListener('dragleave', () => dropArea.classList.remove('highlight'));

dropArea.addEventListener('drop', (e) => {
    dropArea.classList.remove('highlight');
    let dt = e.dataTransfer;
    let files = dt.files;
    if (files.length > 0) {
        fileInput.files = files; // Sinkronkan ke input asli
        previewFile(files[0]);
    }
});

// Event 3: Tombol Simpan ke Database
if (importBtn) {
    importBtn.addEventListener("click", function () {
        this.disabled = true;
        this.innerText = "Sedang memproses...";

        let formData = new FormData();
        formData.append("rows", JSON.stringify(excelRows));

        fetch("/admin/alumni/import-store", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            },
            body: formData,
        })
        .then((res) => res.json())
        .then((data) => {
            const resultDiv = document.getElementById("import-result");
            resultDiv.style.display = "block";
            document.getElementById("result-text").innerHTML = `
                ✔ <b>${data.success}</b> data berhasil diimport.<br>
                ⚠ <b>${data.skip}</b> data NIM sudah ada (dilewati).
            `;
            
            this.style.display = "none"; // sembunyikan tombol jika sukses
            window.scrollTo(0, document.body.scrollHeight);
        })
        .catch((err) => {
            console.error(err);
            alert("Terjadi error saat menyimpan data.");
            this.disabled = false;
            this.innerText = "Mulai Import Data";
        });
    });
}