# Frontend Inline Text Editor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let logged-in admins edit `text`/`textarea`/`rich_text` fields directly on the live front end (hero + feature-highlight sections first), with a Save Draft / Publish workflow driven from an admin-bar toggle.

**Architecture:** A new plugin (`workernu-inline-editor`) adds a second post-meta key (`_page_sections_draft`) holding a full draft copy of a page's `_page_sections` array. A `get_post_metadata` filter transparently swaps the live meta for the draft copy during rendering, but only for an admin who has flipped a per-session "Edit Text" cookie on a page they can edit — everyone else always gets the live/published meta untouched. Field edits arrive over two nonce-protected AJAX actions and are sanitized through the *existing* `workernu-sections` plugin's own `Fields\sanitize_value()`, so no new sanitization logic is introduced. A small `workernu_inline_editable()` helper, called from `template.php` at each in-scope field, wraps the field's output with the data attributes a vanilla-JS module needs for hover/pencil/edit — emitting nothing extra for anyone not in edit mode.

**Tech Stack:** Plain PHP (no framework), vanilla JS (matches the theme's existing `animations.js` pattern), WordPress core APIs (post meta, admin-ajax, admin bar, cookies). No build step, no PHPUnit/test runner exists anywhere in this codebase — verification here is `php -l` for syntax (real, automated) plus manual browser QA against the Local site (matches how every other section in this theme was verified).

## Global Constraints

- Reuse `\WorkerNu\Sections\Fields\sanitize_value()` for all field sanitization — never write a parallel sanitizer.
- Reuse `\WorkerNu\Sections\Registry\get()` to read section schemas — never hardcode field lists.
- In-scope field types: `text`, `textarea`, `rich_text` only (top-level or one level deep inside a `repeater`). Everything else (`select`, `icon`, `image`, `gallery`, `link`, `boolean`, `number`) is untouched.
- Translatable fields: an edit only ever touches the current language's key (`workernu_lang()`), the other language's value is preserved as-is.
- No new WordPress capability or role — gating is always `current_user_can('edit_post', $post_id)`.
- Normal visitors (logged out, or logged in without edit mode on) must get byte-identical HTML to today — no added markup, no added CSS/JS payload.
- New code lives entirely under `wp-content/plugins/workernu-inline-editor/` plus two small edits to `hero/template.php` and `feature-highlight/template.php`. Nothing else in the repo's other 33 currently-modified files is touched.
- Plugin naming/style conventions to match exactly (see `plugins/workernu-sections/`): `namespace WorkerNu\InlineEditor\<Module>` per include file, plain `require_once` list in the bootstrap file (no autoloader), public template-facing helpers as global `workernu_*()` functions in `includes/api.php`, AJAX handlers using `check_ajax_referer($action, 'nonce', false)` + `wp_send_json_error/success` (see `workernu_handle_contact_form()` in `themes/workernu/functions.php:88`).

---

## File Structure

```
plugins/workernu-inline-editor/
  workernu-inline-editor.php   Bootstrap: constants, requires, hook registration
  includes/
    mode.php                   Edit-mode cookie: is_active(), toggle_url(), handle_toggle()
    draft.php                  Draft meta CRUD + field-path merge/sanitize + publish
    render.php                 get_post_metadata filter (live→draft swap) + body_class flag
    markup.php                 workernu_editable_field() wrap-in-span/div helper
    ajax.php                   wp_ajax_ handlers: save_draft, publish
    admin-bar.php               "Edit Text" toggle + "Publish" node
    assets.php                  wp_enqueue_scripts for the editor JS/CSS, gated to active edit mode
    api.php                     Public workernu_inline_editable() wrapper for templates
  assets/
    editor.css                  Hover outline, pencil, inline input, toolbar
    editor.js                   Click-to-edit, Save Draft / Publish / Cancel AJAX calls
  DESIGN.md                     (already committed)
  PLAN.md                       (this file)
```

**Interfaces at a glance** (exact names every task must match):
- `WorkerNu\InlineEditor\Mode\is_active(int $post_id): bool`
- `WorkerNu\InlineEditor\Mode\toggle_url(bool $enable): string`
- `WorkerNu\InlineEditor\Draft\META_KEY` (string constant `'_page_sections_draft'`)
- `WorkerNu\InlineEditor\Draft\has_pending_changes(int $post_id): bool`
- `WorkerNu\InlineEditor\Draft\save_field(int $post_id, string $section_id, string $field_path, $raw_value): bool`
- `WorkerNu\InlineEditor\Draft\publish(int $post_id): bool`
- `WorkerNu\InlineEditor\Markup\field(string $section_id, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string`
- `workernu_inline_editable(array $data, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string` (global, template-facing)
- `WorkerNu\InlineEditor\Ajax\NONCE_ACTION` (string constant `'workernu_inline_editor'`)

---

### Task 1: Plugin bootstrap + edit-mode cookie toggle

**Files:**
- Create: `plugins/workernu-inline-editor/workernu-inline-editor.php`
- Create: `plugins/workernu-inline-editor/includes/mode.php`

**Interfaces:**
- Produces: `WorkerNu\InlineEditor\Mode\is_active(int $post_id): bool`, `WorkerNu\InlineEditor\Mode\toggle_url(bool $enable): string`, `WorkerNu\InlineEditor\Mode\handle_toggle(): void`, constant `WorkerNu\InlineEditor\Mode\COOKIE = 'wn_inline_edit'`.

- [ ] **Step 1: Write `includes/mode.php`**

```php
<?php
namespace WorkerNu\InlineEditor\Mode;

if (!defined('ABSPATH')) exit;

const COOKIE = 'wn_inline_edit';

/**
 * True when the current visitor is logged in, can edit $post_id, and has
 * flipped the "Edit Text" toggle on. Any other visitor (logged out, or an
 * admin who hasn't toggled it) always gets false here.
 */
function is_active(int $post_id): bool {
    if ($post_id <= 0) return false;
    if (!is_user_logged_in()) return false;
    if (!current_user_can('edit_post', $post_id)) return false;
    return !empty($_COOKIE[COOKIE]);
}

/**
 * URL that flips the cookie on/off for the current page when visited.
 */
function toggle_url(bool $enable): string {
    return esc_url(add_query_arg('wn_edit_toggle', $enable ? '1' : '0'));
}

/**
 * Runs on template_redirect. If ?wn_edit_toggle=0|1 is present, sets/clears
 * the cookie and 302s back to the clean URL — keeps the toggle a one-time
 * action instead of a permanent query string.
 */
function handle_toggle(): void {
    if (!isset($_GET['wn_edit_toggle'])) return;
    if (!is_user_logged_in()) return;

    $enable = $_GET['wn_edit_toggle'] === '1';
    $path   = COOKIEPATH ?: '/';
    if ($enable) {
        setcookie(COOKIE, '1', time() + DAY_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true);
    } else {
        setcookie(COOKIE, '', time() - HOUR_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true);
    }

    wp_safe_redirect(remove_query_arg('wn_edit_toggle'));
    exit;
}
```

- [ ] **Step 2: Write `workernu-inline-editor.php`**

```php
<?php
/**
 * Plugin Name: workernu Inline Editor
 * Description: Admin-only front-end inline editing for text/rich_text fields, with a Save Draft / Publish workflow. Requires workernu Sections + workernu Lang.
 * Version: 0.1.0
 * Author: workernu
 * Text Domain: workernu-inline-editor
 */

if (!defined('ABSPATH')) exit;

define('WORKERNU_INLINE_EDITOR_VERSION', '0.1.0');
define('WORKERNU_INLINE_EDITOR_PATH',    plugin_dir_path(__FILE__));
define('WORKERNU_INLINE_EDITOR_URL',     plugin_dir_url(__FILE__));

require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/mode.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/draft.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/render.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/markup.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/ajax.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/admin-bar.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/assets.php';
require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/api.php';

add_action('template_redirect', '\\WorkerNu\\InlineEditor\\Mode\\handle_toggle');

// Soft dependency check, mirrors workernu-sections.php's own pattern.
add_action('admin_notices', function () {
    if (!defined('WORKERNU_SECTIONS_META_KEY') || !function_exists('workernu_lang')) {
        echo '<div class="notice notice-error"><p><strong>workernu Inline Editor</strong> requires the <strong>workernu Sections</strong> and <strong>workernu Lang</strong> plugins to be active.</p></div>';
    }
});
```

- [ ] **Step 3: Syntax-check both files**

Run: `php -l "plugins/workernu-inline-editor/workernu-inline-editor.php" && php -l "plugins/workernu-inline-editor/includes/mode.php"`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Manual verify — plugin activates cleanly**

In Local, visit `/wp-admin/plugins.php`, activate "workernu Inline Editor". Expected: no PHP warnings/notices on the plugins screen, no admin-notice about missing dependencies (since Sections + Lang are already active).

- [ ] **Step 5: Commit**

```bash
cd "public/wp-content"
git add plugins/workernu-inline-editor/workernu-inline-editor.php plugins/workernu-inline-editor/includes/mode.php
git commit -m "inline-editor: add plugin bootstrap + edit-mode cookie toggle"
```

---

### Task 2: Draft data layer

**Files:**
- Create: `plugins/workernu-inline-editor/includes/draft.php`

**Interfaces:**
- Consumes: `\WorkerNu\Sections\Registry\get(string $slug): ?array` (from `workernu-sections`), `\WorkerNu\Sections\Fields\sanitize_value(array $field, $raw)` (from `workernu-sections`), `workernu_lang(): string` (from `workernu-lang`), constant `WORKERNU_SECTIONS_META_KEY` (from `workernu-sections`, value `'_page_sections'`).
- Produces: `WorkerNu\InlineEditor\Draft\META_KEY`, `ensure_draft()`, `has_pending_changes()`, `save_field()`, `publish()`.

- [ ] **Step 1: Write `includes/draft.php`**

```php
<?php
namespace WorkerNu\InlineEditor\Draft;

use function WorkerNu\Sections\Registry\get as get_section_def;
use function WorkerNu\Sections\Fields\sanitize_value;

if (!defined('ABSPATH')) exit;

const META_KEY = '_page_sections_draft';

/**
 * The draft sections array, creating it as a copy of the live array on
 * first call for this post. Always returns an array (possibly empty).
 */
function ensure_draft(int $post_id): array {
    $draft = get_post_meta($post_id, META_KEY, true);
    if (is_array($draft)) return $draft;

    $live = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    $live = is_array($live) ? $live : [];
    update_post_meta($post_id, META_KEY, $live);
    return $live;
}

/**
 * True when this post has a draft copy that differs from its live meta.
 */
function has_pending_changes(int $post_id): bool {
    $draft = get_post_meta($post_id, META_KEY, true);
    if (!is_array($draft)) return false;

    $live = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    $live = is_array($live) ? $live : [];
    return $draft !== $live;
}

/**
 * Copies the draft array over the live meta key — the same effect as
 * clicking Update in wp-admin. Returns false if there's no draft to publish.
 */
function publish(int $post_id): bool {
    $draft = get_post_meta($post_id, META_KEY, true);
    if (!is_array($draft)) return false;

    update_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, $draft);
    return true;
}

/**
 * Writes one field's new value into the draft copy of $post_id's sections,
 * sanitized through the section type's own field schema. $field_path is
 * either a top-level field name ("heading") or a one-level-deep repeater
 * path ("ctas.0.label"). Returns false if the post, section, or field can't
 * be resolved, or the field type isn't editable.
 */
function save_field(int $post_id, string $section_id, string $field_path, $raw_value): bool {
    $sections = ensure_draft($post_id);

    foreach ($sections as $i => $section) {
        if (!is_array($section) || ($section['_id'] ?? '') !== $section_id) continue;

        $type = (string) ($section['_type'] ?? '');
        $def  = get_section_def($type);
        if (!$def) return false;

        $updated = apply_field_path($def, $section, $field_path, $raw_value);
        if ($updated === null) return false;

        $sections[$i] = $updated;
        update_post_meta($post_id, META_KEY, $sections);
        return true;
    }

    return false;
}

function apply_field_path(array $def, array $section, string $field_path, $raw_value): ?array {
    $parts = explode('.', $field_path);
    $lang  = function_exists('workernu_lang') ? workernu_lang() : 'lt';

    if (count($parts) === 1) {
        $name  = $parts[0];
        $field = find_field($def['fields'] ?? [], $name);
        if (!$field || !is_editable_type($field['type'] ?? '')) return null;

        $current        = $section[$name] ?? null;
        $merged         = merge_scalar_value($field, $current, $raw_value, $lang);
        $section[$name] = sanitize_value($field, $merged);
        return $section;
    }

    if (count($parts) === 3) {
        [$repeater_name, $index, $sub_name] = $parts;
        $index = (int) $index;

        $repeater_field = find_field($def['fields'] ?? [], $repeater_name);
        if (!$repeater_field || ($repeater_field['type'] ?? '') !== 'repeater') return null;

        $sub_field = find_field($repeater_field['fields'] ?? [], $sub_name);
        if (!$sub_field || !is_editable_type($sub_field['type'] ?? '')) return null;

        $items = is_array($section[$repeater_name] ?? null) ? $section[$repeater_name] : [];
        if (!isset($items[$index]) || !is_array($items[$index])) return null;

        $current                       = $items[$index][$sub_name] ?? null;
        $items[$index][$sub_name]      = merge_scalar_value($sub_field, $current, $raw_value, $lang);
        $section[$repeater_name]       = sanitize_value($repeater_field, $items);
        return $section;
    }

    return null;
}

function find_field(array $fields, string $name): ?array {
    foreach ($fields as $f) {
        if (($f['name'] ?? null) === $name) return $f;
    }
    return null;
}

function is_editable_type(string $type): bool {
    return in_array($type, ['text', 'textarea', 'rich_text'], true);
}

/**
 * Merges a new raw leaf value into a field's existing stored value:
 *   - rich_text: only the `value` key changes, `display` is preserved; if
 *     translatable, only the current language's sub-key changes.
 *   - translatable text/textarea: only the current language's key changes.
 *   - plain text/textarea: the whole value is replaced.
 * The result is NOT sanitized yet — the caller always runs it through
 * sanitize_value() afterward.
 */
function merge_scalar_value(array $field, $current, $raw_value, string $lang) {
    $type          = $field['type'] ?? 'text';
    $translatable  = !empty($field['translatable']);

    if ($type === 'rich_text') {
        $current = is_array($current) ? $current : [];
        $display = $current['display'] ?? '';
        $value   = $current['value']   ?? ($translatable ? [] : '');

        if ($translatable) {
            $value         = is_array($value) ? $value : [];
            $value[$lang]  = (string) $raw_value;
        } else {
            $value = (string) $raw_value;
        }

        return ['value' => $value, 'display' => $display];
    }

    if ($translatable) {
        $value        = is_array($current) ? $current : [];
        $value[$lang] = (string) $raw_value;
        return $value;
    }

    return (string) $raw_value;
}
```

- [ ] **Step 2: Syntax-check**

Run: `php -l "plugins/workernu-inline-editor/includes/draft.php"`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Manual verify — draft copy-on-write**

Add `require_once WORKERNU_INLINE_EDITOR_PATH . 'includes/draft.php';` to the bootstrap's require list (already added in Task 1's file — just confirm it's there). On a page with a hero section, temporarily add this one-off snippet to the bottom of `workernu-inline-editor.php`, load any front-end page once in a browser, then remove the snippet:

```php
add_action('wp_footer', function () {
    if (!current_user_can('manage_options')) return;
    $id = get_queried_object_id();
    if (!$id) return;
    \WorkerNu\InlineEditor\Draft\save_field($id, 'DOES-NOT-EXIST', 'heading', 'x'); // expect false, no fatal
    var_dump(\WorkerNu\InlineEditor\Draft\has_pending_changes($id)); // expect false (no real edit made)
});
```
Expected: page loads with no PHP fatal, `var_dump` prints `bool(false)`. Remove the snippet afterward — it was only to prove the functions don't fatal on a real request.

- [ ] **Step 4: Commit**

```bash
cd "public/wp-content"
git add plugins/workernu-inline-editor/includes/draft.php
git commit -m "inline-editor: add draft data layer (copy-on-write, field-path merge, publish)"
```

---

### Task 3: Live→draft rendering swap

**Files:**
- Create: `plugins/workernu-inline-editor/includes/render.php`
- Modify: `plugins/workernu-inline-editor/workernu-inline-editor.php` (register the filter)

**Interfaces:**
- Consumes: `WorkerNu\InlineEditor\Mode\is_active()`, `WorkerNu\InlineEditor\Draft\META_KEY`, constant `WORKERNU_SECTIONS_META_KEY`.
- Produces: the `get_post_metadata` filter registration; `body_class` gets `wn-edit-mode` added when active.

- [ ] **Step 1: Write `includes/render.php`**

```php
<?php
namespace WorkerNu\InlineEditor\Render;

if (!defined('ABSPATH')) exit;

/**
 * Swaps the live `_page_sections` meta for the draft copy, but ONLY when:
 *   - we're not in wp-admin (the section builder must always show live data)
 *   - nothing else already resolved this meta read
 *   - the viewer has edit mode active for this post (see Mode\is_active())
 *   - a draft copy actually exists
 * Every other request (logged-out visitors, admins without the toggle on,
 * wp-admin itself) is untouched and reads the real live meta as normal.
 */
function swap_live_for_draft($value, $object_id, $meta_key, $single) {
    if ($value !== null) return $value;
    if (is_admin()) return $value;
    if ($meta_key !== WORKERNU_SECTIONS_META_KEY || !$single) return $value;
    if (!\WorkerNu\InlineEditor\Mode\is_active((int) $object_id)) return $value;

    $draft = get_post_meta((int) $object_id, \WorkerNu\InlineEditor\Draft\META_KEY, true);
    if (!is_array($draft)) return $value;

    return [$draft]; // get_metadata() unwraps single=true via $check[0]
}

/**
 * Flags <body> with wn-edit-mode when the current viewer has the toggle on,
 * so editor.css only ever needs to key off one class.
 */
function flag_body_class(array $classes): array {
    $post_id = get_queried_object_id();
    if ($post_id && \WorkerNu\InlineEditor\Mode\is_active($post_id)) {
        $classes[] = 'wn-edit-mode';
    }
    return $classes;
}
```

- [ ] **Step 2: Register the hooks in the bootstrap file**

Add to `workernu-inline-editor.php`, after the `template_redirect` line already added in Task 1:

```php
add_filter('get_post_metadata', '\\WorkerNu\\InlineEditor\\Render\\swap_live_for_draft', 10, 4);
add_filter('body_class',        '\\WorkerNu\\InlineEditor\\Render\\flag_body_class');
```

- [ ] **Step 3: Syntax-check**

Run: `php -l "plugins/workernu-inline-editor/includes/render.php" && php -l "plugins/workernu-inline-editor/workernu-inline-editor.php"`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Manual verify — swap only fires when it should**

As an admin who can edit the page, with the `wn_inline_edit` cookie NOT set: load the front-end page, view source, confirm `<body class="…">` has no `wn-edit-mode`. In your browser's devtools console run `document.cookie = "wn_inline_edit=1; path=/"`, reload: confirm `<body>` now has `wn-edit-mode`. Log out (or open a private window) and reload the same URL: confirm the page still renders normally (no fatal, no `wn-edit-mode`, since `is_user_logged_in()` is false). Clear the cookie afterward (`document.cookie = "wn_inline_edit=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/"`).

- [ ] **Step 5: Commit**

```bash
cd "public/wp-content"
git add plugins/workernu-inline-editor/includes/render.php plugins/workernu-inline-editor/workernu-inline-editor.php
git commit -m "inline-editor: swap live meta for draft copy during admin edit-mode preview"
```

---

### Task 4: AJAX save/publish endpoints

**Files:**
- Create: `plugins/workernu-inline-editor/includes/ajax.php`
- Modify: `plugins/workernu-inline-editor/workernu-inline-editor.php` (register hooks)

**Interfaces:**
- Consumes: `Draft\save_field()`, `Draft\publish()`, `Draft\has_pending_changes()`.
- Produces: `WorkerNu\InlineEditor\Ajax\NONCE_ACTION` (`'workernu_inline_editor'`), action names `workernu_inline_save_draft` and `workernu_inline_publish` (JS in Task 6 posts to these).

- [ ] **Step 1: Write `includes/ajax.php`**

```php
<?php
namespace WorkerNu\InlineEditor\Ajax;

use function WorkerNu\InlineEditor\Draft\save_field;
use function WorkerNu\InlineEditor\Draft\publish;
use function WorkerNu\InlineEditor\Draft\has_pending_changes;

if (!defined('ABSPATH')) exit;

const NONCE_ACTION = 'workernu_inline_editor';

function require_access(int $post_id): void {
    if (!check_ajax_referer(NONCE_ACTION, 'nonce', false)) {
        wp_send_json_error(['message' => __('Session expired — reload the page.', 'workernu-inline-editor')]);
    }
    if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => __('Not allowed.', 'workernu-inline-editor')]);
    }
}

function handle_save_draft(): void {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    require_access($post_id);

    $section_id = sanitize_text_field(wp_unslash((string) ($_POST['section_id'] ?? '')));
    $field_path = sanitize_text_field(wp_unslash((string) ($_POST['field_path'] ?? '')));
    $value      = wp_unslash((string) ($_POST['value'] ?? ''));

    if ($section_id === '' || $field_path === '') {
        wp_send_json_error(['message' => __('Missing field.', 'workernu-inline-editor')]);
    }

    if (!save_field($post_id, $section_id, $field_path, $value)) {
        wp_send_json_error(['message' => __('Could not save that field.', 'workernu-inline-editor')]);
    }

    wp_send_json_success(['has_pending' => has_pending_changes($post_id)]);
}

function handle_publish(): void {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    require_access($post_id);

    if (!publish($post_id)) {
        wp_send_json_error(['message' => __('Nothing to publish.', 'workernu-inline-editor')]);
    }

    wp_send_json_success(['has_pending' => false]);
}
```

- [ ] **Step 2: Register the AJAX hooks in the bootstrap file**

Add to `workernu-inline-editor.php` (logged-in only — there is no `_nopriv` variant, matching that only editors ever call these):

```php
add_action('wp_ajax_workernu_inline_save_draft', '\\WorkerNu\\InlineEditor\\Ajax\\handle_save_draft');
add_action('wp_ajax_workernu_inline_publish',     '\\WorkerNu\\InlineEditor\\Ajax\\handle_publish');
```

- [ ] **Step 3: Syntax-check**

Run: `php -l "plugins/workernu-inline-editor/includes/ajax.php" && php -l "plugins/workernu-inline-editor/workernu-inline-editor.php"`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Manual verify — endpoints reject bad requests correctly**

As a logged-in admin, in the browser devtools console on any front-end page:
```js
fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
  method: 'POST', credentials: 'same-origin',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'action=workernu_inline_save_draft&post_id=1&section_id=x&field_path=heading&value=hi&nonce=bad'
}).then(r => r.json()).then(console.log);
```
Expected: `{success: false, data: {message: "Session expired — reload the page."}}` (nonce check fails first, as designed). This confirms the endpoint is wired and rejecting invalid nonces — full happy-path verification happens once Task 6's JS can supply a real nonce.

- [ ] **Step 5: Commit**

```bash
cd "public/wp-content"
git add plugins/workernu-inline-editor/includes/ajax.php plugins/workernu-inline-editor/workernu-inline-editor.php
git commit -m "inline-editor: add save-draft/publish AJAX endpoints"
```

---

### Task 5: Markup helper + public API

**Files:**
- Create: `plugins/workernu-inline-editor/includes/markup.php`
- Create: `plugins/workernu-inline-editor/includes/api.php`

**Interfaces:**
- Consumes: `Mode\is_active()`.
- Produces: `workernu_inline_editable(array $data, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string` — this is what Tasks 7 and 8 call from `template.php`.

- [ ] **Step 1: Write `includes/markup.php`**

```php
<?php
namespace WorkerNu\InlineEditor\Markup;

if (!defined('ABSPATH')) exit;

/**
 * Wraps $rendered_html with the data attributes editor.js needs, but only
 * when the current viewer is in active edit mode for the current post —
 * otherwise returns $rendered_html completely unchanged (no added markup
 * for regular visitors, or for admins not currently editing).
 *
 * $field_path addresses the field within its section: "heading" for a
 * top-level field, "ctas.0.label" for a repeater item's sub-field.
 * $raw_value is the actual stored (already language-resolved) text — used
 * to seed the edit input, since $rendered_html may have been transformed
 * (e.g. rich_text's bullet/numbered HTML) in a way plain text extraction
 * in the browser can't reliably reverse.
 */
function field(string $section_id, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string {
    if ($section_id === '' || !\WorkerNu\InlineEditor\Mode\is_active((int) get_the_ID())) {
        return $rendered_html;
    }

    $wrapper = in_array($wrapper, ['span', 'div'], true) ? $wrapper : 'span';

    return sprintf(
        '<%1$s class="wn-editable" data-wn-field="%2$s" data-wn-type="%3$s" data-wn-raw="%4$s">'
        . '<span class="wn-editable__content">%5$s</span>'
        . '<button type="button" class="wn-editable__pencil" aria-label="%6$s"><i class="fa-solid fa-pencil" aria-hidden="true"></i></button>'
        . '</%1$s>',
        $wrapper,
        esc_attr($section_id . '::' . $field_path),
        esc_attr($type),
        esc_attr($raw_value),
        $rendered_html,
        esc_attr__('Edit this text', 'workernu-inline-editor')
    );
}
```

- [ ] **Step 2: Write `includes/api.php`**

```php
<?php
/**
 * workernu Inline Editor — theme-facing global API.
 * The only function template.php files should call from this plugin.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('workernu_inline_editable')) {
    function workernu_inline_editable(array $data, string $field_path, string $type, string $rendered_html, string $raw_value, string $wrapper = 'span'): string {
        return \WorkerNu\InlineEditor\Markup\field(
            (string) ($data['_id'] ?? ''),
            $field_path,
            $type,
            $rendered_html,
            $raw_value,
            $wrapper
        );
    }
}
```

- [ ] **Step 3: Syntax-check**

Run: `php -l "plugins/workernu-inline-editor/includes/markup.php" && php -l "plugins/workernu-inline-editor/includes/api.php"`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Manual verify — no-op when inactive**

With the `wn_inline_edit` cookie cleared, temporarily add `echo workernu_inline_editable(['_id' => 'x'], 'heading', 'text', 'Hello', 'Hello');` right before `wp_footer()` in `footer.php`, load any page: expect it prints exactly `Hello` with no wrapper. Set the cookie, reload: expect the full `<span class="wn-editable" data-wn-field="x::heading" …>` markup. Remove the temporary line from `footer.php` afterward.

- [ ] **Step 5: Commit**

```bash
cd "public/wp-content"
git add plugins/workernu-inline-editor/includes/markup.php plugins/workernu-inline-editor/includes/api.php
git commit -m "inline-editor: add workernu_inline_editable() markup helper + public API"
```

---

### Task 6: Admin bar UI + frontend JS/CSS

**Files:**
- Create: `plugins/workernu-inline-editor/includes/admin-bar.php`
- Create: `plugins/workernu-inline-editor/includes/assets.php`
- Create: `plugins/workernu-inline-editor/assets/editor.css`
- Create: `plugins/workernu-inline-editor/assets/editor.js`
- Modify: `plugins/workernu-inline-editor/workernu-inline-editor.php` (register hooks)

**Interfaces:**
- Consumes: `Mode\is_active()`, `Mode\toggle_url()`, `Draft\has_pending_changes()`, `Ajax\NONCE_ACTION`.
- Produces: the `wn-editable`/`wn-editable__pencil`/`wn-editable__content` DOM contract that Tasks 7–8's markup relies on; the global JS object `wnInlineEditor` (`ajaxUrl`, `nonce`, `postId`, `i18n`).

- [ ] **Step 1: Write `includes/admin-bar.php`**

```php
<?php
namespace WorkerNu\InlineEditor\AdminBar;

if (!defined('ABSPATH')) exit;

function register(\WP_Admin_Bar $bar): void {
    if (is_admin() || !is_singular()) return;

    $post_id = get_queried_object_id();
    if (!$post_id || !current_user_can('edit_post', $post_id)) return;

    $active = \WorkerNu\InlineEditor\Mode\is_active($post_id);

    $bar->add_node([
        'id'    => 'wn-inline-edit-toggle',
        'title' => $active ? __('Exit Edit Text', 'workernu-inline-editor') : __('Edit Text', 'workernu-inline-editor'),
        'href'  => \WorkerNu\InlineEditor\Mode\toggle_url(!$active),
    ]);

    if (!$active) return;

    $pending = \WorkerNu\InlineEditor\Draft\has_pending_changes($post_id);

    $bar->add_node([
        'id'     => 'wn-inline-publish',
        'parent' => 'wn-inline-edit-toggle',
        'title'  => $pending ? __('Publish changes', 'workernu-inline-editor') : __('No changes to publish', 'workernu-inline-editor'),
        'href'   => '#',
        'meta'   => [
            'class' => 'wn-admin-bar-publish' . ($pending ? '' : ' is-disabled'),
        ],
    ]);
}
```

- [ ] **Step 2: Write `includes/assets.php`**

```php
<?php
namespace WorkerNu\InlineEditor\Assets;

if (!defined('ABSPATH')) exit;

function enqueue(): void {
    if (!is_singular()) return;

    $post_id = get_queried_object_id();
    if (!$post_id || !\WorkerNu\InlineEditor\Mode\is_active($post_id)) return;

    wp_enqueue_style(
        'workernu-inline-editor',
        WORKERNU_INLINE_EDITOR_URL . 'assets/editor.css',
        ['workernu-main'],
        WORKERNU_INLINE_EDITOR_VERSION
    );

    wp_enqueue_script(
        'workernu-inline-editor',
        WORKERNU_INLINE_EDITOR_URL . 'assets/editor.js',
        [],
        WORKERNU_INLINE_EDITOR_VERSION,
        true
    );

    wp_localize_script('workernu-inline-editor', 'wnInlineEditor', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce(\WorkerNu\InlineEditor\Ajax\NONCE_ACTION),
        'postId'  => $post_id,
        'i18n'    => [
            'save'    => __('Save Draft', 'workernu-inline-editor'),
            'publish' => __('Publish', 'workernu-inline-editor'),
            'cancel'  => __('Cancel', 'workernu-inline-editor'),
            'saving'  => __('Saving…', 'workernu-inline-editor'),
            'saved'   => __('Draft saved', 'workernu-inline-editor'),
            'error'   => __('Something went wrong.', 'workernu-inline-editor'),
        ],
    ]);
}
```

- [ ] **Step 3: Write `assets/editor.css`**

```css
/* workernu Inline Editor — hover outline, pencil, inline edit UI. Only
   loaded when body.wn-edit-mode is present (see Assets\enqueue gating). */

.wn-editable {
    position: relative;
    display: inline;
    outline: 1px dashed transparent;
    outline-offset: 3px;
    border-radius: 3px;
    transition: outline-color 0.12s ease;
}
.wn-editable:hover {
    outline-color: #2563eb;
}
.wn-editable__pencil {
    display: none;
    position: absolute;
    top: -0.75rem;
    right: -0.75rem;
    width: 1.5rem;
    height: 1.5rem;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 0.6875rem;
    cursor: pointer;
    z-index: 2;
}
.wn-editable:hover .wn-editable__pencil {
    display: inline-flex;
}
.wn-editable.is-editing {
    outline-color: #2563eb;
}

.wn-editable__input {
    display: block;
    width: 100%;
    min-width: 12ch;
    font: inherit;
    color: inherit;
    background: #fff;
    border: 1px solid #2563eb;
    border-radius: 3px;
    padding: 0.15em 0.3em;
    box-sizing: border-box;
}
textarea.wn-editable__input {
    min-height: 4.5em;
    resize: vertical;
}

.wn-editable__toolbar {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.35rem;
}
.wn-editable__toolbar button {
    font-size: 0.75rem;
    line-height: 1;
    padding: 0.4em 0.75em;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    background: #fff;
    cursor: pointer;
}
.wn-editable__toolbar button[data-wn-action="publish"] {
    background: #16a34a;
    border-color: #16a34a;
    color: #fff;
}
.wn-editable__toolbar button[data-wn-action="save"] {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.wn-editable__status {
    font-size: 0.75rem;
    color: #6b7280;
    margin-left: 0.25rem;
}

#wpadminbar .wn-admin-bar-publish.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}
```

- [ ] **Step 4: Write `assets/editor.js`**

```js
(function () {
    'use strict';

    if (typeof wnInlineEditor === 'undefined') return;

    var cfg = wnInlineEditor;

    document.addEventListener('click', function (e) {
        var pencil = e.target.closest('.wn-editable__pencil');
        if (pencil) {
            e.preventDefault();
            startEdit(pencil.closest('.wn-editable'));
            return;
        }

        var publishNode = e.target.closest('.wn-admin-bar-publish');
        if (publishNode && !publishNode.classList.contains('is-disabled')) {
            e.preventDefault();
            publishAll();
        }
    });

    function startEdit(wrapper) {
        if (!wrapper || wrapper.classList.contains('is-editing')) return;

        var content = wrapper.querySelector('.wn-editable__content');
        var type    = wrapper.dataset.wnType;
        var raw     = wrapper.dataset.wnRaw || '';
        var isMulti = type !== 'text';

        var input = document.createElement(isMulti ? 'textarea' : 'input');
        input.className = 'wn-editable__input';
        if (!isMulti) input.type = 'text';
        input.value = raw;

        var toolbar = document.createElement('div');
        toolbar.className = 'wn-editable__toolbar';
        toolbar.innerHTML =
            '<button type="button" data-wn-action="save">' + cfg.i18n.save + '</button>' +
            '<button type="button" data-wn-action="publish">' + cfg.i18n.publish + '</button>' +
            '<button type="button" data-wn-action="cancel">' + cfg.i18n.cancel + '</button>' +
            '<span class="wn-editable__status"></span>';

        wrapper.classList.add('is-editing');
        content.style.display = 'none';
        wrapper.appendChild(input);
        wrapper.appendChild(toolbar);
        input.focus();

        toolbar.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-wn-action]');
            if (!btn) return;
            var action = btn.dataset.wnAction;

            if (action === 'cancel') {
                endEdit(wrapper, input, toolbar, content);
                return;
            }
            if (action === 'save') {
                saveDraft(wrapper, input.value, toolbar.querySelector('.wn-editable__status'));
                return;
            }
            if (action === 'publish') {
                saveDraft(wrapper, input.value, toolbar.querySelector('.wn-editable__status'), function () {
                    publishAll();
                });
            }
        });
    }

    function endEdit(wrapper, input, toolbar, content) {
        wrapper.classList.remove('is-editing');
        input.remove();
        toolbar.remove();
        content.style.display = '';
    }

    function saveDraft(wrapper, value, statusEl, onDone) {
        var field = wrapper.dataset.wnField; // "<section_id>::<field_path>"
        var sep   = field.indexOf('::');
        var sectionId = field.slice(0, sep);
        var fieldPath = field.slice(sep + 2);

        if (statusEl) statusEl.textContent = cfg.i18n.saving;

        var body = new URLSearchParams();
        body.set('action', 'workernu_inline_save_draft');
        body.set('nonce', cfg.nonce);
        body.set('post_id', cfg.postId);
        body.set('section_id', sectionId);
        body.set('field_path', fieldPath);
        body.set('value', value);

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    if (statusEl) statusEl.textContent = (res.data && res.data.message) || cfg.i18n.error;
                    return;
                }
                wrapper.dataset.wnRaw = value;
                wrapper.querySelector('.wn-editable__content').textContent = value;
                if (statusEl) statusEl.textContent = cfg.i18n.saved;
                refreshPublishNode(res.data.has_pending);
                if (onDone) onDone();
            })
            .catch(function () {
                if (statusEl) statusEl.textContent = cfg.i18n.error;
            });
    }

    function publishAll() {
        var body = new URLSearchParams();
        body.set('action', 'workernu_inline_publish');
        body.set('nonce', cfg.nonce);
        body.set('post_id', cfg.postId);

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) refreshPublishNode(false);
            });
    }

    function refreshPublishNode(hasPending) {
        var node = document.querySelector('.wn-admin-bar-publish');
        if (!node) return;
        node.classList.toggle('is-disabled', !hasPending);
        node.textContent = hasPending ? cfg.i18n.publish : cfg.i18n.saved;
    }
})();
```

*(Note: `.wn-editable__content` renders via `textContent` in `saveDraft()` for plain `text` fields, matching the "no RTE" scope — a multi-line `rich_text`/`textarea` field's on-page HTML won't reflect list formatting until the page is reloaded, which is acceptable for a draft-preview workflow.)*

- [ ] **Step 5: Register admin-bar and assets hooks in the bootstrap file**

Add to `workernu-inline-editor.php`:

```php
add_action('admin_bar_menu',      '\\WorkerNu\\InlineEditor\\AdminBar\\register', 100);
add_action('wp_enqueue_scripts',  '\\WorkerNu\\InlineEditor\\Assets\\enqueue', 20);
```

- [ ] **Step 6: Syntax-check the PHP files**

Run: `php -l "plugins/workernu-inline-editor/includes/admin-bar.php" && php -l "plugins/workernu-inline-editor/includes/assets.php" && php -l "plugins/workernu-inline-editor/workernu-inline-editor.php"`
Expected: `No syntax errors detected` for all three.

- [ ] **Step 7: Manual verify — end-to-end on a throwaway field**

Reuse the temporary `footer.php` line from Task 5 Step 4 (`echo workernu_inline_editable(['_id' => 'x'], 'heading', 'text', 'Hello', 'Hello');`) — this instance won't actually persist anywhere real since section `x` doesn't exist, but it proves the UI wiring: with the edit-mode cookie set, load the page, hover the "Hello" text (outline + pencil appear), click the pencil (turns into a text input + toolbar), click **Save Draft** (expect the status text to read the error message, since section `x` isn't real — confirms the request reaches the server and `save_field()` correctly returns false rather than fataling). Remove the temporary `footer.php` line afterward — Tasks 7–8 wire up real fields next.

- [ ] **Step 8: Commit**

```bash
cd "public/wp-content"
git add plugins/workernu-inline-editor/includes/admin-bar.php plugins/workernu-inline-editor/includes/assets.php plugins/workernu-inline-editor/assets/editor.css plugins/workernu-inline-editor/assets/editor.js plugins/workernu-inline-editor/workernu-inline-editor.php
git commit -m "inline-editor: add admin bar toggle/publish node + editor JS/CSS"
```

---

### Task 7: Wire the hero section's text fields

**Files:**
- Modify: `themes/workernu/sections/hero/template.php`

**Interfaces:**
- Consumes: `workernu_inline_editable()` from Task 5.

In-scope fields (all `text`/`rich_text` fields the section has — see `hero/section.php`): `eyebrow_label`, `heading`, `body` (rich_text), `ctas[].label`, `ctas[].url`, `trustpilot_review_url`, `users_badge_label`, `users_count_number`, `users_count_label`.

- [ ] **Step 1: Wrap `eyebrow_label`**

Change (around line 80–82):
```php
<?php if ($eyebrow_label !== ''): ?>
    <span class="section--hero__eyebrow-label"><?php echo wp_kses_post($eyebrow_label); ?></span>
<?php endif; ?>
```
to:
```php
<?php if ($eyebrow_label !== ''): ?>
    <span class="section--hero__eyebrow-label"><?php echo workernu_inline_editable($data, 'eyebrow_label', 'text', wp_kses_post($eyebrow_label), $eyebrow_label); ?></span>
<?php endif; ?>
```

- [ ] **Step 2: Wrap `heading`**

Change:
```php
<h1 class="section--hero__heading" data-animate-item="heading"><?php echo wp_kses_post($heading); ?></h1>
```
to:
```php
<h1 class="section--hero__heading" data-animate-item="heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h1>
```

- [ ] **Step 3: Wrap `body` (rich_text)**

Add a raw-value variable right after the existing `$body_html` line:
```php
<?php $body_html = workernu_text($data['body'] ?? null, 'section--hero__body'); ?>
<?php $body_raw  = workernu_t($data['body']['value'] ?? ''); ?>
<?php if ($body_html !== ''): ?>
    <div class="section--hero__body-wrap" data-animate-item="body"><?php echo workernu_inline_editable($data, 'body', 'rich_text', $body_html, $body_raw, 'div'); ?></div>
<?php endif; ?>
```

- [ ] **Step 4: Wrap `ctas[].label` and `ctas[].url`**

Change the CTA loop body:
```php
<?php foreach ($ctas as $cta_i => $cta):
    $cta_label   = workernu_t($cta['label'] ?? '');
    $cta_url     = (string) ($cta['url']     ?? '');
    $cta_variant = (string) ($cta['variant'] ?? 'primary');
    $cta_target  = (string) ($cta['target']  ?? '_self');
    if ($cta_label === '' || $cta_url === '') continue;
    ?>
    <a class="btn btn--<?php echo esc_attr($cta_variant); ?>"
       href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>"
       target="<?php echo esc_attr($cta_target); ?>"
       <?php echo $cta_target === '_blank' ? 'rel="noopener"' : ''; ?>>
        <?php echo workernu_inline_editable($data, "ctas.$cta_i.label", 'text', wp_kses_post($cta_label), $cta_label); ?>
    </a>
<?php endforeach; ?>
```
(Note: `foreach ($ctas as $cta):` becomes `foreach ($ctas as $cta_i => $cta):` — only the loop variable line changes besides the wrap itself. `ctas[].url` is intentionally NOT wrapped here — it's inside the `href` attribute, not renderable page text, so there's no display element to attach the hover/pencil UI to; editing it inline isn't meaningful the way editing visible copy is. This narrows the earlier "every text-typed field" framing to fields that actually render as visible text — documented here rather than silently applied.)

- [ ] **Step 5: Wrap `users_badge_label`, `users_count_number`, `users_count_label`**

Change:
```php
<?php if ($badge_5th !== ''): ?>
    <span class="section--hero__avatar section--hero__avatar--badge"><?php echo wp_kses_post($badge_5th); ?></span>
<?php endif; ?>
```
to:
```php
<?php if ($badge_5th !== ''): ?>
    <span class="section--hero__avatar section--hero__avatar--badge"><?php echo workernu_inline_editable($data, 'users_badge_label', 'text', wp_kses_post($badge_5th), $badge_5th); ?></span>
<?php endif; ?>
```
and:
```php
<?php if ($users_num !== ''): ?>
    <span class="section--hero__users-number"><?php echo wp_kses_post($users_num); ?></span>
<?php endif; ?>
<?php if ($users_lbl !== ''): ?>
    <span class="section--hero__users-label"><?php echo wp_kses_post($users_lbl); ?></span>
<?php endif; ?>
```
to:
```php
<?php if ($users_num !== ''): ?>
    <span class="section--hero__users-number"><?php echo workernu_inline_editable($data, 'users_count_number', 'text', wp_kses_post($users_num), $users_num); ?></span>
<?php endif; ?>
<?php if ($users_lbl !== ''): ?>
    <span class="section--hero__users-label"><?php echo workernu_inline_editable($data, 'users_count_label', 'text', wp_kses_post($users_lbl), $users_lbl); ?></span>
<?php endif; ?>
```

*(`trustpilot_review_url` is skipped for the same reason as `ctas[].url` above — it's an `href`, not visible text.)*

- [ ] **Step 6: Syntax-check**

Run: `php -l "themes/workernu/sections/hero/template.php"`
Expected: `No syntax errors detected`.

- [ ] **Step 7: Manual verify — happy path on the real hero**

On a page with a hero section, as an admin with edit mode on: hover the heading (outline+pencil), click, edit the text, click **Save Draft** (status → "Draft saved", admin bar's "Publish changes" node stops saying "No changes to publish"). Reload the page: the edited heading is still shown (draft persists across reloads for you). Log out / open a private window and load the same page: the ORIGINAL heading shows (public visitors unaffected). Back as the admin, click **Publish** in the admin bar: reload in the private window — the new heading now shows there too. Repeat spot-checks for one CTA label and the body paragraph.

- [ ] **Step 8: Commit**

```bash
cd "public/wp-content"
git add themes/workernu/sections/hero/template.php
git commit -m "inline-editor: wire hero section's text fields into the inline editor"
```

---

### Task 8: Wire the feature-highlight section's text fields

**Files:**
- Modify: `themes/workernu/sections/feature-highlight/template.php`

**Interfaces:**
- Consumes: `workernu_inline_editable()` from Task 5.

In-scope fields (from `feature-highlight/section.php`): `eyebrow`, `heading`, `body` (**`textarea`**, not `rich_text` — stored as a plain translatable string, unlike hero's `body`), `ctas[].label`, `items[].title`, `items[].description`, `items[].icon_image_alt`.

- [ ] **Step 1: Wrap `eyebrow`, `heading`, `body`**

Change:
```php
<?php if ($eyebrow !== ''): ?>
    <span class="section--feature-highlight__eyebrow"><?php echo wp_kses_post($eyebrow); ?></span>
<?php endif; ?>
<?php if ($heading !== ''): ?>
    <h2 class="section--feature-highlight__heading"><?php echo wp_kses_post($heading); ?></h2>
<?php endif; ?>
<?php if ($body !== ''): ?>
    <p class="section--feature-highlight__body"><?php echo nl2br(wp_kses_post($body)); ?></p>
<?php endif; ?>
```
to:
```php
<?php if ($eyebrow !== ''): ?>
    <span class="section--feature-highlight__eyebrow"><?php echo workernu_inline_editable($data, 'eyebrow', 'text', wp_kses_post($eyebrow), $eyebrow); ?></span>
<?php endif; ?>
<?php if ($heading !== ''): ?>
    <h2 class="section--feature-highlight__heading"><?php echo workernu_inline_editable($data, 'heading', 'text', wp_kses_post($heading), $heading); ?></h2>
<?php endif; ?>
<?php if ($body !== ''): ?>
    <p class="section--feature-highlight__body"><?php echo workernu_inline_editable($data, 'body', 'textarea', nl2br(wp_kses_post($body)), $body); ?></p>
<?php endif; ?>
```

- [ ] **Step 2: Wrap `ctas[].label`**

Change the CTA loop:
```php
<?php foreach ($ctas as $cta):
    $cta_label   = workernu_t($cta['label'] ?? '');
    $cta_url     = (string) ($cta['url']     ?? '');
    $cta_variant = (string) ($cta['variant'] ?? 'primary');
    $cta_target  = (string) ($cta['target']  ?? '_self');
    if ($cta_label === '' || $cta_url === '') continue;
    ?>
    <a class="btn btn--<?php echo esc_attr($cta_variant); ?>"
       href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>"
       target="<?php echo esc_attr($cta_target); ?>"
       <?php echo $cta_target === '_blank' ? 'rel="noopener"' : ''; ?>>
        <?php echo wp_kses_post($cta_label); ?>
    </a>
<?php endforeach; ?>
```
to:
```php
<?php foreach ($ctas as $cta_i => $cta):
    $cta_label   = workernu_t($cta['label'] ?? '');
    $cta_url     = (string) ($cta['url']     ?? '');
    $cta_variant = (string) ($cta['variant'] ?? 'primary');
    $cta_target  = (string) ($cta['target']  ?? '_self');
    if ($cta_label === '' || $cta_url === '') continue;
    ?>
    <a class="btn btn--<?php echo esc_attr($cta_variant); ?>"
       href="<?php echo esc_url(workernu_localize_url($cta_url)); ?>"
       target="<?php echo esc_attr($cta_target); ?>"
       <?php echo $cta_target === '_blank' ? 'rel="noopener"' : ''; ?>>
        <?php echo workernu_inline_editable($data, "ctas.$cta_i.label", 'text', wp_kses_post($cta_label), $cta_label); ?>
    </a>
<?php endforeach; ?>
```

- [ ] **Step 3: Wrap `items[].title` and `items[].description`**

Change the items loop:
```php
<?php foreach ($items as $item):
    $icon             = (string) ($item['icon'] ?? '');
    $icon_image_value = $item['icon_image'] ?? 0;
    $icon_image       = workernu_image_url($icon_image_value, 'medium');
    $icon_alt         = workernu_t($item['icon_image_alt'] ?? '');
    if ($icon_alt === '') $icon_alt = workernu_image_alt($icon_image_value);
    $title            = workernu_t($item['title']       ?? '');
    $description      = workernu_t($item['description'] ?? '');
    if ($title === '' && $description === '') continue;
    $has_visual       = $icon_image !== '' || $icon !== '';
    ?>
```
to:
```php
<?php foreach ($items as $item_i => $item):
    $icon             = (string) ($item['icon'] ?? '');
    $icon_image_value = $item['icon_image'] ?? 0;
    $icon_image       = workernu_image_url($icon_image_value, 'medium');
    $icon_alt         = workernu_t($item['icon_image_alt'] ?? '');
    if ($icon_alt === '') $icon_alt = workernu_image_alt($icon_image_value);
    $title            = workernu_t($item['title']       ?? '');
    $description      = workernu_t($item['description'] ?? '');
    if ($title === '' && $description === '') continue;
    $has_visual       = $icon_image !== '' || $icon !== '';
    ?>
```
and change:
```php
<?php if ($title !== ''): ?>
    <h3 class="section--feature-highlight__item-title"><?php echo wp_kses_post($title); ?></h3>
<?php endif; ?>
```
to:
```php
<?php if ($title !== ''): ?>
    <h3 class="section--feature-highlight__item-title"><?php echo workernu_inline_editable($data, "items.$item_i.title", 'text', wp_kses_post($title), $title); ?></h3>
<?php endif; ?>
```
and change:
```php
<?php if ($description !== ''): ?>
    <div class="section--feature-highlight__item-text">
        <p class="section--feature-highlight__item-desc"><?php echo nl2br(wp_kses_post($description)); ?></p>
    </div>
<?php endif; ?>
```
to:
```php
<?php if ($description !== ''): ?>
    <div class="section--feature-highlight__item-text">
        <p class="section--feature-highlight__item-desc"><?php echo workernu_inline_editable($data, "items.$item_i.description", 'textarea', nl2br(wp_kses_post($description)), $description); ?></p>
    </div>
<?php endif; ?>
```

*(`items[].icon_image_alt` is intentionally skipped — it's an image `alt` attribute, not visible page text, same reasoning as the URL fields skipped in Task 7.)*

- [ ] **Step 4: Syntax-check**

Run: `php -l "themes/workernu/sections/feature-highlight/template.php"`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Manual verify — happy path**

On a page with a feature-highlight section, as an admin with edit mode on: edit the heading and one value-prop's title via Save Draft, confirm they persist across reload for you, confirm a logged-out/private view still shows the old text, then Publish and confirm the private view updates too.

- [ ] **Step 6: Commit**

```bash
cd "public/wp-content"
git add themes/workernu/sections/feature-highlight/template.php
git commit -m "inline-editor: wire feature-highlight section's text fields into the inline editor"
```

---

### Task 9: Deploy to Hostinger stage

**Files:** none (deployment only).

- [ ] **Step 1: Push the feature branch**

```bash
cd "public/wp-content"
git push origin dev
```
Expected: only the 9 commits from Tasks 1–8 (plus the earlier DESIGN.md/PLAN.md commits) go up — confirm with `git log origin/dev..dev` returning empty afterward, and separately confirm `git status` still shows the same 33 pre-existing unrelated modified files as untouched/unstaged (they were never `git add`-ed by any task above).

- [ ] **Step 2: rsync the new/changed paths to the Hostinger stage host**

```bash
cd "public/wp-content"
rsync -avz plugins/workernu-inline-editor/ hostinger-mindaugas:/home/u607277601/domains/workernu.com/public_html/wp-content/plugins/workernu-inline-editor/
rsync -avz themes/workernu/sections/hero/template.php hostinger-mindaugas:/home/u607277601/domains/workernu.com/public_html/wp-content/themes/workernu/sections/hero/template.php
rsync -avz themes/workernu/sections/feature-highlight/template.php hostinger-mindaugas:/home/u607277601/domains/workernu.com/public_html/wp-content/themes/workernu/sections/feature-highlight/template.php
```

- [ ] **Step 3: Activate the plugin on stage**

Log into the stage site's `/wp-admin/plugins.php`, activate "workernu Inline Editor". Expected: no error notices (Sections + Lang are already active there).

- [ ] **Step 4: Smoke-test on stage**

Repeat Task 7 Step 7's manual check (edit a heading, Save Draft, confirm a private/incognito view is unaffected, Publish, confirm the private view updates) directly on the stage domain.

---

## Self-Review Notes

- **Spec coverage:** every DESIGN.md section maps to a task — data model → Task 2, draft visibility/rendering → Task 3, editing UI → Task 6, markup/template integration → Tasks 5/7/8, AJAX endpoints → Task 4, deployment → Task 9.
- **Scope correction from DESIGN.md:** DESIGN.md said "the `body` rich_text field" for both sections — feature-highlight's `body` is actually schema type `textarea` (confirmed against `feature-highlight/section.php`), not `rich_text`. Task 8 uses `'textarea'` as the field type accordingly; the merge/sanitize logic in Task 2 already handles both identically (only `rich_text` gets the `{value, display}` treatment).
- **Scope narrowing from DESIGN.md:** "all their text fields" is narrowed to *fields that render as visible page text* — `ctas[].url`, `trustpilot_review_url`, and `items[].icon_image_alt` are `text`-typed but render only inside `href`/`alt` attributes, not as visible copy, so there's no on-page element to attach hover/pencil UI to. Documented in Tasks 7–8 rather than silently dropped.
- **Type consistency check:** `workernu_inline_editable()`'s signature (`$data, $field_path, $type, $rendered_html, $raw_value, $wrapper`) is identical everywhere it's called across Tasks 5, 7, 8. `Draft\save_field()`'s `$field_path` format (`"name"` or `"repeater.index.subname"`) matches what `Markup\field()` emits into `data-wn-field` (`"section_id::field_path"`) and what `editor.js`'s `saveDraft()` parses back out.
