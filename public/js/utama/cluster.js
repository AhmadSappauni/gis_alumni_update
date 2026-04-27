document.addEventListener("DOMContentLoaded", function() {
    
    const tombolPanel = document.getElementById('toggle-layer-panel');
    const menuPanel = document.getElementById('layer-control-menu');
    const tombolCluster = document.getElementById('toggle-cluster-map'); 
    const tombolPolygon = document.getElementById('toggle-polygon-map');
    const tombolKompas = document.getElementById('toggle-kompas-ui');
    const tombolLegenda = document.getElementById('toggle-legenda-ui');
    const tombolScaleBar = document.getElementById('toggle-scalebar-ui');
    const tombolMiniMap = document.getElementById('toggle-minimap-ui');
    const tombolKomponenPeta = document.getElementById('toggle-map-components');
    const listKomponenPeta = document.getElementById('map-components-list');
    const panahKomponenPeta = document.getElementById('map-components-arrow');
    const listPolygon = document.getElementById('polygon-wilayah-list');
    const tombolListWilayah = document.getElementById('toggle-polygon-wilayah-list');
    const panahListWilayah = document.getElementById('polygon-wilayah-arrow');

    const kompasEl = document.querySelector('.kompas-ui');
    const legendaMarkerEl = document.querySelector('.status-legend');
    const legendaChoroplethEl = document.querySelector('.choropleth-legend');

    const scaleBarEl =
        (window.scaleBarControl && window.scaleBarControl.getContainer && window.scaleBarControl.getContainer()) ||
        document.querySelector('#map .leaflet-control-betterscale, #map .leaflet-control-scale');

    const miniMapEl =
        (window.miniMapControl && window.miniMapControl.getContainer && window.miniMapControl.getContainer()) ||
        document.querySelector('#map .leaflet-control-minimap');

    function setVisible(el, visible) {
        if (!el) return;
        el.classList.toggle('is-hidden', !visible);
    }

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
        // Sinkronkan state awal dengan checkbox (tanpa merombak alur render).
        const prev = !!window.statusClusterAktif;
        window.statusClusterAktif = tombolCluster.checked;
        if (prev !== window.statusClusterAktif && typeof window.filterDanTampilkanMarker === 'function') {
            window.filterDanTampilkanMarker();
        }

        tombolCluster.addEventListener('change', function() {
            // Ubah variabel global agar filter.js tahu statusnya
            window.statusClusterAktif = this.checked; 
            
            // Render ulang supaya marker pindah container (cluster vs non-cluster)
            if (typeof window.filterDanTampilkanMarker === 'function') {
                window.filterDanTampilkanMarker();
            } else if (typeof window.perbaruiTampilanPeta === 'function') {
                window.perbaruiTampilanPeta();
            }
        });
    }

    if (tombolPolygon) {
        tombolPolygon.addEventListener('change', function () {
            if ((window.visualizationMode || 'marker').toString() === 'choropleth' && !this.checked) {
                this.checked = true;
                if (typeof window.showToastKecil === 'function') {
                    window.showToastKecil('Mode choropleth membutuhkan polygon aktif');
                }
                return;
            }

            window.statusPolygonAktif = this.checked;

            if (typeof window.setSemuaStatusPolygonWilayah === 'function') {
                window.setSemuaStatusPolygonWilayah(this.checked);
            }

            if (typeof window.perbaruiTampilanPolygon === 'function') {
                window.perbaruiTampilanPolygon();
            }
        });
    }

    if (tombolKompas) {
        setVisible(kompasEl, tombolKompas.checked);

        tombolKompas.addEventListener('change', function () {
            setVisible(kompasEl, this.checked);
        });
    }

    if (tombolLegenda) {
        setVisible(legendaMarkerEl, tombolLegenda.checked);
        setVisible(legendaChoroplethEl, tombolLegenda.checked);

        tombolLegenda.addEventListener('change', function () {
            setVisible(legendaMarkerEl, this.checked);
            setVisible(legendaChoroplethEl, this.checked);
        });
    }

    if (tombolScaleBar) {
        setVisible(scaleBarEl, tombolScaleBar.checked);

        tombolScaleBar.addEventListener('change', function () {
            setVisible(scaleBarEl, this.checked);
        });
    }

    if (tombolMiniMap) {
        setVisible(miniMapEl, tombolMiniMap.checked);

        tombolMiniMap.addEventListener('change', function () {
            setVisible(miniMapEl, this.checked);
        });
    }

    if (tombolKomponenPeta && listKomponenPeta) {
        tombolKomponenPeta.addEventListener('click', function () {
            const terbuka = listKomponenPeta.classList.toggle('hidden') === false;

            if (panahKomponenPeta) {
                panahKomponenPeta.classList.toggle('open', terbuka);
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

        listPolygon.addEventListener('mouseover', function (e) {
            const item = e.target.closest('.polygon-wilayah-item[data-wilayah-key]');
            if (!item) {
                return;
            }

            const key = item.dataset.wilayahKey || '';
            if (!key) {
                return;
            }

            item.classList.add('is-hovered');

            document.dispatchEvent(new CustomEvent('wilayah-panel-hover', {
                detail: { key, hover: true }
            }));
        });

        listPolygon.addEventListener('mouseout', function (e) {
            const item = e.target.closest('.polygon-wilayah-item[data-wilayah-key]');
            if (!item) {
                return;
            }

            if (item.contains(e.relatedTarget)) {
                return;
            }

            const key = item.dataset.wilayahKey || '';
            item.classList.remove('is-hovered');

            if (!key) {
                return;
            }

            document.dispatchEvent(new CustomEvent('wilayah-panel-hover', {
                detail: { key, hover: false }
            }));
        });
    }

    document.addEventListener('wilayah-map-hover', function (e) {
        const key = e?.detail?.key || '';
        const hover = !!e?.detail?.hover;

        if (!listPolygon || !key) {
            return;
        }

        const item = listPolygon.querySelector('.polygon-wilayah-item[data-wilayah-key="' + key + '"]');
        if (!item) {
            return;
        }

        item.classList.toggle('is-hovered', hover);
    });

});
