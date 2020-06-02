<?php

namespace ACore;

use \ACore\Defines\Common;

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
        // [TODO] maybe implement
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
