---
name: Quiz Online System
colors:
  surface: '#FFFFFF'
  surface-dim: '#dfd7e5'
  surface-bright: '#fef7ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f9f1ff'
  surface-container: '#f3ebf9'
  surface-container-high: '#ede5f3'
  surface-container-highest: '#e8e0ee'
  on-surface: '#1d1a24'
  on-surface-variant: '#4a4455'
  inverse-surface: '#332f39'
  inverse-on-surface: '#f6eefc'
  outline: '#7b7486'
  outline-variant: '#ccc3d7'
  surface-tint: '#7331df'
  primary: '#6D28D9'
  on-primary: '#FFFFFF'
  primary-container: '#5B21B6'
  on-primary-container: '#dac5ff'
  inverse-primary: '#d3bbff'
  secondary: '#831ada'
  on-secondary: '#ffffff'
  secondary-container: '#9e41f5'
  on-secondary-container: '#fffbff'
  error: '#DC2626'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ebddff'
  primary-fixed-dim: '#d3bbff'
  background: '#fef7ff'
  on-background: '#1d1a24'
  surface-variant: '#e8e0ee'
  bg: '#F8FAFC'
  surface-muted: '#F1F5F9'
  border: '#E2E8F0'
  text-strong: '#0F172A'
  text-muted: '#64748B'
  success: '#16A34A'
  warning: '#D97706'
  info: '#2563EB'
  answer-red: '#E21B3C'
  answer-blue: '#1368CE'
  answer-yellow: '#D89E00'
  answer-green: '#26890C'
  podium-gold: '#FBBF24'
  podium-silver: '#CBD5E1'
  podium-bronze: '#D97706'
typography:
  display-xl:
    fontFamily: Poppins
    fontSize: 60px
    fontWeight: '800'
    lineHeight: 60px
    letterSpacing: 0.025em
  display:
    fontFamily: Poppins
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 40px
  headline-1:
    fontFamily: Poppins
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 30px
  headline-2:
    fontFamily: Poppins
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 26px
  body:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  caption:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  scale-base: 4px
  container-host: 896px
  container-form: 512px
  gutter: 16px
  margin-mobile: 12px
---

# DESIGN.md — Quiz Online

> Visual design system for the Kahoot-style real-time quiz app. Stack target:
> **Laravel Blade + Tailwind CSS v4** (no JS framework). Brand violet `#6D28D9`,
> Poppins (display) + Inter (body). Four fixed answer colors paired with shapes.

See the full prose spec in the project root `DESIGN.md`. Key tokens:

- **Primary** `#6D28D9`, hover `#5B21B6`, on-primary white.
- **Neutrals**: bg `#F8FAFC`, surface `#FFFFFF`, border `#E2E8F0`, text `#0F172A`, muted `#64748B`.
- **Semantic**: success `#16A34A`, error `#DC2626`, warning `#D97706`, info `#2563EB`.
- **Answer tiles (fixed by position + shape)**: 1 ▲ red `#E21B3C`, 2 ◆ blue `#1368CE`,
  3 ● yellow `#D89E00`, 4 ■ green `#26890C`. White text always.
- **Hero gradient**: `linear-gradient(135deg, #6D28D9 0%, #9333EA 50%, #DB2777 100%)`.
- **Podium**: gold `#FBBF24`, silver `#CBD5E1`, bronze `#D97706`.
- **Radii**: lg inputs, xl buttons/cards, 2xl tiles/hero panels, full pills.
- Host = bordered cards `max-w-4xl`; Participant = full-bleed mobile-first; tap targets ≥44px.
