# DESIGN.md — Quiz Online

> Visual design system for the Kahoot-style real-time quiz app. Format follows the
> [Stitch DESIGN.md spec](https://stitch.withgoogle.com/docs/design-md/overview).
> Stack target: **Laravel Blade + Tailwind CSS v4** (no JS framework). Tokens map to
> Tailwind theme variables (`@theme`) and utility classes.

---

## 1. Visual Theme & Atmosphere

**Mood:** Playful, energetic, game-show. The app should feel like a live event — bold,
high-contrast, and fast. It must read clearly from a classroom **projector** (host
screens) *and* on a **phone in hand** (participant screens).

**Personality:** Friendly and confident, never corporate or sterile. Big type, rounded
shapes, saturated colors, generous motion on state changes (correct/incorrect, countdown).

**Density:** Low. One primary action per screen. Participant screens are nearly
chrome-free — a question and four large answer tiles. Host screens may show more data
(leaderboard, answer counts) but still favor large, glanceable elements.

**Two contexts, one language:**
- **Host (authoring/projector):** denser, information-rich, light surface, desktop-first.
- **Participant (gameplay):** ultra-minimal, thumb-first, full-bleed color, mobile-first.

---

## 2. Color Palette & Roles

### Brand & UI

| Role | Token | Hex | Usage |
|---|---|---|---|
| Brand / Primary | `--color-primary` | `#6D28D9` | Primary buttons, links, host accents (violet) |
| Primary hover | `--color-primary-hover` | `#5B21B6` | Hover/active state |
| Primary contrast | `--color-on-primary` | `#FFFFFF` | Text/icons on primary |
| Background | `--color-bg` | `#F8FAFC` | App background (light) |
| Surface | `--color-surface` | `#FFFFFF` | Cards, panels, forms |
| Surface muted | `--color-surface-muted` | `#F1F5F9` | Secondary panels, table rows |
| Border | `--color-border` | `#E2E8F0` | Dividers, input borders |
| Text strong | `--color-text` | `#0F172A` | Headings, primary text |
| Text muted | `--color-text-muted` | `#64748B` | Secondary text, labels |

### Semantic / Feedback

| Role | Token | Hex | Usage |
|---|---|---|---|
| Success | `--color-success` | `#16A34A` | Correct answer, "published" |
| Error | `--color-error` | `#DC2626` | Wrong answer, validation errors |
| Warning | `--color-warning` | `#D97706` | Countdown low, draft state |
| Info | `--color-info` | `#2563EB` | Neutral notices |

### Answer Tile Colors (the four classic gameplay colors)

Each multiple-choice option gets a fixed color **by position**, paired with a shape for
accessibility (color is never the only signal). Always use white text on these.

| Position | Shape | Token | Hex |
|---|---|---|---|
| Option 1 | ▲ Triangle | `--color-answer-red` | `#E21B3C` |
| Option 2 | ◆ Diamond | `--color-answer-blue` | `#1368CE` |
| Option 3 | ● Circle | `--color-answer-yellow` | `#D89E00` |
| Option 4 | ■ Square | `--color-answer-green` | `#26890C` |

### Gameplay Backgrounds

Immersive full-screen states use a vivid gradient (lobby, question, results):

- **Hero gradient:** `linear-gradient(135deg, #6D28D9 0%, #9333EA 50%, #DB2777 100%)`
- **Podium gold/silver/bronze:** `#FBBF24` / `#CBD5E1` / `#D97706`

### Dark Mode (optional, projector-friendly)

Invert surfaces: `--color-bg: #0F172A`, `--color-surface: #1E293B`,
`--color-text: #F1F5F9`, `--color-border: #334155`. Brand, semantic, and answer colors
stay identical (they already pop on dark).

---

## 3. Typography Rules

**Font family:** `Poppins` for display/headings (rounded, friendly), `Inter` for body
and UI. System fallback: `ui-sans-serif, system-ui, sans-serif`.

```
--font-display: 'Poppins', ui-sans-serif, system-ui, sans-serif;
--font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
```

| Style | Token | Size / Line | Weight | Use |
|---|---|---|---|---|
| Display XL | `text-6xl` | 60 / 1.0 | 800 | Game PIN, podium, big countdown |
| Display | `text-4xl` | 36 / 1.1 | 700 | Question text (projector), page hero |
| H1 | `text-2xl` | 24 / 1.25 | 700 | Page titles |
| H2 | `text-xl` | 20 / 1.3 | 600 | Section headings |
| Body | `text-base` | 16 / 1.5 | 400 | Default text, inputs |
| Label | `text-sm` | 14 / 1.4 | 500 | Form labels, metadata |
| Caption | `text-xs` | 12 / 1.4 | 400 | Helper text, timestamps |

**Rules:**
- Question text on participant phones: min `text-xl`; on host projector: `text-4xl`+.
- Game PIN and final scores use Display XL, weight 800, `tracking-wide`.
- Never go below 14px for interactive text. Numbers (scores, timers) use `tabular-nums`.

---

## 4. Component Stylings

### Buttons

| Variant | Style |
|---|---|
| **Primary** | `bg-primary text-white rounded-xl px-5 py-3 font-semibold shadow-sm hover:bg-primary-hover` |
| **Secondary** | `bg-surface text-text border border-border rounded-xl px-5 py-3 hover:bg-surface-muted` |
| **Ghost** | `text-primary font-medium hover:underline` |
| **Danger** | `text-error font-medium` / solid: `bg-error text-white rounded-xl` |
| **Disabled** | `opacity-50 cursor-not-allowed` (e.g. "Mulai" when quiz has no questions) |

All buttons: min height 44px, `transition-colors`, visible `focus-visible:ring-2 ring-primary ring-offset-2`.

### Answer Tile (gameplay — the signature component)

- Full-width, large rounded tile: `rounded-2xl p-6 text-white font-bold text-lg flex items-center gap-3`.
- Background = the position color (§2). Leading shape icon + option text.
- 2×2 grid on phones (`grid grid-cols-2 gap-3`), 2×2 or 1×4 on projector.
- **States:** default (full color) → tapped (`ring-4 ring-white scale-[0.98]`) →
  locked (dim others to `opacity-40`) → reveal (correct = solid + ✓ + `scale-105`,
  wrong = `grayscale opacity-50`).

### Cards

`bg-surface border border-border rounded-xl p-4 shadow-sm`. Quiz list rows, question
list items, leaderboard rows. Hover: `hover:shadow-md transition-shadow`.

### Inputs & Forms

- `rounded-lg border border-border px-3 py-2 focus:border-primary focus:ring-2 focus:ring-primary/30`.
- Labels: `text-sm font-medium text-text` above field.
- Errors: `text-sm text-error` below field; input border turns `border-error`.

### PIN Entry (participant join)

Oversized, centered: large numeric input `text-4xl tracking-[0.3em] text-center`,
single field, big primary "Gabung" button. Nickname step mirrors it.

### Game PIN Display (host lobby)

Hero card on gradient: white rounded panel, "Game PIN" label, then the PIN in Display XL
weight 800. Below: live participant chips that pop in as people join.

### Countdown Timer

Circular ring or big number. Color shifts: `success` → `warning` (≤5s) → `error` (≤2s),
with a subtle pulse. Always server-authoritative (visual only).

### Leaderboard / Podium

- **Row:** rank badge + nickname + score (`tabular-nums`), top 3 highlighted.
- **Podium:** three pedestals, heights ordered 2-1-3, gold/silver/bronze, winner center
  with a crown/sparkle and entrance animation.

### Answer Distribution Bar Chart

Horizontal bars per option, colored by position color, height ∝ count; correct option
marked with ✓. Shown on host screen after time expires.

### Badges & Pills

`rounded-full px-2.5 py-0.5 text-xs font-medium`. Status: published = `success` tint,
draft = `surface-muted` / muted text.

### Alerts / Flash

`rounded-lg px-4 py-3 text-sm`, tinted by semantic role (success/error/info background at
~10% opacity with matching text).

---

## 5. Layout Principles

**Spacing scale (Tailwind default, 4px base):** use `2 / 3 / 4 / 6 / 8 / 12 / 16`. Avoid
arbitrary values; prefer the scale.

**Containers:**
- Host content pages: `max-w-4xl mx-auto p-6`.
- Forms: `max-w-lg` / `max-w-sm`.
- Gameplay (participant & projector): **full-bleed**, no max-width, content centered with
  `min-h-dvh flex flex-col`.

**Grids:**
- Quiz/question lists: single column, stacked cards (`space-y-3`).
- Answer tiles: `grid grid-cols-2 gap-3` (mobile), up to `gap-4` on large screens.

**Hierarchy:** one dominant element per screen (the PIN, the question, the podium).
Whitespace is intentional — gameplay screens breathe; admin screens stay tidy, not cramped.

**Radii scale:** `rounded-lg` (8px) inputs/small, `rounded-xl` (12px) buttons/cards,
`rounded-2xl` (16px) answer tiles & hero panels, `rounded-full` pills/avatars/PIN chips.

---

## 6. Depth & Elevation

Soft, low-spread shadows — friendly, not heavy. Surfaces lift on interaction only.

| Level | Token | Tailwind | Use |
|---|---|---|---|
| 0 | flat | none | Page background, inline text |
| 1 | `shadow-sm` | `0 1px 2px rgb(0 0 0 / 0.05)` | Cards, buttons at rest |
| 2 | `shadow-md` | `0 4px 6px rgb(0 0 0 / 0.07)` | Card hover, dropdowns |
| 3 | `shadow-lg` | `0 10px 15px rgb(0 0 0 / 0.1)` | Modals, hero panels, podium |
| 4 | `shadow-2xl` | `0 25px 50px rgb(0 0 0 / 0.25)` | Full-screen result reveals |

Answer tiles use color + slight `scale` for depth rather than heavy shadow. Gradient
gameplay backgrounds get a subtle vignette instead of shadows.

---

## 7. Do's and Don'ts

**Do**
- Pair every answer color with its fixed shape icon (color is never the only cue).
- Keep one primary action per screen; make it the largest, most colorful element.
- Use white text on all four answer colors and on the brand gradient.
- Make timers and scores `tabular-nums` so digits don't jitter.
- Treat the countdown as decoration — the **server** decides timing and scoring.
- Use full-bleed immersive layouts for gameplay; bordered cards for admin.

**Don't**
- Don't reorder or recolor the four answer positions between screens.
- Don't shrink interactive text below 14px or touch targets below 44px.
- Don't put dense tables or tiny labels on participant phones.
- Don't rely on hover for anything essential (participants are on touch).
- Don't mix more than one gradient direction on a single screen.
- Don't use the brand violet as an answer-tile color (reserved for UI/host chrome).

---

## 8. Responsive Behavior

**Breakpoints (Tailwind):** `sm 640 · md 768 · lg 1024 · xl 1280`.

**Participant = mobile-first.** Design at 360–414px width first.
- Answer tiles: `grid-cols-2` always (2×2), filling the viewport height.
- Question text scales up at `md+`; tiles grow but stay 2×2.
- Sticky footer area for the locked-answer state.

**Host projector = desktop-first / large screen.**
- Question + options render large; counts and timer in corners.
- At `lg+`, leaderboard and distribution chart can sit side-by-side.

**Host admin (dashboard, editor) = responsive desktop.**
- `max-w-4xl` centered; forms collapse to single column on `sm`.
- Question editor: option rows stack vertically on mobile.

**Touch:** all tap targets ≥ 44×44px; answer tiles much larger. Spacing between tappable
items ≥ 12px. Use `min-h-dvh` (not `vh`) so mobile browser chrome doesn't clip gameplay.

---

## 9. Agent Prompt Guide

**Quick color reference**
```
Primary violet  #6D28D9   (buttons, links, host accent)
Background      #F8FAFC   Surface #FFFFFF   Text #0F172A   Muted #64748B
Success #16A34A  Error #DC2626  Warning #D97706  Info #2563EB
Answers: 1▲ #E21B3C  2◆ #1368CE  3● #D89E00  4■ #26890C
Hero gradient: 135deg #6D28D9 → #9333EA → #DB2777
Fonts: Poppins (display) · Inter (body)
Radii: 8/12/16/full · Spacing: 4px scale · Targets ≥44px
```

**Screens to generate** (12 total — see `docs/superpowers/specs/2026-06-19-quiz-online-kahoot-design.md`):

*Host (light, bordered cards, `max-w-4xl`):*
1. Landing — hero gradient, big "Enter PIN" box + Login/Register.
2. Login / Register — centered `max-w-sm` form.
3. Dashboard — list of own quizzes (cards), Create/Edit/Delete/Host buttons.
4. Quiz Editor — title/description form + question list + add-question form (2–4 options, mark correct).
5. Host Lobby — gradient bg, giant Game PIN, live participant chips, Start button.
6. Host Game Screen — large question + options, answer count, countdown → reveal (✓ + distribution bar chart + interim leaderboard) + Next.
7. Host Final Results — podium + full leaderboard.

*Participant (full-bleed, mobile-first, thumb targets):*
8. Join — oversized PIN input → nickname input.
9. Participant Lobby — "You're in! Waiting for host…" + nickname.
10. Question Screen — question text + 2×2 colored answer tiles + countdown → locked state.
11. Per-Question Result — correct/wrong, points earned, current rank.
12. Final Screen — final rank / podium.

**Ready-to-use prompt**
> Build the **[screen name]** for a Kahoot-style real-time quiz app. Follow DESIGN.md:
> Poppins/Inter type, violet `#6D28D9` brand, the four fixed answer colors with shape
> icons (▲◆●■), `rounded-xl/2xl` shapes, soft shadows, ≥44px touch targets. Participant
> screens are full-bleed and mobile-first; host screens use bordered cards in a centered
> `max-w-4xl`. One dominant action per screen. Tailwind v4 utility classes, no JS framework.
