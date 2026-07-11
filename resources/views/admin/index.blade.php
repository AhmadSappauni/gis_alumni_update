@extends('admin.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/filter.css') }}">
<style >
/* Membuat area scroll halus */
.table-scroll {
    max-height: 480px;
    overflow-y: auto;
}

#list-view {
    overflow: visible; /* penting */
}
.table-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.table-scroll::-webkit-scrollbar {
    width: 6px;
}
.table-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 74, 135, 0.1);
    border-radius: 10px;
}
.table-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 74, 135, 0.3);
}

/* Memastikan header tabel tetap menempel di atas saat di-scroll */
.alumni-table thead th {
    position: sticky;
    top: 0;
    background: #f1f5f9; /* Sesuaikan dengan warna background card */
    box-shadow: inset 0 -1px 0 rgba(0,0,0,0.05);
}


</style>
@endpush

@section('content')
    <header class="top-header glass-panel">
        <div class="header-left">
            <h1>Data Alumni</h1>
        </div>
        
        <div class="header-center">
            <div class="search-wrapper">
                <form id="alumniSearchForm" method="GET" action="{{ route('admin.alumni.index') }}" class="search-box-mini" style="display:flex; align-items:center; gap:8px;">
                    @foreach (request()->except(['search', 'page']) as $key => $value)
                        @if (is_scalar($value) && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <button type="button" title="Cari" style="all: unset; cursor: pointer; display:flex; align-items:center;">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    <input type="text" id="alumniSearch" name="search" value="{{ request('search') }}"
                        placeholder="Cari alumni berdasarkan nama atau NIM..." autocomplete="off">
                </form>

                <a id="resetSearchLink" href="{{ route('admin.alumni.index', request()->except(['search', 'page'])) }}"
                    style="margin-left: 8px; font-size: 12px; font-weight: 700; color: #004a87; text-decoration: none; {{ request()->filled('search') ? '' : 'display:none;' }}">
                    Reset
                </a>
                
                <div class="filter-dropdown">
                    <button class="filter-btn" onclick="toggleFilterMenu()" title="Filter Data">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                    </button>
                    <div id="filterMenu" class="filter-menu glass-panel">
                        <div class="filter-group">
                            <label>Angkatan</label>
                            <select id="filterAngkatan" onchange="applyFilters()">
                                <option value="">Semua Angkatan</option>
                                @foreach (($angkatanOptions ?? collect()) as $angkatan)
                                    <option value="{{ $angkatan }}" {{ (string) request('angkatan') === (string) $angkatan ? 'selected' : '' }}>{{ $angkatan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Tahun Lulus</label>
                            <select id="filterTahun" onchange="applyFilters()">
                                <option value="">Semua Tahun</option>
                                @foreach (($tahunLulusOptions ?? collect()) as $tahun)
                                    <option value="{{ $tahun }}" {{ (string) request('tahun_lulus') === (string) $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Linearitas Pekerjaan</label>
                            <select id="filterLinear" onchange="applyFilters()">
                                <option value="">Semua Linearitas</option>
                                <option value="Sangat Erat" {{ request('linearitas') === 'Sangat Erat' ? 'selected' : '' }}>Sangat Erat</option>
                                <option value="Erat" {{ request('linearitas') === 'Erat' ? 'selected' : '' }}>Erat</option>
                                <option value="Cukup Erat" {{ request('linearitas') === 'Cukup Erat' ? 'selected' : '' }}>Cukup Erat</option>
                                <option value="Kurang Erat" {{ request('linearitas') === 'Kurang Erat' ? 'selected' : '' }}>Kurang Erat</option>
                                <option value="Tidak Erat" {{ request('linearitas') === 'Tidak Erat' ? 'selected' : '' }}>Tidak Erat</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Bidang Pekerjaan</label>
                            <select id="filterBidang" onchange="applyFilters()">
                                <option value="">Semua Bidang</option>
                                @foreach (($bidangOptions ?? collect()) as $bidang)
                                    <option value="{{ $bidang }}" {{ (string) request('bidang_pekerjaan') === (string) $bidang ? 'selected' : '' }}>{{ $bidang }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Kelengkapan Data</label>
                            <select id="filterKelengkapan" onchange="applyFilters()">
                                <option value="">Semua</option>
                                <option value="complete" {{ request('kelengkapan') === 'complete' ? 'selected' : '' }}>Data Lengkap</option>
                                <option value="incomplete" {{ request('kelengkapan') === 'incomplete' ? 'selected' : '' }}>Belum Lengkap</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Bagian Belum Lengkap</label>
                            <select id="filterKelengkapanBagian" onchange="applyFilters()">
                                <option value="">Semua Bagian</option>
                                <option value="data_diri" {{ request('kelengkapan_bagian') === 'data_diri' ? 'selected' : '' }}>Data Diri</option>
                                <option value="pekerjaan" {{ request('kelengkapan_bagian') === 'pekerjaan' ? 'selected' : '' }}>Pekerjaan</option>
                                <option value="studi_lanjut" {{ request('kelengkapan_bagian') === 'studi_lanjut' ? 'selected' : '' }}>Studi Lanjut</option>
                            </select>
                        </div>
                        <button onclick="resetFilters()" id="reset-filter">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-right">
            <div class="view-switcher">
                <button onclick="switchView('card')" id="btn-card" class="view-btn" title="Tampilan Card">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 14a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1v-5zM14 14a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1v-5z"></path>
                    </svg>
                </button>
                <button onclick="switchView('list')" id="btn-list" class="view-btn active" title="Tampilan List">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div id="alumniResults">
        @include('admin.komponen.content')
    </div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/filter-data.js') }}"></script>
<script>
function confirmDelete(id, nim,nama,) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data alumni " + nama + " (NIM: " + nim + ") akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Buat form dinamis
            let form = document.createElement('form');
            form.action = `/admin/alumni/${id}`;
            form.method = 'POST';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    })
}

function initBulkDeleteAlumni() {
    const checkboxes = Array.from(document.querySelectorAll('.alumni-bulk-checkbox'));
    const selectAllTop = document.getElementById('select-all-alumni');
    const actionBar = document.getElementById('bulk-action-bar');
    const selectLabel = document.getElementById('bulk-select-label');
    const headerCell = document.getElementById('bulk-checkbox-header');
    const rowCells = Array.from(document.querySelectorAll('.bulk-checkbox-cell'));
    const countLabel = document.getElementById('selected-alumni-count');
    const deleteBtn = document.getElementById('btn-delete-selected');
    const form = document.getElementById('bulk-delete-form');
    const inputs = document.getElementById('bulk-delete-inputs');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!checkboxes.length || !selectAllTop || !actionBar || !selectLabel || !headerCell || !countLabel || !deleteBtn || !form || !inputs) {
        return;
    }

    let selectAllGlobal = false;

    function selectableCheckboxes() {
        return checkboxes.filter(cb => {
            const row = cb.closest('tr');
            return !row || row.dataset.clientSearchMatch !== 'false';
        });
    }

    function currentTotal() {
        return Number(actionBar.dataset.total || selectableCheckboxes().length);
    }

    function updateBulkDeleteState() {
        const selectable = selectableCheckboxes();
        const selected = selectable.filter(cb => cb.checked);
        const totalAll = currentTotal();
        const selectedCount = selectAllGlobal ? (totalAll || selected.length) : selected.length;
        const allChecked = selectAllGlobal || (selected.length > 0 && selected.length === selectable.length);

        countLabel.textContent = `${selectedCount} dipilih`;
        deleteBtn.disabled = selectedCount === 0;
        deleteBtn.style.cursor = selectedCount === 0 ? 'not-allowed' : 'pointer';
        deleteBtn.style.opacity = selectedCount === 0 ? '.6' : '1';

        selectAllTop.checked = allChecked;

        if (selectAllGlobal) {
            selectAllTop.indeterminate = false;
        } else {
            selectAllTop.indeterminate = selected.length > 0 && selected.length < selectable.length;
        }
    }

    function setAllChecked(checked) {
        selectableCheckboxes().forEach(cb => {
            cb.checked = checked;
        });

        updateBulkDeleteState();
    }

    async function enableSelectAllGlobal() {
        const totalAll = currentTotal();
        const countText = totalAll ? `${totalAll}` : 'semua';
        const result = await Swal.fire({
            title: 'Pilih semua data?',
            text: `Ini akan memilih ${countText} data alumni di semua halaman.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004a87',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Pilih Semua',
            cancelButtonText: 'Batal',
            reverseButtons: true
        });

        if (!result.isConfirmed) {
            selectAllTop.checked = false;
            return;
        }

        selectAllGlobal = true;
        setAllChecked(true);
    }

    function disableSelectAllGlobal() {
        selectAllGlobal = false;
        setAllChecked(false);
    }

    selectAllTop.addEventListener('change', function () {
        if (this.checked) {
            enableSelectAllGlobal();
        } else {
            disableSelectAllGlobal();
        }
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (selectAllGlobal && !this.checked) {
                // jika user mulai uncheck saat mode global aktif, kembalikan ke mode biasa
                selectAllGlobal = false;
            }
            updateBulkDeleteState();
        });
    });

    window.refreshBulkDeleteAlumni = function ({ clearSelection = false } = {}) {
        if (clearSelection) {
            selectAllGlobal = false;
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
        }

        updateBulkDeleteState();
    };

    function chunkArray(items, size) {
        const chunks = [];
        for (let i = 0; i < items.length; i += size) {
            chunks.push(items.slice(i, i + size));
        }
        return chunks;
    }

    function createBulkDeleteBatchError(error, {
        deletedTotal,
        failedBatchSize,
        unprocessedTotal,
        batchNumber,
        totalBatches
    }) {
        const batchError = new Error(
            error?.message || 'Terjadi kesalahan saat menghapus data alumni.'
        );

        batchError.bulkDeleteReport = {
            deletedTotal: Math.max(0, Number(deletedTotal) || 0),
            failedBatchSize: Math.max(0, Number(failedBatchSize) || 0),
            unprocessedTotal: Math.max(0, Number(unprocessedTotal) || 0),
            batchNumber: Math.max(1, Number(batchNumber) || 1),
            totalBatches: Math.max(1, Number(totalBatches) || 1)
        };

        return batchError;
    }

    function updateDeleteProgress(done, total) {
        const safeTotal = Math.max(0, Number(total) || 0);
        const safeDone = Math.min(Math.max(0, Number(done) || 0), safeTotal);
        const percent = safeTotal > 0 ? Math.round((safeDone / safeTotal) * 100) : 0;
        const progressText = document.getElementById('bulk-delete-progress-text');
        const progressBar = document.getElementById('bulk-delete-progress-bar');
        const progressPercent = document.getElementById('bulk-delete-progress-percent');

        if (progressText) {
            progressText.textContent = `${safeDone} dari ${safeTotal} data berhasil dihapus`;
        }

        if (progressBar) {
            progressBar.style.width = `${percent}%`;
        }

        if (progressPercent) {
            progressPercent.textContent = `${percent}%`;
        }
    }

    function showDeleteProgress(total) {
        Swal.fire({
            title: 'Sedang menghapus data alumni...',
            html: `
                <div style="text-align:left; width:100%;">
                    <div id="bulk-delete-progress-text" style="font-size:13px; font-weight:700; color:#334155; margin-bottom:10px;">
                        0 dari ${total} data berhasil dihapus
                    </div>
                    <div style="width:100%; height:12px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                        <div id="bulk-delete-progress-bar" style="width:0%; height:100%; background:#dc2626; border-radius:999px; transition:width .2s ease;"></div>
                    </div>
                    <div id="bulk-delete-progress-percent" style="margin-top:8px; font-size:12px; color:#64748b; text-align:right;">0%</div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => updateDeleteProgress(0, total)
        });
    }

    async function deleteBatch(payload, { returnMeta = false } = {}) {
        const response = await fetch(form.action, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const text = await response.text();
        let data = null;

        try {
            data = text ? JSON.parse(text) : null;
        } catch (_) {
            data = null;
        }

        if (!response.ok || !data?.success) {
            throw new Error(data?.message || `Gagal menghapus data alumni. Status: ${response.status}`);
        }

        const deleted = Number(data.deleted || 0);

        if (returnMeta) {
            return {
                deleted,
                total: data.total
            };
        }

        return deleted;
    }

    function currentDeleteFilters() {
        const params = new URLSearchParams(window.location.search);
        const filters = {};

        params.forEach((value, key) => {
            if (key !== 'page' && key !== 'per_page') {
                filters[key] = value;
            }
        });

        return filters;
    }

    async function deleteSelectedInBatches(selected, totalToDelete, isGlobalDelete) {
        const batchSize = 10;
        let deletedTotal = 0;
        const filters = currentDeleteFilters();
        let totalBatches = Math.max(1, Math.ceil(totalToDelete / batchSize));
        let totalChangedSincePageLoad = false;

        showDeleteProgress(totalToDelete);

        if (isGlobalDelete) {
            let batchNumber = 0;

            while (deletedTotal < totalToDelete) {
                let failedBatchSize = Math.min(batchSize, totalToDelete - deletedTotal);
                batchNumber++;

                let deleted;
                try {
                    const payload = {
                        ...filters,
                        select_all: true,
                        batch_size: failedBatchSize
                    };
                    const isFirstBatch = batchNumber === 1;

                    if (isFirstBatch) {
                        payload.include_total = true;
                    }

                    const result = await deleteBatch(payload, {
                        returnMeta: isFirstBatch
                    });

                    if (isFirstBatch) {
                        deleted = result.deleted;

                        if (
                            result.total !== undefined
                            && Number.isFinite(Number(result.total))
                            && Number(result.total) >= 0
                        ) {
                            const serverTotal = Number(result.total);

                            if (serverTotal !== totalToDelete) {
                                totalChangedSincePageLoad = true;
                                totalToDelete = serverTotal;
                                totalBatches = Math.max(1, Math.ceil(totalToDelete / batchSize));
                                failedBatchSize = Math.min(
                                    failedBatchSize,
                                    Math.max(0, totalToDelete - deletedTotal)
                                );
                            }
                        }
                    } else {
                        deleted = result;
                    }

                    if (deleted <= 0 && deletedTotal < totalToDelete) {
                        throw new Error('Batch tidak menghapus data alumni.');
                    }
                } catch (error) {
                    const batchError = createBulkDeleteBatchError(error, {
                        deletedTotal,
                        failedBatchSize,
                        unprocessedTotal: totalToDelete - deletedTotal - failedBatchSize,
                        batchNumber,
                        totalBatches
                    });

                    if (totalChangedSincePageLoad) {
                        batchError.bulkDeleteReport.totalChangedSincePageLoad = true;
                    }

                    throw batchError;
                }

                deletedTotal += deleted;
                updateDeleteProgress(deletedTotal, totalToDelete);
            }
        } else {
            const selectedIds = selected.map(cb => cb.value);
            const batches = chunkArray(selectedIds, batchSize);

            for (const [batchIndex, ids] of batches.entries()) {
                let deleted;
                try {
                    deleted = await deleteBatch({
                        ids,
                        batch_size: batchSize
                    });
                } catch (error) {
                    throw createBulkDeleteBatchError(error, {
                        deletedTotal,
                        failedBatchSize: ids.length,
                        unprocessedTotal: totalToDelete - deletedTotal - ids.length,
                        batchNumber: batchIndex + 1,
                        totalBatches: batches.length
                    });
                }

                deletedTotal += deleted;
                updateDeleteProgress(deletedTotal, totalToDelete);
            }
        }

        if (deletedTotal < totalToDelete) {
            const batchError = createBulkDeleteBatchError(
                new Error('Jumlah data yang dihapus tidak sesuai dengan jumlah yang dipilih.'),
                {
                    deletedTotal,
                    failedBatchSize: totalToDelete - deletedTotal,
                    unprocessedTotal: 0,
                    batchNumber: totalBatches,
                    totalBatches
                }
            );

            if (totalChangedSincePageLoad) {
                batchError.bulkDeleteReport.totalChangedSincePageLoad = true;
            }

            throw batchError;
        }

        updateDeleteProgress(deletedTotal, totalToDelete);

        await Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: 'Semua data terpilih berhasil dihapus',
            confirmButtonColor: '#004a87'
        });

        window.location.reload();
    }

    async function handleBulkDeleteFailure(error) {
        const report = error?.bulkDeleteReport;
        const totalChangedNote = report?.totalChangedSincePageLoad
            ? ' Catatan: jumlah data berubah sejak halaman terakhir dimuat.'
            : '';
        const message = report
            ? `${report.deletedTotal} alumni berhasil dihapus secara permanen dan tidak dapat dibatalkan. `
                + `${report.failedBatchSize} alumni gagal diproses pada batch ke-${report.batchNumber} dari ${report.totalBatches}. `
                + `${report.unprocessedTotal} alumni belum sempat diproses karena proses dihentikan. `
                + 'Silakan periksa ulang daftar data dan coba lagi jika diperlukan.'
                + totalChangedNote
            : (error?.message || 'Terjadi kesalahan saat menghapus data alumni.');

        await Swal.fire({
            icon: 'error',
            title: report ? 'Bulk delete berhenti' : 'Gagal menghapus data',
            text: message,
            confirmButtonColor: '#d33'
        });

        if ((Number(report?.deletedTotal) || 0) <= 0) {
            return;
        }

        if (typeof window.alumniFetchAndRender === 'function') {
            try {
                await window.alumniFetchAndRender(window.location.href, {
                    throwOnError: true
                });
            } catch (refreshError) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Tampilan data belum diperbarui',
                    text: 'Gagal menyegarkan tampilan data secara otomatis. Sebagian data sudah terhapus dari database. Silakan refresh halaman secara manual untuk melihat data terbaru.',
                    confirmButtonColor: '#d33'
                });
            }
            return;
        }

        window.location.reload();
    }

    deleteBtn.addEventListener('click', function () {
        const selected = selectableCheckboxes().filter(cb => cb.checked);

        if (!selectAllGlobal && !selected.length) {
            return;
        }

        const totalAll = currentTotal();
        const deleteCount = selectAllGlobal ? (totalAll || selected.length) : selected.length;

        Swal.fire({
            title: 'Hapus data terpilih?',
            text: `${deleteCount} data alumni akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Semua!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            deleteSelectedInBatches(selected, deleteCount, selectAllGlobal)
                .catch(handleBulkDeleteFailure);
        });
    });

    // default state: checkboxes always visible
    actionBar.style.display = 'flex';
    selectLabel.style.display = 'flex';
    headerCell.style.display = 'table-cell';
    rowCells.forEach(cell => {
        cell.style.display = 'table-cell';
    });

    updateBulkDeleteState();
}

document.addEventListener('DOMContentLoaded', initBulkDeleteAlumni);

</script>
@endpush
