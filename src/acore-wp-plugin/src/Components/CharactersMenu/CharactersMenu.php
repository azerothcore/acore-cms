<?php

namespace ACore\Components\CharactersMenu;

use ACore\Components\CharactersMenu\CharactersController;
use ACore\Manager\ACoreServices;

add_action('init', __NAMESPACE__ . '\\characters_menu_init');

class CharactersMenu
{
    private static $instance = null;

    /**
     * Singleton
     * @return CharactersMenu
     */
    public static function I()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function acore_characters_menu()
    {
        $menuTitle = 'Characters';
        try {
            $accId    = ACoreServices::I()->getAcoreAccountId();
            if (!$accId) throw new \Exception('no account');
            $authConn = ACoreServices::I()->getAccountEm()->getConnection();
            $now      = time();

            $isBanned = (bool) $authConn->executeQuery(
                "SELECT 1 FROM `account_banned`
                 WHERE `id` = ? AND `active` = 1
                   AND (`unbandate` = 0 OR `unbandate` = `bandate` OR `unbandate` > UNIX_TIMESTAMP())
                 LIMIT 1", [$accId]
            )->fetchOne();

            if ($isBanned) {
                $menuTitle .= ' <span style="background:#dc3545;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:3px;vertical-align:middle;text-transform:uppercase;">Banned</span>';
            } else {
                $muteRow  = $authConn->executeQuery("SELECT `mutetime` FROM `account` WHERE `id` = ?", [$accId])->fetchAssociative();
                $mutetime = $muteRow ? intval($muteRow['mutetime']) : 0;
                $isMuted  = $mutetime < 0 || $mutetime > $now;
                if ($isMuted) {
                    $menuTitle .= ' <span style="background:#ffc107;color:#000;font-size:9px;font-weight:700;padding:1px 5px;border-radius:3px;vertical-align:middle;text-transform:uppercase;">Muted</span>';
                }
            }
        } catch (\Throwable $e) {
            // silently skip badge on DB error
        }

        add_submenu_page('profile.php', 'Characters', $menuTitle, 'read', ACORE_SLUG . '-characters-menu', array($this, 'acore_characters_menu_page'));
    }

    function acore_characters_menu_page()
    {
        $controller = new CharactersController();
        $controller->loadHome();
    }
}

function characters_menu_init()
{
    $charactersMenu = CharactersMenu::I();

    add_action('admin_menu', array($charactersMenu, 'acore_characters_menu'));
    add_action('admin_head', function () {
        echo '<style>#adminmenu a[href*="acore-characters-menu"]{display:flex!important;align-items:center!important;justify-content:space-between!important;}</style>';
    });
}
