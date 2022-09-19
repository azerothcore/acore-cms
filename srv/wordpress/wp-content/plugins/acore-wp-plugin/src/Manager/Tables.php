<?php

namespace ACore\Manager;

define("ACORE_SOAP_LOGS_TABLENAME", "acore_soap_logs");

/**
 * @since 0.1
 */
function create_acore_soap_logs_table() {
    global $wpdb;
    global $acore_db_version;
    $charset_collate = $wpdb->get_charset_collate();

    //* Create acore_soap_logs table
    $table_name = $wpdb->prefix . ACORE_SOAP_LOGS_TABLENAME;
    $sql = "CREATE TABLE $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    success TINYINT(1) NOT NULL,
    command TEXT NOT NULL,
    result TEXT,
    user_id BIGINT UNSIGNED,
    order_id BIGINT UNSIGNED,
    PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
