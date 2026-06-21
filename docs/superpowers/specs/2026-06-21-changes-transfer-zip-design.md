# Transfer Perubahan via ZIP — Design

**Tanggal:** 2026-06-21
**Status:** Disetujui (menunggu review spec)

## Tujuan

Memudahkan transfer perubahan kode dari satu mesin ke mesin teman tanpa lewat
git remote. Alurnya:

1. Bungkus perubahan working tree ke sebuah file zip.
2. Kirim zip ke teman (chat/flashdisk/dll).
3. Teman extract & timpa ke project miliknya.
4. Teman review (`git status` / `git diff`) lalu commit sendiri.

## Lingkup

Dua Artisan command:

### 1. `php artisan changes:pack`

- Mendeteksi file yang berbeda dari `HEAD` menggunakan **git**:
  - File **modified** dan **untracked (baru)** → dimasukkan ke dalam zip.
  - File **deleted (dihapus)** → dicatat ke dalam manifest (lihat di bawah).
- Otomatis menghormati `.gitignore` karena pakai git (mis. `vendor/`,
  `node_modules/`, `.env` tidak ikut).
- Menulis semua file modified/untracked ke `changes.zip` di root project,
  dibungkus dalam satu folder wrapper, dengan struktur path persis seperti
  project. Contoh isi: `changes/app/Http/Controllers/Controller.php`.
- Menulis **manifest** `changes/.changes-manifest.json` di dalam zip berisi
  daftar path file yang harus **dihapus** di sisi penerima. Array shape:
  `{ "deleted": string[] }` (path relatif terhadap root project).
- Menampilkan ringkasan: jumlah file ditambah/diubah dan jumlah file dihapus.

**Argumen/opsi:**
- `--output=` (opsional) path file zip output. Default: `changes.zip`.

### 2. `php artisan changes:apply {zipfile}`

- Membuka zip, mendeteksi otomatis **satu folder wrapper** di level teratas dan
  menghapusnya dari path (`changes/app/...Controller.php` →
  `app/...Controller.php`). Jika tidak ada wrapper tunggal, path dipakai apa
  adanya relatif ke root project.
- Menyalin tiap file ke lokasi yang sesuai di project, **menimpa** file lama,
  membuat direktori induk bila belum ada. (File `.changes-manifest.json` di
  dalam wrapper tidak disalin sebagai file biasa.)
- Membaca manifest `.changes-manifest.json`; setiap path di `deleted`
  **dihapus** dari project bila ada. File yang sudah tidak ada dilewati.
- Menampilkan daftar file yang ditimpa/dibuat dan file yang dihapus, beserta
  jumlahnya.

**Argumen:**
- `{zipfile}` (wajib) path ke file zip yang akan di-apply.

## Keamanan

`changes:apply` **menimpa file tanpa membuat backup**. Ini aman karena command
dijalankan di dalam git repo: setelah apply, teman menjalankan
`git status` / `git diff` untuk meninjau seluruh perubahan sebelum commit.
Git menjadi jaring pengaman, sehingga backup manual tidak diperlukan.

Validasi minimum pada `apply`:
- File zip harus ada dan valid; jika tidak, command gagal dengan pesan jelas.
- Path di dalam zip dinormalisasi & dicegah keluar dari root project
  (proteksi zip-slip / path traversal).

## Yang TIDAK dilakukan (YAGNI)

- Tidak ada metadata lain di zip selain manifest penghapusan.
- Tidak ada konfirmasi interaktif saat apply.
- Tidak ada mode dry-run (bisa ditambah nanti bila perlu).

## Arsitektur

- Dua class command di `app/Console/Commands/`:
  - `ChangesPackCommand` (`changes:pack`)
  - `ChangesApplyCommand` (`changes:apply`)
- `pack` menjalankan git via `Illuminate\Support\Facades\Process` untuk
  mendapatkan daftar file:
  - `git ls-files --modified --others --exclude-standard` → modified + untracked
    (untuk dimasukkan ke zip), sudah menghormati `.gitignore`.
  - `git ls-files --deleted` → file yang dihapus (untuk manifest).
- Pembuatan & ekstraksi zip memakai ekstensi `ZipArchive` bawaan PHP.
- Path file ditangani relatif terhadap `base_path()`.

## Testing (Pest, feature test)

- **pack:** siapkan repo sementara / file dummy yang diubah, jalankan
  `changes:pack`, assert zip terbentuk dan berisi file yang diharapkan dengan
  struktur wrapper yang benar.
- **apply:** buat zip berisi file dummy + manifest, ubah/hapus file target,
  jalankan `changes:apply`, assert isi file di project sesuai isi dari zip dan
  file pada manifest `deleted` ikut terhapus.
- **keamanan:** assert entri zip dengan path `../` tidak menulis di luar root.
