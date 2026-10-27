<?php
/**
 * Theme settings: Appearance > Customize > ACore Development.
 */

defined('ABSPATH') || exit;

/** Default values of the theme settings. */
function acore_theme_defaults()
{
    return [
        'acore_default_mode'    => 'dark',  // dark | light, until the visitor picks one
        'acore_nav_max_pages'   => 6,       // menu entries shown before the "More" dropdown
        'acore_nav_always_more' => false,   // show "More" even when everything fits
        'acore_price_label'     => '',      // e.g. "ChromiePoints": store prices as "1,000 ChromiePoints"; empty = WooCommerce price
    ];
}

/** A theme setting, with its default. */
function acore_theme_mod($name)
{
    $defaults = acore_theme_defaults();
    return get_theme_mod($name, isset($defaults[$name]) ? $defaults[$name] : null);
}

add_action('customize_register', function ($wp_customize) {
    $defaults = acore_theme_defaults();

    $wp_customize->add_section('acore_theme', [
        'title'    => __('ACore Development', 'acore-development'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('acore_default_mode', [
        'default'           => $defaults['acore_default_mode'],
        'sanitize_callback' => function ($value) {
            return $value === 'light' ? 'light' : 'dark';
        },
    ]);
    $wp_customize->add_control('acore_default_mode', [
        'label'       => __('Default colour mode', 'acore-development'),
        'description' => __('Visitors can switch with the toggle in the menu; their choice is remembered in their browser.', 'acore-development'),
        'section'     => 'acore_theme',
        'type'        => 'radio',
        'choices'     => ['dark' => __('Dark', 'acore-development'), 'light' => __('Light', 'acore-development')],
    ]);

    $wp_customize->add_setting('acore_nav_max_pages', [
        'default'           => $defaults['acore_nav_max_pages'],
        'sanitize_callback' => function ($value) {
            return max(1, min(20, (int) $value));
        },
    ]);
    $wp_customize->add_control('acore_nav_max_pages', [
        'label'       => __('Menu entries before "More"', 'acore-development'),
        'description' => __('The rest go into the "More" dropdown. Uses the Primary menu (Appearance > Menus), or the theme\'s own pages when none is assigned.', 'acore-development'),
        'section'     => 'acore_theme',
        'type'        => 'number',
        'input_attrs' => ['min' => 1, 'max' => 20],
    ]);

    $wp_customize->add_setting('acore_nav_always_more', [
        'default'           => $defaults['acore_nav_always_more'],
        'sanitize_callback' => function ($value) {
            return (bool) $value;
        },
    ]);
    $wp_customize->add_control('acore_nav_always_more', [
        'label'   => __('Always show the "More" button', 'acore-development'),
        'section' => 'acore_theme',
        'type'    => 'checkbox',
    ]);

    $wp_customize->add_setting('acore_price_label', [
        'default'           => $defaults['acore_price_label'],
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('acore_price_label', [
        'label'       => __('Store price label', 'acore-development'),
        'description' => __('Overrides the unit after the price on the store page, e.g. "ChromiePoints". Leave empty to use the WooCommerce currency (AzerothCore Points).', 'acore-development'),
        'section'     => 'acore_theme',
        'type'        => 'text',
    ]);
});
