<?php
namespace WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

const META_TITLE       = '_workernu_seo_title';
const META_DESCRIPTION = '_workernu_seo_description';
const META_OG_IMAGE    = '_workernu_seo_og_image';
const META_NOINDEX     = '_workernu_seo_noindex';

const OPT_ROBOTS         = 'workernu_seo_robots_txt';
const OPT_LLMS           = 'workernu_seo_llms_txt';
const OPT_DEFAULT_DESC   = 'workernu_seo_default_description';
const OPT_TITLE_FORMAT   = 'workernu_seo_title_format';
const OPT_ORG_NAME       = 'workernu_seo_org_name';
const OPT_ORG_LOGO       = 'workernu_seo_org_logo';
const OPT_ORG_SOCIAL     = 'workernu_seo_org_social';
const OPT_DEFAULT_OG     = 'workernu_seo_default_og_image';

function all(): array {
    return [
        'robots_txt'          => (string) get_option(OPT_ROBOTS, ''),
        'llms_txt'            => (string) get_option(OPT_LLMS, default_llms_content()),
        'default_description' => array_merge(['lt' => '', 'en' => ''], (array) get_option(OPT_DEFAULT_DESC, [])),
        'title_format'        => (string) get_option(OPT_TITLE_FORMAT, '{title} | {site_name}'),
        'org_name'            => (string) get_option(OPT_ORG_NAME, get_bloginfo('name')),
        'org_logo'            => (string) get_option(OPT_ORG_LOGO, ''),
        'org_social'          => (string) get_option(OPT_ORG_SOCIAL, ''),
        'default_og_image'    => (int)    get_option(OPT_DEFAULT_OG, 0),
    ];
}

function default_llms_content(): string {
    $site_name = get_bloginfo('name');
    $site_url  = home_url('/');
    $tagline   = get_bloginfo('description');
    return "# {$site_name}\n\n> {$tagline}\n\n## Pages\n\n- [Home]({$site_url})\n";
}
