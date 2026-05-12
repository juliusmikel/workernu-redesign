<?php
$current_lang = function_exists('workernu_lang') ? workernu_lang() : 'lt';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> lang="<?php echo esc_attr($current_lang); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header wf">
    <div class="wf-row container">

        <div class="wf-slot wf-slot--logo">
            <span class="wf-label">LOGO</span>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="wf-content"><?php bloginfo('name'); ?></a>
        </div>

        <nav class="wf-slot wf-slot--nav" aria-label="<?php esc_attr_e('Primary', 'workernu'); ?>">
            <span class="wf-label">PRIMARY NAV</span>
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'wf-content',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                ]);
            }
            ?>
        </nav>

        <div class="wf-slot wf-slot--cta">
            <span class="wf-label">PRIMARY CTA</span>
        </div>

        <div class="wf-slot wf-slot--lang">
            <span class="wf-label">LANGUAGE</span>
            <div class="wf-content">
                <?php if (function_exists('workernu_language_switcher')) workernu_language_switcher(); ?>
            </div>
        </div>

    </div>
</header>

<main class="site-main">
