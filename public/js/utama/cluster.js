document.addEventListener("DOMContentLoaded", function() {
    
    const tombolPanel = document.getElementById('toggle-layer-panel');
    const menuPanel = document.getElementById('layer-control-menu');
    const tombolCluster = document.getElementById('toggle-cluster-map'); 
    const tombolPolygon = document.getElementById('toggle-polygon-map');
    const listPolygon = document.getElementById('polygon-wilayah-list');
    const tombolListWilayah = document.getElementById('toggle-polygon-wilayah-list');
    const panahListWilayah = document.getElementById('polygon-wilayah-arrow');

    if (tombolPanel && menuPanel) {
        tombolPanel.addEventListener('click', function (e) {
            e.stopPropagation();
            menuPanel.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('layer-control-panel')?.contains(e.target)) {
                menuPanel.classList.add('hidden');
            }
        });
    }
    
    if(tombolCluster) {
        tombolCluster.addEventListener('change', function() {
            // Ubah variabel global agar filter.js tahu statusnya
            window.statusClusterAktif = this.checked; 
            
            // Perintahkan filter.js untuk merefresh peta
            if (typeof window.perbaruiTampilanPeta === 'function') {
                window.perbaruiTampilanPeta();
            }
        });
    }

    if (tombolPolygon) {
        tombolPolygon.addEventListener('change', function () {
            window.statusPolygonAktif = this.checked;

            if (typeof window.perbaruiTampilanPolygon === 'function') {
                window.perbaruiTampilanPolygon();
            }
        });
    }

    if (tombolListWilayah && listPolygon) {
        tombolListWilayah.addEventListener('click', function () {
            const terbuka = listPolygon.classList.toggle('hidden') === false;

            if (panahListWilayah) {
                panahListWilayah.classList.toggle('open', terbuka);
            }
        });
    }

    if (listPolygon) {
        listPolygon.addEventListener('change', function (e) {
            const target = e.target;

            if (!target.matches('.toggle-polygon-wilayah')) {
                return;
            }

            const namaWilayah = target.dataset.wilayah || '';

            if (
                typeof window.setStatusPolygonWilayah === 'function' &&
                namaWilayah !== ''
            ) {
                window.setStatusPolygonWilayah(namaWilayah, target.checked);
            }
        });
    }

});
