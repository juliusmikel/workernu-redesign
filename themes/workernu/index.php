<?php
get_header();

if (have_posts()) {
    while (have_posts()) {
        the_post();
        if (function_exists('workernu_render_sections')) {
            workernu_render_sections(get_the_ID());
        } else {
            the_content();
        }
    }
}

get_footer();
