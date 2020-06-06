<?php

namespace ACore;

use \ACore\Defines\Common;
use \ACore\Services;

/**
 * Fires before user profile update errors are returned.
 *
 * @since 2.8.0
 *
 * @param \WP_Error $errors WP_Error object (passed by reference).
 * @param bool     $update  Whether this is a user update.
 * @param \stdClass $user   User object (passed by reference).
 */
function user_profile_update_errors($errors, $update, $user)
{
    $accRepo = Services::I()->getAccountRepo();

    $gameUser = $accRepo->findOneBy(array('email' => $user->user_email));

    if ($update) {
        if ($user->user_login != $gameUser->username)
            $errors->add('invalid_email', __('This email has been already used'));
    }
}


add_action('user_profile_update_errors', __NAMESPACE__ . '\user_profile_update_errors');

/**
 * We cannot make it global since users from other sites could not have
 * an account
 * @param type $user_id
 * @param \WP_User $old_user_data
 */
function user_profile_update($user_id, $old_user_data)
{
    $user = get_userdata($user_id)->data;
    $soap = Services::I()->getAccountSoap();

    if (!is_user_logged_in())
        return;

    // Update user email
    if ($user->user_email != $old_user_data->user_email) {
        /* @var $result \Exception */
        /*$result = $soap->setAccountEmail($user->user_login, $user->user_email);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());*/

        $accRepo = Services::I()->getAccountRepo();

        $acc

        $accRepo->query("UPDATE account SET email= '" . $user->user_email . "' WHERE username = '" . $user->user_login . "'");
    }

    if (isset($_POST['pass1']) && $_POST['pass1'] != '') {
        /* @var $result \Exception */
        $result = $soap->setAccountPassword($user->user_login, $_POST['pass1']);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());
    }
}

add_action('profile_update', __NAMESPACE__ . '\user_profile_update', 10, 2);

add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');

function my_save_extra_profile_fields($user_id)
{

    if (!current_user_can('edit_user', $user_id))
        return false;

    /* Copy and paste this line for additional fields. Make sure to change 'paypal_account' to the field ID. */
    update_usermeta($user_id, 'paypal_account', $_POST['paypal_account']);
}


function prevent_email_domain($user_login, $user_email, $errors)
{
    if (strpos($user_email, '@baddomain.com') != -1) {
        $errors->add('bad_email_domain', '<strong>ERROR</strong>: This email domain is not allowed.');
    }
}
add_action('register_post', 'prevent_email_domain', 10, 3);

/**
 *
 * @param \WP_User $user
 * @param String $new_pass
 */
function user_password_reset($user, $new_pass)
{
    $soap = Services::I()->getAccountSoap();

    if ($result = $soap->setAccountPassword($user->user_login, $new_pass) instanceof \Exception)
        die("Game server error: " . $result->getMessage());
}

add_action('password_reset', __NAMESPACE__ . '\user_password_reset', 10, 2);

function after_delete($user_id)
{
    global $wpdb;

    $user_obj = get_userdata($user_id);
    $email = $user_obj->user_email;
    $username = $user_obj->user_login;

    $soap = Services::I()->getAccountSoap();

    $soap->deleteAccount($username);
}

add_action('wpmu_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);
add_action('wp_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);


// If login but game account doesn't exist
// then create it
add_action('wp_login', function ($user_login, $user) {
    $password = $_POST['pwd'];

    $accRepo = Services::I()->getAccountRepo();

    if (!$accRepo->findOneByUsername($user_login)) {
        $soap = Services::I()->getAccountSoap();

        $soap->createAccountFull($user->user_login, $password, $user->user_email, \ACore\Defines\Common::EXPANSION_WOTLK);

        $soap->setAccountPassword($user->user_login, $password);

        //workaround since soap doesn't work
        $accRepo->query("UPDATE account SET email= '" . $user->user_email . "', reg_mail='" . $user->user_email . "' WHERE username = '" . $user->user_login . "'");
    }
}, 10, 2);


// if login, but exist only game account, then create wordpress account
add_action('wp_authenticate', function ($username, $password) {
    if (!empty($username) && !empty($password)) {

        // we disable auth by email on site for now
        // [TODO] consider a better fix
        if (strpos($username, '@') !== false) {
            return;
        }

        if (!\username_exists($username)) {

            $accRepo = Services::I()->getAccountRepo();

            $userInfo = $accRepo->verifyAccount($username, $password);

            if ($userInfo) {
                $userdata = array(
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => $userInfo->getEmail()
                );

                $user_id = wp_insert_user($userdata);
                if (function_exists("\add_user_to_blog")) {
                    \add_user_to_blog(get_current_blog_id(), $user_id, "subscriber");
                }

                \update_user_meta($user_id, 'active', $userInfo->isLocked());

                //On success
                if (!is_wp_error($user_id)) {
                    //echo "User created : " . $user_id;
                }
            }
        }
    }
}, 30, 2);
