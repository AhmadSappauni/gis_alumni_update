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
                <div class="search-box-mini">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="alumniSearch" placeholder="Cari alumni..." onkeyup="applyFilters()">
                </div>
                
                <div class="filter-dropdown">
                    <button class="filter-btn" onclick="toggleFilterMenu()" title="Filter Data">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                    </button>
                    <div id="filterMenu" class="filter-menu glass-panel">
                        <div class="filter-group">
                            <label>Tahun Lulus</label>
                            <select id="filterTahun" onchange="applyFilters()">
                                <option value="">Semua Tahun</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Linearitas Pekerjaan</label>
                            <select id="filterLinear" onchange="applyFilters()">
                                <option value="">Semua Linearitas</option>
                                <option value="Sangat Erat">Sangat Erat</option>
                                <option value="Erat">Erat</option>
                                <option value="Cukup Erat">Cukup Erat</option>
                                <option value="Kurang Erat">Kurang Erat</option>
                                <option value="Tidak Erat">Tidak Erat</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Bidang Pekerjaan</label>
                            <select id="filterBidang" onchange="applyFilters()">
                                <option value="">Semua Bidang</option>
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
                <button onclick="switchView('card')" id="btn-card" class="view-btn active" title="Tampilan Card">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 14a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1v-5zM14 14a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1v-5z"></path>
                    </svg>
                </button>
                <button onclick="switchView('list')" id="btn-list" class="view-btn" title="Tampilan List">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    @include('admin.komponen.content')
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
    const selectAllTable = document.getElementById('select-all-alumni-table');
    const toggleBtn = document.getElementById('btn-toggle-bulk-delete');
    const cancelBtn = document.getElementById('btn-cancel-bulk-delete');
    const actionBar = document.getElementById('bulk-action-bar');
    const selectLabel = document.getElementById('bulk-select-label');
    const headerCell = document.getElementById('bulk-checkbox-header');
    const rowCells = Array.from(document.querySelectorAll('.bulk-checkbox-cell'));
    const countLabel = document.getElementById('selected-alumni-count');
    const deleteBtn = document.getElementById('btn-delete-selected');
    const form = document.getElementById('bulk-delete-form');
    const inputs = document.getElementById('bulk-delete-inputs');

    if (!checkboxes.length || !selectAllTop || !selectAllTable || !toggleBtn || !cancelBtn || !actionBar || !selectLabel || !headerCell || !countLabel || !deleteBtn || !form || !inputs) {
        return;
    }

    let bulkModeActive = false;

    function setBulkMode(active) {
        bulkModeActive = active;
        actionBar.style.display = active ? 'flex' : 'none';
        selectLabel.style.display = active ? 'flex' : 'none';
        headerCell.style.display = active ? 'table-cell' : 'none';
        rowCells.forEach(cell => {
            cell.style.display = active ? 'table-cell' : 'none';
        });
        toggleBtn.style.display = active ? 'none' : 'inline-flex';

        if (!active) {
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            inputs.innerHTML = '';
        }

        updateBulkDeleteState();
    }

    function updateBulkDeleteState() {
        const selected = checkboxes.filter(cb => cb.checked);
        const selectedCount = selected.length;
        const allChecked = selectedCount > 0 && selectedCount === checkboxes.length;

        countLabel.textContent = `${selectedCount} dipilih`;
        deleteBtn.disabled = selectedCount === 0;
        deleteBtn.style.cursor = selectedCount === 0 ? 'not-allowed' : 'pointer';
        deleteBtn.style.opacity = selectedCount === 0 ? '.6' : '1';

        selectAllTop.checked = allChecked;
        selectAllTable.checked = allChecked;
        selectAllTop.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        selectAllTable.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
    }

    function setAllChecked(checked) {
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });

        updateBulkDeleteState();
    }

    selectAllTop.addEventListener('change', function () {
        setAllChecked(this.checked);
    });

    selectAllTable.addEventListener('change', function () {
        setAllChecked(this.checked);
    });

    toggleBtn.addEventListener('click', function () {
        setBulkMode(true);
    });

    cancelBtn.addEventListener('click', function () {
        setBulkMode(false);
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteState);
    });

    deleteBtn.addEventListener('click', function () {
        const selected = checkboxes.filter(cb => cb.checked);

        if (!selected.length) {
            return;
        }

        Swal.fire({
            title: 'Hapus data terpilih?',
            text: `${selected.length} data alumni akan dihapus permanen.`,
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

            inputs.innerHTML = selected
                .map(cb => `<input type="hidden" name="ids[]" value="${cb.value}">`)
                .join('');

            form.submit();
        });
    });

    setBulkMode(false);
}

document.addEventListener('DOMContentLoaded', initBulkDeleteAlumni);

</script>
@endpush
