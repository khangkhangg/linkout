# Jeton — Style Reference
> Editorial fintech on warm marble — supplied by Khang as the LinkOut visual system.

**Theme:** light

Jeton operates as a fintech operating system on a near-blank canvas: vast white space, one searing orange-red accent, and typography that does all the talking. Flat interface — no heavy elevations, no decorative borders — generous whitespace, rounded geometric cards, single warm hue (#f73b20). Headlines oversized and tight-set (line-height 0.9–1.0 at 100+px). Orange for brand voice, near-black brown (#360802) for body text, occasional pastel wash for feature cards.

## Colors

| Name | Value | Role |
|------|-------|------|
| Signal Orange | `#f73b20` | Brand accent — sole chromatic voice |
| Brand Orange Tint | `#f84d35` | Subtle orange surface variant (nav indicators) |
| Ink Roast | `#360802` | Primary body/input text |
| Paper White | `#ffffff` | Page canvas and cards |
| Carbon Black | `#000000` | Secondary text, utility icons (sparing) |
| Ash Grey | `#ababab` | Disabled, placeholder, low-emphasis dividers |
| Sand Wash | `#e7dcdb` | Tinted neutral surface |
| Linen Blush | `#fdedea` | Ultra-light orange-tinted surface |
| Citrus Wash | `#f5ffbb` | Pale yellow-green highlight tint |
| Mint Wash | `#bcffbb` | Green wash for highlight backgrounds |
| Coral Red | `#fb2d54` | Category accent (LinkOut: Trending rail) |
| Cobalt Blue | `#477ee9` | Category accent (LinkOut: Most liked rail) |
| Emerald Green | `#34c771` | Category accent (LinkOut: Highly rated rail) |

## Typography

Sole typeface: Sequel Sans → **substitute Inter** (or Manrope/DM Sans). Weights 400/450/500 only — hierarchy through size, never boldness. Tracking 0.01em at display, 0.03em at 12–14px.

| Role | Size | LH | Tracking |
|------|------|----|----|
| caption | 12px | 1.5 | 0.36px |
| body-sm | 14px | 1.4 | 0.42px |
| body | 16px | 1 | — |
| subheading | 23px | 1.2 | 0.23px |
| heading-sm | 33px | 1.2 | 0.33px |
| heading | 44px | 1.2 | 0.44px |
| heading-lg | 72px | 1 | 0.72px |
| display | 106px | 1 | — |

## Spacing & shape

Base unit 4px. Scale: 4/8/12/16/20/24/32/48/56/160. Page max-width 1200px, section gap 80px, card padding 16px, element gap 8px.

Radii: nav 84px · cards 16px · links 8px · pills 9999px · inputs 16px · buttons 12px.

Shadows:
- `--shadow-lg`: `rgba(247,59,32,0.1) 0 8px 24px, rgba(247,59,32,0.05) 0 2px 8px` (floating glass)
- `--shadow-md`: `rgba(0,0,0,0.05) 0 -4px 16px` (inverted card lift — signature)

## Components (key)

- **Primary Pill Button:** filled #f73b20, white text, 12px radius, 8/16px padding, 14px w450, 0.03em.
- **Ghost Text Link:** no bg, #f73b20 text, 8px radius, arrow indicator.
- **Frosted Glass Card:** rgba(255,255,255,0.1), 16px radius, backdrop blur 20–40px.
- **Feature Category Card:** pastel tint bg (rgba(247,59,32,0.05) or accent), 16px radius.
- **Floating Input:** rgba(247,59,32,0.05) bg, #360802 text, 16px radius, 17.6/6.4/48px padding.
- **Bordered Content Card:** white, 16px radius, 16px padding, inverted shadow.
- **Top Nav:** white/transparent + backdrop blur, links 14–16px w400–450, pill CTA right.

## Do

- Display headlines 72–155px, LH 0.9–1.0 — non-negotiable.
- #f73b20 as sole chromatic voice; blue/green/coral only for category-coded cards.
- 16px radius on cards/inputs/containers; 9999px only for true pills.
- Warm tints rgba(247,59,32,0.05) + #360802 text.
- Inverted shadow for card lift.
- 0.03em tracking on small text, 0.01em on display.
- White canvas; separate sections with whitespace, not background bands.

## Don't

- No second typeface. No weights ≥600. No text/button drop shadows.
- No #000000 body text (#360802 is body). No competing accent multiplication.
- No LH > 1.2 on display sizes. No sharp corners on interactive surfaces.

## CSS Custom Properties

```css
:root {
  --color-signal-orange: #f73b20;
  --color-brand-orange-tint: #f84d35;
  --color-ink-roast: #360802;
  --color-paper-white: #ffffff;
  --color-carbon-black: #000000;
  --color-ash-grey: #ababab;
  --color-sand-wash: #e7dcdb;
  --color-linen-blush: #fdedea;
  --color-citrus-wash: #f5ffbb;
  --color-mint-wash: #bcffbb;
  --color-coral-red: #fb2d54;
  --color-cobalt-blue: #477ee9;
  --color-emerald-green: #34c771;

  --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;

  --text-caption: 12px;  --leading-caption: 1.5;  --tracking-caption: 0.36px;
  --text-body-sm: 14px;  --leading-body-sm: 1.4;  --tracking-body-sm: 0.42px;
  --text-body: 16px;     --leading-body: 1;
  --text-subheading: 23px; --leading-subheading: 1.2; --tracking-subheading: 0.23px;
  --text-heading-sm: 33px; --leading-heading-sm: 1.2; --tracking-heading-sm: 0.33px;
  --text-heading: 44px;    --leading-heading: 1.2;    --tracking-heading: 0.44px;
  --text-heading-lg: 72px; --leading-heading-lg: 1;   --tracking-heading-lg: 0.72px;
  --text-display: 106px;   --leading-display: 1;

  --font-weight-regular: 400;
  --font-weight-w450: 450;
  --font-weight-medium: 500;

  --spacing-4: 4px; --spacing-8: 8px; --spacing-12: 12px; --spacing-16: 16px;
  --spacing-20: 20px; --spacing-24: 24px; --spacing-32: 32px; --spacing-48: 48px;
  --spacing-56: 56px; --spacing-160: 160px;

  --page-max-width: 1200px;
  --section-gap: 80px;
  --card-padding: 16px;
  --element-gap: 8px;

  --radius-nav: 84px;
  --radius-cards: 16px;
  --radius-links: 8px;
  --radius-pills: 9999px;
  --radius-inputs: 16px;
  --radius-buttons: 12px;

  --shadow-lg: rgba(247, 59, 32, 0.1) 0px 8px 24px 0px, rgba(247, 59, 32, 0.05) 0px 2px 8px 0px;
  --shadow-md: rgba(0, 0, 0, 0.05) 0px -4px 16px 0px;

  --surface-canvas: #ffffff;
  --surface-card-surface: #ffffff;
  --surface-orange-tint: #fdedea;
  --surface-frosted-glass: #ffffff1a;
  --surface-category-tint: #f73b200d;
}
```
