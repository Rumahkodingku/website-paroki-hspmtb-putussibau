---
version: 2.0
name: HSPMTB Design System
description: A refreshed, photography-led design system for the public website of Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. The visual direction is contemporary, editorial, welcoming, and grounded in parish life. HSPMTB Red (#AB020E) is the fixed primary brand color. Light and dark themes are designed intentionally rather than treating dark mode as an inverted light theme.
stack:
    frontend: React + Inertia.js
    styling: Tailwind CSS
    primitives: shadcn/ui
    icons: Lucide React
    accessibility: Semantic HTML, keyboard support, WCAG-oriented contrast
    primary-color: "#AB020E"
---

# HSPMTB Design System

## 1. Design Direction

### Product intent

The website should feel like the digital home of a living Catholic parish: welcoming to parishioners, clear for newcomers, useful for families, and trustworthy for people seeking worship schedules or pastoral services.

The visual system is built around four principles:

1. **Parish first** — real church architecture, worship, clergy, volunteers, and community life should lead the visual story.
2. **Editorial clarity** — clear page titles, concise introductions, readable content widths, and consistent section rhythm.
3. **Quiet interface chrome** — navigation, filters, controls, and metadata support the content without competing with it.
4. **Warm restraint** — the brand red provides recognition and action; navy and gold are supporting colors, not competing primaries.

### Visual character

- Modern and calm, with an editorial rather than dashboard-like layout.
- Photography-led, using genuine parish images wherever available.
- Strong, readable typography and obvious content hierarchy.
- Consistent card and list patterns; avoid placing every section inside a rounded box.
- A deliberately designed light theme and dark theme.
- No decorative gradients as a default styling device.
- No oversized shadows, glowing borders, or excessive glass effects.

### Do not imitate

Do not copy Apple, a SaaS dashboard, or a generic church template literally. Use editorial spacing and clean typography while retaining HSPMTB's own red identity, parish photography, Catholic context, and information architecture.

---

## 2. Brand Color System

### Brand colors

| Token               | Value     | Role                                                                         |
| ------------------- | --------- | ---------------------------------------------------------------------------- |
| `primary`           | `#AB020E` | Fixed HSPMTB primary; main actions, active states, important links           |
| `primary-hover`     | `#8F010B` | Hover/pressed state for primary actions                                      |
| `primary-active`    | `#760109` | Active/pressed state where needed                                            |
| `primary-soft`      | `#FBEAEC` | Light-theme selected backgrounds and subtle callouts                         |
| `primary-soft-dark` | `#35191D` | Dark-theme selected backgrounds                                              |
| `navy`              | `#01266D` | Secondary brand color for selected editorial sections and structural accents |
| `navy-deep`         | `#001A4D` | Rare deep-blue surface, not a general page background                        |
| `navy-soft`         | `#EAF0FA` | Light-theme informational surface                                            |
| `gold`              | `#FCB027` | Small ceremonial details, icons, and decorative highlights                   |
| `gold-soft`         | `#FFF4D6` | Light-theme gold tint; use sparingly                                         |

**The primary color must remain `#AB020E`.** Do not replace it with a brighter red or pink. For dark-theme text links, use a lighter accessible red tint only when the primary red itself does not provide sufficient contrast against the specific surface; this does not change the brand primary token.

### Light theme neutrals

| Token            | Value     | Role                                      |
| ---------------- | --------- | ----------------------------------------- |
| `canvas`         | `#FFFFFF` | Main page background                      |
| `canvas-subtle`  | `#F7F8FA` | Secondary page regions and footer         |
| `surface`        | `#FFFFFF` | Cards, menus, forms                       |
| `surface-raised` | `#FFFFFF` | Dialogs and popovers with a border/shadow |
| `text-primary`   | `#171A21` | Headings and primary text                 |
| `text-body`      | `#343A46` | Body copy                                 |
| `text-muted`     | `#626B79` | Dates, locations, descriptions            |
| `text-subtle`    | `#77808D` | Non-essential metadata only               |
| `border`         | `#E0E3E8` | Card and control borders                  |
| `border-subtle`  | `#ECEEF2` | Quiet dividers                            |
| `control`        | `#FFFFFF` | Inputs and selects                        |

### Dark theme neutrals

| Token                 | Value     | Role                                       |
| --------------------- | --------- | ------------------------------------------ |
| `dark-canvas`         | `#111318` | Main page background                       |
| `dark-canvas-subtle`  | `#171A21` | Header/footer and secondary regions        |
| `dark-surface`        | `#1D2129` | Cards, forms, content panels               |
| `dark-surface-raised` | `#252A34` | Dialogs, menus, selected elevated controls |
| `dark-text-primary`   | `#F3F4F6` | Headings and primary text                  |
| `dark-text-body`      | `#D4D8E0` | Body copy                                  |
| `dark-text-muted`     | `#A6ADBA` | Dates, locations, descriptions             |
| `dark-text-subtle`    | `#8992A1` | Non-essential metadata only                |
| `dark-border`         | `#343A46` | Card and control borders                   |
| `dark-border-subtle`  | `#292E38` | Quiet dividers                             |
| `dark-control`        | `#191D24` | Inputs and selects                         |

### Semantic tokens

Components must use semantic tokens rather than individual hard-coded colors.

| Semantic role            | Light                       | Dark                                         |
| ------------------------ | --------------------------- | -------------------------------------------- |
| Page background          | `canvas`                    | `dark-canvas`                                |
| Header/footer background | `canvas-subtle` or `canvas` | `dark-canvas-subtle`                         |
| Card                     | `surface`                   | `dark-surface`                               |
| Elevated card/menu       | `surface-raised`            | `dark-surface-raised`                        |
| Main text                | `text-primary`              | `dark-text-primary`                          |
| Body text                | `text-body`                 | `dark-text-body`                             |
| Supporting text          | `text-muted`                | `dark-text-muted`                            |
| Border                   | `border`                    | `dark-border`                                |
| Quiet divider            | `border-subtle`             | `dark-border-subtle`                         |
| Primary action           | `primary`                   | `primary`                                    |
| Selected state           | `primary-soft`              | `primary-soft-dark`                          |
| Focus ring               | `primary`                   | `#F06A73` when required for visible contrast |

### Color usage rules

- Red is for meaningful actions, active navigation, selected tabs, important links, and small status accents.
- Do not make every icon, heading, border, and badge red.
- Navy is a supporting brand color. Use it in limited editorial sections, map/identity accents, and selected highlights.
- Gold is decorative and ceremonial; do not use it for large panels or routine actions.
- Keep body text neutral. Do not color entire paragraphs navy or red.
- Do not use a full-page navy background in either theme.
- Do not use opacity as the only way to indicate muted text.
- Use `#AB020E` consistently as the primary token across all public pages and controls.

---

## 3. Theme Behavior

### Theme modes

- Supported: `light`, `dark`, `system`.
- Default: `light`.
- `light` and `dark` must be user-selectable and `system` follows the operating system preference.
- Persist the user's explicit preference.
- Use CSS variables and semantic Tailwind tokens so every component changes theme consistently.
- Theme changes must not change layout, spacing, image crops, or component hierarchy.

### Light theme

The light theme should feel open, clean, and welcoming. Use white as the main canvas, soft neutral blocks for section changes, thin borders, dark readable text, and restrained red accents.

### Dark theme

The dark theme should feel like a carefully designed editorial site at night, not a light website with colors mechanically inverted.

- Use `dark-canvas` for the main page background.
- Use `dark-surface` for cards and content panels; it must be visibly distinguishable from the canvas but not look like a bright tile.
- Use `dark-canvas-subtle` for the navbar/footer and selected full-width section backgrounds.
- Keep images natural. Do not apply a global dark filter to every image.
- Use `#AB020E` for primary buttons. Use a suitable border or neutral surrounding space so red actions do not overwhelm the page.
- Use `dark-text-primary`, `dark-text-body`, and `dark-text-muted` according to content hierarchy.
- Avoid large blocks of pure white, saturated navy, or red.
- Avoid pale blue cards, white cards, and light-mode-only surfaces in dark mode unless the content itself is intentionally a light document preview.
- Do not apply `text-white/40` or parent opacity to essential text.
- Keep dark borders subtle but visible.
- Footer must use dark theme surfaces and readable text; it must never remain white while the page is dark.
- SVG logos must have transparent backgrounds unless the artwork itself contains a background.

### Theme audit checklist

For every route, check:

- navbar and mobile menu;
- title, breadcrumb, and lead paragraph;
- cards and feature panels;
- inputs, selects, tabs, badges, and pagination;
- list separators and selected states;
- image captions and overlays;
- map containers;
- footer headings, links, address, phone, email, and legal copy;
- loading, empty, error, hover, focus, and disabled states.

---

## 4. Typography

### Font stack

Mona Sans (SIL OFL 1.1) is the approved UI family, self-hosted through the `bunny` provider of `laravel-vite-plugin`. It is loaded at weights 400/500/600/700 normal and 400/600 italic, so italic text uses a real italic rather than a synthesized one:

```css
font-family:
    "Mona Sans",
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
```

Use Mona Sans consistently and degrade to the system stack when it is unavailable. Do not mix several font families across pages. One family covers both body and display; there is no separate display face.

### Type scale

| Token        |     Desktop |      Mobile |  Weight | Usage                   |
| ------------ | ----------: | ----------: | ------: | ----------------------- |
| `display-xl` | 52px / 1.08 | 36px / 1.12 | 650–700 | Homepage hero only      |
| `display-lg` | 40px / 1.12 | 32px / 1.15 | 650–700 | Major page title        |
| `display-md` | 32px / 1.18 |  26px / 1.2 | 600–650 | Large section heading   |
| `heading-lg` | 24px / 1.25 |  22px / 1.3 |     600 | Section title           |
| `heading-md` |  20px / 1.3 | 18px / 1.35 |     600 | Card title / subsection |
| `heading-sm` |  16px / 1.4 |  16px / 1.4 |     600 | Small heading           |
| `body-lg`    |  18px / 1.6 | 17px / 1.55 |     400 | Lead / introduction     |
| `body`       | 16px / 1.65 |  16px / 1.6 |     400 | Main body copy          |
| `body-sm`    | 14px / 1.55 |  14px / 1.5 |     400 | Supporting copy         |
| `label`      |  13px / 1.4 |  13px / 1.4 | 550–600 | Labels and metadata     |
| `fine-print` | 12px / 1.45 | 12px / 1.45 |     400 | Legal and copyright     |

### Typography rules

- Do not use very small text for important content; metadata should generally be at least 13–14px.
- Keep paragraph widths comfortable: usually 60–75 characters per line.
- Use sentence case for titles, navigation, and buttons.
- Avoid long all-caps labels.
- Use one clear page H1, followed by logical H2/H3 levels.
- Do not use light font weights for body text on dark surfaces.
- Long parish names must wrap naturally; never clip them to preserve a single line.

---

## 5. Layout, Spacing, and Grid

### Content widths

- Wide editorial/homepage sections: max-width `1280px`.
- Text-heavy article and history content: `760–900px`.
- Standard page container: `max-width: 1200px` to `1280px` with responsive horizontal padding.
- Navbar inner content aligns to the same page container.
- Do not stretch paragraphs across the full width of a large desktop viewport.

### Spacing scale

Use a consistent 4px base with common 8px rhythm.

| Token      | Value |
| ---------- | ----: |
| `space-1`  |   4px |
| `space-2`  |   8px |
| `space-3`  |  12px |
| `space-4`  |  16px |
| `space-5`  |  20px |
| `space-6`  |  24px |
| `space-8`  |  32px |
| `space-10` |  40px |
| `space-12` |  48px |
| `space-16` |  64px |
| `space-20` |  80px |
| `space-24` |  96px |

### Section rhythm

- Desktop section spacing: 64–88px depending on content density.
- Tablet section spacing: 48–64px.
- Mobile section spacing: 40–56px.
- Card padding: 20–24px desktop, 16–20px mobile.
- Use consistent gaps within each grid; do not create arbitrary gaps page by page.
- Do not wrap a section in a card if the section already has a clear heading and layout.
- Use alternating section backgrounds sparingly. The page should feel like one coherent site, not a stack of unrelated blocks.

### Grid rules

- News grid: 3 columns on desktop, 2 on tablet, 1 on mobile; 4 columns only for compact, image-led cards on very wide screens.
- Service/community grids: 3 columns desktop, 2 tablet, 1 mobile.
- Profile pages: sidebar plus content on desktop; horizontal/collapsible profile navigation on mobile.
- Mass schedule: readable list/table-like rows on desktop; stacked schedule items on narrow screens.
- Gallery: responsive grid with deliberate crop ratios.
- Do not create empty columns or leave content cramped into one side without a clear editorial reason.

---

## 6. Shape, Borders, and Elevation

### Radius

| Token         |  Value | Usage                                |
| ------------- | -----: | ------------------------------------ |
| `radius-sm`   |    6px | Small controls and chips             |
| `radius-md`   |   10px | Inputs and compact media             |
| `radius-lg`   |   14px | Cards and panels                     |
| `radius-xl`   |   20px | Feature cards and large image panels |
| `radius-pill` | 9999px | Buttons and filter chips             |

Use moderate rounding. Do not make every element pill-shaped or excessively rounded.

### Borders and shadows

- Standard card border: 1px semantic border.
- Use a subtle shadow only when a component needs separation from a similar surface.
- Avoid heavy shadows on every card.
- Avoid glowing red borders and large blurred shadows.
- Use border contrast before shadow to establish hierarchy.
- Keep divider lines quiet and consistent.
- Focus indicators must remain clearly visible.

---

## 7. Photography and Media

Photography is a core part of the parish identity and should replace abstract filler wherever real images are available.

### Image priorities

1. Parish church and grounds.
2. Mass and liturgical celebrations.
3. Parishioners, volunteers, and community events.
4. Pastoral services and sacramental celebrations.
5. Parish facilities and service areas.
6. Approved archival images for the history timeline.

### Image treatment

- Prefer authentic HSPMTB photography over generic stock images.
- Do not repeat the same image across several visible cards when alternative images exist.
- Use image aspect ratios intentionally: hero 16:9–21:9, cards 16:9, portraits 4:5 or 1:1.
- Avoid cropping faces, sacred objects, or key church architecture.
- Use overlays only when needed for text contrast, and keep them local to the text area.
- Never apply a global dark overlay to all images when dark mode is active.
- Provide meaningful alt text for informative images and empty alt text for purely decorative images.
- Use responsive image sizes and lazy loading below the fold.

### Placeholder policy

If approved images are not available, use a clearly intentional placeholder or neutral media treatment. Do not invent parish photography or use a single abstract image as the permanent replacement for every content item.

---

## 8. Navigation

### `ParishNavbar`

The navbar should be compact, stable, and easy to scan.

Desktop:

- Parish logo and wordmark at left.
- Main navigation in the center/right.
- Theme control and mobile utilities at the end.
- Height approximately 64–72px.
- Subtle bottom border.
- Active route indicated with primary red text and a thin underline or small marker.
- Do not make the entire navbar red or navy.

Primary navigation:

- Beranda
- Profil
- Jadwal Misa
- Berita & Artikel
- Agenda
- Pelayanan
- Komunitas
- Area
- Galeri
- Dokumen
- Kontak

Only include items that exist in the current approved MVP; label routes consistently with the implemented information architecture.

Mobile:

- Keep logo/wordmark visible.
- Use a shadcn/ui `Sheet` for the menu.
- Touch targets must be at least 44×44px.
- The menu must close after navigation and support keyboard navigation.
- Prevent the navigation from overflowing horizontally.

### `ProfileSubNav`

- Used only within the Profil section.
- Desktop: horizontal contextual navigation or sidebar, chosen consistently across profile routes.
- Mobile: horizontally scrollable tabs or an accessible collapsible selector.
- Active item uses a soft red tint and red label, not a full red block unless the component is a compact selected pill.

---

## 9. Buttons, Links, and Controls

### Primary button

- Background: `primary` (`#AB020E`).
- Text: white.
- Hover: `primary-hover`.
- Active: `primary-active`.
- Focus: visible 2px ring with offset.
- Minimum height: 44px.
- Use for the single most important action in a section.

Examples: `Lihat Jadwal Misa`, `Lihat Semua Berita`, `Hubungi Sekretariat`.

### Secondary button

- Neutral or transparent surface.
- Border: semantic border.
- Text: main text color.
- Hover: subtle neutral surface.
- Use for secondary actions beside the primary CTA.

### Tertiary/link action

- Text link using `primary`.
- On dark surfaces, verify contrast and use the documented accessible red tint where needed.
- Underline on hover/focus where it improves affordance.
- Do not use red for ordinary non-interactive copy.

### Icon button

- Minimum 44×44px hit area.
- Must have an accessible name.
- Use Lucide React icons with consistent 16–20px sizing.
- Do not use an icon alone if its meaning is ambiguous.

### Inputs and filters

Use shadcn/ui primitives:

- `Input`
- `Textarea`
- `Select`
- `Checkbox`
- `RadioGroup`
- `Switch`
- `Calendar`
- `Form`

Rules:

- Inputs use the semantic control surface and visible border.
- Labels must remain visible when the field contains a value.
- Placeholder text must be distinguishable from entered text.
- Error text must explain how to fix the issue, not rely on color alone.
- Search, category filters, and sort controls should align cleanly and wrap on small screens.
- Never use hard-coded light backgrounds in dark mode.

---

## 10. Content Components

### `ParishHero`

Purpose: introduce the parish and provide a clear path to the most common action.

- Use a strong, authentic church photograph.
- Place text in a readable area with natural contrast.
- Main title should identify the parish clearly.
- One primary CTA and one secondary CTA maximum.
- Avoid multiple competing buttons and long paragraphs.
- Mobile hero must preserve the church image's focal point and readable text.

### `QuickAccessBar`

A compact row of common destinations, such as Jadwal Misa, Berita & Artikel, Agenda, and Pelayanan.

- Use a simple icon, short label, and optional supporting line.
- Keep each item equally spaced.
- Do not make the bar taller than the hero content requires.
- On mobile, use a 2-column grid or horizontally scrollable row with clear affordances.

### `NewsCard`

Required hierarchy:

1. Image
2. Category and date
3. Title
4. Short excerpt
5. `Baca selengkapnya` link

Rules:

- Card titles should have consistent line-height.
- Cards in the same row should align without forcing excessive empty space.
- Avoid low-contrast text on top of images.
- Make the card link accessible; avoid nested links that conflict.

### `FeaturedNews`

- One editorial feature with a clear image, category, title, date, and short summary.
- On desktop, use a balanced horizontal layout.
- On mobile, stack image and text.
- Avoid a pale blue/white feature panel in dark mode.
- Use primary red only for the category/action accent.

### `MassScheduleCard`

Display day/date, time, Mass name, and location as distinct information.

- Time must be easy to scan.
- Location must be readable and not truncated unnecessarily.
- Next upcoming Mass may use a subtle primary-red accent.
- Do not use a large red panel for the whole schedule.
- Recurring Masses should use a clean list/table-like structure with separators.
- Special Masses should be clearly labeled without changing the base component unnecessarily.
- Filters for location/day/type must remain visible and usable in both themes.

### `UpcomingMassPanel`

- Highlight the nearest upcoming Mass.
- Use a neutral surface, subtle border, and small red eyebrow or time accent.
- Avoid bright gradient fills.
- Show only the most useful details: title, date, time, location, and optional link to the complete schedule.

### `AgendaCard`

- Show date, title, time, location, and a short summary where available.
- Date can be visually emphasized with a compact date block.
- Do not rely on color alone to communicate upcoming/past status.
- Use a list on text-heavy pages and a grid only where cards benefit from scanning.

### `SacramentServiceCard`

For Baptism, First Communion, Confirmation, Marriage, Anointing of the Sick, and other approved services.

- Show service title, short description, relevant requirements/procedure link, and next action.
- Use genuine relevant imagery when available.
- Do not imply online registration exists unless the feature is implemented and approved.
- If the MVP uses an external Google Form, label the destination clearly.

### `CommunityCard`

For parish communities, wilayah, and lingkungan.

- Prioritize real people and community activity.
- Show the official community name and a short approved description.
- Do not invent membership numbers, meeting schedules, or organizational claims.

### `ParishTimeline`

- A clear vertical timeline with year, title, short description, and optional archival image.
- Keep the text readable and the timeline line subtle.
- Red may be used for markers and year labels.
- On mobile, use a single-column timeline with consistent image sizing.
- Do not invent historical dates or milestones; use parish-approved content only.

### `PastorCard`

- Portrait, approved name, role, and approved biography.
- Do not invent credentials, tenure, or personal details.

### `OrganizationChart`

- Prefer semantic nested lists or accessible relationship structures.
- Use visual connectors only as supporting cues.
- The hierarchy must remain understandable on mobile and to assistive technology.

### `ServiceAreaMap`

- The map must not be the only way to communicate locations.
- Provide names and text descriptions for each location.
- Use a theme-aware container and keep map controls accessible.
- Avoid a blank, oversized map area when the embed fails; show a helpful fallback link.

### `ParishGallery`

- Use an intentional responsive grid.
- Use a lightbox only if keyboard navigation, focus management, escape-to-close, and alt text are supported.
- Avoid duplicate images and repetitive crops.
- Captions should identify the event or subject where known and approved.

### `ParishCTA`

- One clear message and one primary action.
- Use a neutral or restrained navy surface, not a large saturated-red block by default.
- Example message: “Bersama Membangun Gereja yang Hidup dan Misioner”.
- Keep the CTA connected to an actual destination or action.

### `ParishFooter`

The footer is a full-width site section and must adapt intentionally to both themes.

Include where data is available:

- parish identity/logo and approved description;
- primary navigation;
- profile navigation;
- services and community links;
- address, phone, email, and map link;
- approved social media links;
- privacy/terms links if implemented;
- copyright.

Light theme:

- background `canvas-subtle`;
- headings `text-primary`;
- body/links `text-muted`, with visible hover/focus state;
- subtle top border.

Dark theme:

- background `dark-canvas-subtle`;
- headings `dark-text-primary`;
- body `dark-text-body`;
- links `dark-text-muted` with visible hover/focus;
- subtle top border `dark-border-subtle`.

Never use low-opacity text for essential contact information. Never leave a light footer background under dark-theme text. The logo should retain a transparent background unless its own artwork intentionally includes one.

---

## 11. Page Composition Patterns

These patterns define composition, not new product features. Use only content supported by the approved PRD and parish data.

### Homepage

Recommended order:

1. Navbar
2. Hero with parish identity and main actions
3. Quick access to key destinations
4. Upcoming Mass
5. Latest news
6. Upcoming agenda
7. Latest reflection/approved pastoral message, if available
8. Services and communities
9. Gallery preview
10. Contact/map preview
11. Footer

Rules:

- Keep the first screen focused on parish identity and the next useful action.
- Avoid showing every feature above the fold.
- Use varied compositions rather than repeating identical three-card sections throughout the page.
- Keep section backgrounds restrained and consistent.

### News & Articles

Recommended order:

1. Navbar and breadcrumb
2. Page title and concise introduction
3. Optional featured story
4. Search, category filter, and sort control
5. News grid/list
6. Pagination or load-more control
7. Footer

Rules:

- Search/filter controls should not dominate the page.
- The featured story should be visually distinct without using a completely unrelated color scheme.
- News cards should be image-led and easy to scan.
- Empty results need a helpful message and a way to reset filters.

### Mass Schedule

Recommended order:

1. Navbar and breadcrumb
2. Page title and introduction
3. Upcoming Mass panel
4. Location/day/type filters
5. Routine Mass schedule
6. Special Masses, when available
7. Location cards and directions
8. Share/print actions if implemented
9. Footer

Rules:

- Time, day, Mass name, and location must be easy to distinguish.
- Keep routine schedules as scannable rows, not oversized cards.
- On mobile, preserve the same information order without horizontal overflow.
- Do not display invented or stale schedules.

### Parish Profile

Recommended order:

1. Navbar
2. Profile sub-navigation
3. Breadcrumb and page title
4. Page-specific content
5. Related profile links where useful
6. Footer

For history, use the timeline pattern. For pastor/leadership, use approved portraits and role information. For wilayah pelayanan, make each location understandable without requiring the map.

### Services, Communities, Gallery, Documents, Contact

- Use a page title and a short explanation.
- Provide a clear primary content pattern rather than a collection of unrelated cards.
- Use only actions and destinations that exist.
- Keep forms and maps secondary to readable contact details.
- Provide clear loading, error, and empty states.

---

## 12. Responsive Design

### Breakpoints

Use the project's existing Tailwind breakpoints unless there is a documented implementation reason to change them.

| Range         | Layout behavior                                                                     |
| ------------- | ----------------------------------------------------------------------------------- |
| `< 640px`     | Single-column content; mobile navigation; stacked CTAs                              |
| `640–767px`   | Wider mobile/tablet layout; two-column utility grids only where readable            |
| `768–1023px`  | Tablet; profile navigation collapses or becomes horizontal; grids usually 2 columns |
| `1024–1279px` | Desktop layout with reduced gutters and careful navigation spacing                  |
| `1280px+`     | Full editorial layout with centered content container                               |

### Mobile requirements

- No horizontal page overflow.
- Navigation and filter controls must wrap or stack.
- Minimum interactive target: 44×44px.
- Cards stack to one column unless a two-column layout remains genuinely readable.
- Hero text must not cover the main subject of the photo.
- Long Indonesian labels and parish names must wrap naturally.
- Footer becomes stacked groups or accessible accordions.
- Tables/schedules should become readable stacked rows when a wide table would overflow.

### Responsive media

- Hero image loads eagerly and has appropriate dimensions to avoid layout shift.
- Below-the-fold images are lazy-loaded.
- Use WebP/AVIF when supported by the asset pipeline.
- Use responsive crops and `object-position` to preserve the important subject.

---

## 13. Accessibility and Interaction

- Use semantic landmarks: `header`, `nav`, `main`, `section`, `article`, and `footer`.
- Every route has one clear H1.
- All interactive elements work with keyboard input.
- Focus states are visible in both themes.
- Do not rely on color alone to communicate state, category, or error.
- Maintain readable contrast for text, icons, controls, and borders.
- Provide accessible names for icon-only buttons.
- Provide alt text for informative images.
- Respect reduced-motion preferences.
- Keep transitions subtle, usually 150–220ms for color, border, and opacity.
- Avoid scroll-jacking, excessive parallax, and animations that delay access to content.
- Do not animate layout dimensions when a color or opacity transition is sufficient.
- Use shadcn/ui primitives for accessible dialogs, sheets, menus, tabs, and form controls.

---

## 14. Tailwind CSS and shadcn/ui Implementation Rules

### Tokens

- Implement color roles through CSS variables and Tailwind semantic tokens.
- Keep the brand primary variable fixed to `#AB020E`.
- Components should use semantic classes such as `bg-background`, `text-foreground`, `bg-card`, `text-muted-foreground`, `border-border`, and `bg-primary`.
- Avoid repeated arbitrary values such as `bg-[#AB020E]` in feature components.
- Do not hard-code light-only utility classes in components that support dark mode.
- Prefer reusable component variants to page-specific overrides.

### Component primitives

Reuse shadcn/ui for:

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
- Textarea
- Select
- Calendar
- Tooltip
- Separator
- Skeleton
- Form controls

Do not introduce another UI library for isolated components.

### Custom component naming

Use consistent, domain-specific components where repeated:

- `ParishNavbar`
- `ParishHero`
- `QuickAccessBar`
- `FeaturedNews`
- `NewsCard`
- `MassScheduleCard`
- `UpcomingMassPanel`
- `AgendaCard`
- `SacramentServiceCard`
- `CommunityCard`
- `ParishTimeline`
- `PastorCard`
- `OrganizationChart`
- `ServiceAreaMap`
- `ParishGallery`
- `ParishCTA`
- `ParishFooter`

### Avoid

- Global selectors that force all images to have a white background.
- Global dark-mode filters on all images.
- Hard-coded `bg-white`, `text-gray-900`, or `border-gray-200` where semantic tokens are needed.
- `text-white/40` or parent opacity for essential text.
- One-off hex values in individual components.
- Duplicated theme logic scattered across page components.

---

## 15. Definition of Done

A component or page is not considered finished until:

- [ ] It follows the approved hierarchy and page composition.
- [ ] It uses the semantic color and typography tokens.
- [ ] It looks intentional in both light and dark themes.
- [ ] The primary color remains `#AB020E`.
- [ ] It has visible hover, focus, active, disabled, and loading states where applicable.
- [ ] It works at mobile, tablet, and desktop widths.
- [ ] It has no horizontal overflow or clipped important text.
- [ ] Images have appropriate crops and alt text.
- [ ] Empty, error, and loading states are considered.
- [ ] Keyboard interaction works.
- [ ] Text and controls have sufficient contrast.
- [ ] The footer, navigation, and shared components match the theme.
- [ ] Content is supported by approved parish data; no history, schedules, names, or claims are invented.

### Required visual review

Review at least:

- Homepage in light and dark mode.
- News list and featured story in light and dark mode.
- Mass schedule and filters in light and dark mode.
- Profile history/timeline in light and dark mode.
- Footer in light and dark mode.
- Mobile navigation and one long-content page.
- Empty search results and a page with long Indonesian labels.

---

## 16. Known Decisions and Open Items

### Fixed decisions

- Primary brand color: `#AB020E`.
- Frontend: React + Inertia.js.
- Styling: Tailwind CSS.
- Component primitives: shadcn/ui.
- Icon set: Lucide React.
- Light theme is the default.
- Dark theme uses neutral charcoal surfaces rather than a saturated navy canvas.
- The public website is photography-led and content-first.

### To confirm during implementation

- Mona Sans is loaded (weights, italics, subsets) — see D-74 for the selected weights and the decision to self-host it.
- Final official logo asset and transparent-background behavior.
- Approved parish photography and image rights.
- Exact approved content for history, Mass schedules, clergy, service procedures, and community descriptions.
- Final accessibility/contrast audit on implemented pages.
- Motion preferences and any page-specific transitions.

Do not invent missing information to fill these gaps. Use approved parish content or a clearly identified placeholder until the parish confirms it.
