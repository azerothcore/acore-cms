# Themes

ACore-CMS is based on Wordpress, that means that it's compatible with all its themes.
But it also means that you can hire anyone to create your personalized installable theme.

To install a Wordpress theme is very easy, here you can find the [official guide](https://wordpress.org/support/article/using-themes/)

NOTE: not all wordpress themes provide the support to Woocommerce (the shop plugin), so make sure that your theme has Woocommerce within the list of its features.

## ACore Theme (included)

ACore-CMS comes with a small standalone theme in `src/acore-theme`, mainly to test the CMS features (no parent theme or other dependencies):

* a dark / light look; visitors switch with the toggle in the menu and their choice is remembered
* a header menu that shows the first entries and puts the rest in a "More" dropdown
* a store page: put `[acore_store]` on any page for category tiles, product cards and a search box (`[acore_store category="acore-items"]` limits it to categories)
* category tabs and badges on the WooCommerce shop pages

Settings: *Appearance > Customize > ACore Theme* (default mode, menu entries before "More", a price label such as "ChromiePoints").

With Docker, the ACore Theme is activated on a fresh install (or in development). On an existing site, activate it under *Appearance > Themes*.

### Test shop (development only)

When the CMS runs in development (`DOCKER_ENVIRONMENT=development` in `.env`, which sets WordPress's `WP_ENVIRONMENT_TYPE`), the theme sets up a shop ready for testing:

* product categories and one test product for every ACore service (character changes, item send, transmog, guild rename, CarbonCopy tickets, SmartStone, ...)
* a *Store* page with `[acore_store]`
* classic cart and checkout pages (the services add their fields through the classic WooCommerce hooks)
* an **ACore test payment** method that completes the order immediately, so the services really run in game

It runs once automatically and only adds what's missing; run it again with `wp acore-theme dev-setup` (add `--force` to restore the classic cart / checkout and re-enable the test payment). None of this exists in production.

## Other themes

Below you can find a list of RPG-based themes as well as Warcraft ones

## Free themes

* [https://www.warcraftwordpress.com/product/vanilla-warcraft-wordpress-theme/](https://web.archive.org/web/20230130150044/https://www.warcraftwordpress.com/)

* You can find a lot of free thems on [wordpress website](https://wordpress.org/themes) that can be customized by adding your logo and assets.
 

## Premium themes

* https://www.ejeet.net/product-category/wordpress-templates/world-of-warcraft/

* [Themeforest](https://themeforest.net/category/wordpress?tags=gaming) is a great place where to find premium wordpress themes with a game-style design



To hire someone that can create your theme from scratch, we can suggest to use [fiverr](https://www.fiverr.com/categories/programming-tech/wordpress-services/customization?source=category_filters)
