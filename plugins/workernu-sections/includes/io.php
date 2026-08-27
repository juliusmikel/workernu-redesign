<?php
namespace WorkerNu\Sections\IO;

use WorkerNu\Sections\Fields;
use function WorkerNu\Sections\Registry\get as get_section;

if (!defined('ABSPATH')) exit;

const EXPORT_ACTION = 'workernu_sections_export';
const IMPORT_ACTION = 'workernu_sections_import';
const FORMAT        = 'workernu/sections-export';
const FORMAT_VERSION = 1;

function page_theme_meta_key(): string {
    return defined('WORKERNU_PAGE_THEME_META_KEY') ? WORKERNU_PAGE_THEME_META_KEY : '_workernu_page_theme';
}

/* ─────────────────────────────────────────────────────────────────
   Image-field walking — locate every image attachment ID inside a
   section, using its schema (top-level image fields + repeater
   sub-fields of type image). Shared by export (collect) and import (remap).
   ───────────────────────────────────────────────────────────────── */

function map_section_images(array $section, callable $fn): array {
    $def = get_section((string) ($section['_type'] ?? ''));
    if (!$def) return $section;

    foreach (($def['fields'] ?? []) as $field) {
        $name = $field['name'] ?? null;
        if (!$name) continue;
        $type = $field['type'] ?? '';

        if ($type === 'image') {
            if (isset($section[$name])) {
                $section[$name] = $fn((int) $section[$name]);
            }
        } elseif ($type === 'repeater') {
            $image_subs = [];
            foreach (($field['fields'] ?? []) as $sub) {
                if (($sub['type'] ?? '') === 'image' && !empty($sub['name'])) {
                    $image_subs[] = $sub['name'];
                }
            }
            if ($image_subs && isset($section[$name]) && is_array($section[$name])) {
                foreach ($section[$name] as $i => $item) {
                    if (!is_array($item)) continue;
                    foreach ($image_subs as $sn) {
                        if (isset($item[$sn])) {
                            $section[$name][$i][$sn] = $fn((int) $item[$sn]);
                        }
                    }
                }
            }
        }
    }
    return $section;
}

function collect_image_ids(array $sections): array {
    $ids = [];
    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        map_section_images($section, function (int $id) use (&$ids) {
            if ($id > 0) $ids[$id] = true;
            return $id;
        });
    }
    return array_keys($ids);
}

function build_assets_map(array $ids): array {
    $assets = [];
    foreach ($ids as $id) {
        $url = wp_get_attachment_url($id);
        if (!$url) continue;
        $file = get_attached_file($id);
        $assets[(string) $id] = [
            'url'      => $url,
            'alt'      => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
            'filename' => $file ? basename($file) : basename((string) wp_parse_url($url, PHP_URL_PATH)),
            'title'    => get_the_title($id),
            'mime'     => get_post_mime_type($id) ?: '',
        ];
    }
    return $assets;
}

/* ─────────────────────────────────────────────────────────────────
   EXPORT
   ───────────────────────────────────────────────────────────────── */

function build_payload(int $post_id): ?array {
    $post = get_post($post_id);
    if (!$post) return null;

    $sections = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    if (!is_array($sections)) $sections = [];

    return [
        '_format'     => FORMAT,
        '_version'    => FORMAT_VERSION,
        'exported_at' => gmdate('c'),
        'source'      => home_url(),
        'post'        => [
            'title'     => $post->post_title,
            'slug'      => $post->post_name,
            'status'    => $post->post_status,
            'post_type' => $post->post_type,
        ],
        'theme'    => (string) get_post_meta($post_id, page_theme_meta_key(), true),
        'sections' => array_values(array_filter($sections, 'is_array')),
        'assets'   => build_assets_map(collect_image_ids($sections)),
    ];
}

function handle_export(): void {
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_die(__('You are not allowed to export this content.', 'workernu-sections'));
    }
    check_admin_referer(EXPORT_ACTION . '_' . $post_id);

    $payload = build_payload($post_id);
    if (!$payload) wp_die(__('Nothing to export.', 'workernu-sections'));

    $post = get_post($post_id);
    $slug = $post->post_name ?: 'page';
    $filename = 'workernu-' . $slug . '-' . $post_id . '.json';

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ─────────────────────────────────────────────────────────────────
   IMPORT
   ───────────────────────────────────────────────────────────────── */

/**
 * Sideload one asset (by URL) into the media library. Returns the new
 * attachment ID, or 0 on failure. Caches per-URL within a single request.
 */
function sideload_asset(array $asset, array &$cache): int {
    $url = (string) ($asset['url'] ?? '');
    if ($url === '') return 0;
    if (isset($cache[$url])) return $cache[$url];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        $cache[$url] = 0;
        return 0;
    }

    $name = (string) ($asset['filename'] ?? '');
    if ($name === '') $name = basename((string) wp_parse_url($url, PHP_URL_PATH)) ?: 'image';

    $file_array = ['name' => $name, 'tmp_name' => $tmp];
    $id = media_handle_sideload($file_array, 0, (string) ($asset['title'] ?? ''));

    if (is_wp_error($id)) {
        if (file_exists($tmp)) @unlink($tmp);
        $cache[$url] = 0;
        return 0;
    }

    if (!empty($asset['alt'])) {
        update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field((string) $asset['alt']));
    }
    $cache[$url] = (int) $id;
    return (int) $id;
}

/**
 * Build an old-ID → new-ID map for all assets referenced in the payload.
 * Same-site imports keep existing IDs (no re-download) when the attachment
 * still exists; otherwise the asset is sideloaded.
 */
function resolve_assets(array $payload, bool $same_site, int &$failed): array {
    $assets = is_array($payload['assets'] ?? null) ? $payload['assets'] : [];
    $map    = [];
    $cache  = [];

    foreach ($assets as $old_id => $asset) {
        $old_id = (int) $old_id;
        if (!is_array($asset)) continue;

        if ($same_site && get_post($old_id) && wp_get_attachment_url($old_id)) {
            $map[$old_id] = $old_id;
            continue;
        }
        $new_id = sideload_asset($asset, $cache);
        if ($new_id > 0) {
            $map[$old_id] = $new_id;
        } else {
            $map[$old_id] = 0;
            $failed++;
        }
    }
    return $map;
}

/**
 * Re-sanitize an imported section through its schema's field sanitizers,
 * mirroring the save path so imported JSON can't store unexpected shapes.
 */
function sanitize_section(array $raw): ?array {
    $type = (string) ($raw['_type'] ?? '');
    $def  = get_section($type);
    if (!$def) return null;

    $section = [
        '_type' => $type,
        '_id'   => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($raw['_id'] ?? '')) ?: ($type . '-' . bin2hex(random_bytes(4))),
    ];
    foreach (($def['fields'] ?? []) as $field) {
        $name = $field['name'] ?? null;
        if (!$name) continue;
        $section[$name] = Fields\sanitize_value($field, $raw[$name] ?? null);
    }
    foreach (($def['modifiers'] ?? []) as $mod) {
        $name = $mod['name'] ?? null;
        if (!$name) continue;
        $section[$name] = Fields\sanitize_value($mod, $raw[$name] ?? ($mod['default'] ?? null));
    }
    return $section;
}

function handle_import(): void {
    if (!current_user_can('edit_pages') || !current_user_can('upload_files')) {
        wp_die(__('You are not allowed to import content.', 'workernu-sections'));
    }
    check_admin_referer(IMPORT_ACTION);

    $redirect = wp_get_referer() ?: admin_url('tools.php?page=workernu-sections-import');

    if (empty($_FILES['workernu_import_file']['tmp_name']) || !is_uploaded_file($_FILES['workernu_import_file']['tmp_name'])) {
        wp_safe_redirect(add_query_arg('workernu_import', 'nofile', $redirect));
        exit;
    }

    $json    = file_get_contents($_FILES['workernu_import_file']['tmp_name']);
    $payload = json_decode((string) $json, true);

    if (!is_array($payload) || ($payload['_format'] ?? '') !== FORMAT) {
        wp_safe_redirect(add_query_arg('workernu_import', 'badformat', $redirect));
        exit;
    }

    $sections_raw = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
    $same_site    = isset($payload['source']) && untrailingslashit((string) $payload['source']) === untrailingslashit(home_url());

    // 1. Resolve image attachments (sideload cross-site, reuse same-site).
    $failed   = 0;
    $id_map   = resolve_assets($payload, $same_site, $failed);

    // 2. Remap image IDs, then re-sanitize each section.
    $clean = [];
    foreach ($sections_raw as $section) {
        if (!is_array($section)) continue;
        if (!$same_site && $id_map) {
            $section = map_section_images($section, fn(int $id) => $id_map[$id] ?? 0);
        }
        $sanitized = sanitize_section($section);
        if ($sanitized) $clean[] = $sanitized;
    }

    // 3. Resolve the destination page — overwrite by slug if requested,
    //    otherwise create a new draft.
    $post_arr   = is_array($payload['post'] ?? null) ? $payload['post'] : [];
    $allowed    = apply_filters('workernu_sections_post_types', ['page']);
    $post_type  = in_array($post_arr['post_type'] ?? '', $allowed, true) ? $post_arr['post_type'] : 'page';
    $title      = sanitize_text_field((string) ($post_arr['title'] ?? __('Imported page', 'workernu-sections')));

    $target_slug = isset($_POST['workernu_target_slug'])
        ? sanitize_title((string) wp_unslash($_POST['workernu_target_slug']))
        : '';

    $overwrote = false;
    $target_id = 0;

    if ($target_slug !== '') {
        $existing = get_page_by_path($target_slug, OBJECT, $allowed);
        if ($existing && current_user_can('edit_post', $existing->ID)) {
            $target_id = (int) $existing->ID;
            $overwrote = true;
        }
    }

    if (!$target_id) {
        $insert = [
            'post_type'    => $post_type,
            'post_title'   => $title,
            'post_status'  => 'draft',
            'post_content' => '',
        ];
        if ($target_slug !== '') {
            $insert['post_name'] = $target_slug;
        }
        $new_id = wp_insert_post($insert, true);
        if (is_wp_error($new_id) || !$new_id) {
            wp_safe_redirect(add_query_arg('workernu_import', 'insertfail', $redirect));
            exit;
        }
        $target_id = (int) $new_id;
    }

    // 4. Persist sections + theme (overwrite both meta keys on the target).
    update_post_meta($target_id, WORKERNU_SECTIONS_META_KEY, $clean);

    $theme = sanitize_key((string) ($payload['theme'] ?? ''));
    if ($theme !== '') {
        update_post_meta($target_id, page_theme_meta_key(), $theme);
    } else {
        delete_post_meta($target_id, page_theme_meta_key());
    }

    $target = add_query_arg(
        [
            'workernu_imported'    => $overwrote ? 'overwrote' : 1,
            'workernu_import_warn' => $failed,
        ],
        admin_url('post.php?post=' . $target_id . '&action=edit')
    );
    wp_safe_redirect($target);
    exit;
}

/* ─────────────────────────────────────────────────────────────────
   ADMIN UI
   ───────────────────────────────────────────────────────────────── */

function export_url(int $post_id): string {
    return wp_nonce_url(
        admin_url('admin-post.php?action=' . EXPORT_ACTION . '&post=' . $post_id),
        EXPORT_ACTION . '_' . $post_id
    );
}

function register_meta_box(): void {
    $post_types = apply_filters('workernu_sections_post_types', ['page']);
    foreach ($post_types as $post_type) {
        add_meta_box(
            'workernu-sections-io',
            __('Export / Import', 'workernu-sections'),
            __NAMESPACE__ . '\\render_meta_box',
            $post_type,
            'side',
            'low'
        );
    }
}

function render_meta_box(\WP_Post $post): void {
    $is_new = $post->post_status === 'auto-draft';
    echo '<p style="margin-top:0;">';
    if ($is_new) {
        echo esc_html__('Save the page first, then you can export its sections as JSON.', 'workernu-sections');
    } else {
        echo '<a class="button button-secondary" href="' . esc_url(export_url($post->ID)) . '">'
           . esc_html__('Download JSON', 'workernu-sections') . '</a>';
    }
    echo '</p>';
    echo '<p class="description">'
       . esc_html__('Moves this page\'s sections, theme, and images to another site.', 'workernu-sections')
       . ' <a href="' . esc_url(admin_url('tools.php?page=workernu-sections-import')) . '">'
       . esc_html__('Import a page →', 'workernu-sections') . '</a></p>';
}

function register_admin_page(): void {
    add_management_page(
        __('Import WorkerNu Page', 'workernu-sections'),
        __('Import WorkerNu Page', 'workernu-sections'),
        'edit_pages',
        'workernu-sections-import',
        __NAMESPACE__ . '\\render_import_page'
    );
}

function render_import_page(): void {
    if (!current_user_can('edit_pages')) return;

    $status = isset($_GET['workernu_import']) ? sanitize_key((string) $_GET['workernu_import']) : '';
    $messages = [
        'nofile'     => __('No file was uploaded.', 'workernu-sections'),
        'badformat'  => __('That file is not a WorkerNu page export.', 'workernu-sections'),
        'insertfail' => __('Could not create the page. Check your permissions.', 'workernu-sections'),
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Import WorkerNu Page', 'workernu-sections'); ?></h1>
        <?php if ($status && isset($messages[$status])): ?>
            <div class="notice notice-error"><p><?php echo esc_html($messages[$status]); ?></p></div>
        <?php endif; ?>
        <p><?php esc_html_e('Upload a JSON file exported from a WorkerNu page. Images are copied into this site\'s media library.', 'workernu-sections'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo esc_attr(IMPORT_ACTION); ?>">
            <?php wp_nonce_field(IMPORT_ACTION); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="workernu_import_file"><?php esc_html_e('JSON file', 'workernu-sections'); ?></label></th>
                    <td>
                        <input type="file" id="workernu_import_file" name="workernu_import_file" accept="application/json,.json" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="workernu_target_slug"><?php esc_html_e('Target slug', 'workernu-sections'); ?></label></th>
                    <td>
                        <input type="text" id="workernu_target_slug" name="workernu_target_slug" class="regular-text" placeholder="<?php esc_attr_e('e.g. about-us', 'workernu-sections'); ?>">
                        <p class="description">
                            <?php esc_html_e('Optional. If a page with this slug already exists, its sections and theme will be overwritten. If no page matches (or the field is blank), a new draft is created — using this slug if provided.', 'workernu-sections'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary"><?php esc_html_e('Import', 'workernu-sections'); ?></button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Success / warning notice on the imported page's edit screen.
 */
function maybe_import_notice(): void {
    if (empty($_GET['workernu_imported'])) return;
    $mode = sanitize_key((string) $_GET['workernu_imported']);
    $warn = isset($_GET['workernu_import_warn']) ? (int) $_GET['workernu_import_warn'] : 0;
    echo '<div class="notice notice-success is-dismissible"><p>';
    if ($mode === 'overwrote') {
        echo esc_html__('Sections overwritten from the imported file. Review and update when ready.', 'workernu-sections');
    } else {
        echo esc_html__('Page imported. Review the sections below and publish when ready.', 'workernu-sections');
    }
    if ($warn > 0) {
        echo ' <strong>' . sprintf(
            esc_html__('%d image(s) could not be copied — re-add them manually.', 'workernu-sections'),
            $warn
        ) . '</strong>';
    }
    echo '</p></div>';
}
