<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    register_nav_menus([
        'primary' => __('Primary Navigation', 'workernu'),
        'footer'  => __('Footer Navigation', 'workernu'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    $theme_uri  = get_template_directory_uri();
    $theme_path = get_template_directory();
    $version    = wp_get_theme()->get('Version');

    // Font Awesome — global so any section can use icons.
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
        [],
        '6.7.2'
    );

    wp_enqueue_style(
        'workernu-main',
        $theme_uri . '/assets/css/main.css',
        [],
        file_exists($theme_path . '/assets/css/main.css') ? filemtime($theme_path . '/assets/css/main.css') : $version
    );

    wp_enqueue_script(
        'workernu-main',
        $theme_uri . '/assets/js/main.js',
        [],
        file_exists($theme_path . '/assets/js/main.js') ? filemtime($theme_path . '/assets/js/main.js') : $version,
        true
    );

    if (is_singular() && function_exists('workernu_enqueue_section_assets')) {
        workernu_enqueue_section_assets(get_queried_object_id());
    }
});

/**
 * Register the page-theme palettes. Each entry shows up in the Page Theme
 * meta box as a swatch + label, and applies via the body class `theme--<slug>`.
 * The CSS for each lives in assets/css/main.css.
 */
add_filter('workernu_themes', function (array $themes): array {
    return array_merge($themes, [
        'default'  => ['label' => __('Default',  'workernu'), 'swatch' => '#ffffff'],
        'midnight' => ['label' => __('Midnight', 'workernu'), 'swatch' => '#0a0a0b'],
    ]);
});
