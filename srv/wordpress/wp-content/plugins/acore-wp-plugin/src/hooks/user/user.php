<?php

namespace ACore;

use \ACore\Defines\Common;
use \ACore\Services;

/**
 * We cannot make it global since users from other sites could not have
 * an azerothshard account
 * @param type $user_id
 * @param \WP_User $old_user_data
 */
function user_profile_update($user_id, $old_user_data) {
    $user = get_userdata($user_id)->data;
    $soap = Services::I()->getAccountSoap();

    // Update user email
    if ($user->user_email != $old_user_data->user_email) {
        /* @var $result \Exception */
        /*$result = $soap->setAccountEmail($user->user_login, $user->user_email);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());*/
        
        $accRepo = Services::I()->getAccountRepo();
        //workaround since soap doesn't work
        $accRepo->query("UPDATE account SET email= '".$user->user_email."' WHERE username = '".$user->user_login."'");
    }

    if (isset($_POST['pass1']) && $_POST['pass1'] != '') {
        /* @var $result \Exception */
        $result = $soap->setAccountPassword($user->user_login, $_POST['pass1']);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());
    }
}

add_action('profile_update', __NAMESPACE__ . '\user_profile_update', 10, 2);

/**
 *
 * @param \WP_User $user
 * @param String $new_pass
 */
function user_password_reset($user, $new_pass) {
    $soap = Services::I()->getAccountSoap();

    if ($result = $soap->setAccountPassword($user->user_login, $new_pass) instanceof \Exception)
        die("Game server error: " . $result->getMessage());
}

add_action('password_reset', __NAMESPACE__ . '\user_password_reset', 10, 2);

function after_delete($user_id) {
    global $wpdb;

    $user_obj = get_userdata($user_id);
    $email = $user_obj->user_email;
    $username = $user_obj->user_login;
    
    $soap = Services::I()->getAccountSoap();

    $soap->deleteAccount($username);
}

add_action('wpmu_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);
add_action('wp_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);
