<?php
/**
 * DEVELOPMENT ONLY: "ACore test payment".
 *
 * Completes the order straight away ($order->payment_complete()), which fires woocommerce_payment_complete - the
 * hook every ACore service (character changes, item send, CarbonCopy tickets, SmartStone, ...) runs on. The built-in
 * offline methods (cheque, bank transfer, cash on delivery) never fire it, so they can't test the services.
 *
 * Loaded only when wp_get_environment_type() is "development" or "local" (see functions.php).
 */

defined('ABSPATH') || exit;

add_filter('woocommerce_payment_gateways', function ($gateways) {
    if (class_exists('WC_Payment_Gateway')) {
        require_once __DIR__ . '/class-acore-test-gateway.php';
        $gateways[] = 'ACore_Theme_Test_Gateway';
    }
    return $gateways;
});
