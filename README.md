# Workernu Redesign — Local Development

WordPress theme with custom sections for the Workernu homepage. This guide covers the section system architecture and CSS patterns to understand when making changes.

## Setup

1. Open `workernu-redesign` in [Local](https://localwp.com/)
2. Database: Import `workernu-redesign.sql` via Local > Backups or wp-admin
3. Navigate to `/wp-admin/` and review page content in the visual builder

## Section Architecture

All content sections live in `wp-content/themes/workernu/sections/`. Each section is self-contained:

```
sections/
├── section-name/
│   ├── section.php       (ACF field definitions for wp-admin)
│   ├── template.php      (frontend HTML render)
│   ├── style.css         (all styles for this section)
│   └── animations.js     (optional: interactivity)
```

### CSS Organization

Each section's `style.css` uses **BEM naming** and is self-documenting:

```css
.section--section-name { /* root */ }
.section--section-name__inner { /* container */ }
.section--section-name__heading { /* child element */ }
.section--section-name--modifier { /* variant */ }
```

**Key pattern**: Every CSS file starts with a comment block describing structure, modifiers, and any special behavior (CSS fallbacks, absolute positioning tricks, animation logic, etc.). **Read the comment block first** — it explains the "why" behind complex rules.

### Modifiers

Modifiers are BEM variants rendered by `workernu_section_classes()` in PHP. Common ones:

- `--align-left | --align-center` — text/content alignment
- `--media_position-left | --media_position-right` — image placement
- `--columns-2 | --columns-3 | --columns-4` — grid layout
- `--tone-inverted | --tone-default` — color theme
- `--card_style-card` — visual treatment

Modifiers chain with specificity:
```css
.section--name--modifier .section--name__child { /* applies only when modifier is present */ }
```

## Things to Watch For

### 1. Container Width & `max-width: *ch`

**Pattern**: Many headers have `max-width: 64ch` on the parent container to control line length for readability.

**When changing**: If you remove a `max-width: Xch` constraint, headings will expand to full viewport width. This is intentional only if the design calls for it — check alignment and overflow behavior on mobile.

### 2. Responsive Breakpoints

Media queries are **mobile-last** (desktop defaults, then `@media (max-width: 900px)` or `@media (max-width: 600px)` for mobile overrides):

```css
/* desktop */
.section--name__grid { display: flex; gap: var(--space-6); }

/* tablet + mobile */
@media (max-width: 900px) {
    .section--name__grid { flex-direction: column; }
}
```

**Watch out**: Stacking order, gap sizing, and font-size clamps all change at breakpoints. Test mobile view when editing.

### 3. CSS Variables & Token System

Color, spacing, typography use global tokens from `main.css`:

```css
color: var(--color-accent);         /* brand blue */
color: var(--color-muted);          /* secondary text */
padding: var(--space-4);            /* 1rem spacing scale */
font-size: clamp(1.5rem, 2vw, 2rem); /* responsive sizing */
```

**Never hardcode colors or spacing** — use tokens so design changes propagate everywhere.

### 4. Flexbox `min-width: 0` Pattern

When text needs to wrap or truncate inside flex columns:

```css
.section--name__list {
    display: flex;
    flex-wrap: wrap;
}
.section--name__item {
    flex: 1 1 0;    /* equal share of space */
    min-width: 0;   /* CRITICAL: allows flex children to shrink below content size */
}
```

Without `min-width: 0`, flex children won't shrink past their content, breaking layouts.

### 5. Absolute Positioning for Images

Several sections (hero, zigzag-rows, feature-accordion) use absolute positioning for images to achieve bleed/overflow effects:

```css
.section--name__media {
    position: relative;
    height: 500px;  /* explicit height for the container */
}
.section--name__image {
    position: absolute;
    top: 0;
    left: 0;        /* or right: 0 for different anchor */
    width: auto;
    height: 100%;
    max-width: none; /* allow overflow beyond container */
}
```

**Watch out**: The parent **must** have explicit height and `position: relative`. Images can bleed off-viewport and need `overflow-x: clip` on the section root to contain them.

### 6. `clamp()` for Responsive Typography

Font sizes use `clamp(min, preferred, max)`:

```css
font-size: clamp(1.5rem, 2.5vw, 2rem);
/* scales smoothly between 1.5rem (mobile) and 2rem (desktop)
   based on viewport width (2.5vw) */
```

**Don't replace with fixed sizes** — clamp provides fluid scaling without breakpoints.

### 7. CSS `:has()` Fallbacks

Some sections (feature-accordion, tabs) use `:has()` selectors for state-driven styling. These have `.is-js` guards to switch between CSS fallback (no JS) and JS-driven logic:

```css
.section--name__grid:not(.is-js):has(.details[open]) .image { opacity: 1; }
.section--name__image.is-active { opacity: 1; }  /* JS stamps .is-active */
```

**Key pattern**: If JS is present, `.is-js` class is added at init, and JS rules take over. Without JS, `:has()` fallback rules provide basic interactivity. Both paths must work.

### 8. BEM Specificity

BEM avoids nesting and keeps specificity flat so modifiers work cleanly:

```css
/* ✅ Good — flat specificity, modifiers override naturally */
.section--name { color: black; }
.section--name--inverted { color: white; }

/* ❌ Avoid — nesting creates specificity wars */
.section--name .section--name__child { color: black; }
.section--name--inverted .section--name__child { color: white; } /* needs !important or higher specificity to win */
```

## Making Changes

### Adding a New Modifier

1. Add the class to `section.php` in the field definitions
2. Add a matching CSS rule in `style.css`:
   ```css
   .section--name--new-modifier .section--name__element { /* new styles */ }
   ```
3. Test in wp-admin: the modifier should appear as a checkbox/select option

### Removing `max-width` Constraints

If you remove a `max-width: Xch` rule from a header:

- Heading expands to full container width
- Check alignment (center vs. left) — centered text looks bad when too wide
- Test mobile: does the heading still fit without breaking awkwardly?

### Editing Images or Overflow Behavior

Before changing absolute positioning or overflow rules:

1. Check the comment block in `style.css` — it explains the trick
2. Test desktop and mobile views
3. Verify images don't bleed off-screen unintentionally (or do, if by design)

## Deployment

- **Local**: Changes in `wp-content/themes/workernu/sections/` are live immediately
- **Stage (Hostinger)**: Push via rsync: `rsync -avz wp-content/themes/workernu/sections/ hostinger-mindaugas:/home/u607277601/domains/workernu.com/public_html/wp-content/themes/workernu/sections/`
- **Database**: Export from Local > Backups, import on stage via wp-admin or WP-CLI

## Resources

- **ACF Field Documentation**: `section.php` comments describe each field
- **Template Variables**: `template.php` is readable PHP — check how fields render
- **CSS Comments**: Read the top comment block in `style.css` before editing complex rules
