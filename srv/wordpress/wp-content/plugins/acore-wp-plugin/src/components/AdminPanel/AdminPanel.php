<?php

namespace ACore;

require_once "Settings.controller.php";

add_action('init', __NAMESPACE__ . '\\admin_panel_init');

class AdminPanel {

    private static $instance = null;

    /**
     * Singleton
     * @return Opts
     */
    public static function I() {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

// mt_settings_page() displays the page content for the Test settings submenu
    function acore_settings_page() {
        $SettingsCtrl = new SettingsController();
        $SettingsCtrl->init();
    }

// action function for above hook
    function acore_add_pages() {
        // Add a new submenu under Settings:
        add_options_page(__('ACore Settings Panel', sOpts()->org_alias), __('ACore Settings Panel', sOpts()->org_alias), 'manage_options', 'basettings', array($this, 'acore_settings_page'));
    }

}

function admin_panel_init() {
    $adminPanel = AdminPanel::I();

    if (is_admin())
        add_action('admin_menu', array($adminPanel, 'acore_add_pages'));
}
