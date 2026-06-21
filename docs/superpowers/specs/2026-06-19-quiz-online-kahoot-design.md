# Quiz Online — Desain (Klon Kahoot Real-time)

- **Tanggal:** 2026-06-19
- **Status:** Disetujui (hasil brainstorming)
- **Konteks:** Tugas kuliah Web Development. Sengaja dijaga ramping — bukan produk skala besar.

## 1. Ringkasan

Aplikasi kuis online real-time gaya Kahoot. **Host** (user terdaftar) membuat bank
soal dan memandu sesi game live. **Peserta** bergabung lewat **PIN + nickname**
tanpa perlu membuat akun. Soal berganti serempak dengan hitung mundur per soal.
Skor dihitung dari **benar + kecepatan**, dan setiap game punya **leaderboard**
sendiri.

### Keputusan inti

| Aspek | Keputusan |
|---|---|
| Jenis game | Live serempak, dipandu host (Kahoot-style) |
| Peran | Host (punya akun) + Peserta (tamu, PIN + nickname) |
| Jenis soal | Pilihan ganda saja |
| Timer | Hitung mundur per soal |
| Skoring | Benar + cepat (lebih cepat = poin lebih besar) |
| Pengerjaan ulang | 1x per kuis (alami dalam satu sesi) |
| Leaderboard | Per game/sesi |
| Real-time | Laravel Reverb (WebSocket) |

### Stack & dependency

- **Sudah ada:** Laravel 13, Tailwind v4, Vite, Pest 4.
- **Dependency baru yang disetujui (wajib untuk jalur Reverb):**
  - `laravel/reverb` (server WebSocket)
  - `laravel/echo` + `pusher-js` (klien JS penerima broadcast)
- **Dibangun manual tanpa library tambahan:** auth host (auth bawaan Laravel),
  UI interaktif (Blade + JavaScript biasa + Tailwind). **Tanpa** Livewire,
  Breeze, Alpine, maupun Inertia.

## 2. Arsitektur & Peran

- **Host** (user terdaftar): login, kelola bank kuis, pandu game live.
- **Peserta** (tamu): tidak daftar; masuk via PIN + nickname. Identitas disimpan
  via cookie `session_token` agar bisa refresh/rejoin tanpa kehilangan skor.

**Prinsip kunci — server adalah sumber kebenaran waktu.** Countdown di layar
peserta hanya visual. Server mencatat kapan soal dimulai; saat peserta menjawab,
**server** yang menghitung waktu respons & poin. Ini mencegah manipulasi jam
klien dan masalah selisih waktu.

**Alur broadcast.** State game disimpan di database. Aksi host (Mulai, Soal
Berikutnya) lewat HTTP biasa → server mengubah state → server menyiarkan event
lewat channel Reverb `game.{sessionId}` → semua layar peserta & host update
seketika.

## 3. Model Data

| Tabel | Kolom penting | Relasi |
|---|---|---|
| `users` | name, email, password (bawaan Laravel) | punya banyak `quizzes` |
| `quizzes` | title, description, is_published | milik user; punya banyak `questions` |
| `questions` | question_text, time_limit (detik), points_base, position | milik quiz; punya banyak `options` |
| `options` | option_text, is_correct, position (2–4 opsi/soal) | milik question |
| `game_sessions` | game_pin (unik), status, current_question_index, current_question_started_at | milik quiz + host |
| `participants` | nickname, session_token, total_score | milik game_session |
| `answers` | selected_option_id, is_correct, response_time_ms, points_awarded | milik participant + question |

**Constraint kunci:**
- `answers` unik per (participant, question) → cegah jawab ganda.
- `game_pin` unik & pendek (mis. 6 digit).
- nickname unik dalam satu `game_session`.

**Status `game_sessions`:**
`lobby` → `question_active` → `question_results` → (ulang) → `finished`.

## 4. Daftar Halaman

### Sisi Host (perlu login)

1. **Landing / Welcome** — penjelasan singkat + kotak besar "Masuk PIN" (peserta)
   + tombol Login/Daftar (host).
2. **Daftar / Login** — form auth host.
3. **Dashboard** — daftar kuis milikku; tombol Buat Kuis, Edit, Hapus, Mulai Host.
4. **Editor Kuis** — atur judul & deskripsi; tambah/edit/hapus/urutkan soal. Tiap
   soal: teks, batas waktu, 2–4 opsi dengan menandai mana yang benar.
5. **Lobby Host** — PIN besar, daftar peserta yang masuk (real-time), tombol Mulai.
6. **Layar Game Host** (per soal) — soal + opsi (layar proyektor), jumlah peserta
   yang sudah menjawab, countdown. Setelah waktu habis → ungkap jawaban benar +
   diagram batang sebaran jawaban + leaderboard sementara; tombol Soal Berikutnya.
7. **Hasil Akhir Host** — podium juara + leaderboard lengkap.

### Sisi Peserta (tamu, tanpa akun)

8. **Halaman Gabung** — masukkan PIN → masukkan nickname.
9. **Lobby Peserta** — "Kamu sudah masuk! Menunggu host memulai…" + nickname.
10. **Layar Soal Peserta** — teks soal + tombol opsi warna-warni + countdown.
    Setelah memilih → "Jawaban terkunci, menunggu peserta lain…".
11. **Hasil Per Soal Peserta** — benar/salah + poin yang didapat + peringkat saat ini.
12. **Layar Akhir Peserta** — peringkat akhir / podium.

## 5. Alur Game & Event Real-time

### State machine

```
lobby ──(host: Mulai)──► question_active ──(waktu habis / semua jawab)──► question_results
   ▲                                                                          │
   └──────────────── (bukan soal terakhir) ◄── (host: Soal Berikutnya) ◄──────┤
                                                                              │
                                          (soal terakhir) ──► finished ◄──────┘
```

### Event broadcast (channel `game.{sessionId}`)

| Event | Dipicu oleh | Isi | Penerima |
|---|---|---|---|
| `ParticipantJoined` | peserta gabung | nickname, jumlah peserta | layar lobby host |
| `GameStarted` | host klik Mulai | — | semua |
| `QuestionStarted` | server | soal + opsi + batas waktu + waktu mulai | semua |
| `AnswerReceived` | peserta menjawab | jumlah jawaban masuk | host |
| `QuestionEnded` | server (waktu habis) | opsi benar + sebaran + leaderboard | semua |
| `GameEnded` | server (soal terakhir) | podium & leaderboard akhir | semua |

**Otoritas waktu:** saat `QuestionStarted` disiarkan, server set
`current_question_started_at`. Saat peserta submit, server hitung
`response_time_ms = now − started_at`, validasi masih dalam batas waktu, lalu
hitung poin.

## 6. Skoring

Hanya jawaban **benar** dapat poin; salah/tidak jawab = 0.

```
poin = round( points_base × (1 − (response_time_ms / time_limit_ms) / 2) )
```

- Jawab benar tercepat ≈ poin penuh; benar paling lambat ≈ setengah poin.
- `points_base` default mis. **1000**.
- `total_score` peserta = jumlah poin semua soal.
- Leaderboard urut `total_score` menurun.

## 7. Edge Case yang Ditangani

- **Nickname kembar** di sesi sama → tolak, minta ganti.
- **Gabung setelah game mulai** → tolak ("game sudah berjalan").
- **PIN tidak ada / game selesai** → pesan error jelas.
- **Peserta refresh / putus** → rejoin otomatis via cookie `session_token`, skor tetap.
- **Jawab ganda** → ditolak constraint unik (participant, question); hanya yang pertama dihitung.
- **Host buka kuis tanpa soal** → tombol Mulai dinonaktifkan.

## 8. Testing (Pest)

- **Feature test:** CRUD kuis; gabung via PIN (sukses & gagal); perhitungan poin
  (benar/salah/cepat/lambat); transisi state game; constraint jawab ganda. Pakai
  factory + `Event::fake()` untuk verifikasi broadcast.
- **Browser test (Pest 4)** opsional: satu alur happy-path host → peserta → hasil.

## 9. Pembagian Fase

- **Fase A — Fondasi (tanpa real-time):** auth host; model & migrasi; CRUD kuis +
  soal + opsi; seeder contoh. Bisa dites penuh tanpa Reverb.
- **Fase B — Game live:** `game_sessions` + PIN; gabung peserta; state machine;
  setup Reverb/Echo; event broadcast; layar host & peserta; skoring & leaderboard.
- **Fase C — Poles:** hasil akhir/podium; edge case; testing; styling Tailwind.

## 10. Di Luar Lingkup (catatan untuk masa depan — TIDAK dikerjakan di project ini)

Disimpan sebagai ide, sengaja tidak dimasukkan ke rencana implementasi agar
project tetap ramping sesuai kebutuhan tugas kuliah:

- QR code untuk gabung cepat; avatar/warna peserta; efek suara & animasi.
- Gambar pada soal; tipe soal lain (Benar/Salah, isian singkat).
- Riwayat & analitik game per host.
- Perpustakaan kuis publik (jelajah & pakai kuis orang lain).
- Leaderboard global akumulatif lintas game.
