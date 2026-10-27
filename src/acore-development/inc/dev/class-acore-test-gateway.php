<?php
/**
 * The "ACore test payment" method, see test-gateway.php.
 */

defined('ABSPATH') || exit;

class ACore_Theme_Test_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id                 = 'acore_test';
        $this->method_title       = __('ACore test payment', 'acore-development');
        $this->method_description = __('Marks the order as paid immediately so the ACore services run. Everyone can use it in development; otherwise only administrators see it at checkout.', 'acore-development');
        $this->has_fields         = false;
        $this->supports           = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled'     => [
                'title'   => __('Enable', 'acore-development'),
                'type'    => 'checkbox',
                'label'   => __('Enable the ACore test payment', 'acore-development'),
                'default' => 'yes',
            ],
            'title'       => [
                'title'   => __('Title', 'acore-development'),
                'type'    => 'text',
                'default' => __('Test payment', 'acore-development'),
            ],
            'description' => [
                'title'   => __('Description', 'acore-development'),
                'type'    => 'textarea',
                'default' => __('No money is taken: the order is completed at once and the purchase is delivered in game.', 'acore-development'),
            ],
        ];
    }

    /** Everyone in development, only administrators otherwise. */
    public function is_available()
    {
        if (!acore_theme_is_dev() && !current_user_can('manage_woocommerce')) {
            return false;
        }
        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            wc_add_notice(__('The order could not be found.', 'acore-development'), 'error');
            return ['result' => 'failure'];
        }

        $order->add_order_note(__('Paid with the ACore test payment.', 'acore-development'));
        $order->payment_complete('acore-test-' . $order_id);
        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
