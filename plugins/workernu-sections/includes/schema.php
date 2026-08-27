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

    // First pass: collect each section's contribution.
    $contributions = [];
    foreach ($sections as $section) {
        if (!is_array($section)) continue;
        $type = $section['_type'] ?? '';
        $def  = get_section($type);
        if (!$def || empty($def['schema']) || !is_callable($def['schema'])) continue;

        $resolved = \WorkerNu\Sections\Defaults\resolve($section);
        $entry    = call_user_func($def['schema'], $resolved);
        if (!$entry) continue;

        // Normalise to a list of entities.
        $list = isset($entry['@type']) ? [$entry] : (is_array($entry) ? $entry : []);
        foreach ($list as $sub) {
            if (is_array($sub) && isset($sub['@type'])) $contributions[] = $sub;
        }
    }

    // Detect which entities are actually declared in this graph + the
    // contributions (so dependent fragments can decide whether to emit).
    $declared_ids = [];
    foreach (array_merge($graph, $contributions) as $entity) {
        if (!empty($entity['@id'])) $declared_ids[(string) $entity['@id']] = true;
    }

    // Second pass: reconcile references.
    //   - Offer → itemOffered → SoftwareApplication. If SoftwareApplication
    //     isn't declared, drop the Offer entirely (a price without a product
    //     is meaningless).
    //   - Review / AggregateRating → itemReviewed. If the referenced entity
    //     isn't declared, fall back to the Organization (which is sitewide)
    //     so the Review still emits as a valid testimonial about the company.
    $org_id = home_url('/#organization');
    $kept = [];
    foreach ($contributions as $entity) {
        // Offers — strict: drop if the product isn't on the page.
        if (isset($entity['itemOffered']['@id']) && empty($declared_ids[(string) $entity['itemOffered']['@id']])) {
            continue;
        }
        // Reviews + AggregateRating — graceful: rewrite itemReviewed to point
        // at the Organization when the original target isn't declared.
        if (isset($entity['itemReviewed']['@id']) && empty($declared_ids[(string) $entity['itemReviewed']['@id']])) {
            if (!empty($declared_ids[$org_id])) {
                $entity['itemReviewed'] = ['@id' => $org_id];
            } else {
                continue; // No fallback target either, drop.
            }
        }
        $kept[] = $entity;
    }

    // Third pass — structural back-references. Google's validators expect
    // `offers` and `aggregateRating` as NAMED properties of the parent entity,
    // not just as separate nodes that point back via itemOffered/itemReviewed.
    // We satisfy both patterns: keep the separate nodes (Google still uses
    // them for indexing) AND inject @id references onto the parent so the
    // named-property check passes.
    //
    //   - SoftwareApplication present → offers + aggregateRating attached to it.
    //   - No SoftwareApplication but Organization present → aggregateRating
    //     attached to Organization (so the company knowledge panel can show
    //     stars on pages without a product presentation).
    $merged = array_merge($graph, $kept);
    $sa_index  = null;
    $org_index = null;
    foreach ($merged as $idx => $entity) {
        $type = $entity['@type'] ?? '';
        if ($type === 'SoftwareApplication' && $sa_index === null)  $sa_index  = $idx;
        if ($type === 'Organization'        && $org_index === null) $org_index = $idx;
    }

    $offer_refs    = [];
    $aggregate_ref = null;
    foreach ($kept as $entity) {
        if (($entity['@type'] ?? '') === 'Offer' && !empty($entity['@id'])) {
            $offer_refs[] = ['@id' => (string) $entity['@id']];
        } elseif (($entity['@type'] ?? '') === 'AggregateRating' && !empty($entity['@id'])) {
            $aggregate_ref = ['@id' => (string) $entity['@id']];
        }
    }

    if ($sa_index !== null) {
        if ($offer_refs)    $merged[$sa_index]['offers']          = $offer_refs;
        if ($aggregate_ref) $merged[$sa_index]['aggregateRating'] = $aggregate_ref;
    } elseif ($org_index !== null && $aggregate_ref) {
        // Fallback: no product on the page → put the stars on the Organization.
        $merged[$org_index]['aggregateRating'] = $aggregate_ref;
    }

    return $merged;
}
