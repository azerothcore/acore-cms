<?php

namespace ACore\Components\UserPanel;

use ACore\Manager\Opts;
use ACore\Manager\ACoreServices;
use ACore\Components\UserPanel\UserController;

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
            add_submenu_page('profile.php', 'Recruit a Friend', 'Recruit a Friend', 'read', ACORE_SLUG . '-eluna-raf-progress', array($this, 'eluna_raf_progress_page'));
        }

        if (Opts::I()->acore_item_restoration == '1') {
            add_submenu_page('profile.php', 'Item Restoration', 'Item Restoration', 'read', ACORE_SLUG . '-item-restoration', array($this, 'item_restoration_page'));
        }
    }

    // action function for above hook
    function eluna_raf_progress_page()
    {
        $SettingsCtrl = new UserController();
        $SettingsCtrl->showRafProgress();
    }

    function item_restoration_page()
    {
        $SettingsCtrl = new UserController();
        $SettingsCtrl->showItemRestorationPage();
    }
}

function user_menu_init()
{
    $userMenu = UserMenu::I();
    add_action( 'admin_menu', array( $userMenu, 'acore_user_menu' ) );
}

function remove_dashboard_meta() {
    if ( ! current_user_can( 'manage_options' ) ) {
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
        //remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');// hide update notifications
    }
    if (! current_user_can('update_core') ) {
        remove_action( 'admin_notices', 'update_nag', 3 );
    }
}

add_action( 'admin_init', __NAMESPACE__ . '\\remove_dashboard_meta' );

/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function acore_user_dashboard() {
    add_meta_box(
        'wpexplorer_dashboard_widget', // Widget slug.
        __( 'Personal player stats', 'textdomain'), // Title.
        __NAMESPACE__ . '\\personal_player_stats', // Display function.
        'dashboard',
        'side',
        'high'
    );
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\acore_user_dashboard' );

/**
 * Create the function to output the contents of your Dashboard Widget.
 */
function personal_player_stats() {
    $user = wp_get_current_user();
    $startDate = (new \DateTime($user->get("user_registered")))->format('D, d M Y H:i');
    $acServices = ACoreServices::I();
    try {
        $totalPlaytime = $acServices->getAcoreAccountTotaltime(true);
        echo "<p>You are here since <b>$startDate</b></p>";
        echo "<p>Your total playtime is <b>$totalPlaytime</b></p>";
    } catch (\Exception $e) {
        echo "";
    }
}

// Custom Admin footer
function acore_copyright () {
    echo '<span id="footer-thankyou">Made with ❤️  by <a href="https://www.azerothcore.org/" target="_blank">AzerothCore</a>. Powered by <a href="https://wordpress.org/" target="_blank">WordPress</a>.</span>';
}

add_filter( 'admin_footer_text', __NAMESPACE__ . '\\acore_copyright' );
