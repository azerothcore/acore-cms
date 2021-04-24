<?php

namespace ACore;

require_once "Settings.controller.php";

add_action('init', __NAMESPACE__ . '\\admin_panel_init');

class AdminPanel
{

    private static $instance = null;

    /**
     * Singleton
     * @return Opts
     */
    public static function I()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // mt_settings_page() displays the page content for the Test settings submenu
    function acore_settings_page()
    {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->loadSettings();
    }

    // mt_settings_page() displays the page content for the Test settings submenu
    function acore_home_page()
    {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->loadHome();
    }

    // action function for above hook
    function acore_add_pages()
    {
        // Add a new submenu under Settings:
        add_submenu_page(
            'acore',
            __('ACore Settings Panel', Opts::I()->org_alias),
            __('Realm Settings', Opts::I()->org_alias),
            'manage_options',
            'settings',
            array($this, 'acore_settings_page')
        );
    }

    // action function for above hook
    function acore_admin_menu()
    {
        $file = file_get_contents(plugins_url( 'acore-wp-plugin/web/assets/logo.svg' ));
        add_menu_page(
            __('ACore Home', Opts::I()->org_alias),
            __('AzerothCore', Opts::I()->org_alias),
            'manage_options',
            'acore',
            array($this, 'acore_home_page'),
            'data:image/svg+xml;base64,' . base64_encode($file)
        );
    }
}

function admin_panel_init()
{
    $adminPanel = AdminPanel::I();

    if (is_admin()) {
        add_action( 'admin_menu', array( $adminPanel, 'acore_admin_menu' ), 8);
        add_action( 'admin_menu', array( $adminPanel, 'acore_add_pages' ), 8);
    }

}
