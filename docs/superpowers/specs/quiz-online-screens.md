# Quiz Online — Screen Requirements & Stitch Prompts

> Companion to **`DESIGN.md`** (visual language) and the design spec
> (`2026-06-19-quiz-online-kahoot-design.md`). This file describes **what is on each
> screen** (elements, content, actions, states) and gives a **copy-paste prompt for
> Stitch** per screen.
>
> **How to use in Stitch:** import/attach `DESIGN.md` for style, then paste one
> "Stitch prompt" block below to generate that screen. Keep the global style line
> identical across screens so the set stays consistent.

**Global style reminder (already in DESIGN.md):** Poppins (display) + Inter (body);
brand violet `#6D28D9`; four fixed answer colors with shape icons (1▲ red, 2◆ blue,
3● yellow, 4■ green); `rounded-xl/2xl`; soft shadows; touch targets ≥44px. Host screens =
light, bordered cards, centered `max-w-4xl`, desktop-first. Participant screens =
full-bleed, mobile-first, thumb-first.

---

## HOST SIDE (logged in)

### 1. Landing / Welcome
**Context:** Public, desktop + mobile. Entry point for both roles.
**Goal:** Participant joins fast; host finds login/register.

**Elements (top → bottom):**
- Top bar: app logo/name (left), "Login" + "Daftar" buttons (right).
- Hero on brand gradient: headline "Quiz Online", subtitle one line.
- **Big "Masuk PIN" box** (center, dominant): large numeric input + "Gabung" button.
- Secondary row: "Punya kuis? Login sebagai Host" link.
- Footer: small credit line.

**States:** invalid/empty PIN → inline error under the box.

**Stitch prompt:**
> Landing page for a Kahoot-style quiz app, following DESIGN.md. Top bar with logo and
> Login/Daftar buttons. Centered hero on the violet→pink gradient with a large white
> "Masuk PIN" card containing a big numeric PIN input and a primary "Gabung" button.
> Below it a small "Login sebagai Host" link. Playful, energetic, one dominant action.

---

### 2. Login / Register
**Context:** Host only, centered `max-w-sm` card on light bg.
**Goal:** Authenticate or create a host account.

**Elements — Login:**
- Heading "Login Host".
- Fields: Email, Password.
- Checkbox "Ingat saya".
- Primary button "Login"; link "Belum punya akun? Daftar".

**Elements — Register:**
- Heading "Daftar sebagai Host".
- Fields: Nama, Email, Password, Konfirmasi Password.
- Primary button "Daftar"; link "Sudah punya akun? Login".

**States:** field-level validation errors in red under each field; wrong credentials → error above form.

**Stitch prompt:**
> Centered authentication card (max-w-sm) following DESIGN.md. Create two variants: a
> Login form (Email, Password, "Ingat saya" checkbox, "Login" primary button, link to
> Register) and a Register form (Nama, Email, Password, Konfirmasi Password, "Daftar"
> button, link to Login). Show inline red validation error text under fields. Light
> surface, rounded-lg inputs with violet focus ring.

---

### 3. Dashboard (Quiz List)
**Context:** Host home after login, `max-w-4xl`.
**Goal:** Manage own quizzes.

**Elements:**
- Header row: title "Kuis Saya" (left); buttons "Buat Kuis" (primary) + "Logout" (ghost) (right).
- **List of quiz cards**, one per quiz. Each card shows:
  - Quiz title (bold).
  - Meta line: status badge (Terbit = green / Draf = muted) + "{N} soal".
  - **Action buttons:** "Mulai Host" (primary, **disabled if 0 questions**), "Edit", "Hapus" (danger, with confirm dialog).
- **Empty state:** dashed card "Belum ada kuis. Buat yang pertama!" + Buat Kuis button.

**States:** empty list; delete confirmation; disabled "Mulai Host" with tooltip "Tambahkan soal dulu".

**Stitch prompt:**
> Host dashboard following DESIGN.md, centered max-w-4xl. Header with "Kuis Saya" title
> and "Buat Kuis" primary + "Logout" ghost buttons. Vertical list of quiz cards; each
> card shows the quiz title, a status badge (Terbit/Draf) and "N soal" meta, and three
> actions: "Mulai Host" (primary), "Edit" (secondary), "Hapus" (danger). Include a
> disabled "Mulai Host" example and an empty state card "Belum ada kuis. Buat yang
> pertama!". Bordered cards, soft shadow, hover lift.

---

### 4. Quiz Editor
**Context:** Host, `max-w-4xl`. Create/edit quiz + manage questions.
**Goal:** Set quiz info; add/edit/delete questions with 2–4 options and mark the correct one.

**Elements:**
- Back link "← Kembali ke Dashboard".
- **Quiz settings form:** Judul, Deskripsi, "Terbitkan" toggle, "Simpan Pengaturan" button.
- Divider, then **"Soal (N)"** heading.
- **Question list:** each item shows position number + question text, its options (✅ marks correct), time limit; per item "Edit" + "Hapus" (confirm).
- **Add-question form:**
  - Field: Pertanyaan (text).
  - Field: Batas waktu (number, seconds, 5–120).
  - **Options block:** 2–4 rows, each = a radio (mark correct) + text input; "+ Tambah opsi (maks 4)" button; ability to remove a row.
  - "Tambah Soal" primary button.

**States:** empty question list; validation (min 2 options, must pick correct, title required).

**Stitch prompt:**
> Quiz editor screen following DESIGN.md, centered max-w-4xl. Top: a "Kembali" back link
> and a quiz settings form (Judul input, Deskripsi textarea, "Terbitkan" toggle, "Simpan
> Pengaturan" button). Below a divider: a "Soal (N)" section listing existing questions as
> cards — each shows the number, question text, its 2–4 options with a check icon on the
> correct one, the time limit, and Edit/Hapus actions. Then an "Tambah Soal" form: a
> question text input, a numeric "Batas waktu (detik)" input, an options block of
> radio+text rows (2 by default, "+ Tambah opsi" up to 4, each removable, radio = correct
> answer), and a "Tambah Soal" primary button.

---

### 5. Host Lobby
**Context:** Host, full-bleed on gradient (projector). Waiting room before start.
**Goal:** Show PIN, gather participants live, start the game.

**Elements:**
- Centered hero panel: label "Game PIN" + the PIN in huge display-XL weight 800.
- Helper line: "Buka [url] dan masukkan PIN ini".
- **Live participant area:** chips/avatars that pop in as people join; counter "N peserta".
- Primary button "Mulai" (large; **disabled until ≥1 participant**).
- Small "Batalkan sesi" link.

**States:** 0 participants (Start disabled); participants joining (animated); quiz with 0 questions can't reach here.

**Stitch prompt:**
> Host lobby for a live quiz, full-bleed on the violet→pink gradient, following DESIGN.md.
> A large white rounded panel centered: "Game PIN" label and a huge bold PIN number. Below,
> a helper line and a grid of participant name chips that appear as players join, with a
> live "N peserta" counter. A big "Mulai" primary button (show a disabled state when zero
> participants) and a small "Batalkan sesi" link. Celebratory, projector-friendly, big type.

---

### 6. Host Game Screen (per question)
**Context:** Host, full-bleed projector. Two sub-states: **Active** and **Results**.
**Goal:** Display the question to the room, then reveal the answer and standings.

**Elements — ACTIVE:**
- Top bar: question counter "Soal 2 / 5", countdown timer (ring/number, color shifts), "{N} menjawab" count.
- Big question text (display).
- The 2–4 answer options shown as colored tiles with shape icons (▲◆●■). No correct indication yet.

**Elements — RESULTS (after time/all answered):**
- Correct option highlighted (✓), wrong ones dimmed.
- **Answer distribution bar chart** — one bar per option, colored by position color, height ∝ count, ✓ on correct.
- **Interim leaderboard** (top 5): rank + nickname + score.
- Primary button "Soal Berikutnya" (or "Lihat Hasil Akhir" on last question).

**Stitch prompt:**
> Host game screen for a live quiz (projector, full-bleed), following DESIGN.md. Create two
> states. ACTIVE: top bar with "Soal X / Y", a circular countdown timer, and a "N menjawab"
> counter; a large question text; and the 2–4 answer options as big colored tiles with
> shape icons (▲ red, ◆ blue, ● yellow, ■ green). RESULTS: highlight the correct option
> with a check and dim the rest, show a horizontal bar chart of how many picked each option
> (bars colored per option), an interim leaderboard (top 5: rank, nickname, score), and a
> "Soal Berikutnya" primary button.

---

### 7. Host Final Results
**Context:** Host, full-bleed projector. End of game.
**Goal:** Celebrate winners; show full standings.

**Elements:**
- **Podium:** 3 pedestals ordered 2-1-3 (gold/silver/bronze), winner center with crown/sparkle, entrance animation.
- **Full leaderboard** below: rank + nickname + final score (`tabular-nums`).
- Buttons: "Main Lagi" (new session of same quiz) + "Kembali ke Dashboard".

**Stitch prompt:**
> Final results screen for a live quiz (projector, full-bleed) following DESIGN.md. A
> celebratory podium with three pedestals (2nd left, 1st center taller with a crown, 3rd
> right) in gold/silver/bronze, plus a full leaderboard list below (rank, nickname, final
> score). Two buttons: "Main Lagi" and "Kembali ke Dashboard". Confetti/celebration mood.

---

## PARTICIPANT SIDE (guest, no account)

### 8. Join
**Context:** Participant, mobile-first, full-bleed gradient. Two steps.
**Goal:** Enter PIN, then nickname.

**Elements — Step 1 (PIN):** big centered numeric input (`tracking-wide`), "Gabung" button.
**Elements — Step 2 (Nickname):** big text input "Nickname", "Masuk" button.

**States:** PIN not found / game already started / finished → clear error message; duplicate nickname → "Nickname sudah dipakai, coba lain".

**Stitch prompt:**
> Participant join flow for a Kahoot-style quiz, mobile-first and full-bleed on the
> gradient, following DESIGN.md. Step 1: a large centered numeric PIN input with wide
> letter-spacing and a "Gabung" button. Step 2: a large "Nickname" text input and a
> "Masuk" button. Show example error messages ("PIN tidak ditemukan", "Nickname sudah
> dipakai"). Minimal, big, thumb-friendly.

---

### 9. Participant Lobby
**Context:** Participant, mobile, full-bleed.
**Goal:** Confirm joined; wait for host.

**Elements:**
- Big check/celebration icon.
- "Kamu sudah masuk!" + the player's nickname prominently.
- "Menunggu host memulai…" with a subtle animated indicator.

**Stitch prompt:**
> Participant waiting lobby (mobile, full-bleed gradient) following DESIGN.md. A big
> success check, "Kamu sudah masuk!" headline, the player's nickname shown large, and a
> "Menunggu host memulai…" line with a subtle pulsing animation. Calm but fun.

---

### 10. Question Screen (Participant)
**Context:** Participant, mobile, full-bleed. Two sub-states: **Answering** and **Locked**.
**Goal:** Pick an answer fast.

**Elements — ANSWERING:**
- Slim top bar: countdown number + question counter.
- Question text (`text-xl`+).
- **2×2 grid of answer tiles**, each = position color + shape icon (▲◆●■) + option text, large tap targets.

**Elements — LOCKED (after tap):**
- Chosen tile keeps full color with a ring; others dim to opacity-40.
- Message "Jawaban terkunci, menunggu peserta lain…".

**Stitch prompt:**
> Participant question screen (mobile, full-bleed) following DESIGN.md. Slim top bar with a
> countdown and "Soal X / Y". The question text, then a 2×2 grid of large answer tiles,
> each in its fixed position color with a shape icon (▲ red, ◆ blue, ● yellow, ■ green) and
> the option text. Add a "locked" state: the tapped tile stays bright with a white ring,
> the others dim, and a "Jawaban terkunci, menunggu peserta lain…" message shows.

---

### 11. Per-Question Result (Participant)
**Context:** Participant, mobile, full-bleed.
**Goal:** Tell the player if they were right and their standing.

**Elements:**
- Big result state: **Benar** (green, ✓, celebratory) or **Salah** (red, ✕, gentle).
- Points earned this question (e.g. "+850") — large, only if correct.
- Current rank line: "Peringkat #3 dari 20".
- Subtle "Bersiap untuk soal berikutnya…".

**Stitch prompt:**
> Participant per-question result screen (mobile, full-bleed) following DESIGN.md. Two
> variants: a "Benar" state on green with a big check and "+850" points, and a "Salah"
> state on red with an X and no points. Both show the current rank ("Peringkat #3 dari
> 20") and a small "Bersiap untuk soal berikutnya…" line. Expressive, immediate feedback.

---

### 12. Final Screen (Participant)
**Context:** Participant, mobile, full-bleed.
**Goal:** Show the player's final placement.

**Elements:**
- Final rank, large (e.g. "#2").
- Final score (`tabular-nums`).
- Encouraging message keyed to placement (e.g. "Hebat! Kamu juara 2 🎉").
- Mini podium or top-3 list for context.
- "Main lagi" / "Selesai" action.

**Stitch prompt:**
> Participant final screen (mobile, full-bleed gradient) following DESIGN.md. Show the
> player's final rank large (e.g. "#2"), their final score, an encouraging message based
> on placement, and a small top-3 mini podium for context. A "Selesai" button.
> Celebratory, personal.

---

## Tips for consistent Stitch output

- **Generate host screens together, participant screens together** — each group shares a
  layout context (max-w-4xl vs full-bleed), so batching keeps them coherent.
- **Reuse the exact same answer-color sentence** (▲ red, ◆ blue, ● yellow, ■ green) in
  every gameplay prompt so tiles stay consistent across screens 6, 10, 11.
- **Ask for explicit states** (empty / disabled / locked / reveal) — Stitch will skip them
  unless named.
- After generating, if a screen drifts from the palette, paste the §9 "Quick color
  reference" from DESIGN.md and ask Stitch to "match these exact colors".
