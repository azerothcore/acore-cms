<?php

namespace ACore;

require_once "User.controller.php";

add_action('init', __NAMESPACE__ . '\\user_menu_init');

class UserMenu
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

    // action function for above hook
    function acore_user_menu()
    {
        if (Opts::I()->eluna_recruit_a_friend == '1') {
            $user = wp_get_current_user();
            add_submenu_page('profile.php', 'Recruit a Friend', 'Recruit a Friend', 'read', 'eluna-raf-progress', array($this, 'eluna_raf_progress_page'));
        }
    }

    // action function for above hook
    function eluna_raf_progress_page()
    {
        $SettingsCtrl = new UserController();
        $SettingsCtrl->showRafProgress();
    }
}

function user_menu_init()
{
    $userMenu = UserMenu::I();
    add_action( 'admin_menu', array( $userMenu, 'acore_user_menu' ) );

}
