<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\Common;
use ACore\Manager\ACoreServices;

require_once __DIR__ . "/FieldElements.php";
require_once __DIR__ . "/ItemSend.php";
require_once __DIR__ . "/CharChange.php";
require_once __DIR__ . "/CharTransfer.php";
require_once __DIR__ . "/CarbonCopy.php";
require_once __DIR__ . "/GuildChange.php";
require_once __DIR__ . "/ItemRestoration.php";
require_once __DIR__ . "/TransmogItemSend.php";
require_once __DIR__ . "/TransmogItemsetSend.php";
require_once __DIR__ . "/NameUnlock.php";
require_once __DIR__ . "/CartValidation.php";

// Add WooCommerce customer username to edit/view order admin page
add_action('woocommerce_admin_order_data_after_billing_address', __NAMESPACE__ . '\woo_display_order_username', 10, 1);

function woo_display_order_username($order) {
    global $post;

    $customer_user = \get_post_meta($post->ID, '_customer_user', true);
    echo '<p><strong style="display: block;">' . __('Customer Username', 'acore-wp-plugin') . ':</strong> <a href="user-edit.php?user_id=' . $customer_user . '">' . \get_userdata($customer_user)->user_login . '</a></p>';
}

// avoid some emails to admin
/*
add_action('woocommerce_email_enabled_new_order', function ($enabled, $order) {
    if ($order) {
        $items = $order->get_items();

        $enabled = false;

        foreach ($items as $item) {
            // enable mails if there are product different by this list:
            if ($item["product_id"] == xxx) {
                $enabled = true;
            }
        }


        return $enabled;
    }
}, 10, 2);
*/

// Allow lua for digital downloads ( not used )
add_filter('upload_mimes', function($mimetypes, $user) {
    // Only allow these mimetypes for admins or shop managers
    $manager = $user ? \user_can($user, 'manage_woocommerce') : current_user_can('manage_woocommerce');

    if ($manager) {
        $mimetypes = array_merge($mimetypes, [
            'lua' => 'application/octet-stream'
        ]);
    }

    return $mimetypes;
}, 10, 2);



/*
  ENABLE ADMIN BAR
 */
add_filter('woocommerce_disable_admin_bar', __NAMESPACE__ . '\wc_disable_admin_bar', 10, 1);

function wc_disable_admin_bar($prevent_admin_access) {
    //if (!current_user_can('example_role')) {
    //    return $prevent_admin_access;
    //}
    return false;
}

add_filter('woocommerce_prevent_admin_access', __NAMESPACE__ . '\wc_prevent_admin_access', 10, 1);

function wc_prevent_admin_access($prevent_admin_access) {
    //if (!current_user_can('example_role')) {
    //    return $prevent_admin_access;
    //}
    return false;
}

function wc_checkout_fields($fields) {
    global $woocommerce;
    // if the total is more than 0 then we still need the fields
    /* if ( 0 != $woocommerce->cart->total ) {
      return $fields;
      }
      // return the regular billing fields if we need shipping fields
      if ( $woocommerce->cart->needs_shipping() ) {
      return $fields;
      } */
    // we don't need the billing fields so empty all of them except the email
    unset($fields['billing']['billing_first_name']);
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_phone']);
    //unset($fields['order']['order_comments']);
    unset($fields['billing']['billing_email']);
    return $fields;
}
add_filter('woocommerce_checkout_fields', __NAMESPACE__ . '\wc_checkout_fields', 20);
