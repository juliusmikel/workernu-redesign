</main>

<?php
$col1_label       = get_theme_mod('footer_col_1_label',      'Produktas');
$col2_label       = get_theme_mod('footer_col_2_label',      'Įmonė');
$col3_label       = get_theme_mod('footer_col_3_label',      'Kontaktai');
$description      = get_theme_mod('footer_description',      '');
$contact_email    = get_theme_mod('footer_contact_email',    '');
$contact_phone    = get_theme_mod('footer_contact_phone',    '');
$contact_address  = get_theme_mod('footer_contact_address',  '');
$linkedin         = get_theme_mod('footer_social_linkedin',  '');
$facebook         = get_theme_mod('footer_social_facebook',  '');
$instagram        = get_theme_mod('footer_social_instagram', '');
$copyright        = get_theme_mod('footer_copyright',        '&copy; ' . date('Y') . ' WorkerNu. Visos teisės saugomos.');

$nav_defaults = [
    'container'   => false,
    'depth'       => 1,
    'fallback_cb' => false,
    'items_wrap'  => '<ul class="site-footer__nav-list">%3$s</ul>',
];
?>

<footer class="site-footer">
    <div class="site-footer__inner container">

        <!-- Brand column -->
        <div class="site-footer__brand">
            <a class="site-footer__logo-link" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php bloginfo('name'); ?>">
                <?php
                $logo_id = get_theme_mod('custom_logo');
                if ($logo_id) {
                    echo wp_get_attachment_image($logo_id, 'full', false, ['class' => 'site-footer__logo', 'loading' => 'lazy']);
                } else {
                    echo '<span class="site-footer__logo-text">' . esc_html(get_bloginfo('name')) . '</span>';
                }
                ?>
            </a>

            <?php if ($description !== ''): ?>
                <p class="site-footer__description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>

            <?php if ($linkedin || $facebook || $instagram): ?>
                <div class="site-footer__social">
                    <?php if ($linkedin): ?>
                        <a class="site-footer__social-btn" href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                            <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($facebook): ?>
                        <a class="site-footer__social-btn" href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                            <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($instagram): ?>
                        <a class="site-footer__social-btn" href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                            <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nav columns -->
        <nav class="site-footer__cols" aria-label="<?php esc_attr_e('Footer navigation', 'workernu'); ?>">

            <div class="site-footer__col">
                <p class="site-footer__col-heading"><?php echo esc_html($col1_label); ?></p>
                <?php wp_nav_menu(array_merge($nav_defaults, ['theme_location' => 'footer_col_1'])); ?>
            </div>

            <div class="site-footer__col">
                <p class="site-footer__col-heading"><?php echo esc_html($col2_label); ?></p>
                <?php wp_nav_menu(array_merge($nav_defaults, ['theme_location' => 'footer_col_2'])); ?>
            </div>

            <div class="site-footer__col">
                <p class="site-footer__col-heading"><?php echo esc_html($col3_label); ?></p>
                <ul class="site-footer__nav-list site-footer__contact-list">
                    <?php if ($contact_email !== ''): ?>
                        <li>
                            <a href="mailto:<?php echo esc_attr($contact_email); ?>">
                                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                <?php echo esc_html($contact_email); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($contact_phone !== ''): ?>
                        <li>
                            <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $contact_phone)); ?>">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                <?php echo esc_html($contact_phone); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($contact_address !== ''): ?>
                        <li class="site-footer__contact-address">
                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            <?php echo esc_html($contact_address); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

        </nav>

    </div>

    <!-- Bottom bar -->
    <div class="site-footer__bottom">
        <div class="site-footer__bottom-inner container">

            <nav class="site-footer__legal" aria-label="<?php esc_attr_e('Legal links', 'workernu'); ?>">
                <?php wp_nav_menu(array_merge($nav_defaults, [
                    'theme_location' => 'footer_legal',
                    'items_wrap'     => '<ul class="site-footer__legal-list">%3$s</ul>',
                ])); ?>
            </nav>

            <?php if ($copyright !== ''): ?>
                <p class="site-footer__copyright"><?php echo wp_kses_post($copyright); ?></p>
            <?php endif; ?>


        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
