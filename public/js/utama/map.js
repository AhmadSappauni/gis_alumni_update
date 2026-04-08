// 1. Inisialisasi Peta & Base Map
var map = L.map("map", {
    zoomControl: false 
}).setView([-3.316694, 114.590111], 8); // Zoom saya ubah ke 8 agar Kalsel terlihat utuh

// Pindahkan tombol zoom ke pojok kanan bawah
L.control.zoom({
    position: 'bottomright'
}).addTo(map);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);

var markerLayer = L.layerGroup().addTo(map);

// 1. Fungsi untuk menentukan warna berdasarkan nama kabupaten
// Kamu bisa menyesuaikan list warna ini agar estetik
function getColor(d) {
    return d === 'Banjarmasin'  ? '#004a87' :
           d === 'Banjarbaru'   ? '#0ea5e9' :
           d === 'Banjar'       ? '#10b981' :
           d === 'Barito Kuala' ? '#f59e0b' :
           d === 'Tanah Laut'   ? '#ef4444' :
           d === 'Tanah Bumbu'  ? '#8b5cf6' :
           d === 'Kotabaru'     ? '#ec4899' :
           d === 'Tapin'        ? '#f97316' :
           d === 'Hulu Sungai Selatan' ? '#14b8a6' :
           d === 'Hulu Sungai Tengah'  ? '#6366f1' :
           d === 'Hulu Sungai Utara'   ? '#a855f7' :
           d === 'Balangan'     ? '#fbbf24' :
           d === 'Tabalong'     ? '#4ade80' :
                                  '#94a3b8'; // Warna default jika nama tidak cocok
}

// 2. Load GeoJSON Kalsel
fetch('/data/data_kalsel.geojson')
    .then(response => response.json())
    .then(geojsonData => {
        L.geoJSON(geojsonData, {
            style: function (feature) {
                // Ambil properti nama kabupaten dari GeoJSON
                // CATATAN: Cek file JSON kamu, apakah kolomnya bernama 'nama', 'NAMOBJ', atau 'KAB_KOTA'
                var namaKab = feature.properties.nama || feature.properties.NAMOBJ || feature.properties.KAB_KOTA;
                
                return {
                    fillColor: getColor(namaKab), // Ambil warna dari fungsi di atas
                    weight: 1,
                    opacity: 1,
                    color: 'black',  // Warna garis pembatas antar kabupaten
                    dashArray: '3',
                    fillOpacity: 0.5 // Transparansi warna agar peta dasar tetap terlihat
                };
            },
            onEachFeature: function (feature, layer) {
                // Tambahkan tooltip/label saat kabupaten diklik atau di-hover
                var nama = feature.properties.nama || feature.properties.NAMOBJ || feature.properties.KAB_KOTA;
                
                
                // Efek hover: warna sedikit lebih terang saat mouse di atas wilayah
                layer.on({
                    mouseover: function (e) {
                        var layer = e.target;
                        layer.setStyle({
                            fillOpacity: 0.7,
                            weight: 1
                        });
                    },
                    mouseout: function (e) {
                        layer.setStyle({
                            fillOpacity: 0.5,
                            weight: 1
                        });
                    }
                });
            }
        }).addTo(map);
    })
    .catch(error => console.error('Error:', error));