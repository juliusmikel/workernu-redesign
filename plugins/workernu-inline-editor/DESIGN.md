# Frontend Inline Text Editor — Design

## Purpose

Let logged-in admins edit plain-text and rich-text (body copy) fields directly on
the live front end, with a Save Draft / Publish workflow, instead of going
through the wp-admin page editor for small copy tweaks.

## Scope

- **Field types**: `text` fields (including ones nested in repeaters, e.g. a
  CTA button's `label`) and `rich_text` fields (edited as raw newline-delimited
  text — no formatting toolbar, matching how the wp-admin builder already
  stores them).
- **Not in scope**: `select`, `icon`, `image` fields; adding/removing repeater
  items; any field type other than text/rich_text.
- **Languages**: translatable fields (`{lt: "...", en: "..."}`) are edited for
  the currently-viewed language only. The other language's value is untouched.
- **Sections wired up in this pass**: `hero` and `feature-highlight` (all their
  `text` fields, plus the `body` rich_text field). Every other section keeps
  its current (non-editable) rendering. Extending coverage to another section
  is a mechanical follow-up: wrap its `template.php` output points with the
  same `workernu_editable()` helper this build introduces.

## Data model

New post meta key: `_workernu_sections_draft`.

- Holds a full copy of the page's sections array (same shape as the existing
  `WORKERNU_SECTIONS_META_KEY` live meta).
- Created lazily: the first inline save on a page copies the live array into
  this key, then writes the edited field into the copy.
- Subsequent edits write into the existing draft copy.
- Nothing here is ever read by public rendering — only by the editing admin's
  own preview (see below).

## Draft visibility / rendering

- Admin bar node **"Edit Text"** toggles edit mode for the current admin's own
  session (cookie-scoped, not a site-wide setting).
- While edit mode is on, for a user who can `edit_post` the current page,
  section rendering reads `_workernu_sections_draft` (falling back to the live
  meta if no draft exists yet) instead of the live meta.
- Edit mode off, or any other visitor (including other logged-in admins not in
  edit mode): always reads the live/published meta. Unpublished text is never
  visible to anyone except the editing admin, and only while their toggle is on.

## Editing UI

- New vanilla-JS module (no framework — matches the theme's existing
  `animations.js` pattern), enqueued only when the viewer is logged in and can
  edit the current post.
- Hover on a wrapped field shows an outline + a pencil icon.
- Click swaps the field's rendered content for an `<input>` (plain `text`) or
  `<textarea>` (`rich_text`), pre-filled with the raw stored value.
- A small floating toolbar offers **Save Draft**, **Publish**, **Cancel**.
  - *Save Draft*: AJAX call writes the field into `_workernu_sections_draft`.
    Page can be reloaded/left and the draft persists (it's server-side meta).
  - *Publish*: AJAX call copies `_workernu_sections_draft` → the live meta key
    wholesale (same effect as clicking Update in wp-admin). Draft key is left
    in place afterward (now identical to live) rather than deleted.
  - *Cancel*: discards the in-progress edit, no request sent.
- The admin bar node also shows a lightweight indicator when the current
  page's draft differs from its live content, and offers a "Publish all"
  action as a shortcut to publish everything staged on this page at once.

## Markup / template integration

New helper in the `workernu-sections` plugin's public API, e.g.:

```php
workernu_editable(string $section_id, string $field_path, string $html): string
```

- `$field_path` addresses the field: `heading`, or `ctas.0.label` for a
  repeater item.
- Only wraps `$html` in a `data-wn-field="<section_id>::<field_path>"
  data-wn-type="text|rich_text"` span/div when the current request is an admin
  in edit mode; otherwise returns `$html` unchanged. Normal visitor requests
  get byte-identical markup to today — no added weight.
- `hero/template.php` and `feature-highlight/template.php` call this helper at
  each in-scope field's output point instead of `echo`-ing directly.

## AJAX endpoints

Both nonce-protected and gated on `current_user_can('edit_post', $post_id)`:

- `workernu_inline_save_draft` — sanitizes one field's new value through the
  existing `Fields\sanitize_value()` (same sanitizer the wp-admin save path
  uses for that field's type) and writes it into the draft copy.
- `workernu_inline_publish` — copies the full draft array over the live meta
  key for the given post.

No new capability or role is introduced — anyone who could already edit this
page's content in wp-admin can now also edit it inline; the trust boundary is
unchanged.

## Deployment

New code lives in a new plugin folder,
`wp-content/plugins/workernu-inline-editor/`, plus the two template.php edits
above. This is committed and pushed as its own commit on `dev`, then rsynced
to the Hostinger stage host — the 33 other files currently modified locally
(an unrelated, pre-existing mobile-responsive pass) are left untouched and
unpushed.

## Out of scope / explicitly deferred

- Formatting/rich-text toolbar (bold, links, etc.) — fields don't support it
  today, so the editor doesn't add it.
- Editing images, icons, selects, or repeater add/remove.
- Site-wide rollout beyond hero/feature-highlight (follow-up work per
  section).
- Page/edge caching considerations (none present on this stage host).
