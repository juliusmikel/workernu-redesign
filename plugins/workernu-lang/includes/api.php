<?php
/**
 * workernu Lang — theme-facing global API.
 *
 * These three functions are the only public surface theme templates should call.
 * Internals live in the WorkerNu\Lang namespace.
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('workernu_t')) {
    /**
     * Resolve a value to the current (or specified) language string.
     *   workernu_t(['lt' => '...', 'en' => '...'])
     *   workernu_t('static value')
     */
    function workernu_t($value, ?string $lang = null) {
        return \WorkerNu\Lang\t($value, $lang);
    }
}

if (!function_exists('workernu_lang')) {
    /**
     * Current language code: 'lt' or 'en'.
     */
    function workernu_lang(): string {
        return \WorkerNu\Lang\current_lang();
    }
}

if (!function_exists('workernu_language_switcher')) {
    /**
     * Output the two-language switcher.
     */
    function workernu_language_switcher(): void {
        \WorkerNu\Lang\language_switcher();
    }
}

if (!function_exists('workernu_languages')) {
    /**
     * Returns the list of supported language codes.
     */
    function workernu_languages(): array {
        return \WorkerNu\Lang\LANGUAGES;
    }
}

if (!function_exists('workernu_default_language')) {
    /**
     * Returns the default language code (used as fallback when a translation is missing).
     */
    function workernu_default_language(): string {
        return \WorkerNu\Lang\DEFAULT_LANG;
    }
}
