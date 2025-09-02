<?php

/* 3D viewer custom fields */
function add_custom_3d_checkbox_field() {
    woocommerce_wp_checkbox( array(
        'id' => '_custom_3d_checkbox',
        'label' => __('3D Viewer', 'woocommerce'),
        'description' => __('Check this box to enable the 3D viewer for this product.', 'woocommerce'),
    ));
}
add_action('woocommerce_product_options_inventory_product_data', 'add_custom_3d_checkbox_field');


function save_3d_checkbox_field($post_id) {
    $custom_checkbox = isset($_POST['_custom_3d_checkbox']) ? 'yes' : 'no';
    update_post_meta($post_id, '_custom_3d_checkbox', $custom_checkbox);
}
add_action('woocommerce_process_product_meta', 'save_3d_checkbox_field');
?>
