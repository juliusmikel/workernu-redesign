<?php
namespace WorkerNu\SEO\Output;

use WorkerNu\SEO\Helpers;
use WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

/**
 * If the post has a custom SEO title, completely override the document title.
 * Otherwise leave WP's natural title chain alone.
 */
function override_title(string $title): string {
    if (!is_singular()) return $title;
    $post_id = get_queried_object_id();
    $stored  = get_post_meta($post_id, Options\META_TITLE, true);
    $custom  = function_exists('workernu_t') ? workernu_t($stored) : $stored;

    if (!$custom) return $title;

    $format    = Options\all()['title_format'];
    $site_name = get_bloginfo('name');
    return strtr($format, [
        '{title}'     => (string) $custom,
        '{site_name}' => $site_name,
    ]);
}

/**
 * Adjust the title parts (used when no custom override exists) to apply the site format.
 */
function filter_title_parts(array $parts): array {
    if (!empty($parts['title']) && isset($parts['site'])) {
        // WP joins parts with " - " by default; the format is applied via override_title for custom titles.
    }
    return $parts;
}

/**
 * Emit description, canonical, robots, OpenGraph, Twitter, and JSON-LD tags in <head>.
 */
function head_tags(): void {
    $post_id = is_singular() ? get_queried_object_id() : 0;
    $opts    = Options\all();
    $lang    = function_exists('workernu_lang') ? workernu_lang() : 'lt';

    $title       = Helpers\resolve_title($post_id);
    $description = Helpers\resolve_description($post_id);
    $og_image    = Helpers\resolve_og_image($post_id);
    $canonical   = Helpers\canonical_url();
    $noindex     = Helpers\is_noindex($post_id);

    echo "\n<!-- workernu SEO -->\n";

    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    if ($noindex) {
        echo '<meta name="robots" content="noindex,nofollow">' . "\n";
    }

    if ($canonical) {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    // OpenGraph
    echo '<meta property="og:type" content="' . ($post_id ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description) echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    if ($canonical)   echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($lang === 'lt' ? 'lt_LT' : 'en_US') . '">' . "\n";
    if ($og_image) echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";

    // Twitter
    echo '<meta name="twitter:card" content="' . ($og_image ? 'summary_large_image' : 'summary') . '">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description) echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    if ($og_image)    echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";

    // JSON-LD: Organization (site-wide) + WebPage (current page)
    $json_ld = build_json_ld($post_id, $title, $description, $canonical, $og_image, $opts);
    if ($json_ld) {
        echo '<script type="application/ld+json">' . wp_json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    echo "<!-- /workernu SEO -->\n";
}

function build_json_ld(int $post_id, string $title, string $description, string $canonical, string $og_image, array $opts): array {
    $graph = [];

    if ($opts['org_name']) {
        $org = [
            '@type' => 'Organization',
            '@id'   => home_url('/#organization'),
            'name'  => $opts['org_name'],
            'url'   => home_url('/'),
        ];
        if ($opts['org_logo']) {
            $org['logo'] = [
                '@type'    => 'ImageObject',
                'url'      => $opts['org_logo'],
            ];
        }
        if ($opts['org_social']) {
            $profiles = array_filter(array_map('trim', preg_split('/\s+/', $opts['org_social']) ?: []));
            if ($profiles) $org['sameAs'] = array_values($profiles);
        }
        $graph[] = $org;
    }

    if ($post_id) {
        $page = [
            '@type'       => 'WebPage',
            'name'        => $title,
            'url'         => $canonical ?: home_url('/'),
            'description' => $description,
        ];
        if ($og_image) {
            $page['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url'   => $og_image,
            ];
        }
        $graph[] = $page;
    }

    /**
     * Let other plugins (e.g. workernu-sections) contribute entries to the @graph.
     * Hook signature: function (array $graph, int $post_id): array
     */
    $graph = apply_filters('workernu_seo_json_ld_graph', $graph, $post_id);

    if (!$graph) return [];
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}
