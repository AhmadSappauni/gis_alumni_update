<div id="layer-control-panel" class="layer-control-panel">
    <button id="toggle-layer-panel" class="layer-toggle-btn" type="button" title="Tampilkan pengaturan layer">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l9 4.5-9 4.5-9-4.5L12 3z"></path>
            <path d="M3 12l9 4.5 9-4.5"></path>
            <path d="M3 16.5L12 21l9-4.5"></path>
        </svg>
    </button>

        <div id="layer-control-menu" class="layer-control-menu hidden">
            <div class="layer-control-section">
                <div class="layer-control-item is-column">
                    <span class="layer-label">Mode Visualisasi</span>
                    <select id="visualization-mode-ui" class="custom-select">
                        <option value="marker" selected>Marker</option>
                        <option value="choropleth">Choropleth</option>
                        <option value="heatmap">Heatmap</option>
                    </select>
                </div>
            </div>

            <div class="layer-control-section">
                <div class="layer-control-item">
                    <span class="layer-label">Polygon Wilayah</span>
                    <label class="switch-kustom">
                        <input type="checkbox" id="toggle-polygon-map" checked>
                        <span class="slider-kustom"></span>
                    </label>
                </div>

            <button
                id="toggle-map-components"
                class="map-components-toggle-btn"
                type="button"
            >
                <span>Komponen Peta</span>
                <span id="map-components-arrow" class="polygon-wilayah-arrow"></span>
            </button>

            <div id="map-components-list" class="map-components-list hidden">
                <div class="layer-control-item">
                    <span class="layer-label">Kompas</span>
                    <label class="switch-kustom switch-mini">
                        <input type="checkbox" id="toggle-kompas-ui">
                        <span class="slider-kustom"></span>
                    </label>
                </div>

                <div class="layer-control-item">
                    <span class="layer-label">Legenda</span>
                    <label class="switch-kustom switch-mini">
                        <input type="checkbox" id="toggle-legenda-ui" checked>
                        <span class="slider-kustom"></span>
                    </label>
                </div>

                <div class="layer-control-item">
                    <span class="layer-label">Scale Bar</span>
                    <label class="switch-kustom switch-mini">
                        <input type="checkbox" id="toggle-scalebar-ui" checked>
                        <span class="slider-kustom"></span>
                    </label>
                </div>

                <div class="layer-control-item">
                    <span class="layer-label">Mini Map</span>
                    <label class="switch-kustom switch-mini">
                        <input type="checkbox" id="toggle-minimap-ui">
                        <span class="slider-kustom"></span>
                    </label>
                </div>
            </div>

            <button
                id="toggle-polygon-wilayah-list"
                class="polygon-wilayah-toggle-btn"
                type="button"
            >
                <span>Per Wilayah</span>
                <span id="polygon-wilayah-arrow" class="polygon-wilayah-arrow"></span>
            </button>

            <div id="polygon-wilayah-list" class="polygon-wilayah-list hidden"></div>
        </div>

        <div class="layer-control-section">
            <div class="layer-control-item">
                <span class="layer-label">Cluster Marker</span>
                <label class="switch-kustom">
                    <input type="checkbox" id="toggle-cluster-map" checked>
                    <span class="slider-kustom"></span>
                </label>
            </div>
        </div>
    </div>
</div>
