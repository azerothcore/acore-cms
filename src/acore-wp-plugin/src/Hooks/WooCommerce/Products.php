<?php

/* 3D viewer custom section and fields */

// Section 3D viewer under product settings
add_filter('woocommerce_product_data_tabs', 'add_3d_viewer_tab');
function add_3d_viewer_tab($tabs) {
    $tabs['3d_viewer_tab'] = array(
        'label' => __('3D viewer', 'woocommerce'), // Etichetta della scheda
        'target' => '3d_viewer_product_data',
        'class' => array('show_if_simple', 'show_if_variable'), // Tipi di prodotto in cui mostrare la scheda
    );
    return $tabs;
}

// add 3D viewer options
add_action('woocommerce_product_data_panels', 'add_custom_3d_checkbox_fields');
function add_custom_3d_checkbox_fields() {
    echo '<div id="3d_viewer_product_data" class="panel woocommerce_options_panel hidden">';

    // enable/disable 3D
    woocommerce_wp_checkbox( array(
        'id' => '_custom_3d_checkbox',
        'label' => __('3D Viewer', 'woocommerce'),
        'description' => __('Check this box to enable the 3D viewer for this product.', 'woocommerce'),
    ));

    woocommerce_wp_text_input(array(
        'id'            => '_3d_displayid',
        'label'         => __('Force displayid', 'woocommerce'),
        'description'   => __('Enter a specific Creature displayid.', 'woocommerce'),
        'desc_tip'      => 'true',
    ));
    
    woocommerce_wp_select(array(
        'id'            => '_3d_race',
        'label'         => __('Race (optional)', 'woocommerce'),
        'options'       => array(
            '1' => __('Human', 'woocommerce'),
            '2' => __('Orc', 'woocommerce'),
            '3' => __('Dwarf', 'woocommerce'),
            '4' => __('Nightelf', 'woocommerce'),
            '5' => __('Undead', 'woocommerce'),
            '6' => __('Tauren', 'woocommerce'),
            '7' => __('Gnome', 'woocommerce'),
            '8' => __('Troll', 'woocommerce'),
            // '9' => __('Goblin', 'woocommerce'),
            '10' => __('Bloodelf', 'woocommerce'),
            '11' => __('Draenei', 'woocommerce'),
            '0' => __('Random', 'woocommerce'),
        ),
    ));

    woocommerce_wp_select(array(
        'id'            => '_3d_gender',
        'label'         => __('Gender (optional)', 'woocommerce'),
        'options'       => array(
            '2' => __('Random', 'woocommerce'),
            '0' => __('Male', 'woocommerce'),
            '1' => __('Female', 'woocommerce'),
        ),
    ));

    woocommerce_wp_checkbox( array(
        'id' => '_3d_single_item',
        'label' => __('Show single item', 'woocommerce'),
        'description' => __('Show 3D model of the single item (valid only for Head, Shoulder and Weapons).', 'woocommerce'),
    ));

    echo '</div>';
}

add_action('woocommerce_process_product_meta', 'save_3d_checkbox_field');
function save_3d_checkbox_field($post_id) {
    $enable_3d = isset($_POST['_custom_3d_checkbox']) ? 'yes' : 'no';
    update_post_meta($post_id, '_custom_3d_checkbox', $enable_3d);

    $isSingleItem = isset($_POST['_3d_single_item']) ? 'yes' : 'no';
    update_post_meta($post_id, '_3d_single_item', $isSingleItem);

    $custom_text_input = isset($_POST['_3d_displayid']) ? sanitize_text_field($_POST['_3d_displayid']) : '';
    update_post_meta($post_id, '_3d_displayid', $custom_text_input);

    $custom_select = isset($_POST['_3d_race']) ? sanitize_text_field($_POST['_3d_race']) : '';
    update_post_meta($post_id, '_3d_race', $custom_select);

    $custom_select = isset($_POST['_3d_gender']) ? sanitize_text_field($_POST['_3d_gender']) : '';
    update_post_meta($post_id, '_3d_gender', $custom_select);
}
?>
