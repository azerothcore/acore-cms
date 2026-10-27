<?php
/**
 * The test shop, set up whatever the environment.
 *
 * Runs once (automatically, on the first page load) and creates what's missing:
 *  - the required plugins (WooCommerce, myCred, WP 2FA) installed and activated, WooCommerce pre-set (plugins.php)
 *  - prices and payments in AzerothCore Points (myCred) instead of money
 *  - product categories for the ACore services (SmartStone with its sub-categories)
 *  - one test product for every ACore service SKU (existing products with that SKU are left alone)
 *  - a "Store" page with [acore_store]
 *  - an "ACore Development" page with [acore_dev_info], made the front page (unless a front page is already chosen)
 *  - classic cart / checkout pages (the services add their fields through the classic WooCommerce hooks)
 *  - the "ACore test payment" method, enabled (see test-gateway.php for who can use it)
 *
 * Run it again with: wp acore-dev setup [--force]
 */

defined('ABSPATH') || exit;

/** Bump to make every development site run the setup again (it only ever adds what's missing). */
define('ACORE_THEME_DEV_SETUP_VERSION', 7);

/** Categories: slug => [name, parent slug or null]. */
function acore_theme_dev_categories()
{
    return [
        'acore-characters'           => ['Characters', null],
        'acore-items'                => ['Items', null],
        'acore-transmog'             => ['Transmog', null],
        'acore-guild'                => ['Guild', null],
        'acore-carboncopy'           => ['CarbonCopy', null],
        'acore-smartstone'           => ['SmartStone', null],
        // SmartStone sub-categories, one per service type `.smartstone unlock service` accepts (the ACore plugin's
        // SmartstoneProductAdmin::types() puts SmartStone products in them): 0 Companion, 1 Combat pet, 2 Costume,
        // 6 Aura, 7 Vehicle, 9 Class perk
        'acore-smartstone-companion' => ['Companion', 'acore-smartstone'],
        'acore-smartstone-pet'       => ['Combat Pet', 'acore-smartstone'],
        'acore-smartstone-costume'   => ['Costume', 'acore-smartstone'],
        'acore-smartstone-aura'      => ['Aura', 'acore-smartstone'],
        'acore-smartstone-vehicle'   => ['Vehicle', 'acore-smartstone'],
        'acore-smartstone-perk'      => ['Class Perk', 'acore-smartstone'],
    ];
}

/** One test product per ACore service: sku, name, price, category slug, description. */
function acore_theme_dev_products()
{
    $character = 'Pick the character on this page (you need to be logged in with a game account that has characters).';
    return [
        ['char-change-name', 'Character Rename', 5, 'acore-characters', 'Renames a character at its next login. ' . $character],
        ['char-change-faction', 'Faction Change', 10, 'acore-characters', 'Lets a character change faction at its next login. ' . $character],
        ['char-change-race', 'Race Change', 10, 'acore-characters', 'Lets a character change race at its next login. ' . $character],
        ['char-change-customize', 'Appearance Change', 5, 'acore-characters', 'Lets a character change its appearance at its next login. ' . $character],
        ['char-restore-delete', 'Restore Deleted Character', 10, 'acore-characters', 'Restores a deleted character. Pick it from your deleted characters on this page.'],
        ['char-transfer-sku', 'Character Transfer', 15, 'acore-characters', 'Moves a character to another game account. ' . $character . ' Then enter the destination account.'],
        ['name-unlock', 'Name Unlock', 5, 'acore-characters', 'Frees the name of an inactive character for one of your characters, and renames yours to it (the rename is included). Enter the name and pick your character on this page.'],
        ['itemsend_6948', 'Item: Hearthstone', 1, 'acore-items', 'Sends a Hearthstone (item 6948) to a character by mail. ' . $character],
        ['itemsend_4306_stack', 'Item: Silk Cloth (stack)', 1, 'acore-items', 'Sends Silk Cloth (item 4306) by mail; the quantity is the stack size. ' . $character],
        ['item-restoration', 'Item Restoration', 5, 'acore-items', 'Restores an item the character deleted or sold. Pick the character and the item on this page.'],
        ['transmog-item_2586', 'Transmog: Gamemaster\'s Robe', 3, 'acore-transmog', 'Unlocks the appearance of item 2586 for transmogrification. ' . $character],
        ['transmog-itemset_1', 'Transmog: Item Set 1', 5, 'acore-transmog', 'Unlocks the appearances of item set 1 for transmogrification. ' . $character],
        ['guild-rename', 'Guild Rename', 10, 'acore-guild', 'Renames the guild of the chosen character (it must be the guild master). Enter the new name on this page.'],
        ['carboncopy-tickets', 'CarbonCopy Ticket', 2, 'acore-carboncopy', 'Adds CarbonCopy tickets to your game account; the quantity is the number of tickets. Use them in game with .carboncopy.'],
        ['smartstone_0_1', 'SmartStone: Companion #1', 3, 'acore-smartstone-companion', 'Unlocks SmartStone companion 1 for a character (the id must exist in your SmartStone data). ' . $character],
        ['smartstone_2_1', 'SmartStone: Costume #1', 3, 'acore-smartstone-costume', 'Unlocks SmartStone costume 1 for the whole account (the id must exist in your SmartStone data).'],
    ];
}

/** The full product description of a test product (defaults to its short description). */
function acore_theme_dev_product_description($sku, $short)
{
    $descriptions = [
        'name-unlock' => '<p>' . __('The perfect name for your character is already in use by someone else, but they are not active anymore? You can now free up an inactive character\'s name with this service!', 'acore-development') . '</p>'
            . '<p>' . __('This service allows you to claim a name that is currently held by an old, inactive character. The inactive character will be renamed with a randomly generated name, freeing up the desired name for your use.', 'acore-development') . '</p>'
            . '<p><strong>' . __('Example:', 'acore-development') . '</strong></p><ul>'
            . '<li>' . __('You want your character "mycharacterName" to have the name "lockedName".', 'acore-development') . '</li>'
            . '<li>' . __('The inactive character currently named "lockedName" will be automatically renamed with a random name.', 'acore-development') . '</li>'
            . '<li>' . __('Your character "mycharacterName" will then be renamed to "lockedName".', 'acore-development') . '</li></ul>'
            . '<p>' . __('The rename is included: you don\'t need to buy a character rename.', 'acore-development') . '</p>'
            . '<p><strong>' . __('Note:', 'acore-development') . '</strong> ' . __('you can\'t unlock a character\'s name if the character\'s account has a subscription.', 'acore-development') . '</p>',
    ];
    return $descriptions[$sku] ?? $short;
}

/** Term id of a product category, created when missing. */
function acore_theme_dev_category_id($slug, $categories, &$log)
{
    $term = get_term_by('slug', $slug, 'product_cat');
    if ($term) {
        return (int) $term->term_id;
    }
    list($name, $parentSlug) = $categories[$slug];
    $args = ['slug' => $slug];
    if ($parentSlug) {
        $args['parent'] = acore_theme_dev_category_id($parentSlug, $categories, $log);
    }
    $result = wp_insert_term($name, 'product_cat', $args);
    if (is_wp_error($result)) {
        $log[] = "Category $slug: " . $result->get_error_message();
        return 0;
    }
    $log[] = "Category created: $name";
    return (int) $result['term_id'];
}

/** Puts a classic WooCommerce shortcode on a page that uses the cart / checkout block (or always with $force). */
function acore_theme_dev_classic_page($pageId, $shortcode, $force, &$log)
{
    $page = $pageId > 0 ? get_post($pageId) : null;
    if (!$page) {
        $log[] = "No $shortcode page found (WooCommerce > Settings > Advanced > Page setup).";
        return;
    }
    if (!$force && strpos($page->post_content, '<!-- wp:woocommerce/') === false && trim($page->post_content) !== '') {
        return;   // already classic or customised: leave it
    }
    wp_update_post([
        'ID'           => $pageId,
        'post_content' => "<!-- wp:shortcode -->\n[$shortcode]\n<!-- /wp:shortcode -->",
    ]);
    $log[] = "Page \"{$page->post_title}\" now uses the classic [$shortcode].";
}

/**
 * Creates the test shop. Returns what it did, one line per change.
 * $force: also put the classic cart / checkout back, make the info page the front page and re-enable the test payment.
 * $allowInstall: may download missing plugins (administrators, WP-CLI); activating installed ones always happens.
 */
function acore_theme_dev_setup($force = false, $allowInstall = true)
{
    $log = [];

    // the info page, as the front page unless the site already has one
    $info = get_page_by_path('acore-development');
    if (!$info) {
        $infoId = wp_insert_post([
            'post_title'   => 'ACore Development',
            'post_name'    => 'acore-development',
            'post_content' => "<!-- wp:shortcode -->\n[acore_dev_info]\n<!-- /wp:shortcode -->",
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'menu_order'   => 0,
        ]);
        $log[] = is_wp_error($infoId) ? 'Info page: ' . $infoId->get_error_message() : 'Page created: ACore Development ([acore_dev_info])';
        $info = is_wp_error($infoId) ? null : get_post($infoId);
    }
    if ($info && ($force || get_option('show_on_front') !== 'page' || !get_option('page_on_front'))) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $info->ID);
        $log[] = 'Front page: ACore Development';
    }

    acore_theme_ensure_plugins($log, $allowInstall);
    if (!class_exists('WooCommerce') || !taxonomy_exists('product_cat')) {
        $log[] = 'WooCommerce is not loaded yet: the shop is set up on the next page load.';
        return $log;
    }
    if (!acore_theme_points_defaults($log)) {
        return $log;   // myCred loads on the next page load: the setup finishes then
    }
    acore_theme_woocommerce_defaults($log);

    // WooCommerce's own pages (cart, checkout, my account, shop)
    if (wc_get_page_id('cart') <= 0 || wc_get_page_id('checkout') <= 0) {
        WC_Install::create_pages();
        $log[] = 'WooCommerce pages created.';
    }

    $categories = acore_theme_dev_categories();
    foreach (array_keys($categories) as $slug) {
        acore_theme_dev_category_id($slug, $categories, $log);
    }

    $order = 0;
    foreach (acore_theme_dev_products() as $definition) {
        list($sku, $name, $price, $categorySlug, $description) = $definition;
        $order++;
        if (wc_get_product_id_by_sku($sku)) {
            continue;
        }
        try {
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_sku($sku);
            $product->set_regular_price((string) $price);
            $product->set_virtual(true);
            $product->set_menu_order($order);
            $product->set_short_description($description);
            $product->set_description(acore_theme_dev_product_description($sku, $description) . "\n\n<em>Test product created by the ACore Development theme.</em>");
            $categoryId = acore_theme_dev_category_id($categorySlug, $categories, $log);
            if ($categoryId) {
                $product->set_category_ids([$categoryId]);
            }
            $product->save();
            $log[] = "Product created: $name ($sku)";
        } catch (Exception $e) {
            $log[] = "Product $sku: " . $e->getMessage();
        }
    }

    // the store page
    if (!get_page_by_path('store')) {
        $id = wp_insert_post([
            'post_title'   => 'Store',
            'post_name'    => 'store',
            'post_content' => "<!-- wp:shortcode -->\n[acore_store]\n<!-- /wp:shortcode -->",
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'menu_order'   => 1,
        ]);
        $log[] = is_wp_error($id) ? 'Store page: ' . $id->get_error_message() : 'Page created: Store ([acore_store])';
    }

    // categories from earlier versions: Mount (can't be granted by command) goes when empty; the old names follow
    $mount = get_term_by('slug', 'acore-smartstone-mount', 'product_cat');
    if ($mount && (int) $mount->count === 0) {
        wp_delete_term($mount->term_id, 'product_cat');
        $log[] = 'Category removed: Mount (SmartStone mounts can\'t be granted by command)';
    }
    foreach (['acore-smartstone-pet' => ['Pet', 'Combat Pet'], 'acore-smartstone-perk' => ['Perk', 'Class Perk']] as $slug => $names) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term && $term->name === $names[0]) {
            wp_update_term($term->term_id, 'product_cat', ['name' => $names[1]]);
            $log[] = "Category renamed: {$names[0]} -> {$names[1]}";
        }
    }

    // test products from earlier versions: the name unlock's old short text gets the full description
    $nameUnlockId = wc_get_product_id_by_sku('name-unlock');
    $nameUnlock   = $nameUnlockId ? wc_get_product($nameUnlockId) : null;
    if ($nameUnlock && strpos($nameUnlock->get_short_description(), 'Frees a reserved character name') === 0) {
        foreach (acore_theme_dev_products() as $definition) {
            if ($definition[0] === 'name-unlock') {
                $nameUnlock->set_short_description($definition[4]);
                $nameUnlock->set_description(acore_theme_dev_product_description('name-unlock', $definition[4]) . "\n\n<em>Test product created by the ACore Development theme.</em>");
                $nameUnlock->save();
                $log[] = 'Product updated: Name Unlock (description)';
            }
        }
    }

    acore_theme_dev_classic_page(wc_get_page_id('cart'), 'woocommerce_cart', $force, $log);
    acore_theme_dev_classic_page(wc_get_page_id('checkout'), 'woocommerce_checkout', $force, $log);

    // the test payment method, enabled
    $settings = get_option('woocommerce_acore_test_settings', []);
    if ($force || empty($settings)) {
        $settings = is_array($settings) ? $settings : [];
        $settings['enabled'] = 'yes';
        update_option('woocommerce_acore_test_settings', $settings);
        $log[] = 'ACore test payment enabled.';
    }

    update_option('acore_theme_dev_setup', ACORE_THEME_DEV_SETUP_VERSION);
    return $log;
}

/**
 * Runs the setup once per ACORE_THEME_DEV_SETUP_VERSION, on an administrator's page load only (it installs plugins and
 * changes the shop settings; Docker runs wp acore-dev setup at start). The lock stops two requests from both running it.
 */
add_action('init', function () {
    if ((int) get_option('acore_theme_dev_setup', 0) >= ACORE_THEME_DEV_SETUP_VERSION) {
        return;   // done (it keeps trying on each administrator page load until WooCommerce is active and the shop is set up)
    }
    if (!is_user_logged_in() || !current_user_can('install_plugins')) {
        return;
    }
    $lock = (int) get_option('acore_theme_dev_setup_lock', 0);
    if ($lock && $lock > time() - 300) {
        return;
    }
    if (!$lock && !add_option('acore_theme_dev_setup_lock', time(), '', false)) {
        return;
    }
    update_option('acore_theme_dev_setup_lock', time(), false);
    $log = acore_theme_dev_setup(false, true);
    delete_option('acore_theme_dev_setup_lock');
    if (!empty($log)) {
        error_log('ACore Development setup: ' . implode(' | ', $log));
    }
}, 20);

/** A reminder on the dashboard that this is the test shop. */
add_action('admin_notices', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, ['dashboard', 'edit-product', 'woocommerce_page_wc-settings'], true)) {
        return;
    }
    $info = get_page_by_path('acore-development');
    echo '<div class="notice notice-info"><p>'
        . esc_html__('The ACore Development theme is active: test products for every ACore service, the Store page and the "ACore test payment" method are set up.', 'acore-development')
        . ($info ? ' <a href="' . esc_url(get_permalink($info)) . '">' . esc_html__('See what is set up', 'acore-development') . '</a>' : '')
        . '</p></div>';
});

if (defined('WP_CLI') && WP_CLI) {
    /**
     * Creates the ACore test shop: installs / activates the required plugins, pre-sets WooCommerce, categories,
     * a test product per service, the Store and info pages, classic cart / checkout and the test payment method.
     * Only adds what's missing.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Also put the classic cart / checkout pages back, make the info page the front page and re-enable the test payment method.
     */
    WP_CLI::add_command('acore-dev setup', function ($args, $assoc) {
        foreach (acore_theme_dev_setup(!empty($assoc['force']), true) as $line) {
            WP_CLI::log($line);
        }
        WP_CLI::success('ACore test shop is ready.');
    });
}
