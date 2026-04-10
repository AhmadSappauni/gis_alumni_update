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
    if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
        alert("File harus format Excel (.xlsx atau .xls)");
        return;
    }
    if (!file) return;

    fileNameDisplay.innerText = "Membaca file...";
    fileNameDisplay.style.background = '#e0f2fe';

    let formData = new FormData();
    formData.append("file", file);

    fetch("/admin/alumni/import-preview", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        },
        body: formData,
    })
    .then(async (res) => {
        if (!res.ok) {
            const text = await res.text();
            throw new Error(text);
        }
        return res.json();
    })
    .then((data) => {
        excelRows = data.map(row => {
            if (!row) return row;

            return row.map(item => {
                if (typeof item === 'number' && item > 40000) {
                    let date = new Date((item - 25569) * 86400 * 1000);
                    return date.getFullYear();
                }
                return typeof item === 'string' ? item.trim() : item;
            });
        });
        tbody.innerHTML = "";
        fileNameDisplay.innerText = file.name;

        data.forEach((row) => {
            if (!row || !row[0]) return;
            row = row.map((item, index) => {

            // 🔥 HANDLE NaN KHUSUS NO HP (index 5)
            if (index === 5) {
                if (!item || Number.isNaN(item)) return null;
                return item.toString();
            }

            // 🔥 HANDLE NaN umum
            if (
                item === null ||
                item === undefined ||
                item === '' ||
                item === 'NaN' ||
                Number.isNaN(item)
            ) {
                return null;
            }

            // tanggal excel
            if (typeof item === 'number' && item > 40000) {
                let date = new Date((item - 25569) * 86400 * 1000);
                return date.getFullYear();
            }

            return typeof item === 'string' ? item.trim() : item;
        });
            console.log("FULL ROW:", row);
            let tr = document.createElement("tr");
            
            // Kolom yang ditampilkan disesuaikan dengan header di Blade kamu
            const columnsToShow = [
                row[0],  // NIM
                row[1],  // Nama
                formatTanggal(row[7]),  // Tahun Wisuda
                row[10], // Perusahaan
                row[12], // Jabatan
                row[4],  // Alamat Instansi
                row[3],  // Status
                row[2],  // Email
                row[5],  // No HP
                formatTanggal(row[6]),  // Yudisium
                row[13], // TOEFL
                row[14],  // Masa Tunggu (hari) → kalau ada
                formatRupiah(fixGaji(row[11])),  // Gaji
                row[15], // 🔥 Linearitas
                row[16]  // 🔥 Studi lanjut
            ];

            columnsToShow.forEach((col) => {
                let td = document.createElement("td");
                td.innerText = (col === null || col === undefined || col === '') ? '-' : col;
                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });

        tableWrapper.style.display = "block";
        importBtn.style.display = "flex";
    })
    .catch((err) => {
        console.error(err);
        alert("Gagal membaca file Excel. Pastikan format benar.");
        fileNameDisplay.innerText = "Pilih File Alumni";
    });
}

function formatTanggal(val) {
    if (!val) return '-';

    // 🛑 kalau null/undefined → skip
    if (val === null || val === undefined) return '-';

    // 🟢 format ISO (2024-02-20T...)
    if (typeof val === 'string' && val.includes('T')) {
        let date = new Date(val);
        return !isNaN(date) ? date.getFullYear() : '-';
    }

    // 🟢 format DD/MM/YYYY
    if (typeof val === 'string' && val.includes('/')) {
        let parts = val.split('/');
        if (parts.length === 3) {
            return parts[2]; // ambil tahun
        }
    }

    // 🟢 kalau sudah angka (hasil konversi excel)
    if (typeof val === 'number') {
        return val;
    }

    return val;
}

function formatRupiah(num) {
    if (!num) return '-';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function fixGaji(val) {
    if (!val) return 0;

    // kalau string → bersihkan dulu
    if (typeof val === 'string') {
        val = val.replace(/\./g, '').replace(/,/g, '');
        val = parseInt(val);
    }

    // kalau angka kecil → kemungkinan ribuan
    if (val > 0 && val < 1000000) {
        return val * 1000;
    }

    return val;
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
        fileInput.files = files;
        previewFile(files[0]);
    }
});

// Event 3: Tombol Simpan ke Database
if (importBtn) {
    importBtn.addEventListener("click", function () {
        if (!excelRows || excelRows.length === 0) {
            Swal.fire('Error', 'Tidak ada data untuk diimport.', 'error');
            return;
        }
        Swal.fire({
            title: 'Mulai Import?',
            text: `Sistem akan memproses ${excelRows.length} data dan mencari lokasi otomatis.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004a87',
            confirmButtonText: 'Ya, Mulai!'
        }).then((result) => {
            if (result.isConfirmed) {
                this.disabled = true;
                this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Sedang memproses lokasi...`;

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
                        ✔ <b>${data.success}</b> data berhasil diimport & dipetakan.<br>
                        ⚠ <b>${data.skip}</b> data NIM sudah ada (dilewati).
                    `;
                    
                    this.style.display = "none";
                    Swal.fire('Selesai!', 'Data berhasil disimpan.', 'success');
                    
                    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                })
                .catch((err) => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal menyimpan data.', 'error');
                    this.disabled = false;
                    this.innerText = "Mulai Import Data";
                });
            }
        });
    });
}