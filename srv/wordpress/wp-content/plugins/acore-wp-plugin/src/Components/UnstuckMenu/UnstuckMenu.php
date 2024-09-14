<?php

namespace ACore\Components\UnstuckMenu;

use ACore\Components\UnstuckMenu\UnstuckController;

require_once 'UnstuckApi.php';

add_action('init', __NAMESPACE__ . '\\unstuck_menu_init');

class UnstuckMenu
{
    private static $instance = null;

    /**
     * Singleton
     * @return UnstuckMenu
     */
    public static function I()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function acore_unstuck_menu()
    {
        add_submenu_page('profile.php', 'Unstuck', 'Unstuck', 'read', ACORE_SLUG . '-unstuck-menu', array($this, 'acore_unstuck_menu_page'));
    }

    function acore_unstuck_menu_page()
    {
        $controller = new UnstuckController();
        $controller->renderCharacters();
    }
}

function unstuck_menu_init()
{
    $unstuckMenu = UnstuckMenu::I();

    add_action('admin_menu', array($unstuckMenu, 'acore_unstuck_menu'));
}
