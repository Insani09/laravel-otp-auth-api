# Analisis Sistem Wilayah

## Kesimpulan utama

Sistem ini adalah **cascading location selector hibrida**. Ia menyatukan dua sumber data dan dua arti hierarki yang berbeda di bawah satu tampilan formulir:

| Cakupan | Sumber kebenaran | Hierarki yang dipakai | Sifat data |
|---|---|---|---|
| Indonesia | Tabel lokal hasil seed `azishapidin/indoregion` | provinsi → kabupaten/kota → kecamatan | Relasional, dapat dipakai tanpa internet |
| Luar negeri | GeoNames, melalui backend Laravel | negara → subdivision/ADM1 → populated place | Hasil pencarian eksternal, bergantung koneksi dan kelengkapan GeoNames |

Jadi tampilan memang memakai istilah yang serupa—Negara, Provinsi, Kota/Kabupaten, Kecamatan—tetapi **maknanya tidak identik**. Untuk Indonesia, "kota/kabupaten" dan "kecamatan" adalah tingkatan administrasi resmi dalam dataset lokal. Untuk negara lain, "provinsi/state" adalah unit ADM1 GeoNames dan "kota" berarti sembarang *populated place* (`featureClass=P`); kecamatan tidak dimodelkan dan sengaja disembunyikan.

## Arsitektur dan aliran data

```text
Browser (Select2 + jQuery)
        │ GET /api/...
        ▼
RegionController
  ├─ Indonesia ─────► MySQL: provinces → regencies → districts
  └─ Internasional ─► GeoNames HTTPS dengan username server-side
                              │
                              └─ hasil dinormalisasi menjadi { id, text }
        ▼
Dropdown browser
        │ hanya label terpilih
        ▼
hidden input → register/profile endpoint → kolom teks users
```

Proxy backend untuk GeoNames adalah keputusan yang benar secara prinsip: browser tidak menerima kredensial layanan. GeoNames sendiri mensyaratkan parameter `username` pada setiap request dan menyediakan endpoint aman `secure.geonames.org`. [Dokumentasi GeoNames](https://www.geonames.org/export/web-services.html)

## API yang benar-benar tersedia

Semua endpoint wilayah bersifat `GET`, berada di `routes/api.php`, dan saat ini **tidak memerlukan autentikasi**.

| Endpoint | Sumber | Input | Respons sukses | Fungsi di UI |
|---|---|---|---|---|
| `/api/provinces` | `provinces` lokal | — | `[{id,name}]` | setelah memilih Indonesia |
| `/api/regencies/{provinceId}` | `regencies` lokal | ID provinsi dua karakter | `[{id,name}]` | setelah memilih provinsi Indonesia |
| `/api/districts/{regencyId}` | `districts` lokal | ID kabupaten/kota empat karakter | `[{id,name}]` | setelah memilih kabupaten/kota Indonesia |
| `/api/geo/countries` | GeoNames `countryInfoJSON` | — | `{results:[{id:ISO2,text:nama}]}` | daftar negara |
| `/api/geo/subdivisions/{countryCode}` | GeoNames `searchJSON` | ISO alpha-2 | `{results:[{id:adminCode1,text:nama}]}` | provinsi/state luar negeri |
| `/api/geo/cities/{countryCode}/{adminCode1}` | GeoNames `searchJSON` | ISO alpha-2, adminCode1 | `{results:[{id:geonameId,text:nama}]}` | kota luar negeri |

Kontrak respons Indonesia dan internasional tidak sama dengan sengaja: frontend mengubah `{id,name}` lokal menjadi `{id,text}`, sedangkan backend GeoNames telah mengeluarkan `{id,text}`. Implementasi utamanya berada di [RegionController.php](app/Http/Controllers/RegionController.php:17) dan pemetaannya di [region-cascading.blade.php](resources/views/partials/region-cascading.blade.php:220).

Contoh alur Indonesia:

```text
Pilih ID (Indonesia)
  → GET /api/provinces
  → pilih `32` (contoh ID provinsi)
  → GET /api/regencies/32
  → pilih `3273` (contoh ID kota/kabupaten)
  → GET /api/districts/3273
  → pilih kecamatan
```

Contoh alur luar negeri:

```text
Pilih US
  → GET /api/geo/subdivisions/US
  → pilih adminCode1, misalnya `CA`
  → GET /api/geo/cities/US/CA
  → pilih sebuah GeoNames geonameId
  → tidak ada request kecamatan
```

`adminCode1` bukan ID database aplikasi; ia adalah kode administrasi level pertama yang diberikan GeoNames. `geonameId` adalah ID objek geografis GeoNames. Keduanya hanya hidup selama proses pilihan di browser—tidak disimpan pada tabel `users`.

## Indonesia: bagaimana data lokal bekerja

Migrasi membentuk relasi berikut:

```text
provinces.id (char 2)
  1 └── * regencies.province_id; regencies.id (char 4)
              1 └── * districts.regency_id; districts.id (char 7)
                              1 └── * villages.district_id; villages.id (char 10)
```

Foreign key untuk rantai tersebut ada di migrasi [regencies](database/migrations/2017_05_02_140444_create_regencies_tables.php:23), [districts](database/migrations/2017_05_02_142019_create_districts_tables.php:23), dan [villages](database/migrations/2017_05_02_143454_create_villages_tables.php:23). `DatabaseSeeder` memanggil `IndoRegionSeeder`, yang mengimpor semua level hingga desa/kelurahan.

Namun aplikasi hanya mengekspos sampai kecamatan. Tabel/model `villages` ada, tetapi tidak ada route, method controller, atau dropdown desa. Ini bukan kesalahan bila kebutuhan memang berhenti di kecamatan, tetapi penting dipahami: data desa yang di-seed saat ini belum dipakai.

Paket IndoRegion memang dirancang untuk mengimpor data provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan ke database lokal. Dokumentasi paket juga menyebut sumber datanya berasal dari pemutakhiran BPS pada **11 Januari 2018**. Artinya data lokal praktis dan offline, tetapi perlu dinilai ulang sebelum dipakai sebagai referensi administratif mutakhir; perubahan wilayah setelah tanggal tersebut berisiko belum ada. [Dokumentasi IndoRegion](https://github.com/azishapidin/indoregion/blob/master/README.md)

## Luar negeri: bagaimana GeoNames dipakai

`RegionController::geoNames()` membaca `services.geonames.username`, lalu mengirim request dari Laravel ke `https://secure.geonames.org/...`. Ia menetapkan timeout 10 detik dan retry dua kali; kegagalan koneksi menjadi HTTP 503, sedangkan respons GeoNames yang tidak sukses menjadi 502. Lihat [RegionController.php](app/Http/Controllers/RegionController.php:146).

Pemetaan yang digunakan adalah:

| Tahap | Parameter GeoNames | Filter | ID yang dikirim ke browser |
|---|---|---|---|
| negara | `countryInfoJSON` | kode dua huruf | `countryCode` |
| subdivision | `searchJSON?country=XX&featureCode=ADM1` | ADM1 | `adminCode1` |
| "kota" | `searchJSON?country=XX&adminCode1=...&featureClass=P` | feature class `P` | `geonameId` |

GeoNames `searchJSON` adalah layanan pencarian, bukan register administrasi yang menjamin setiap negara mempunyai struktur tiga tingkat yang sama. Dokumentasinya mendukung paging (`startRow`, `maxRows`) dan membatasi `maxRows` maksimal 1.000. Implementasi ini selalu meminta paling banyak 300 dan tidak menyediakan paging, sehingga daftar lokasi bisa tidak lengkap pada subdivision yang besar. [Dokumentasi GeoNames Search](https://www.geonames.org/export/geonames-search.html)

`featureClass=P` berarti *populated place*, bukan jaminan "municipality" resmi. Konsekuensinya, label UI "Kota" untuk luar negeri adalah penyederhanaan; hasil dapat mencakup kota, town, village, atau tempat berpenduduk lain sesuai klasifikasi sumber.

## Apa yang disimpan saat pengguna mendaftar atau mengubah profil

Dropdown memakai ID internal sebagai `value`, tetapi JavaScript menyalin **teks yang terlihat** ke hidden input: `negara`, `provinsi`, `kota`, dan `kecamatan`. Lihat [region-cascading.blade.php](resources/views/partials/region-cascading.blade.php:95). Controller profil kemudian hanya memvalidasi bahwa nilai tersebut string dengan panjang tertentu dan menyimpannya ke empat kolom teks pengguna, tanpa memverifikasi relasi wilayahnya. [ProfileController.php](app/Http/Controllers/ProfileController.php:31)

Efeknya:

1. Data profil mudah dibaca dan ditampilkan sebagai alamat.
2. Tetapi tidak tersimpan `country_code`, ID provinsi/regency/district Indonesia, `adminCode1`, atau `geonameId` luar negeri.
3. Sistem tidak dapat membedakan dua tempat bernama sama secara andal, melakukan join geografis, memvalidasi bahwa kecamatan benar milik kabupaten, atau mempertahankan identitas saat nama sumber berubah.
4. Klien yang mengirim request langsung dapat menyimpan kombinasi nama yang tidak valid karena backend tidak memvalidasi master data.

## Temuan dan prioritas

### Kritis — pengeditan profil dapat menghapus wilayah lama

Saat profil dibuka, kode hanya memilih kembali negara, lalu setelah 600 ms mengisi hidden input dengan nama lama. Ia **tidak memilih ulang** provinsi → kota → kecamatan pada dropdown berdasarkan ID. Pada saat submit, `syncHidden()` menimpa hidden input berdasarkan dropdown yang masih kosong. Jika pengguna mengubah nama/avatar saja dan menyimpan, wilayah lama dapat terkirim sebagai kosong. Lihat [profile.blade.php](resources/views/profile.blade.php:317) dan pemanggilan `syncHidden()` di [profile.blade.php](resources/views/profile.blade.php:505).

Perbaikan: simpan ID/kode, lalu buat `setLocation()` asinkron yang menunggu tiap daftar selesai dimuat, memilih parent dan child secara berurutan, baru menyinkronkan label. Hindari `setTimeout` tetap karena waktu jaringan tidak deterministik.

### Tinggi — master data internasional belum benar-benar memiliki tabel

Ada model `Country` dan `City`, tetapi keduanya tidak dipakai oleh controller. Bahkan migrasi `countries` hanya berisi ID dan timestamps—tanpa kode maupun nama—sedangkan `cities` tidak mempunyai foreign key, `geonameId`, seed, atau pembacaan. [create_countries_table.php](database/migrations/2026_08_11_025142_create_countries_table.php:14) dan [create_cities_table.php](database/migrations/2026_08_11_030926_create_cities_table.php:14). Jadi jangan menganggap tabel ini sebagai cache GeoNames atau sumber data luar negeri; saat ini ia dead schema.

### Tinggi — ketidaklengkapan dan beban layanan GeoNames

Endpoint publik tidak punya cache atau rate limit aplikasi. Setiap pengguna dapat memicu request GeoNames; setiap kegagalan dapat diretry. Browser menunggu 12 detik sedangkan backend dapat mencoba ulang request 10-detik, sehingga browser dapat timeout lebih dulu sementara backend masih bekerja. Daftar kota dibatasi 300 hasil tanpa pagination. Tambahkan cache per parameter (negara/subdivision), throttling, timeout yang selaras, serta search/autocomplete/pagination untuk kota.

### Tinggi — versi dataset Indonesia berpotensi usang

Sumber paket mencantumkan pemutakhiran 2018. Audit jumlah dan nama wilayah terhadap dataset resmi yang berlaku sebelum sistem dipakai sebagai rujukan legal/operasional. Untuk kebutuhan alamat biasa, dampaknya lebih rendah; untuk kepatuhan, pengiriman, pajak, atau statistik, gunakan dataset yang dikelola dan memiliki proses pembaruan.

### Sedang — kontrak API dan test sudah tidak sinkron

Controller mengembalikan `results.0.id` dan `results.0.text`, tetapi test mengharapkan `countries.0.countryName` dan `subdivisions.0.name`. [GeoNamesRegionTest.php](tests/Feature/GeoNamesRegionTest.php:34). Test ini akan gagal setelah aplikasi dapat dijalankan, meski fake HTTP-nya benar.

Verifikasi runtime belum dapat dilakukan di workspace saat ini: `php artisan test` berhenti sebelum Laravel bootstrap karena PHP aktif 8.2.30, sementara dependency terpasang meminta minimal PHP 8.4.1. `composer.json` sendiri mendeklarasikan minimal PHP 8.3, sehingga requirement proyek dan vendor terpasang juga tidak konsisten. [composer.json](composer.json:8)

### Sedang — respons lokal untuk ID yang tidak ada ambigu

`/api/regencies/{provinceId}` dan `/api/districts/{regencyId}` mengembalikan `[]` dengan status 200 baik untuk parent yang valid tetapi kosong maupun ID yang tidak ada. Untuk dropdown ini cukup, tetapi untuk API publik lebih jelas bila ID divalidasi dan parent tidak ditemukan mengembalikan 404/422.

### Rendah — integritas skema IndoRegion

ID wilayah pada migrasi diberi `index()`, bukan `primary()` atau `unique()`. Foreign key tetap dapat dibuat pada database yang menerima index target, namun keunikan ID tidak ditegakkan secara eksplisit oleh skema aplikasi. Karena data seed tepercaya, risiko praktis saat ini terbatas; tetap jadikan primary/unique bila skema dimodernisasi.

## Rekomendasi rancangan

Pilih salah satu tujuan, karena keduanya menghasilkan desain berbeda:

| Jika tujuan utama | Rancangan yang tepat |
|---|---|
| Form alamat sederhana lintas negara | Simpan `country_code`, `admin1_code`, `place_geoname_id` **beserta snapshot nama**; cache GeoNames; label luar negeri menjadi “State/Province” dan “City/Locality”. |
| Data alamat Indonesia yang ketat | Jadikan ID IndoRegion sebagai foreign key/logical key pada user; validasi rantai parent-child server-side; tetap simpan nama sebagai snapshot opsional. |
| Pelaporan/analitik global | Buat tabel master country/subdivision/place yang memiliki `source`, `external_id`, `parent_id`, `name`, `code`, dan `synced_at`; GeoNames menjadi proses sinkronisasi, bukan dependency langsung saat pengguna mengisi form. |

Urutan perbaikan yang paling aman:

1. Perbaiki restore cascading profil agar tidak mengosongkan data.
2. Tambahkan kolom kode/ID wilayah dan migrasikan data label lama sebagai snapshot.
3. Validasi server-side untuk rantai Indonesia dan bentuk kode ISO luar negeri.
4. Tambahkan cache, rate limit, observability, serta pagination/autocomplete untuk GeoNames.
5. Putuskan apakah tabel `countries`/`cities` akan diisi dan dipakai sebagai cache/master, atau hapus melalui migrasi baru agar tidak membingungkan.
6. Perbarui dataset Indonesia dan tambah test API berdasarkan kontrak `results` saat ini, termasuk 422/502/503 dan skenario profil.

## Batasan analisis

Analisis ini menilai kode dan konfigurasi proyek yang tersedia serta dokumentasi sumber data. Tidak ada perubahan aplikasi dilakukan. Konektivitas dan respons live GeoNames tidak diuji agar tidak memakai kuota layanan produksi; test Laravel juga tidak dapat dieksekusi sampai runtime PHP diselaraskan.

## Sumber

1. [GeoNames Web Service Documentation](https://www.geonames.org/export/web-services.html) — autentikasi username, endpoint aman, dan layanan country info.
2. [GeoNames Search Webservice](https://www.geonames.org/export/geonames-search.html) — parameter pencarian, `maxRows`, dan paging.
3. [azishapidin/indoregion README](https://github.com/azishapidin/indoregion/blob/master/README.md) — cakupan paket, proses seed, dan asal/tanggal dataset.
4. Implementasi proyek: [RegionController.php](app/Http/Controllers/RegionController.php:17), [region-cascading.blade.php](resources/views/partials/region-cascading.blade.php:1), dan migrasi wilayah pada folder `database/migrations`.
