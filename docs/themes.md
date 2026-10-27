# Themes

ACore-CMS is based on Wordpress, that means that it's compatible with all its themes.
But it also means that you can hire anyone to create your personalized installable theme.

To install a Wordpress theme is very easy, here you can find the [official guide](https://wordpress.org/support/article/using-themes/)

NOTE: not all wordpress themes provide the support to Woocommerce (the shop plugin), so make sure that your theme has Woocommerce within the list of its features.

## ACore Development (included, default)

ACore-CMS comes with a standalone theme in `src/acore-development` (no parent theme or other dependencies). With Docker it is activated once as the default theme; you can switch to any other theme under *Appearance > Themes*.

It sets up everything needed to try the CMS features, once: with Docker when the container starts (`wp acore-dev setup`), otherwise the first time an administrator opens the site (it installs plugins and changes shop settings, so other visitors never trigger it):

* an **ACore Development** front page (`[acore_dev_info]`) showing what the CMS needs and whether it's set up: WooCommerce and the ACore plugin, the ACore SOAP / database settings and whether the game server answers (administrators only), your game account and characters, the shop pages, payment methods, and a test product for every service
* product categories and one test product for every ACore service (character changes, transfer, name unlock, item send, item restoration, transmog, guild rename, CarbonCopy tickets, SmartStone)
* a *Store* page with `[acore_store]`: category tiles, product cards and a search box (`[acore_store category="acore-items"]` limits it to categories)
* classic cart and checkout pages (the services add their fields through the classic WooCommerce hooks)
* an **ACore test payment** method that completes the order immediately, so the services really run in game. Everyone can use it in development (`DOCKER_ENVIRONMENT=development` in `.env`, which sets WordPress's `WP_ENVIRONMENT_TYPE`); otherwise only administrators see it.
* the plugins the shop needs - **WooCommerce**, **myCred** and **WP 2FA** - downloaded from wordpress.org and activated (network-wide on multisite). When the newest version needs a newer WordPress, the newest one that supports yours is installed (on WordPress 6.8: WooCommerce 10.1.4, myCred 2.9.4.5, WP 2FA 2.9.3, the versions Live runs). A failed download is retried after 10 minutes on an administrator's visit (or with `wp acore-dev setup`); the reason is shown on the front page.
* shop defaults: no "coming soon" mode, no WooCommerce task list, an account is required at checkout
* prices in **AzerothCore Points** (myCred) instead of money: the point type is named AzerothCore Points, the WooCommerce currency is set to the points (no decimals) and "Pay with AzerothCore Points" is enabled. Logged-in users see their balance in the menu; on the Store page an **Add points** panel adds 100 / 500 / 1,000 / 5,000 or any amount to your balance without leaving the page (in development, or for administrators).
* the **AzerothCore logo** as the image of every product that has none (shop, Store page, cart, checkout)
* for users who can edit products (administrators, shop managers): an **Edit** button on every product card and product page, and a **Shop admin** bar (Manage products, Add product, Categories) on the front page, the Store page and the shop pages

Run the setup again with `wp acore-dev setup` (`--force` also restores the classic cart / checkout, the front page and the test payment). A new theme version with more setup steps runs them once by itself; it never overwrites products or pages you changed. The look: dark / light mode with a toggle in the menu, a menu with a "More" dropdown, category tabs on the shop pages; settings in *Appearance > Customize > ACore Development*.

## Other themes

Below you can find a list of RPG-based themes as well as Warcraft ones

## Free themes

* [https://www.warcraftwordpress.com/product/vanilla-warcraft-wordpress-theme/](https://web.archive.org/web/20230130150044/https://www.warcraftwordpress.com/)

* You can find a lot of free thems on [wordpress website](https://wordpress.org/themes) that can be customized by adding your logo and assets.
 

## Premium themes

* https://www.ejeet.net/product-category/wordpress-templates/world-of-warcraft/

* [Themeforest](https://themeforest.net/category/wordpress?tags=gaming) is a great place where to find premium wordpress themes with a game-style design



To hire someone that can create your theme from scratch, we can suggest to use [fiverr](https://www.fiverr.com/categories/programming-tech/wordpress-services/customization?source=category_filters)
