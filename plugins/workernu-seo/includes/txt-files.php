<?php
namespace WorkerNu\SEO\TxtFiles;

use WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

const LLMS_QUERY_VAR = 'workernu_llms';

/**
 * Filter WordPress's default robots.txt output. If admin has saved custom content, use it verbatim.
 */
function robots_txt(string $output, $public): string {
    $custom = (string) get_option(Options\OPT_ROBOTS, '');
    return $custom !== '' ? $custom : $output;
}

/**
 * Map /llms.txt to a virtual route handled by serve_llms() below.
 */
function add_rewrite(): void {
    add_rewrite_rule('^llms\.txt$', 'index.php?' . LLMS_QUERY_VAR . '=1', 'top');
}

function add_query_var(array $vars): array {
    $vars[] = LLMS_QUERY_VAR;
    return $vars;
}

function serve_llms(): void {
    if (!get_query_var(LLMS_QUERY_VAR)) return;

    $content = (string) get_option(Options\OPT_LLMS, Options\default_llms_content());

    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    echo $content;
    exit;
}
