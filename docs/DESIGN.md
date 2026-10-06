---
version: alpha
name: HSPMTB-design-system
description: A photography-first parish interface for Paroki HSPMTB Putussibau. The system combines Apple-inspired editorial composition with HSPMTB's red, navy, gold, and white identity. Tailwind CSS is the styling foundation, shadcn/ui provides accessible primitives, and custom HSPMTB components define the parish-specific experience. UI chrome stays quiet so parish photography, worship information, community life, and pastoral services remain the focus.

stack:
  styling: "Tailwind CSS"
  component-primitives: "shadcn/ui"
  custom-components: "HSPMTB UI components"
  frontend: "React + Inertia.js"
  accessibility: "WCAG-oriented, semantic HTML, keyboard accessible"
  icon-library: "Lucide React"

colors:
  primary: "#AB020E"
  primary-hover: "#8F010B"
  primary-focus: "#C51624"
  primary-on-dark: "#FF6670"
  navy: "#01266D"
  navy-dark: "#001A4D"
  navy-light: "#EAF0FA"
  gold: "#FCB027"
  gold-dark: "#D99400"
  gold-light: "#FFF4D6"
  ink: "#111827"
  body: "#1F2937"
  body-on-dark: "#FFFFFF"
  body-muted: "#667085"
  ink-muted-80: "#475467"
  ink-muted-48: "#98A2B3"
  divider-soft: "#F2F4F7"
  hairline: "#E4E7EC"
  canvas: "#FFFFFF"
  canvas-soft: "#F8F9FB"
  surface-pearl: "#FCFCFD"
  surface-navy: "#01266D"
  surface-navy-dark: "#001A4D"
  surface-red: "#AB020E"
  surface-gold: "#FFF4D6"
  surface-black: "#0B1220"
  surface-chip-translucent: "#E5E7EB"
  on-primary: "#FFFFFF"
  on-dark: "#FFFFFF"

semantic:
  background: "canvas"
  foreground: "ink"
  card: "canvas"
  card-foreground: "ink"
  primary: "primary"
  primary-foreground: "on-primary"
  secondary: "navy-light"
  secondary-foreground: "navy-dark"
  accent: "gold-light"
  accent-foreground: "gold-dark"
  muted: "canvas-soft"
  muted-foreground: "body-muted"
  destructive: "primary"
  destructive-foreground: "on-primary"
  border: "hairline"
  input: "hairline"
  ring: "primary-focus"

typography:
  hero-display:
    fontFamily: "SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 56px
    fontWeight: 600
    lineHeight: 1.07
    letterSpacing: -0.28px
  display-lg:
    fontFamily: "SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 40px
    fontWeight: 600
    lineHeight: 1.1
    letterSpacing: -0.4px
  display-md:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 34px
    fontWeight: 600
    lineHeight: 1.18
    letterSpacing: -0.374px
  lead:
    fontFamily: "SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 28px
    fontWeight: 400
    lineHeight: 1.14
    letterSpacing: 0.196px
  lead-airy:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 24px
    fontWeight: 300
    lineHeight: 1.5
    letterSpacing: 0
  tagline:
    fontFamily: "SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 21px
    fontWeight: 600
    lineHeight: 1.19
    letterSpacing: 0.231px
  body-strong:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 17px
    fontWeight: 600
    lineHeight: 1.24
    letterSpacing: -0.374px
  body:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 17px
    fontWeight: 400
    lineHeight: 1.47
    letterSpacing: -0.2px
  dense-link:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 17px
    fontWeight: 400
    lineHeight: 2.0
    letterSpacing: 0
  caption:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.43
    letterSpacing: -0.224px
  caption-strong:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 14px
    fontWeight: 600
    lineHeight: 1.29
    letterSpacing: -0.224px
  button-large:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 18px
    fontWeight: 500
    lineHeight: 1.0
    letterSpacing: 0
  button-utility:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.29
    letterSpacing: -0.224px
  fine-print:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 12px
    fontWeight: 400
    lineHeight: 1.3
    letterSpacing: -0.12px
  micro-legal:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 10px
    fontWeight: 400
    lineHeight: 1.3
    letterSpacing: -0.08px
  nav-link:
    fontFamily: "SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif"
    fontSize: 13px
    fontWeight: 400
    lineHeight: 1.0
    letterSpacing: -0.12px

rounded:
  none: 0px
  xs: 5px
  sm: 8px
  md: 12px
  lg: 18px
  xl: 24px
  pill: 9999px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 80px
  section-lg: 112px

components:
  button-primary:
    base: "shadcn/ui Button variant=default"
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.body}"
    rounded: "{rounded.pill}"
    padding: 11px 22px
  button-primary-focus:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.pill}"
    focusRing: "2px solid {colors.primary-focus}"
  button-primary-active:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.pill}"
    transform: "scale(0.98)"
  button-secondary-pill:
    base: "shadcn/ui Button variant=outline"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.primary}"
    borderColor: "{colors.primary}"
    typography: "{typography.body}"
    rounded: "{rounded.pill}"
    padding: 11px 22px
  button-navy:
    base: "Custom HSPMTB Button"
    backgroundColor: "{colors.navy}"
    textColor: "{colors.on-dark}"
    rounded: "{rounded.pill}"
    padding: 11px 22px
  button-gold:
    base: "Custom HSPMTB Button"
    backgroundColor: "{colors.gold}"
    textColor: "{colors.navy-dark}"
    rounded: "{rounded.pill}"
    padding: 10px 20px
  button-dark-utility:
    base: "shadcn/ui Button variant=ghost or custom utility"
    backgroundColor: "{colors.ink}"
    textColor: "{colors.on-dark}"
    typography: "{typography.button-utility}"
    rounded: "{rounded.sm}"
    padding: 8px 15px
  button-pearl-capsule:
    base: "shadcn/ui Button variant=secondary"
    backgroundColor: "{colors.surface-pearl}"
    textColor: "{colors.ink-muted-80}"
    typography: "{typography.caption}"
    rounded: "{rounded.md}"
    padding: 8px 14px
  button-icon-circular:
    base: "shadcn/ui Button size=icon"
    backgroundColor: "{colors.surface-chip-translucent}"
    textColor: "{colors.ink}"
    rounded: "{rounded.full}"
    size: 44px
  text-link:
    base: "Custom HSPMTB text link"
    backgroundColor: transparent
    textColor: "{colors.primary}"
    typography: "{typography.body}"
  text-link-on-dark:
    base: "Custom HSPMTB text link"
    backgroundColor: transparent
    textColor: "{colors.primary-on-dark}"
    typography: "{typography.body}"
  global-nav:
    base: "Custom ParishNavbar"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.nav-link}"
    height: 64px
    borderBottom: "1px solid {colors.divider-soft}"
  sub-nav-frosted:
    base: "Custom ProfileSubNav"
    backgroundColor: "rgba(248, 249, 251, 0.84)"
    textColor: "{colors.ink}"
    typography: "{typography.tagline}"
    height: 56px
    backdropFilter: "saturate(180%) blur(20px)"
  hero:
    base: "Custom ParishHero"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    minHeight: "clamp(520px, 72vh, 760px)"
    imageTreatment: "full-bleed photographic image; no decorative gradient"
  profile-hero:
    base: "Custom ProfileHero"
    backgroundColor: "{colors.navy}"
    textColor: "{colors.on-dark}"
    minHeight: "360px"
    imageTreatment: "full-width parish photography"
  profile-sidebar:
    base: "Custom ParishProfileSidebar"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    activeBackgroundColor: "{colors.red-light}"
    activeTextColor: "{colors.primary}"
    rounded: "{rounded.lg}"
    padding: 16px
  parish-card:
    base: "shadcn/ui Card + custom HSPMTB variants"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.lg}"
    padding: 24px
  news-card:
    base: "Custom ParishNewsCard"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    imageRadius: "{rounded.md}"
  mass-schedule-card:
    base: "Custom MassScheduleCard"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.lg}"
    padding: 24px
  agenda-card:
    base: "Custom AgendaCard"
    backgroundColor: "{colors.navy-light}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: 24px
  service-card:
    base: "Custom SacramentServiceCard"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    imageRadius: "{rounded.sm}"
  community-card:
    base: "Custom CommunityCard"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
  timeline:
    base: "Custom ParishTimeline"
    lineColor: "{colors.primary}"
    markerColor: "{colors.primary}"
    yearColor: "{colors.navy}"
  quote-card:
    base: "Custom ParishQuoteCard"
    backgroundColor: "{colors.navy-light}"
    textColor: "{colors.navy-dark}"
    rounded: "{rounded.lg}"
    padding: 32px
  gallery:
    base: "Custom ParishGallery"
    backgroundColor: "{colors.canvas}"
    imageRadius: "{rounded.md}"
  parish-cta:
    base: "Custom ParishCTA"
    backgroundColor: "{colors.navy}"
    textColor: "{colors.on-dark}"
    rounded: "{rounded.lg}"
    padding: 48px
  floating-sticky-bar:
    base: "Custom HSPMTB FloatingBar"
    backgroundColor: "rgba(248, 249, 251, 0.84)"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    height: 64px
    padding: 12px 32px
    backdropFilter: "saturate(180%) blur(20px)"
  search-input:
    base: "shadcn/ui Input"
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.pill}"
    padding: 12px 20px
    height: 44px
    borderColor: "{colors.hairline}"
  footer:
    base: "Custom ParishFooter"
    backgroundColor: "{colors.canvas-soft}"
    textColor: "{colors.ink-muted-80}"
    typography: "{typography.fine-print}"
    padding: 64px

---

## Overview

HSPMTB's web presence is designed as a **modern digital home for parish life**, not as a generic corporate website. The interface uses photography, generous whitespace, clear information hierarchy, and quiet UI chrome so that the parish identity, worship schedule, pastoral services, community activities, and people remain the visual focus.

The visual language is **Apple-inspired, but not an Apple clone**. Apple contributes the editorial composition, restrained chrome, photography-first presentation, whitespace philosophy, and responsive behavior. HSPMTB contributes the identity: red, navy, gold, Catholic imagery, parish photography, pastoral language, and information architecture.

The implementation is intentionally limited to **Tailwind CSS + shadcn/ui + custom HSPMTB components**. shadcn/ui is the primitive layer; custom components own the parish-specific visual language. No Ant Design or other component library should be introduced into the public UI.

### Key Characteristics

- Photography-first presentation; parish imagery should communicate place, people, worship, and community.
- White and soft-neutral surfaces dominate the interface.
- HSPMTB Red (`#AB020E`) is the primary interactive color.
- HSPMTB Navy (`#01266D`) is the principal structural and editorial dark color.
- HSPMTB Gold (`#FCB027`) is a restrained accent, not a second primary CTA color.
- Apple-inspired spacing and typography create an editorial, premium feel.
- Cards use subtle borders and restrained radius; shadows are used sparingly.
- Full-bleed photography is rectangular in hero areas; rounded corners are reserved for inline cards and supporting media.
- Public navigation stays simple and content-oriented.
- Profile pages use a persistent profile sub-navigation/sidebar pattern.
- The interface must remain understandable to parishioners, newcomers, families, and general public users.
- The design must work well on mobile because parish information is frequently accessed from phones.
- Tailwind utility classes should be preferred over bespoke CSS; custom CSS is reserved for cases that Tailwind cannot express cleanly.
- shadcn/ui components should be reused before creating a new primitive.
- Custom HSPMTB components should be created when the UI expresses parish-specific meaning or repeated domain behavior.

## Colors

### Brand & Accent

- **HSPMTB Red** (`{colors.primary}` — `#AB020E`): The primary action color. Use for main CTAs, active navigation, selected states, important links, and high-priority interface signals.
- **Primary Red Hover** (`{colors.primary-hover}` — `#8F010B`): Used for pressed/active interaction where a darker red improves feedback.
- **Focus Red** (`{colors.primary-focus}` — `#C51624`): Used for keyboard focus rings and visible focus indication.
- **Red on Dark** (`{colors.primary-on-dark}` — `#FF6670`): Used sparingly for links and highlights on navy/dark surfaces.
- **HSPMTB Navy** (`{colors.navy}` — `#01266D`): The principal structural color. Use for dark editorial sections, parish CTA surfaces, headings in selected contexts, and navigation emphasis.
- **Navy Dark** (`{colors.navy-dark}` — `#001A4D`): Used for deeper dark surfaces and high-contrast navy text.
- **Navy Light** (`{colors.navy-light}` — `#EAF0FA`): Used for soft information surfaces, agenda panels, and secondary sections.
- **HSPMTB Gold** (`{colors.gold}` — `#FCB027`): A ceremonial accent derived from the parish logo. Use for small highlights, icons, decorative rules, badges, and selected emphasis.
- **Gold Light** (`{colors.gold-light}` — `#FFF4D6`): Soft gold background for accent surfaces.

### Surface

- **Pure White** (`{colors.canvas}` — `#FFFFFF`): Primary content canvas and dominant background.
- **Soft Canvas** (`{colors.canvas-soft}` — `#F8F9FB`): Secondary page canvas, footer, and utility sections.
- **Pearl Surface** (`{colors.surface-pearl}` — `#FCFCFD`): Near-white surface for secondary controls.
- **Navy Surface** (`{colors.surface-navy}` — `#01266D`): Primary dark editorial surface.
- **Navy Dark Surface** (`{colors.surface-navy-dark}` — `#001A4D`): Deep CTA or footer-adjacent surface.
- **Red Surface** (`{colors.surface-red}` — `#AB020E`): Reserved for strong promotional or action-led surfaces.
- **Gold Surface** (`{colors.surface-gold}` — `#FFF4D6`): Soft accent background.
- **Near Black** (`{colors.surface-black}` — `#0B1220`): Reserved for rare high-contrast media or utility contexts.

### Text

- **Primary Ink** (`{colors.ink}` — `#111827`): Default headings and body copy.
- **Body** (`{colors.body}` — `#1F2937`): Main readable text.
- **Body on Dark** (`{colors.body-on-dark}` — `#FFFFFF`): Text on navy/dark surfaces.
- **Body Muted** (`{colors.body-muted}` — `#667085`): Secondary descriptions and metadata.
- **Ink Muted 80** (`{colors.ink-muted-80}` — `#475467`): Supporting text and footer content.
- **Ink Muted 48** (`{colors.ink-muted-48}` — `#98A2B3`): Disabled or low-priority information.

### Hairlines & Borders

- **Divider Soft** (`{colors.divider-soft}` — `#F2F4F7`): Very subtle section and navigation separation.
- **Hairline** (`{colors.hairline}` — `#E4E7EC`): Standard 1px border for cards, inputs, and utility surfaces.

### Brand Gradient

**No decorative gradients.** Depth should come from photography, surface changes, whitespace, typography, and restrained overlays when necessary for text legibility. If an image naturally contains light or atmospheric gradients, that is content photography rather than a UI gradient.

## Typography

### Font Family

- **Display**: `SF Pro Display, system-ui, -apple-system, BlinkMacSystemFont, sans-serif` — used for hero and large editorial headings.
- **Body / UI**: `SF Pro Text, system-ui, -apple-system, BlinkMacSystemFont, sans-serif` — used for paragraphs, navigation, metadata, buttons, and UI controls.
- **Fallback**: On platforms without SF Pro, use `system-ui`. Do not add a new font dependency unless the project explicitly decides to do so.

### Hierarchy

| Token                         | Size | Weight | Line Height | Letter Spacing | Use                            |
| ----------------------------- | ---: | -----: | ----------: | -------------: | ------------------------------ |
| `{typography.hero-display}`   | 56px |    600 |        1.07 |        -0.28px | Homepage/profile hero          |
| `{typography.display-lg}`     | 40px |    600 |        1.10 |         -0.4px | Major page headings            |
| `{typography.display-md}`     | 34px |    600 |        1.18 |       -0.374px | Section headings               |
| `{typography.lead}`           | 28px |    400 |        1.14 |        0.196px | Hero supporting copy           |
| `{typography.lead-airy}`      | 24px |    300 |         1.5 |              0 | Editorial lead                 |
| `{typography.tagline}`        | 21px |    600 |        1.19 |        0.231px | Sub-navigation/category labels |
| `{typography.body-strong}`    | 17px |    600 |        1.24 |       -0.374px | Strong body/UI emphasis        |
| `{typography.body}`           | 17px |    400 |        1.47 |         -0.2px | Default body copy              |
| `{typography.dense-link}`     | 17px |    400 |         2.0 |              0 | Footer/navigation link stacks  |
| `{typography.caption}`        | 14px |    400 |        1.43 |       -0.224px | Metadata and secondary labels  |
| `{typography.caption-strong}` | 14px |    600 |        1.29 |       -0.224px | Small headings                 |
| `{typography.button-large}`   | 18px |    500 |         1.0 |              0 | Major CTA                      |
| `{typography.button-utility}` | 14px |    400 |        1.29 |       -0.224px | Utility controls               |
| `{typography.fine-print}`     | 12px |    400 |         1.3 |        -0.12px | Footer/legal                   |
| `{typography.micro-legal}`    | 10px |    400 |         1.3 |        -0.08px | Rare legal microcopy           |
| `{typography.nav-link}`       | 13px |    400 |         1.0 |        -0.12px | Main navigation                |

### Principles

- Large headings use slightly negative tracking to create a compact editorial rhythm.
- Body copy stays at 17px for comfortable reading.
- Weight 600 is the main heading emphasis; avoid unnecessary 700-heavy typography.
- Weight 500 may be used for prominent CTA labels, but should not become the default text weight.
- Body line-height should remain generous; do not compress paragraphs simply to fit more content.
- Typography must support Indonesian language readability and longer parish names.
- Avoid all-caps for long content. Small uppercase labels may be used for eyebrow text.
- Do not use typography as decoration when a clear information hierarchy would be more useful.

### Note on Font Substitutes

- Use `system-ui, -apple-system, BlinkMacSystemFont` as the primary fallback stack.
- If SF Pro is unavailable, do not introduce a visually unrelated font.
- Tailwind typography utilities should map to the documented tokens rather than creating one-off sizes throughout the project.

## Layout

### Spacing System

- **Base unit:** 8px.
- **Tokens:** `{spacing.xxs}` 4px · `{spacing.xs}` 8px · `{spacing.sm}` 12px · `{spacing.md}` 16px · `{spacing.lg}` 24px · `{spacing.xl}` 32px · `{spacing.xxl}` 48px · `{spacing.section}` 80px · `{spacing.section-lg}` 112px.
- Structural layout should primarily snap to 8/12/16/24/32/48/80.
- Section spacing should be generous enough to separate worship, news, agenda, service, community, and gallery content.
- Mobile section spacing may reduce from 80px to 48–64px.
- Card padding defaults to 24px on desktop and 16–20px on mobile.
- Interactive controls should maintain comfortable touch targets.

### Grid & Container

- **Maximum content width:** 1440px for full homepage compositions.
- **Text-heavy content:** approximately 980–1120px.
- **Standard application/content container:** `max-w-7xl` with responsive horizontal padding.
- **Hero:** full-bleed where photography benefits from it.
- **Profile content:** two-column desktop layout with profile navigation/sidebar and content area.
- **Utility cards:** 3–5 columns depending on viewport and content density.
- **Mobile:** single-column content flow.
- **Gutters:** 16–24px between cards depending on viewport.

### Whitespace Philosophy

Whitespace is a functional part of the design. It should help users distinguish:

- parish identity from utility navigation;
- worship schedules from events;
- services from community information;
- editorial content from calls to action.

Do not fill empty space merely because it is available. Prefer fewer, clearer blocks over dense dashboards.

## Elevation & Depth

| Level            | Treatment                           | Use                                                          |
| ---------------- | ----------------------------------- | ------------------------------------------------------------ |
| Flat             | No shadow, no border                | Hero surfaces, footer, major editorial sections              |
| Soft hairline    | 1px `{colors.hairline}`             | Cards, inputs, navigation separators                         |
| Soft card shadow | `0 8px 30px rgba(16, 24, 40, 0.06)` | Only where a card needs separation from a similar background |
| Image shadow     | `0 8px 30px rgba(0, 0, 0, 0.16)`    | Selected photography/product-like imagery                    |
| Backdrop blur    | `saturate(180%) blur(20px)`         | Sticky/frosted navigation surfaces                           |

### Shadow Philosophy

Shadows are intentionally restrained. They should establish separation, not create a floating SaaS-card aesthetic.

- Do not place strong shadows on every card.
- Do not use shadows on text.
- Prefer border or surface contrast when it is sufficient.
- Use stronger image shadows only when the image needs visual weight.
- Use backdrop blur only where it provides a functional sticky/floating navigation effect.

### Decorative Depth

- Parish photography supplies atmosphere.
- Surface changes between white, soft-neutral, and navy create section rhythm.
- Red and gold provide identity without becoming decorative noise.
- Photography should never be hidden behind excessive overlays.
- If an overlay is necessary for text contrast, keep it subtle and functional.

## Shapes

### Border Radius Scale

| Token            |        Value | Use                                   |
| ---------------- | -----------: | ------------------------------------- |
| `{rounded.none}` |          0px | Full-bleed hero/editorial surfaces    |
| `{rounded.xs}`   |          5px | Rare compact inline elements          |
| `{rounded.sm}`   |          8px | Compact controls and image thumbnails |
| `{rounded.md}`   |         12px | Inputs, small capsules, media         |
| `{rounded.lg}`   |         18px | Cards, content panels                 |
| `{rounded.xl}`   |         24px | Large feature/CTA surfaces            |
| `{rounded.pill}` |       9999px | Primary actions, search, chips        |
| `{rounded.full}` | 9999px / 50% | Circular icon controls                |

### Photography Geometry

- **Hero photography:** full-width rectangular imagery, generally 16:9 to 21:9 on desktop.
- **Profile hero:** wide landscape photography with responsive crop.
- **Card imagery:** 16:9 or 4:3 depending on content.
- **Gallery:** mixed aspect-ratio grid is allowed when the composition benefits from it.
- **Portraits:** 4:5 or square crops for pastors and parish leaders.
- **No excessive rounded corners on hero photography.**
- Use responsive `srcset`, `sizes`, WebP/AVIF where supported, and lazy loading below the fold.
- Above-the-fold hero imagery should load eagerly.

## Components

### Top Navigation

**`ParishNavbar`** — Persistent white navigation with HSPMTB logo/wordmark on the left, primary parish sections in the center, and search/menu utility on the right. It replaces the black Apple global navigation with a quieter parish identity surface.

Desktop navigation should include:

- Beranda
- Profil
- Jadwal Misa
- Berita & Artikel
- Agenda
- Pelayanan
- Komunitas
- Galeri
- Download
- Kontak

On mobile, use a shadcn/ui `Sheet` for the navigation drawer. Keep the logo visible and ensure the menu trigger is at least 44×44px.

**`ProfileSubNav`** — A contextual navigation for the Profil section. It may use a frosted soft-neutral background and remains visually secondary to the page content.

Profile submenu:

- Sejarah
- Visi & Misi
- Wilayah Pelayanan
- Pastor
- Struktur Kepengurusan

The active item uses HSPMTB Red, a subtle red-tinted background, and clear selected-state semantics.

### Buttons

**`button-primary`** — The main HSPMTB action. Use HSPMTB Red with white text and pill geometry.

Typical labels:

- Jadwal Misa
- Lihat Selengkapnya
- Hubungi Kami
- Pelajari Profil
- Daftar Pelayanan

Active/pressed state uses a small scale reduction or darker red. Keyboard focus uses a visible red focus ring.

**`button-secondary-pill`** — Transparent/white surface with red border and red text. Use for secondary actions.

**`button-navy`** — Navy CTA for contexts where red would compete with another important red element.

**`button-gold`** — Reserved for small ceremonial or highlight actions. Never use gold as the default primary action.

**`button-icon-circular`** — 44×44px circular control for carousel, gallery, close, or navigation actions.

**`text-link`** — Red inline link on light surfaces.

**`text-link-on-dark`** — Light red/pink or white link on navy surfaces. Ensure sufficient contrast.

### Cards & Containers

**`ParishCard`** — Base custom card built from shadcn/ui `Card`. Use for repeated information blocks. Prefer hairline borders over heavy shadows.

**`NewsCard`** — Image-first editorial card with date, category, title, excerpt, and optional arrow. The image should carry most of the visual weight.

**`MassScheduleCard`** — Highly scannable schedule component. Date/day, Mass name, time, and location should be visually separated. The next upcoming Mass may receive a subtle red accent.

**`AgendaCard`** — Event card using a navy-light surface or neutral surface. Date is visually prominent, followed by title, time, and location.

**`SacramentServiceCard`** — Used for Baptism, First Communion, Confirmation, Marriage, Anointing of the Sick, and other pastoral services. Show image, service title, short description, and CTA.

**`CommunityCard`** — Used for OMK, WKRI, BIA/BIR, kategorial communities, wilayah, and lingkungan. Photography should communicate people and community rather than abstract decoration.

### Profile Components

**`ParishProfileSidebar`** — Persistent navigation for Profile pages. On desktop it sits beside the content; on mobile it becomes a horizontal/tab or collapsible navigation.

**`ParishTimeline`** — Vertical historical timeline with years, markers, short descriptions, and optional archival images.

**`PastorCard`** — Portrait-led profile card with name, role, and short description. Avoid inventing credentials or biographical information that is not approved by the parish.

**`OrganizationChart`** — Custom hierarchy visualization for parish leadership and pastoral structures. Prefer accessible HTML relationships over a purely visual diagram.

**`ServiceAreaMap`** — Map/image panel for parish service areas. The map should be treated as supporting information, not the only way to understand the geography.

**`ProfileQuoteCard`** — Editorial quote surface for parish values, Scripture, or approved parish messages. Quotes must be sourced/approved before publication.

### Gallery

**`ParishGallery`** — Photography-first gallery. Use responsive grids and optional lightbox behavior. The lightbox should use shadcn/ui `Dialog` primitives and preserve keyboard accessibility.

Gallery images should prioritize:

- Eucharistic celebrations
- Parish activities
- Community life
- Clergy/pastoral activities
- Parish facilities
- Local environment

### CTA

**`ParishCTA`** — Large closing call-to-action. Preferred surface is HSPMTB Navy with white typography and a single red or gold action where appropriate.

Example:

> Bersama Membangun Gereja yang Hidup dan Misioner

CTA should have one primary action, not a cluster of competing buttons.

### Inputs & Forms

Use shadcn/ui primitives for forms:

- `Input`
- `Textarea`
- `Select`
- `Checkbox`
- `RadioGroup`
- `Switch`
- `Calendar`
- `Form`

Public forms should be used only where the MVP requires them. For sacramental/service registration in the MVP, use clear links to the approved Google Forms rather than inventing a native registration workflow.

**`search-input`** — Pill-shaped search input with a leading search icon. Search should be unobtrusive and easy to access.

### Footer

**`ParishFooter`** — Soft-neutral footer with:

- parish identity/logo;
- short parish description;
- Menu Utama;
- Profil Paroki submenu;
- Pelayanan and Community links;
- contact information;
- address;
- social media;
- map link;
- legal links;
- copyright.

Footer may be denser than the main page because it is intended to expose the site's information architecture at a glance.

## Do's and Don'ts

### Do

- Use HSPMTB Red (`#AB020E`) as the primary interactive signal.
- Use HSPMTB Navy (`#01266D`) for structural dark surfaces and editorial emphasis.
- Use HSPMTB Gold (`#FCB027`) sparingly as a ceremonial accent.
- Keep white and soft-neutral surfaces dominant.
- Use Tailwind CSS utilities and semantic tokens instead of arbitrary inline styling.
- Reuse shadcn/ui primitives before creating new primitives.
- Build custom components when the behavior or visual language is parish-specific.
- Use photography of the actual parish and community whenever possible.
- Keep headings concise and easy to scan.
- Preserve generous whitespace.
- Maintain minimum 44×44px touch targets.
- Use visible keyboard focus states.
- Use red active states consistently across navigation.
- Keep hero photography full-width and editorial.
- Use subtle borders and restrained shadows.
- Use surface changes before adding decorative UI chrome.
- Keep the public site simpler than the admin panel.

### Don't

- Do not introduce Ant Design, Material UI, Bootstrap, or another UI component library.
- Do not use arbitrary brand colors outside the documented palette.
- Do not use red, navy, and gold at equal visual intensity.
- Do not make gold the primary CTA color.
- Do not use decorative gradients as a default visual treatment.
- Do not add heavy shadows to every card.
- Do not turn every section into a rounded container.
- Do not create dense dashboard-like layouts for public pages.
- Do not use generic stock photography when approved parish photography is available.
- Do not invent parish history, clergy names, schedules, statistics, or pastoral claims.
- Do not use hover as the only indication of interactivity.
- Do not create inaccessible custom controls when a shadcn/ui primitive can provide the behavior.
- Do not add one-off CSS values without a clear reason.
- Do not use a second component library just to solve one isolated UI problem.
- Do not make mobile layouts simply scaled-down desktop layouts.

## Responsive Behavior

### Breakpoints

| Name             |       Width | Key Changes                                                                                  |
| ---------------- | ----------: | -------------------------------------------------------------------------------------------- |
| Small phone      |     ≤ 419px | Single-column layout; compact hero; profile navigation becomes collapsible                   |
| Phone            |   420–640px | Single-column sections; hero type 34px; cards stack                                          |
| Large phone      |   641–735px | Tighter section padding; two-column utility blocks where appropriate                         |
| Tablet portrait  |   736–833px | Main navigation collapses to Sheet/hamburger; profile sidebar becomes horizontal/collapsible |
| Tablet landscape |  834–1023px | Expanded navigation may return; grids move to 2–3 columns                                    |
| Small desktop    | 1024–1068px | Full layout with reduced gutters                                                             |
| Desktop          | 1069–1440px | Full editorial layout; content max-width applied                                             |
| Wide desktop     |    ≥ 1441px | Content locks around 1440px; margins absorb additional width                                 |

The structural breakpoints that matter most are:

`1440px`, `1068px`, `833px`, `735px`, `640px`, and `419px`.

### Touch Targets

- Interactive controls must target at least 44×44px.
- Icon buttons are exactly 44×44px minimum.
- Navigation items must have comfortable vertical and horizontal padding.
- Do not reduce touch targets merely to preserve desktop density.

### Collapsing Strategy

- **Main navigation:** horizontal desktop navigation → shadcn/ui `Sheet` navigation on mobile.
- **Profile navigation:** sidebar → collapsible/horizontal navigation on mobile.
- **Cards:** 3–5 columns → 2 columns → 1 column.
- **Gallery:** masonry/grid → fewer columns → single-column or horizontally scrollable composition where appropriate.
- **Hero typography:** 56px → 40px → 34px → 28px.
- **CTA groups:** horizontal on desktop → stacked or wrapped on mobile.
- **Footer:** multi-column desktop → accordion/stacked groups on mobile.

### Image Behavior

- Hero photography uses responsive `srcset` and breakpoint-specific crops.
- Portrait photography maintains intentional face/headroom cropping.
- Gallery imagery uses responsive sizes and lazy loading.
- Above-the-fold hero image loads eagerly.
- Images below the fold should be lazy-loaded.
- Use WebP/AVIF where the deployment pipeline supports it.
- Never sacrifice meaningful image composition merely to achieve a fixed aspect ratio.

## Tailwind CSS & shadcn/ui Implementation

### Tailwind Rules

- Prefer semantic Tailwind classes backed by CSS variables.
- Avoid arbitrary values such as `bg-[#AB020E]` in application components.
- Prefer `bg-primary`, `text-primary`, `border-border`, `bg-muted`, etc.
- Define brand tokens in the project's global theme/CSS variables.
- Use responsive utilities rather than page-specific media queries.
- Use `cn()` for conditional class composition.
- Keep component variants explicit and discoverable.

Example:

```tsx
<Button className="rounded-full">
  Jadwal Misa
</Button>
```

Prefer:

```tsx
<Button variant="default">
  Jadwal Misa
</Button>
```

when the shadcn theme already defines the correct radius and colors.

### shadcn/ui Rules

Use shadcn/ui as the primitive layer for:

- Button
- Card
- Sheet
- Dialog
- Tabs
- Accordion
- Breadcrumb
- Dropdown Menu
- Navigation Menu
- Input
- Form
- Select
- Calendar
- Tooltip
- Separator
- Skeleton

Do not fork shadcn primitives unnecessarily. Extend them with variants when the same behavior is needed across multiple HSPMTB features.

### Custom Component Rules

Create a custom component when:

1. the component expresses a parish-specific domain concept;
2. the component repeats across multiple pages;
3. the composition is more complex than a primitive;
4. the component requires custom content/data behavior.

Examples:

```text
ParishNavbar
ParishHero
MassScheduleCard
AgendaCard
SacramentServiceCard
CommunityCard
ParishProfileSidebar
ParishTimeline
PastorCard
OrganizationChart
ParishGallery
ParishCTA
ParishFooter
```

## Iteration Guide

1. Start with the semantic token rather than an inline hex value.
2. Reuse a shadcn/ui primitive before creating a new primitive.
3. Build one custom HSPMTB component at a time.
4. Keep component variants explicit: default, active, selected, disabled, destructive, and loading where needed.
5. Do not document hover as the only state; document default, focus, active/pressed, selected, disabled, and loading when applicable.
6. Keep typography tokens centralized.
7. Keep spacing and radius tokens centralized.
8. Use surface changes before adding additional decorative elements.
9. When an existing component needs a visual change, prefer a reusable variant rather than a one-off page override.
10. Validate each component at mobile, tablet, and desktop widths.
11. Test keyboard navigation for every interactive custom component.
12. Test long Indonesian labels and parish names before finalizing fixed-width UI.
13. Keep content and visual hierarchy aligned with the MVP specification.

## Known Gaps

- Exact parish brand color values have been approximated from the supplied logo image; production brand colors should be confirmed from an official vector/brand source if one exists.
- Exact parish typography assets have not been supplied; the system therefore uses SF Pro/system fallbacks.
- The final icon set has not been formally locked beyond the recommendation to use Lucide React.
- Exact image dimensions/crops for every parish page are not yet defined.
- Dark-mode requirements have not been established; the public MVP should remain light-dominant unless the project explicitly adds dark mode.
- Form validation/error states should be documented as implementation proceeds.
- Exact motion/animation tokens are not yet formalized. Motion should remain subtle and functional.
- The final accessibility audit should be performed against the implemented interface, not only this design document.
- The final content hierarchy must be validated against approved parish content before launch.
