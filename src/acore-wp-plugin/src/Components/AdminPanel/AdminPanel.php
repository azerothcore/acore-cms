<?php

namespace ACore\Components\AdminPanel;

use ACore\Manager\Opts;
use ACore\Components\AdminPanel\SettingsController;

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
    function acore_pvpreward_page()
    {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->loadPvpRewards();
    }

    function acore_eluna_settings()
    {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->loadElunaSettings();
    }

    // mt_settings_page() displays the page content for the Test settings submenu
    function acore_home_page()
    {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->loadHome();
    }

    // mt_settings_page() displays the page content for the Test settings submenu
    function acore_tools_page()
    {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->loadTools();
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
            ACORE_SLUG . '-settings',
            array($this, 'acore_settings_page')
        );

        // Add a new submenu under Settings:
        add_submenu_page(
            'acore',
            __('ACore Settings Panel', Opts::I()->org_alias),
            __('Eluna', Opts::I()->org_alias),
            'manage_options',
            ACORE_SLUG . '-eluna-settings',
            array($this, 'acore_eluna_settings')
        );

        // Add a new submenu under Settings:
        add_submenu_page(
            'acore',
            __('ACore Settings Panel', Opts::I()->org_alias),
            __('PvP Rewards', Opts::I()->org_alias),
            'manage_options',
            ACORE_SLUG . '-pvp-rewards',
            array($this, 'acore_pvpreward_page')
        );

        // Add a new submenu under Settings:
        add_submenu_page(
            'acore',
            __('ACore Settings Panel', Opts::I()->org_alias),
            __('Tools', Opts::I()->org_alias),
            'manage_options',
            ACORE_SLUG . '-tools',
            array($this, 'acore_tools_page')
        );
    }

    // action function for above hook
    function acore_admin_menu()
    {
        $file = file_get_contents( ACORE_PATH_PLG . 'web/assets/admin_logo.svg' );
        add_menu_page(
            __('ACore Home', Opts::I()->org_alias),
            __('AzerothCore', Opts::I()->org_alias),
            'manage_options',
            ACORE_SLUG,
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

    // get administrator role and add new acore capabilities
    $role = get_role( 'administrator' );
    $role->add_cap( 'game_master' );
    $role->add_cap( 'manage_pvp_rewards' );

}
