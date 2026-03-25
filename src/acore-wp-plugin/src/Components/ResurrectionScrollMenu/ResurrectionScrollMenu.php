<?php

namespace ACore\Components\ResurrectionScrollMenu;

use ACore\Manager\Opts;
use ACore\Components\ResurrectionScrollMenu\ResurrectionScrollController;

add_action('init', __NAMESPACE__ . '\\resurrection_scroll_menu_init');

class ResurrectionScrollMenu
{
    private static $instance = null;

    /**
     * Singleton
     * @return ResurrectionScrollMenu
     */
    public static function I()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function acore_resurrection_scroll_menu()
    {
        if (Opts::I()->acore_resurrection_scroll == '1') {
            add_submenu_page('profile.php', 'Scroll of Resurrection', 'Scroll of Resurrection', 'read', ACORE_SLUG . '-resurrection-scroll', array($this, 'acore_resurrection_scroll_menu_page'));
        }
    }

    function acore_resurrection_scroll_menu_page()
    {
        $controller = new ResurrectionScrollController();
        $controller->render();
    }
}

function resurrection_scroll_menu_init()
{
    $menu = ResurrectionScrollMenu::I();

    add_action('admin_menu', array($menu, 'acore_resurrection_scroll_menu'));
}
