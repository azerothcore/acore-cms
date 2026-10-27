<?php
/**
 * Dark / light mode and the theme's styles.
 *
 * The mode is a class on <html> (acore-dark / acore-light): the Customizer default is written by the server, then a
 * tiny inline script in <head> applies the visitor's saved choice before the page paints (no flash).
 */

defined('ABSPATH') || exit;

/** The default mode as an <html> class, so the page is styled even without JavaScript. */
add_filter('language_attributes', function ($output) {
    if (is_admin()) {
        return $output;
    }
    return $output . ' class="acore-' . esc_attr(acore_theme_mod('acore_default_mode')) . '"';
});

/** Apply the visitor's saved mode as early as possible. */
add_action('wp_head', function () {
    ?>
    <script>
    (function () {
        try {
            var mode = localStorage.getItem('acore_theme');
            if (mode === 'dark' || mode === 'light') {
                var html = document.documentElement;
                html.classList.remove('acore-dark', 'acore-light');
                html.classList.add('acore-' + mode);
            }
        } catch (e) {}
    })();
    </script>
    <?php
}, 0);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('acore-development', ACORE_THEME_URI . '/assets/css/theme.css', [], ACORE_THEME_VERSION);
    wp_enqueue_script('acore-development', ACORE_THEME_URI . '/assets/js/theme.js', [], ACORE_THEME_VERSION, true);
    wp_localize_script('acore-development', 'acoreTheme', [
        'labelDark'  => __('Dark', 'acore-development'),
        'labelLight' => __('Light', 'acore-development'),
    ]);
}, 20);
