</main>

<footer class="site-footer wf">
    <div class="wf-row container">

        <div class="wf-slot wf-slot--nav">
            <span class="wf-label">FOOTER NAV</span>
            <?php
            if (has_nav_menu('footer')) {
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'wf-content',
                    'depth'          => 2,
                    'fallback_cb'    => false,
                ]);
            }
            ?>
        </div>

        <div class="wf-slot wf-slot--copy">
            <span class="wf-label">COPYRIGHT</span>
            <p class="wf-content">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
        </div>

        <div class="wf-slot wf-slot--lang">
            <span class="wf-label">LANGUAGE</span>
            <div class="wf-content">
                <?php if (function_exists('workernu_language_switcher')) workernu_language_switcher(); ?>
            </div>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
