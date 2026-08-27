<?php
namespace WorkerNu\SEO\Options;

if (!defined('ABSPATH')) exit;

const META_TITLE       = '_workernu_seo_title';
const META_DESCRIPTION = '_workernu_seo_description';
const META_OG_IMAGE    = '_workernu_seo_og_image';
const META_NOINDEX     = '_workernu_seo_noindex';

const OPT_ROBOTS              = 'workernu_seo_robots_txt';
const OPT_LLMS                = 'workernu_seo_llms_txt';
const OPT_DEFAULT_DESC        = 'workernu_seo_default_description';
const OPT_TITLE_FORMAT        = 'workernu_seo_title_format';
const OPT_ORG_NAME            = 'workernu_seo_org_name';
const OPT_ORG_LOGO            = 'workernu_seo_org_logo';
const OPT_ORG_SOCIAL          = 'workernu_seo_org_social';
const OPT_ORG_DESCRIPTION     = 'workernu_seo_org_description';
const OPT_ORG_EMAIL           = 'workernu_seo_org_email';
const OPT_ORG_PHONE           = 'workernu_seo_org_phone';
const OPT_ORG_CONTACT_TYPE    = 'workernu_seo_org_contact_type';
const OPT_ORG_STREET          = 'workernu_seo_org_street';
const OPT_ORG_LOCALITY        = 'workernu_seo_org_locality';
const OPT_ORG_REGION          = 'workernu_seo_org_region';
const OPT_ORG_POSTAL          = 'workernu_seo_org_postal';
const OPT_ORG_COUNTRY         = 'workernu_seo_org_country';
const OPT_ORG_FOUNDING_DATE   = 'workernu_seo_org_founding_date';
const OPT_DEFAULT_OG          = 'workernu_seo_default_og_image';

function all(): array {
    return [
        'robots_txt'          => (string) get_option(OPT_ROBOTS, ''),
        'llms_txt'            => (string) get_option(OPT_LLMS, default_llms_content()),
        'default_description' => array_merge(['lt' => '', 'en' => ''], (array) get_option(OPT_DEFAULT_DESC, [])),
        'title_format'        => (string) get_option(OPT_TITLE_FORMAT, '{title} | {site_name}'),
        'org_name'            => (string) get_option(OPT_ORG_NAME, get_bloginfo('name')),
        'org_logo'            => (string) get_option(OPT_ORG_LOGO, ''),
        'org_social'          => (string) get_option(OPT_ORG_SOCIAL, ''),
        'org_description'     => array_merge(['lt' => '', 'en' => ''], (array) get_option(OPT_ORG_DESCRIPTION, [])),
        'org_email'           => (string) get_option(OPT_ORG_EMAIL, ''),
        'org_phone'           => (string) get_option(OPT_ORG_PHONE, ''),
        'org_contact_type'    => (string) get_option(OPT_ORG_CONTACT_TYPE, 'customer service'),
        'org_street'          => (string) get_option(OPT_ORG_STREET, ''),
        'org_locality'        => (string) get_option(OPT_ORG_LOCALITY, ''),
        'org_region'          => (string) get_option(OPT_ORG_REGION, ''),
        'org_postal'          => (string) get_option(OPT_ORG_POSTAL, ''),
        'org_country'         => (string) get_option(OPT_ORG_COUNTRY, ''),
        'org_founding_date'   => (string) get_option(OPT_ORG_FOUNDING_DATE, ''),
        'default_og_image'    => (int)    get_option(OPT_DEFAULT_OG, 0),
    ];
}

function default_llms_content(): string {
    $site_name = get_bloginfo('name');
    $site_url  = home_url('/');
    $tagline   = get_bloginfo('description');
    return "# {$site_name}\n\n> {$tagline}\n\n## Pages\n\n- [Home]({$site_url})\n";
}
