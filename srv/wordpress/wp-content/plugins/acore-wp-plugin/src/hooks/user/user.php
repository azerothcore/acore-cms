<?php

namespace ACore;

use \ACore\Defines\Common;
use \ACore\Services;

/**
 * Even if the server account has been registered correctly,
 * Maybe we should assure that wordpress user has been registered too, otherwise
 * we have to delete just created game account
 */
function validateUserSignup($errors) {
    // if there are errors yet
    // then we do not create the game account
    if (!empty($errors))
        return $errors;
    
    $accRepo = Services::I()->getAzthAccountRepo();
    $charRepo = Services::I()->getAzthCharactersRepo();
    $connection=$accRepo->getDbConn();

    $username = \esc_sql($_POST['user_login']);

    if (!$username || \username_exists($username)) {
        $errors = '<p>' . "Questo nome utente è già esistente" . '</p>';
        return $errors;
    }

    if ($username !== strtolower($username)) {
        $errors = '<p>' . "Il nome utente deve contenere tutti caratteri minuscoli" . '</p>';
        return $errors;
    }

    $origPass=$_POST["user-password"];
    $password = \esc_sql($origPass);
    
    if ($origPass != $password){
        $errors = '<p>' . "La password $origPass  contiene caratteri speciali non ammessi $password" . '</p>';
        return $errors;
    }

    if (strlen($password) > 16) {
        $errors = '<p>' . "La password non deve superare i 16 caratteri" . '</p>';
        return $errors;
    }

    // strange theme order
    $expansions = array(
        "6" => \ACore\Defines\Common::EXPANSION_WOTLK,
        "7" => \ACore\Defines\Common::EXPANSION_TBC,
        "8" => \ACore\Defines\Common::EXPANSION_CLASSIC
    );

    $email = \esc_sql($_POST['user_email']);
    $expansion = \esc_sql($_POST['custom_fields'][2]);

    //$addon = array_key_exists($expansion, $expansions) ? $expansions[$expansion] : 2;
    $addon = \ACore\Defines\Common::EXPANSION_WOTLK; // force expansion 2
    $soap = Services::I()->getAzthAccountSoap();

    $result = $soap->createAccountFull($username, $password, $email, $addon);

    if ($result instanceof \Exception) {
        // print message using buddypress method
        //bp_core_add_message($result->getMessage(), 'error');
        $errors = '<p>' . "Game Server error: " . $result->getMessage() . '</p>';
    }
    
    
    //workaround since soap doesn't work
    $accRepo->query("UPDATE account SET email= '".$email."', reg_mail='".$email."' WHERE username = '".$username."'");

    $thisAccount = $accRepo->findOneByUsername($username);
    $id=$thisAccount->getId();
    if ($_POST["account-type"] == "full-pvp") {
        $charRepo->query("REPLACE INTO azth_pvp_accounts VALUES(".$id.",1);");
    }
    
    /* CODE FOR LANGUAGE SELECTION
        if (get_current_blog_id() == AZEROTHSHARD_ENG) {
        $accRepo->query("REPLACE INTO azth_account_info (id,custom_lang) VALUES(".$id.",1);");
    }*/

    $accRepo->setAccountLock($username, '1.1.1.1', true);

    return $errors;
}

// we should keep with low priority because we need to do default and captcha check before
//add_action('bp_signup_validate', __NAMESPACE__ . '\validateUserSignup', 999);

function activate_user($uid) {
    $user = get_user_by('id', $uid);
    $username = $user->data->user_login;

    $accRepo = Services::I()->getAzthAccountRepo();
    $accRepo->setAccountLock($username, '127.0.0.1', false);
}

function user_meta_added($user_id, $meta_key, $meta_value) {
    if (!$user = get_userdata($user_id))
        return false;

    $username = $user->user_login;
    $email = $user->user_email;

    switch ($meta_key) {
        // account activation
        //case "wp_" . AZEROTHSHARD . "_capabilities": // for multisite
        case "wp_capabilities":
            if (!\is_user_member_of_blog($user_id/*, AZEROTHSHARD*/)) {
                //Exist's but is not user to the current blog id
                //$result = add_user_to_blog( $blog_id, $user_id, $_POST['user_role']);
                if (array_key_exists("pwd", $_POST) && $_POST["pwd"]) {
                    $soap = Services::I()->getAzthAccountSoap();

                    $password = $_POST["pwd"];
                    //[TODO] We should add a check: if the server is not reachable we should delete
                    // the just created metadata and show an error
                    /* @var $result \Exception */
                    $result = $soap->createAccountFull($username, $password, $email, Common::EXPANSION_WOTLK);
                    if ($result instanceof \Exception) {
                        die("Game server error: " . $result->getMessage());
                    }
                }
            }

            break;
    }
}

/**
 * This action is created by do_action( "add_{$meta_type}_meta"
 * in add_metadata method just before inserting metadata
 */
add_action('add_user_meta', __NAMESPACE__ . '\user_meta_added', 10, 3);

/**
 * We cannot make it global since users from other sites could not have
 * an azerothshard account
 * @param type $user_id
 * @param \WP_User $old_user_data
 */
function user_profile_update($user_id, $old_user_data) {
    $user = get_userdata($user_id)->data;
    $soap = Services::I()->getAzthAccountSoap();

    // Update user email
    if ($user->user_email != $old_user_data->user_email) {
        /* @var $result \Exception */
        /*$result = $soap->setAccountEmail($user->user_login, $user->user_email);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());*/
        
        $accRepo = Services::I()->getAzthAccountRepo();
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

function sph_user_profile_update($message, $thisUser) {
    $soap = Services::I()->getAzthAccountSoap();
    
    // don't know why but simplepress doesn't update wordpress email
    $user_id = wp_update_user( array( 'ID' => $thisUser, 'user_email' => $_POST['email'] ) );

    $user = get_userdata($user_id)->data;
    
    // Update user email  
    $accRepo = Services::I()->getAzthAccountRepo();
    //workaround since soap doesn't work
    $accRepo->query("UPDATE account SET email= '".$user->user_email."' WHERE username = '".$user->user_login."'");

    if (isset($_POST['pass1']) && $_POST['pass1'] != '') {
        /* @var $result \Exception */
        $result = $soap->setAccountPassword($user->user_login, $_POST['pass1']);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());
    }
}

add_filter("sph_UpdateProfileSettings", __NAMESPACE__ . '\sph_user_profile_update',10,2);

/**
 *
 * @param \WP_User $user
 * @param String $new_pass
 */
function user_password_reset($user, $new_pass) {
    $soap = Services::I()->getAzthAccountSoap();

    if ($result = $soap->setAccountPassword($user->user_login, $new_pass) instanceof \Exception)
        die("Game server error: " . $result->getMessage());
}

add_action('password_reset', __NAMESPACE__ . '\user_password_reset', 10, 2);

function after_delete($user_id) {
    global $wpdb;

    $user_obj = get_userdata($user_id);
    $email = $user_obj->user_email;
    $username = $user_obj->user_login;
    
    $soap = Services::I()->getAzthAccountSoap();

    $soap->deleteAccount($username);

    // clean buddypress data on user delete, even if there are other users garbage data
    $wpdb->query("DELETE FROM `wp_bp_xprofile_data` WHERE `user_id` NOT IN ( SELECT ID FROM wp_users );");
}

add_action('wpmu_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);
add_action('wp_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);
