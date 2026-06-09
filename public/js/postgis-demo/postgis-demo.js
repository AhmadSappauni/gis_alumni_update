(function () {
    const config = window.postgisDemoConfig || {};
    const dataUrl = config.dataUrl;
    const defaultCenter = [-3.3167, 114.5901];

    const tableBody = document.getElementById("postgis-demo-table");
    const helper = document.getElementById("table-helper");
    const statusText = document.getElementById("postgis-status-text");
    const statusDot = document.getElementById("postgis-status-dot");
    const summary = {
        total: document.getElementById("summary-total"),
        sama: document.getElementById("summary-sama"),
        berbeda: document.getElementById("summary-berbeda"),
        tidak: document.getElementById("summary-tidak"),
    };

    let map = null;
    let markerLayer = null;

    initTabs();
    initMap();
    loadData();

    function initTabs() {
        const buttons = document.querySelectorAll("[data-demo-tab]");
        const panels = document.querySelectorAll("[data-demo-panel]");

        buttons.forEach((button) => {
            button.addEventListener("click", () => {
                const target = button.getAttribute("data-demo-tab");

                buttons.forEach((item) => item.classList.toggle("is-active", item === button));
                panels.forEach((panel) => {
                    panel.classList.toggle(
                        "is-active",
                        panel.getAttribute("data-demo-panel") === target
                    );
                });
            });
        });
    }

    function initMap() {
        if (!window.L) {
            setStatus("Leaflet belum termuat. Tabel tetap dapat digunakan.", "warning");
            return;
        }

        map = L.map("postgis-demo-map", {
            zoomControl: true,
            scrollWheelZoom: true,
        }).setView(defaultCenter, 8);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);

        markerLayer = L.layerGroup().addTo(map);
    }

    function loadData() {
        if (!dataUrl) {
            renderError("Endpoint data demo belum dikonfigurasi.");
            return;
        }

        fetch(dataUrl, {
            headers: {
                Accept: "application/json",
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Endpoint mengembalikan status " + response.status);
                }

                return response.json();
            })
            .then((payload) => {
                const samples = Array.isArray(payload.samples) ? payload.samples : [];
                const meta = payload.meta || {};

                renderSummary(samples);
                renderTable(samples);
                renderMarkers(samples);
                renderMeta(meta, samples.length);
            })
            .catch((error) => {
                renderError("Data demo belum dapat dimuat. " + error.message);
            });
    }

    function renderMeta(meta, count) {
        const message = meta.message || "Data demo berhasil dimuat.";
        const type = meta.postgis_available === false ? "warning" : "ok";

        setStatus(message, type);

        if (helper) {
            helper.textContent = count
                ? "Menampilkan " + count + " sampel dari endpoint demo."
                : "Belum ada data alumni dengan koordinat yang dapat ditampilkan.";
        }
    }

    function renderSummary(samples) {
        const counts = samples.reduce(
            (carry, item) => {
                const status = item.status_perbandingan || "tidak_terdeteksi";
                carry.total += 1;

                if (status === "sama") {
                    carry.sama += 1;
                } else if (status === "berbeda") {
                    carry.berbeda += 1;
                } else {
                    carry.tidak += 1;
                }

                return carry;
            },
            { total: 0, sama: 0, berbeda: 0, tidak: 0 }
        );

        setText(summary.total, counts.total);
        setText(summary.sama, counts.sama);
        setText(summary.berbeda, counts.berbeda);
        setText(summary.tidak, counts.tidak);
    }

    function renderTable(samples) {
        if (!tableBody) {
            return;
        }

        if (!samples.length) {
            tableBody.innerHTML = '<tr><td colspan="5" class="empty-cell">Belum ada data koordinat untuk demo.</td></tr>';
            return;
        }

        tableBody.innerHTML = samples
            .map((item) => {
                const latitude = Number(item.latitude);
                const longitude = Number(item.longitude);
                const coordinate = isFinite(latitude) && isFinite(longitude)
                    ? latitude.toFixed(6) + ", " + longitude.toFixed(6)
                    : "-";

                return [
                    "<tr>",
                    '<td class="name-cell">' + escapeHtml(item.nama_alumni || "-") + "</td>",
                    '<td class="coord-cell">' + escapeHtml(coordinate) + "</td>",
                    "<td>" + escapeHtml(item.wilayah_tanpa_postgis || "Tidak tersedia") + "</td>",
                    "<td>" + escapeHtml(item.wilayah_postgis || "Tidak terdeteksi") + "</td>",
                    "<td>" + statusBadge(item.status_perbandingan) + "</td>",
                    "</tr>",
                ].join("");
            })
            .join("");
    }

    function renderMarkers(samples) {
        if (!map || !markerLayer) {
            return;
        }

        markerLayer.clearLayers();
        const bounds = [];

        samples.forEach((item) => {
            const latitude = Number(item.latitude);
            const longitude = Number(item.longitude);

            if (!isFinite(latitude) || !isFinite(longitude)) {
                return;
            }

            const marker = L.circleMarker([latitude, longitude], markerStyle(item.status_perbandingan))
                .bindPopup(popupContent(item));

            marker.addTo(markerLayer);
            bounds.push([latitude, longitude]);
        });

        if (bounds.length) {
            map.fitBounds(bounds, {
                padding: [34, 34],
                maxZoom: 12,
            });
        }
    }

    function markerStyle(status) {
        if (status === "sama") {
            return {
                radius: 8,
                color: "#16835f",
                weight: 2,
                fillColor: "#31b783",
                fillOpacity: 0.85,
            };
        }

        if (status === "berbeda") {
            return {
                radius: 8,
                color: "#b96012",
                weight: 2,
                fillColor: "#f2a443",
                fillOpacity: 0.9,
            };
        }

        return {
            radius: 8,
            color: "#757f7a",
            weight: 2,
            fillColor: "#aeb8b2",
            fillOpacity: 0.85,
        };
    }

    function popupContent(item) {
        return [
            '<span class="popup-title">' + escapeHtml(item.nama_alumni || "-") + "</span>",
            '<div class="popup-meta">',
            "Teks: " + escapeHtml(item.wilayah_tanpa_postgis || "Tidak tersedia") + "<br>",
            "PostGIS: " + escapeHtml(item.wilayah_postgis || "Tidak terdeteksi") + "<br>",
            "Alamat: " + escapeHtml(item.alamat_teks || "-"),
            "</div>",
        ].join("");
    }

    function statusBadge(status) {
        const normalized = status || "tidak_terdeteksi";
        const labels = {
            sama: "Sama",
            berbeda: "Berbeda",
            tidak_terdeteksi: "Tidak terdeteksi",
        };
        const classes = {
            sama: "status-sama",
            berbeda: "status-berbeda",
            tidak_terdeteksi: "status-tidak",
        };

        return (
            '<span class="status-badge ' +
            (classes[normalized] || classes.tidak_terdeteksi) +
            '">' +
            escapeHtml(labels[normalized] || labels.tidak_terdeteksi) +
            "</span>"
        );
    }

    function renderError(message) {
        setStatus(message, "error");
        renderSummary([]);

        if (helper) {
            helper.textContent = message;
        }

        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="5" class="empty-cell">' + escapeHtml(message) + "</td></tr>";
        }
    }

    function setStatus(message, type) {
        if (statusText) {
            statusText.textContent = message;
        }

        if (statusDot) {
            statusDot.className = "status-dot";
            statusDot.classList.add("is-" + type);
        }
    }

    function setText(element, value) {
        if (element) {
            element.textContent = String(value);
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
