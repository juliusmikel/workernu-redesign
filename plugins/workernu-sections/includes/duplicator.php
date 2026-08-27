<?php
/**
 * Post duplicator — adds a "Duplicate" row action to the Posts/Pages list
 * screen. Creates a draft copy of the source post, carrying over title,
 * content, taxonomies, featured image, and ALL post meta (most importantly
 * `_page_sections`, the SEO meta, and the IO settings).
 *
 * Why this lives here: section-based pages have a lot of meta state. Generic
 * WP duplicator plugins copy posts but miss the sections array, leaving the
 * duplicate visually empty. Bundling it next to the section data ensures
 * the copy is faithful.
 */
namespace WorkerNu\Sections\Duplicator;

if (!defined('ABSPATH')) exit;

const ACTION       = 'workernu_duplicate_post';
const NONCE_ACTION = 'workernu_duplicate_post';

/**
 * Inject a "Duplicate" link into the row actions on Posts and Pages screens.
 * Position: between Edit/Quick Edit and Trash, matching the muscle memory
 * users have from WP's own row actions.
 */
function add_row_action(array $actions, \WP_Post $post): array {
    if (!current_user_can('edit_post', $post->ID)) return $actions;
    if (!post_type_supported($post->post_type))   return $actions;
    if (in_array($post->post_status, ['trash', 'auto-draft'], true)) return $actions;

    $url = wp_nonce_url(
        add_query_arg([
            'action' => ACTION,
            'post'   => $post->ID,
        ], admin_url('admin.php')),
        NONCE_ACTION
    );

    $actions['workernu_duplicate'] = sprintf(
        '<a href="%s" title="%s" aria-label="%s">%s</a>',
        esc_url($url),
        esc_attr__('Duplicate this item as a draft', 'workernu-sections'),
        esc_attr(sprintf(__('Duplicate "%s"', 'workernu-sections'), wp_strip_all_tags(get_the_title($post)))),
        esc_html__('Duplicate', 'workernu-sections')
    );

    return $actions;
}

/**
 * Which post types can be duplicated. Filterable for callers who want to
 * limit or extend the set. Defaults to public post types that have an admin
 * UI — skips internal types like attachments and revisions.
 */
function post_type_supported(string $post_type): bool {
    $defaults = ['post', 'page'];
    $allowed  = apply_filters('workernu_duplicator_post_types', $defaults);
    return in_array($post_type, (array) $allowed, true);
}

/**
 * Handle the duplicate action. Triggered via admin.php?action=workernu_duplicate_post.
 * On success, redirects to the edit screen for the new draft.
 */
function handle(): void {
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
    if (!$post_id) wp_die(__('No post specified.', 'workernu-sections'));

    check_admin_referer(NONCE_ACTION);

    if (!current_user_can('edit_post', $post_id)) {
        wp_die(__('You do not have permission to duplicate this item.', 'workernu-sections'));
    }

    $original = get_post($post_id);
    if (!$original) wp_die(__('Source post not found.', 'workernu-sections'));
    if (!post_type_supported($original->post_type)) {
        wp_die(__('This post type cannot be duplicated.', 'workernu-sections'));
    }

    $new_id = duplicate_post($original);
    if (is_wp_error($new_id)) wp_die($new_id->get_error_message());

    // Land on the new post's edit screen so the user can finish what they came
    // for (edit title, swap sections, etc.) without an extra click.
    wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id));
    exit;
}

/**
 * Duplicate a post: title gets a "(Copy)" suffix, status becomes draft, all
 * meta + taxonomies are carried over. Returns the new post ID or WP_Error.
 */
function duplicate_post(\WP_Post $original) {
    $title = trim($original->post_title) !== ''
        ? $original->post_title . ' ' . __('(Copy)', 'workernu-sections')
        : __('(Copy)', 'workernu-sections');

    $new_post_data = [
        'post_title'     => $title,
        'post_content'   => $original->post_content,
        'post_excerpt'   => $original->post_excerpt,
        'post_status'    => 'draft',
        'post_type'      => $original->post_type,
        'post_author'    => get_current_user_id() ?: $original->post_author,
        'post_parent'    => $original->post_parent,
        'menu_order'     => $original->menu_order,
        'comment_status' => $original->comment_status,
        'ping_status'    => $original->ping_status,
        'post_password'  => $original->post_password,
        // Force a fresh slug — WP auto-derives "title-copy" from post_title.
        'post_name'      => '',
    ];

    $new_id = wp_insert_post(wp_slash($new_post_data), true);
    if (is_wp_error($new_id) || !$new_id) return $new_id ?: new \WP_Error('insert_failed', __('Could not create the duplicate.', 'workernu-sections'));

    copy_taxonomies($original->ID, $new_id, $original->post_type);
    copy_post_meta($original->ID, $new_id);

    /**
     * Fires after a successful duplication. Useful for callers that want to
     * scrub copy-specific state (e.g. clear analytics IDs) or log the event.
     *   do_action('workernu_post_duplicated', $new_id, $original);
     */
    do_action('workernu_post_duplicated', $new_id, $original);

    return $new_id;
}

/**
 * Copy every term assignment across every taxonomy that applies to this post type.
 */
function copy_taxonomies(int $from_id, int $to_id, string $post_type): void {
    $taxonomies = get_object_taxonomies($post_type);
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($from_id, $taxonomy, ['fields' => 'ids']);
        if (is_wp_error($terms) || !$terms) continue;
        wp_set_object_terms($to_id, array_map('intval', $terms), $taxonomy, false);
    }
}

/**
 * Copy every postmeta key from source to destination. Each value goes through
 * maybe_unserialize/wp_slash so arrays + multi-value keys (like multiple
 * `_edit_lock` entries) survive intact.
 *
 * Skips a small denylist of meta keys that should NOT travel:
 *   _edit_lock / _edit_last  → WP's editor lock state, per-user
 *   _wp_old_slug             → redirect history for the original slug
 */
function copy_post_meta(int $from_id, int $to_id): void {
    $skip = apply_filters('workernu_duplicator_skip_meta_keys', [
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_old_date',
    ]);

    $all_meta = get_post_meta($from_id);
    if (!is_array($all_meta)) return;

    foreach ($all_meta as $key => $values) {
        if (in_array($key, $skip, true)) continue;
        foreach ((array) $values as $value) {
            add_post_meta($to_id, $key, wp_slash(maybe_unserialize($value)));
        }
    }
}
