<?php

namespace ACore;

require_once "User.controller.php";

add_action('init', __NAMESPACE__ . '\\user_panel_init');

class UserPanel
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

    function acore_account()
    {
        $UserCtrl = new UserController();
        $UserCtrl->loadAccount();
    }

    // action function for above hook
    function show_raf_progress()
    {
        $SettingsCtrl = new UserController();
        $SettingsCtrl->showRafProgress();
    }
}

function user_panel_init()
{
    $userPanel = UserPanel::I();

    add_action( 'personal_options', array( $userPanel, 'show_raf_progress' ) );

}
