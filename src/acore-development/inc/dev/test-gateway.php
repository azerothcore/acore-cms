<?php
/**
 * "ACore test payment": completes the order straight away ($order->payment_complete()), which fires
 * woocommerce_payment_complete - the hook every ACore service (character changes, item send, CarbonCopy tickets,
 * SmartStone, ...) runs on. The built-in offline methods (cheque, bank transfer, cash on delivery) never fire it,
 * so they can't test the services.
 *
 * Who can use it: everyone in development (WP_ENVIRONMENT_TYPE development / local), only administrators
 * (manage_woocommerce) otherwise - so players can't take things for free on a live site.
 */

defined('ABSPATH') || exit;

add_filter('woocommerce_payment_gateways', function ($gateways) {
    if (class_exists('WC_Payment_Gateway')) {
        require_once __DIR__ . '/class-acore-test-gateway.php';
        $gateways[] = 'ACore_Theme_Test_Gateway';
    }
    return $gateways;
});
