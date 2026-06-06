<?php

namespace ACore\Hooks\Various;

add_action('admin_bar_menu', __NAMESPACE__ . '\acore_dark_mode_bar_node', 999);

function acore_dark_mode_bar_node($wp_admin_bar) {
    if (!is_user_logged_in() || !is_admin()) {
        return;
    }

    $is_dark = get_user_meta(get_current_user_id(), 'acore_dark_mode', true) === '1';

    $wp_admin_bar->add_node([
        'id'     => 'acore-dark-mode',
        'parent' => 'top-secondary',
        'title'  => '<span id="acore-dm-icon">' . ($is_dark ? '&#9728;' : '&#9790;') . '</span>',
        'href'   => '#',
        'meta'   => ['title' => $is_dark ? 'Switch to light mode' : 'Switch to dark mode'],
    ]);
}

add_filter('admin_body_class', __NAMESPACE__ . '\acore_dark_mode_body_class');

function acore_dark_mode_body_class($classes) {
    if (get_user_meta(get_current_user_id(), 'acore_dark_mode', true) === '1') {
        $classes .= ' acore-dark-mode';
    }
    return $classes;
}

add_action('admin_enqueue_scripts', __NAMESPACE__ . '\acore_dark_mode_enqueue');

function acore_dark_mode_enqueue() {
    wp_enqueue_style('acore-dark-mode', ACORE_URL_PLG . 'web/assets/css/dark-mode.css', [], '1.7');

    $nonce = wp_create_nonce('acore_dark_mode');
    wp_add_inline_script('jquery-core', acore_dark_mode_js($nonce));
}

/*
 * Late inline <style> injected at end of admin head (priority 9999) so it always
 * wins the cascade over WordPress color-scheme CSS and any plugin stylesheets,
 * even those that use !important.
 */
add_action('admin_head', __NAMESPACE__ . '\acore_dark_mode_late_styles', 9999);

function acore_dark_mode_late_styles() {
    if (get_user_meta(get_current_user_id(), 'acore_dark_mode', true) !== '1') {
        return;
    }
    ?>
    <style id="acore-dark-mode-late">
        /* character list rows — handled by dark-mode.css; only overrides needed here */
        .acore-dark-mode .acore-char-meta img { border-color: rgba(255, 255, 255, 0.15) !important; }
        .acore-dark-mode #acore-characters-mail .acore-char-row.active { background: #1f3a5c !important; border-left-color: var(--cls-dark, #58a6ff) !important; }

        /* Bootstrap card */
        .acore-dark-mode .card,
        .acore-dark-mode .card-body,
        .acore-dark-mode .card-header { background-color: #161b22 !important; border-color: #30363d !important; color: #c9d1d9 !important; }

        /* mail entries */
        .acore-dark-mode .mail-entry { background: #161b22 !important; border-color: #30363d !important; }
        .acore-dark-mode .mail-recipient,
        .acore-dark-mode .mail-items { background: #0d1117 !important; }
        .acore-dark-mode .mail-entry *,
        .acore-dark-mode .mail-recipient *,
        .acore-dark-mode .mail-meta { color: #c9d1d9 !important; }

        /* myCred / points widgets */
        .acore-dark-mode [id^="mycred"],
        .acore-dark-mode [class*="mycred"] { background: #161b22 !important; border-color: #30363d !important; color: #c9d1d9 !important; }
        .acore-dark-mode [id^="mycred"] *,
        .acore-dark-mode [class*="mycred"] * { color: #c9d1d9 !important; }

        /* Bootstrap form-select */
        .acore-dark-mode .form-select { background-color: #0d1117 !important; border-color: #30363d !important; color: #c9d1d9 !important; }

        /* hr */
        .acore-dark-mode hr { border-color: #30363d !important; }
    </style>
    <?php
}

add_action('wp_ajax_acore_toggle_dark_mode', __NAMESPACE__ . '\acore_ajax_toggle_dark_mode');

function acore_ajax_toggle_dark_mode() {
    check_ajax_referer('acore_dark_mode', 'nonce');
    $user_id = get_current_user_id();
    $new     = get_user_meta($user_id, 'acore_dark_mode', true) === '1' ? '0' : '1';
    update_user_meta($user_id, 'acore_dark_mode', $new);
    wp_send_json_success(['dark' => $new === '1']);
}

function acore_dark_mode_js($nonce) {
    return <<<JS
jQuery(function($) {
    $('#wp-admin-bar-acore-dark-mode > .ab-item').on('click', function(e) {
        e.preventDefault();
        $.post(ajaxurl, { action: 'acore_toggle_dark_mode', nonce: '{$nonce}' }, function(res) {
            if (!res.success) return;
            var dark = res.data.dark;
            $('body').toggleClass('acore-dark-mode', dark);
            $('#acore-dm-icon').html(dark ? '&#9728;' : '&#9790;');
            $(this).attr('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
        });
    });
});
JS;
}
