<?php get_header(); ?>

<section class="error-404">
    <div class="container">
        <h1>404</h1>
        <p>
            <?php
            $current_lang = function_exists('workernu_lang') ? workernu_lang() : 'lt';
            echo $current_lang === 'lt' ? 'Puslapis nerastas.' : 'Page not found.';
            ?>
        </p>
        <p><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo $current_lang === 'lt' ? 'Į pradžią' : 'Back home'; ?> →</a></p>
    </div>
</section>

<?php get_footer(); ?>
