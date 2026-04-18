// VARIABEL GLOBAL
var currentTab = 0;
var map;
var marker;
let nimExists = false;
let debounceTimer;
let kotaTimer;

// FUNGSI NAVIGASI (Diletakkan di luar agar GLOBAL)
function showTab(n) {
    var x = document.getElementsByClassName("form-step");
    if (!x[n]) return;

    for (var i = 0; i < x.length; i++) {
        x[i].style.display = "none";
        x[i].classList.remove("active");
    }

    // Tampilkan tab yang dipilih
    x[n].style.display = "block";
    x[n].classList.add("active");

    // Atur tombol navigasi
    document.getElementById("prevBtn").style.display = (n == 0) ? "none" : "block";

    var nextBtn = document.getElementById("nextBtn");
    if (n == x.length - 1) {
        setTimeout(function () {
            updateReview();
        }, 300);

        nextBtn.innerHTML = "Simpan Data Alumni";
        // Refresh peta khusus di tab terakhir
        setTimeout(function () {
            if (map) {
                map.invalidateSize();
            }
        }, 300);
    } else {
        nextBtn.innerHTML = "Lanjut";
    }

    updateStepIndicator(n);
    updateProgressBar(n);
}

function nextPrev(n) {
    var x = document.getElementsByClassName("form-step");

    if (n == 1) {
        if (!validateStep(currentTab)) {
            return;
        }
    }

    if (n == 1 && currentTab >= x.length - 1) {
        submitForm();
        return false;
    }

    x[currentTab].classList.remove("active");

    currentTab = currentTab + n;

    showTab(currentTab);
}

function submitForm() {
    const form = document.getElementById("wizardForm");
    Swal.fire({
        title: "Menyimpan data...",
        text: "Mohon tunggu sebentar",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    const disabledInputs = form.querySelectorAll(':disabled');
    disabledInputs.forEach(input => input.disabled = false);

    setTimeout(function () {
        document.getElementById("wizardForm").submit();
    }, 800);
}

function updateStepIndicator(n) {
    var i,
        x = document.getElementsByClassName("step");
    for (i = 0; i < x.length; i++) {
        x[i].classList.remove("active");
        x[i].classList.remove("completed");

        if (i < n) {
            x[i].classList.add("completed");
        }
        if (i == n) {
            x[i].classList.add("active");
        }
    }
}

// INISIALISASI PETA
function initMap() {
    if (map !== undefined) { map.remove(); }
    map = L.map("map-tambah").setView([-3.3194, 114.5908], 12);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap",
    }).addTo(map);

    marker = L.marker([-3.3194, 114.5908], {
        draggable: true,
    }).addTo(map);
    document.getElementById("lat").value = -3.3194;
    document.getElementById("lng").value = 114.5908;

    marker.on("dragend", function (e) {
        var latlng = marker.getLatLng();

        document.getElementById("lat").value = latlng.lat;
        document.getElementById("lng").value = latlng.lng;

        getCity(latlng.lat, latlng.lng);
    });

    map.on("click", function (e) {
        marker.setLatLng(e.latlng);

        document.getElementById("lat").value = e.latlng.lat;
        document.getElementById("lng").value = e.latlng.lng;

        getCity(e.latlng.lat, e.latlng.lng);

        updateReview();
    });
}

function getCity(lat, lng) {
    fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`,
    )
        .then((res) => res.json())

        .then((data) => {
            let address = data.address;
            let city = address.city || address.town || address.village || address.county || "";

            document.getElementById("kota").value = city; // Gunakan ID
            if (data.display_name) {
                document.getElementById("alamat_lengkap").value = data.display_name;
            }
            
            // PANGGIL INI supaya review langsung update saat peta diklik
            updateReview(); 
        })
        .catch((err) => console.log(err));
}

function updateReview() {

    const reviewBox = document.getElementById("review-box");

    if (!reviewBox) return;

    let nama = document.querySelector("input[name='nama_lengkap']")?.value || '';
    let nim = document.querySelector("input[name='nim']")?.value || '';
    let email = document.querySelector("input[name='email']")?.value || '';
    let no_hp = document.querySelector("input[name='no_hp']")?.value || '';

    let angkatan = document.querySelector("input[name='angkatan']")?.value || '';
    let tahun = document.querySelector("input[name='tahun_lulus']")?.value || '';

    let perusahaan = document.querySelector("input[name='nama_perusahaan']")?.value || '';
    let jabatan = document.querySelector("input[name='jabatan']")?.value || '';

    let bidang =
        document.querySelector("select[name='bidang_pekerjaan']")?.value || '';

    let kota = document.getElementById("kota")?.value || '';
    let lat = document.getElementById("lat")?.value || '';
    let lng = document.getElementById("lng")?.value || '';

    if (currentTab == 2 && nama && nim) {

        reviewBox.style.display = "block";

        const rn = document.getElementById("review_nama");
        if (rn) rn.innerText = nama;

        const rnim = document.getElementById("review_nim");
        if (rnim) rnim.innerText = `${nim} | ${email}`;

        const ral = document.getElementById("review_angkatan_lulus");
        if (ral) ral.innerText = `${angkatan} / ${tahun}`;

        const rp = document.getElementById("review_perusahaan");
        if (rp) rp.innerText = perusahaan || "-";

        const rj = document.getElementById("review_jabatan");
        if (rj) rj.innerText = `${jabatan} (${no_hp})`;

        const rk = document.getElementById("review_kota");
        if (rk) rk.innerText = kota || "-";

        const rc = document.getElementById("review_coords");
        if (rc) rc.innerText = `${lat}, ${lng}`;

    } else {
        reviewBox.style.display = "none";
    }
}

function validateStep(step) {
    if (step == 0) {
        let nim = document.querySelector("input[name='nim']").value;
        let nama = document.querySelector("input[name='nama_lengkap']").value;
        if (!nim || !nama) {
            showAlert("Lengkapi data identitas (NIM & Nama).");
            return false;
        }
        if (nimExists) { showAlert("NIM sudah terdaftar"); return false; }
    }

    if (step == 1) {
        // CEK APAKAH CHECKBOX DICENTANG
        const isUnemployed = document.getElementById('is_unemployed').checked;
        if (!isUnemployed) {
            let perusahaan = document.querySelector("input[name='nama_perusahaan']").value;
            let jabatan = document.querySelector("input[name='jabatan']").value;
            if (!perusahaan || !jabatan) {
                showAlert("Lengkapi data pekerjaan atau centang 'Belum Bekerja'");
                return false;
            }
        }
    }
    return true;
}

function updateProgressBar(step) {
    let percent = ((step + 1) / 3) * 100;

    document.getElementById("progress-bar").style.width = percent + "%";
}

document.getElementById("foto").addEventListener("change", function (e) {
    let reader = new FileReader();

    reader.onload = function () {
        let preview = document.getElementById("preview-foto");

        preview.src = reader.result;
        preview.style.display = "block";
    };

    reader.readAsDataURL(e.target.files[0]);
});

function showAlert(message) {
    Swal.fire({
        icon: "warning",
        title: "Data belum lengkap",
        text: message,
        confirmButtonText: "Oke",
        confirmButtonColor: "#004a87",
    });
}

function scrollToError() {
    window.scrollTo({
        top: 0,
        behavior: "smooth",
    });
}

const kotaInput = document.getElementById("kota");
const kotaStatus = document.getElementById("kota-status");

if(kotaInput){
    kotaInput.addEventListener("keyup", function () {
        let city = kotaInput.value;

        if (city.length < 3) {
            if(kotaStatus){
                kotaStatus.style.color = "#64748b";
                kotaStatus.innerText = "Ketik minimal 3 huruf nama kota";
            }
            return;
        }

        if(kotaStatus) kotaStatus.style.color = "#f59e0b";
        if(kotaStatus) kotaStatus.innerText = "Sedang mencari lokasi...";

        clearTimeout(kotaTimer);

        kotaTimer = setTimeout(function () {
            fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${city}`,
            )
                .then((res) => res.json())

                .then((data) => {
                    if (data.length > 0) {
                        let lat = data[0].lat;
                        let lon = data[0].lon;

                        map.setView([lat, lon], 13);

                        marker.setLatLng([lat, lon]);

                        document.getElementById("lat").value = lat;
                        document.getElementById("lng").value = lon;

                        getCity(lat, lon);

                        if(kotaStatus) kotaStatus.style.color = "#10b981";
                        if(kotaStatus) kotaStatus.innerText = "✓ Tempat ditemukan";
                    } else {
                        if(kotaStatus) kotaStatus.style.color = "#ef4444";
                        if(kotaStatus) kotaStatus.innerText = "Kota tidak ditemukan";
                    }
                })
                .catch(() => {
                    if(kotaStatus) kotaStatus.style.color = "#ef4444";
                    if(kotaStatus) kotaStatus.innerText = "Gagal mencari lokasi";
                });
        }, 700);
    });
}

// JALANKAN SAAT HALAMAN SELESAI DIMUAT
document.addEventListener("DOMContentLoaded", function () {
    const nimStatus = document.getElementById("nim-status");
    const nextBtn = document.getElementById("nextBtn");
    
    // Variabel Checkbox
    const checkUnemployed = document.getElementById('is_unemployed');
    const sectionPekerjaan = document.getElementById('section-pekerjaan');
    
    // Pagar keamanan: Hanya jalankan logika checkbox jika elemennya ADA
    if (checkUnemployed && sectionPekerjaan) {
    const inputsPekerjaan = sectionPekerjaan.querySelectorAll('input, select');
    
    checkUnemployed.addEventListener('change', function() {
        if (this.checked) {
            inputsPekerjaan.forEach(input => {
                // Gunakan readOnly agar data tetap terkirim ke server
                input.readOnly = true; 
                // Khusus Select (karena tidak ada readOnly), kita bisa pakai cara ini
                if(input.tagName === 'SELECT') input.style.pointerEvents = "none";
                
                input.style.backgroundColor = "#f1f5f9";
                if(input.name !== 'linearitas') input.value = "-";
            });
            // Set Linearitas otomatis
            let linSelect = document.querySelector("select[name='linearitas']");
            if(linSelect) {
                linSelect.value = "Tidak Erat";
                linSelect.style.pointerEvents = "none";
            }
        } else {
            inputsPekerjaan.forEach(input => {
                input.readOnly = false;
                if(input.tagName === 'SELECT') input.style.pointerEvents = "auto";
                input.style.backgroundColor = "#ffffff";
                input.value = "";
            });
        }
        updateReview();
    });
}
    const nimInput = document.getElementById("nim");
    if (nimInput) {
    nimInput.addEventListener("keyup", function () {
        let nim = nimInput.value;

        if(nimStatus) nimStatus.innerHTML = "";
        nimExists = false;

        if (nim.length < 10) {
            nextBtn.disabled = false;
            return;
        }

        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(function () {
            fetch("/admin/check-nim", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({ nim: nim }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.exists) {
                        nimExists = true;

                        if(nimStatus) nimStatus.style.color = "red";
                        if(nimStatus) nimStatus.innerHTML = "⚠ NIM sudah terdaftar";

                        nextBtn.disabled = true;
                    } else {
                        nimExists = false;

                        if(nimStatus) nimStatus.style.color = "green";
                        if(nimStatus) nimStatus.innerHTML = "✓ NIM tersedia";

                        nextBtn.disabled = false;
                    }
                });
        }, 500); // delay 500ms
    });
}
    initMap();
    showTab(currentTab);
});

