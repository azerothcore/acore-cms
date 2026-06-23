<?php

namespace ACore\Hooks\User;

use ACore\Manager\ACoreServices;
use ACore\Manager\UserValidator;

add_action('admin_init', __NAMESPACE__ . '\acore_process_security_password');

function acore_process_security_password() {
    if (!is_user_logged_in() || !isset($_POST['acore_change_password'])) {
        return;
    }

    $user         = wp_get_current_user();
    $security_url = admin_url('profile.php?page=' . ACORE_SLUG . '-security');

    if (!wp_verify_nonce($_POST['acore_pw_nonce'] ?? '', 'acore_security_change_password')) {
        acore_pw_set_message($user->ID, 'error', __('Security check failed.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    if (\ACore\Components\ServerInfo\acore_website_2fa_enabled($user->ID)
        && !get_transient(\ACore\Components\ServerInfo\acore_2fa_unlock_key($user->ID))) {
        acore_pw_set_message($user->ID, 'error', __('Please verify your 2FA code before changing your password.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    $oldPass     = isset($_POST['acore_old_pass'])     ? (string) wp_unslash($_POST['acore_old_pass'])     : '';
    $newPass     = isset($_POST['acore_new_pass'])     ? (string) wp_unslash($_POST['acore_new_pass'])     : '';
    $confirmPass = isset($_POST['acore_confirm_pass']) ? (string) wp_unslash($_POST['acore_confirm_pass']) : '';

    if (!wp_check_password($oldPass, $user->user_pass, $user->ID)) {
        acore_pw_set_message($user->ID, 'error', __('Current password is incorrect.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    if ($newPass !== $confirmPass) {
        acore_pw_set_message($user->ID, 'error', __('New passwords do not match.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    if (wp_check_password($newPass, $user->user_pass, $user->ID)) {
        acore_pw_set_message($user->ID, 'error', __('New password must be different from your current password.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    $validation = UserValidator::validatePassword($newPass);
    if ($validation !== true) {
        acore_pw_set_message($user->ID, 'error', $validation);
        wp_redirect($security_url);
        exit;
    }

    if (acore_password_is_reused($user->ID, $newPass)) {
        acore_pw_set_message($user->ID, 'error', __('This password has been used before. Please choose a different one.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    $soapError = null;
    try {
        $soap   = ACoreServices::I()->getAccountSoap();
        $result = $soap->setAccountPassword($user->user_login, $newPass);
        if ($result instanceof \Exception) {
            $soapError = $result->getMessage();
        } elseif (!is_string($result) || stripos($result, 'changed') === false) {
            $raw = is_string($result) ? trim($result) : '';
            $soapError = $raw !== '' ? $raw : __('the game server rejected the change', 'acore-wp-plugin');
        }
    } catch (\Exception $e) {
        $soapError = $e->getMessage();
    }

    if ($soapError !== null) {
        error_log('[acore] in-game password sync failed (user ID ' . $user->ID . '): ' . $soapError);
        acore_pw_set_message($user->ID, 'error', __('The in-game password could not be updated, so your website password was left unchanged. Please try again later.', 'acore-wp-plugin'));
        wp_redirect($security_url);
        exit;
    }

    acore_password_record_history($user->ID, $user->user_pass);

    wp_set_password($newPass, $user->ID);
    update_user_meta($user->ID, 'acore_password_changed_at', current_time('mysql'));

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, false);

    acore_pw_set_message($user->ID, 'success', __('Password updated successfully.', 'acore-wp-plugin'));
    wp_redirect($security_url);
    exit;
}

function acore_pw_set_message($user_id, $type, $text) {
    set_transient('acore_pw_msg_' . $user_id, ['type' => $type, 'text' => $text], 60);
}

function acore_pw_get_message($user_id) {
    $msg = get_transient('acore_pw_msg_' . $user_id);
    if ($msg) {
        delete_transient('acore_pw_msg_' . $user_id);
    }
    return $msg ?: null;
}

function acore_password_is_reused($user_id, string $newPass): bool {
    if (get_option('acore_allow_old_passwords', '0') === '1') {
        return false;
    }
    $history = get_user_meta($user_id, 'acore_password_history', true) ?: [];
    foreach ($history as $oldHash) {
        if (wp_check_password($newPass, $oldHash, $user_id)) {
            return true;
        }
    }
    return false;
}

function acore_password_record_history($user_id, string $currentHash): void {
    $history = get_user_meta($user_id, 'acore_password_history', true) ?: [];
    array_unshift($history, $currentHash);
    update_user_meta($user_id, 'acore_password_history', array_slice($history, 0, 10));
}
