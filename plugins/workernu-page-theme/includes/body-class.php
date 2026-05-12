<?php
namespace WorkerNu\PageTheme\BodyClass;

if (!defined('ABSPATH')) exit;

function add(array $classes): array {
    if (!function_exists('workernu_page_theme')) return $classes;
    $classes[] = 'theme--' . sanitize_html_class(workernu_page_theme());
    return $classes;
}
