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

<a class="skip-link" href="#site-main"><?php esc_html_e('Skip to content', 'workernu'); ?></a>

<header class="site-header" data-site-header>
    <div class="site-header__inner container">
        <div class="site-header__bar">

        <div class="site-header__brand">
            <?php
            if (function_exists('the_custom_logo') && has_custom_logo()) {
                the_custom_logo();
            } else {
                printf(
                    '<a class="site-header__logo-text" href="%s">%s</a>',
                    esc_url(home_url('/')),
                    esc_html(get_bloginfo('name'))
                );
            }
            ?>
        </div>

        <button type="button"
                class="site-header__toggle"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="site-nav"
                aria-label="<?php esc_attr_e('Toggle menu', 'workernu'); ?>">
            <span class="site-header__toggle-bars" aria-hidden="true"></span>
        </button>

        <nav id="site-nav" class="site-nav" aria-label="<?php esc_attr_e('Primary', 'workernu'); ?>">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-nav__menu',
                    'menu_id'        => '',
                    'depth'          => 2,
                    'fallback_cb'    => false,
                ]);
            }
            ?>

            <?php if (function_exists('workernu_language_switcher')): ?>
                <div class="site-header__lang">
                    <?php workernu_language_switcher(); ?>
                </div>
            <?php endif; ?>
        </nav>

        </div>
    </div>
</header>

<main id="site-main" class="site-main">
