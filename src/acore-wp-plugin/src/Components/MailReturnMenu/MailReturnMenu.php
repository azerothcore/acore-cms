<?php

namespace ACore\Components\MailReturnMenu;

use ACore\Components\MailReturnMenu\MailReturnController;

require_once 'MailReturnApi.php';

add_action('init', __NAMESPACE__ . '\\mail_return_menu_init');

class MailReturnMenu
{
    private static $instance = null;

    /**
     * Singleton
     * @return MailReturnMenu
     */
    public static function I()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function acore_mail_return_menu()
    {
        add_submenu_page('profile.php', 'Mail Return', 'Mail Return', 'read', ACORE_SLUG . '-mail-return-menu', array($this, 'acore_mail_return_menu_page'));
    }

    function acore_mail_return_menu_page()
    {
        $controller = new MailReturnController();
        $controller->renderCharacters();
    }
}

function mail_return_menu_init()
{
    $mailReturnMenu = MailReturnMenu::I();

    add_action('admin_menu', array($mailReturnMenu, 'acore_mail_return_menu'));
}
