<?php
namespace WorkerNu\Sections\Schema;

use function WorkerNu\Sections\Registry\get as get_section;

if (!defined('ABSPATH')) exit;

/**
 * Hooks into workernu-seo's `workernu_seo_json_ld_graph` filter.
 * Walks every section on the post; if a section's section.php declared a `schema` callback,
 * call it with the section's data and append the result to the @graph.
 */
function contribute_section_schemas(array $graph, int $post_id): array {
    if (!$post_id) return $graph;

    $sections = get_post_meta($post_id, WORKERNU_SECTIONS_META_KEY, true);
    if (!is_array($sections)) return $graph;

    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        $type = $section['_type'] ?? '';
        $def  = get_section($type);
        if (!$def || empty($def['schema']) || !is_callable($def['schema'])) continue;

        $entry = call_user_func($def['schema'], $section);
        if (!$entry) continue;

        // Allow a section to return either a single entry or an array of entries.
        if (isset($entry['@type'])) {
            $graph[] = $entry;
        } elseif (is_array($entry)) {
            foreach ($entry as $sub) {
                if (is_array($sub) && isset($sub['@type'])) $graph[] = $sub;
            }
        }
    }

    return $graph;
}
