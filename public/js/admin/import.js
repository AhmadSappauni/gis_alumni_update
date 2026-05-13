let excelRows = [];
let previewHeaders = [];

const fileInput = document.getElementById("file-input");
const tableWrapper = document.getElementById("table-wrapper");
const table = document.getElementById("preview-table");
const tbody = table.querySelector("tbody");
const theadRow = document.getElementById("preview-head-row");
const importBtn = document.getElementById("btn-import");
const progressWrap = document.getElementById("import-progress");
const progressText = document.getElementById("import-progress-text");
const progressBar = document.getElementById("import-progress-bar");
const progressSubtext = document.getElementById("import-progress-subtext");
const fileNameDisplay = document.getElementById("file-name-display");
const dropArea = document.getElementById("drop-area");
const cancelBtn = document.getElementById("btn-cancel-import");
const resultDiv = document.getElementById("import-result");
const resultText = document.getElementById("result-text");
const showTemplateBtn = document.getElementById("btn-show-template");
const templateModal = document.getElementById("template-modal");

function openTemplateModal() {
    if (!templateModal) return;
    templateModal.classList.add("is-open");
    templateModal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeTemplateModal() {
    if (!templateModal) return;
    templateModal.classList.remove("is-open");
    templateModal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

if (showTemplateBtn) {
    showTemplateBtn.addEventListener("click", openTemplateModal);
}

if (templateModal) {
    templateModal.querySelectorAll("[data-template-close]").forEach((el) => {
        el.addEventListener("click", closeTemplateModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && templateModal.classList.contains("is-open")) {
            closeTemplateModal();
        }
    });
}

function getVal(row, key) {
    if (!row) return null;
    return row[key] ?? null;
}

function cleanCellValue(key, item) {
    if (item === null || item === undefined || item === '' || item === 'NaN') return null;

    return typeof item === 'string' ? item.trim() : item;
}

function titleCaseFromSnake(str) {
    return String(str)
        .replace(/_/g, ' ')
        .split(' ')
        .filter(Boolean)
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
}

function headerLabel(key) {
    const k = String(key || '').toLowerCase();
    const map = {
        nim: 'NIM',
        no_hp: 'No HP',
        nama_lengkap: 'Nama Lengkap',
        nama_perusahaan: 'Perusahaan',
        alamat_lengkap_perusahaan: 'Alamat Instansi',
        gaji_nominal: 'Gaji',
    };
    return map[k] || titleCaseFromSnake(k);
}

function maybeExcelSerialToDate(val) {
    if (typeof val !== 'number') return null;
    if (val < 20000 || val > 90000) return null;
    const date = new Date((val - 25569) * 86400 * 1000);
    return isNaN(date) ? null : date;
}

function formatCellByKey(key, val) {
    const k = String(key || '').toLowerCase();

    if (val === null || val === undefined || val === '') return '-';

    if (k.startsWith('tahun_') || k.endsWith('_tahun')) {
        const asYear = formatTanggal(val);
        return asYear || '-';
    }

    if (k.startsWith('tanggal_') || k.endsWith('_tanggal')) {
        const serialDate = maybeExcelSerialToDate(val);
        if (serialDate) return serialDate.toISOString().slice(0, 10);
        if (typeof val === 'string') return val.trim() || '-';
        return String(val);
    }

    if (k === 'gaji' || k === 'gaji_nominal') {
        return formatRupiah(fixGaji(val));
    }

    return typeof val === 'string' ? (val.trim() || '-') : String(val);
}

function resetImportUI() {
    excelRows = [];
    previewHeaders = [];

    try {
        // reset input file supaya bisa pilih file yang sama lagi
        fileInput.value = '';
    } catch (_) {
        // ignore
    }

    if (tbody) tbody.innerHTML = "";
    if (theadRow) theadRow.innerHTML = "";

    if (tableWrapper) tableWrapper.style.display = "none";
    if (importBtn) {
        importBtn.style.display = "none";
        importBtn.disabled = false;
        importBtn.innerText = "Mulai Import Data";
    }

    if (progressWrap) progressWrap.style.display = "none";
    if (progressText) progressText.innerText = "0/0";
    if (progressBar) progressBar.style.width = "0%";
    if (progressSubtext) progressSubtext.innerText = "";

    if (resultDiv) resultDiv.style.display = "none";
    if (resultText) resultText.innerHTML = "";

    if (fileNameDisplay) {
        fileNameDisplay.innerText = "Pilih File Alumni";
        fileNameDisplay.style.background = "";
    }

    if (cancelBtn) cancelBtn.style.display = "none";
}

// FUNGSI UTAMA: Preview Excel
function previewFile(file) {
    if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
        alert("File harus format Excel (.xlsx atau .xls)");
        return;
    }
    if (!file) return;

    fileNameDisplay.innerText = "Membaca file...";
    fileNameDisplay.style.background = '#e0f2fe';
    if (cancelBtn) cancelBtn.style.display = "inline-flex";

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
            const rows = Array.isArray(data) ? data : (data?.rows || []);
            const headers = Array.isArray(data?.headers) ? data.headers : [];

            // backend mengirim array of object (key = header excel)
            excelRows = (rows || []).map((row) => {
                if (!row || typeof row !== 'object') return row;
                const cleaned = {};
                Object.keys(row).forEach((k) => {
                    cleaned[k] = cleanCellValue(k, row[k]);
                });
                return cleaned;
            });

            previewHeaders = (headers && headers.length > 0)
                ? headers
                : (excelRows[0] && typeof excelRows[0] === 'object' ? Object.keys(excelRows[0]) : []);

            tbody.innerHTML = "";
            if (theadRow) theadRow.innerHTML = "";
            fileNameDisplay.innerText = file.name;

            if (theadRow && previewHeaders.length > 0) {
                previewHeaders.forEach((h) => {
                    const th = document.createElement("th");
                    th.innerText = headerLabel(h);
                    theadRow.appendChild(th);
                });
            }

            excelRows.forEach((row) => {
                if (!row || typeof row !== 'object') return;
                if (!getVal(row, 'nim')) return;

                let tr = document.createElement("tr");

                (previewHeaders || []).forEach((h) => {
                    let td = document.createElement("td");
                    td.innerText = formatCellByKey(h, getVal(row, h));
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });

            tableWrapper.style.display = "block";
            importBtn.style.display = "flex";
            if (cancelBtn) cancelBtn.style.display = "inline-flex";
        })
        .catch((err) => {
            console.error(err);
            alert("Gagal membaca file Excel. Pastikan format benar.");
            resetImportUI();
        });
}

function formatTanggal(val) {
    if (!val) return '-';

    if (val === null || val === undefined) return '-';

    // format ISO (2024-02-20T...)
    if (typeof val === 'string' && val.includes('T')) {
        let date = new Date(val);
        return !isNaN(date) ? date.getFullYear() : '-';
    }

    // format DD/MM/YYYY
    if (typeof val === 'string' && val.includes('/')) {
        let parts = val.split('/');
        if (parts.length === 3) {
            return parts[2]; // ambil tahun
        }
    }

    // kalau sudah angka
    if (typeof val === 'number') {
        // excel serial date -> ambil tahun
        if (val > 20000 && val < 90000) {
            const date = new Date((val - 25569) * 86400 * 1000);
            return !isNaN(date) ? date.getFullYear() : '-';
        }
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

    // kalau string -> bersihkan dulu
    if (typeof val === 'string') {
        val = val.replace(/\./g, '').replace(/,/g, '');
        val = parseInt(val);
    }

    // kalau angka kecil -> kemungkinan ribuan
    if (val > 0 && val < 1000000) {
        return val * 1000;
    }

    return val;
}

function setImportProgress(done, total, subtext) {
    if (!progressWrap || !progressText || !progressBar) return;
    progressWrap.style.display = "block";
    const safeTotal = total || 0;
    const safeDone = Math.min(done || 0, safeTotal);
    const pct = safeTotal > 0 ? Math.round((safeDone / safeTotal) * 100) : 0;

    progressText.innerText = `${safeDone}/${safeTotal} (${pct}%)`;
    progressBar.style.width = `${pct}%`;
    if (progressSubtext) progressSubtext.innerText = subtext || '';
}

async function postImportBatch(batchRows) {
    let formData = new FormData();
    formData.append("rows", JSON.stringify(batchRows));

    const res = await fetch("/admin/alumni/import-store", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        },
        body: formData,
    });

    const text = await res.text();
    let payload = null;
    try {
        payload = text ? JSON.parse(text) : null;
    } catch (_) {
        // non-JSON response
    }

    if (!res.ok) {
        const msg = payload?.message || `Request gagal (${res.status}).`;
        throw new Error(msg);
    }

    return payload ?? {};
}

// Event 1: Lewat Klik Input
fileInput.addEventListener("change", function () {
    if (this.files.length > 0) {
        previewFile(this.files[0]);
    }
});

// Event: Tombol cancel/reset
if (cancelBtn) {
    cancelBtn.addEventListener("click", function (e) {
        e.preventDefault();
        resetImportUI();
    });
}

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
            if (!result.isConfirmed) {
                return;
            }

            const total = excelRows.length;
            const batchSize = 10;

            this.disabled = true;
            this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Sedang memproses...`;
            setImportProgress(0, total, 'Memulai import...');

            (async () => {
                let processed = 0;
                let success = 0;
                let skip = 0;
                let failed = 0;
                let noMap = 0;

                for (let i = 0; i < total; i += batchSize) {
                    const batch = excelRows.slice(i, i + batchSize);
                    setImportProgress(processed, total, `Memproses data ${i + 1}-${Math.min(i + batchSize, total)}...`);

                    const data = await postImportBatch(batch);
                    success += Number(data?.success || 0);
                    skip += Number(data?.skip || 0);
                    failed += Number(data?.failed || 0);
                    noMap += Number(data?.no_map || 0);

                    processed = Math.min(i + batch.length, total);
                    setImportProgress(processed, total, 'Menyimpan & memetakan lokasi...');
                }

                setImportProgress(total, total, 'Selesai.');

                resultDiv.style.display = "block";

                document.getElementById("result-text").innerHTML = `
                    ✔ <b>${success}</b> data berhasil diimport & dipetakan.<br>
                    ⚠ <b>${skip}</b> data NIM sudah ada (dilewati).<br>
                    ✖ <b>${failed}</b> data gagal diproses.<br>
                    🗺️ <b>${noMap}</b> data tersimpan tapi belum punya koordinat (belum muncul di peta).
                `;

                Swal.fire('Selesai!', 'Import selesai diproses.', 'success');
                this.style.display = "none";
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            })().catch((err) => {
                console.error(err);
                Swal.fire('Error', err?.message || 'Gagal menyimpan data.', 'error');
                this.disabled = false;
                this.innerText = "Mulai Import Data";
            });
        });
    });
}
