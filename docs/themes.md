# Themes

ACore-CMS is based on Wordpress, that means that it's compatible with all its themes.
But it also means that you can hire anyone to create your personalized installable theme.

To install a Wordpress theme is very easy, here you can find the [official guide](https://wordpress.org/support/article/using-themes/)

NOTE: not all wordpress themes provide the support to Woocommerce (the shop plugin), so make sure that your theme has Woocommerce within the list of its features.

## ACore Development (included, default)

ACore-CMS comes with a standalone theme in `src/acore-development` (no parent theme or other dependencies). With Docker it is activated once as the default theme; you can switch to any other theme under *Appearance > Themes*.

It sets up everything needed to try the CMS features (once, automatically, whatever the environment; only what's missing is added):

* an **ACore Development** front page (`[acore_dev_info]`) showing what the CMS needs and whether it's set up: WooCommerce and the ACore plugin, the ACore SOAP / database settings and whether the game server answers (administrators only), your game account and characters, the shop pages, payment methods, and a test product for every service
* product categories and one test product for every ACore service (character changes, transfer, name unlock, item send, item restoration, transmog, guild rename, CarbonCopy tickets, SmartStone)
* a *Store* page with `[acore_store]`: category tiles, product cards and a search box (`[acore_store category="acore-items"]` limits it to categories)
* classic cart and checkout pages (the services add their fields through the classic WooCommerce hooks)
* an **ACore test payment** method that completes the order immediately, so the services really run in game. Everyone can use it in development (`DOCKER_ENVIRONMENT=development` in `.env`, which sets WordPress's `WP_ENVIRONMENT_TYPE`); otherwise only administrators see it.

Run the setup again with `wp acore-dev setup` (`--force` also restores the classic cart / checkout, the front page and the test payment). The look: dark / light mode with a toggle in the menu, a menu with a "More" dropdown, category tabs on the shop pages; settings in *Appearance > Customize > ACore Development*.

## Other themes

Below you can find a list of RPG-based themes as well as Warcraft ones

## Free themes

* [https://www.warcraftwordpress.com/product/vanilla-warcraft-wordpress-theme/](https://web.archive.org/web/20230130150044/https://www.warcraftwordpress.com/)

* You can find a lot of free thems on [wordpress website](https://wordpress.org/themes) that can be customized by adding your logo and assets.
 

## Premium themes

* https://www.ejeet.net/product-category/wordpress-templates/world-of-warcraft/

* [Themeforest](https://themeforest.net/category/wordpress?tags=gaming) is a great place where to find premium wordpress themes with a game-style design



To hire someone that can create your theme from scratch, we can suggest to use [fiverr](https://www.fiverr.com/categories/programming-tech/wordpress-services/customization?source=category_filters)
