<?php

namespace ACore\Hooks\User;

use ACore\Manager\Common;
use ACore\Manager\ACoreServices;
use ACore\Manager\UserValidator;
use ACore\Manager\Auth\Entity\AccountAccessEntity;
use Doctrine\DBAL\Exception\ConnectionException;
use PDOException;

/**
 * Fires before user profile update errors are returned.
 * It's called when an user is added to a blog or updated (not at very first registration)
 *
 * @since 2.8.0
 *
 * @param \WP_Error $errors WP_Error object (passed by reference).
 * @param bool     $update  Whether this is a user update.
 * @param \stdClass $user   User object (passed by reference).
 */
function user_profile_update_errors($errors, $update, $user)
{
    if (strpos($user->user_login, '@') !== false) {
        $errors->add('invalid_username', sprintf(__('ACore Error: Username cannot contain: %s', 'acore-wp-plugin'), "@"));
        return $errors;
    }

    validateComplexPassword($errors);

    $accRepo = ACoreServices::I()->getAccountRepo();

    $gameUser = $accRepo->findOneByUsername($user->user_login);
    if ($update) {
        $userByEmail = $accRepo->findOneBy(array('email' => $user->user_email));
        if ($gameUser && $userByEmail && strtoupper($user->user_login) != strtoupper($userByEmail->getUsername())) {
            $errors->add('invalid_email', __('ACore Error: This email has been already used', 'acore-wp-plugin'));
        }
    }
}

add_action('user_profile_update_errors', __NAMESPACE__ . '\user_profile_update_errors', 10, 3);

function user_registration_errors($errors, $sanitized_user_login, $user_email)
{
    if (strpos($sanitized_user_login, '@') !== false) {
        $errors->add('invalid_username', sprintf(__('ACore Error: Username cannot contain: %s', 'acore-wp-plugin'), "@"));
        return $errors;
    }

    validateComplexPassword($errors);

    $accRepo = ACoreServices::I()->getAccountRepo();

    $gameUserByLogin = $accRepo->findOneByUsername($sanitized_user_login);

    $gameUserByEmail = $accRepo->findOneBy(array('email' => $user_email));

    if ($gameUserByLogin) {
        $errors->add('invalid_email', __('ACore Error: This username has been already taken', 'acore-wp-plugin'));
    }

    if ($gameUserByEmail) {
        $errors->add('invalid_email', __('ACore Error: This email has been already taken', 'acore-wp-plugin'));
    }

    return $errors;
}

add_filter('registration_errors', __NAMESPACE__ . '\user_registration_errors', 10, 3);

/**
 * This is called when an user is updated
 *
 * We cannot make it global since users from other sites could not have
 * an account
 * @param type $user_id
 * @param \WP_User $old_user_data
 */
function user_profile_update($user_id, $old_user_data)
{
    $user = get_userdata($user_id)->data;
    $soap = ACoreServices::I()->getAccountSoap();

    if (!is_user_logged_in()) {
        return;
    }

    // Update user email
    if ($user->user_email != $old_user_data->user_email) {
        /* @var $result \Exception */
        /*$result = $soap->setAccountEmail($user->user_login, $user->user_email);
        if ($result instanceof \Exception)
            die("Game server error: " . $result->getMessage());*/

        $conn = ACoreServices::I()->getAccountEm()->getConnection();

        $conn->executeQuery(
            "UPDATE account SET email = :email WHERE username = :username",
            array('email' => $user->user_email, 'username' => $user->user_login)
        );
    }

    if (isset($_POST['pass1']) && $_POST['pass1'] != '') {
        /* @var $result \Exception */
        $result = $soap->setAccountPassword($user->user_login, $_POST['pass1']);
        if ($result instanceof \Exception) {
            die(sprintf(__("#2 ACore Error: Game server error: %s", 'acore-wp-plugin'), $result->getMessage()));
        }
    }
}

add_action('profile_update', __NAMESPACE__ . '\user_profile_update', 10, 2);

do_action('validate_password_reset', function (\WP_Error $errors, \WP_User $user) {
    validateComplexPassword($errors);
}, 10, 2);

/**
 *
 * @param \WP_User $user
 * @param String $new_pass
 */
function user_password_reset($user, $new_pass)
{
    create_account_if_not_exists($user, $new_pass);

    $soap = ACoreServices::I()->getAccountSoap();

    $result = $soap->setAccountPassword($user->user_login, $new_pass);
    if ($result instanceof \Exception) {
        die(sprintf(__("#1 ACore Error: Game server error: %s", 'acore-wp-plugin'), $result->getMessage()));
    }
}

add_action('password_reset', __NAMESPACE__ . '\user_password_reset', 10, 2);

function after_delete($user_id)
{
    global $wpdb;

    $user_obj = get_userdata($user_id);
    $username = $user_obj->user_login;

    $soap = ACoreServices::I()->getAccountSoap();

    $soap->deleteAccount($username);
}

add_action('wpmu_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);
add_action('wp_delete_user', __NAMESPACE__ . '\after_delete', 10, 1);

function create_account_if_not_exists($user, $password): void {
    try {
        $accRepo = ACoreServices::I()->getAccountRepo();
    } catch (PDOException $e) {
        wp_redirect(admin_url('admin.php?page=' . ACORE_SLUG . '-settings'));
        echo "<div class='notice notice-error'><p>It was not possible to entablish a connection with the database. Please check your server settings.</p></div><";
        exit;
    } catch (ConnectionException $e) {
        wp_redirect(admin_url('admin.php?page=' . ACORE_SLUG . '-settings'));
        echo "<div class='notice notice-error'><p>It was not possible to entablish a connection with the database. Please check your server settings.</p></div><";
        exit;
    }

    if (!$accRepo->findOneByUsername($user->user_login)) {
        $soap = ACoreServices::I()->getAccountSoap();

        $res = $soap->createAccountFull($user->user_login, $password, $user->user_email, Common::EXPANSION_WOTLK);

        if ($res !== true) {
            die($res->getMessage());
        }

        $res = $soap->setAccountPassword($user->user_login, $password);

        if (!!$res !== true && $res->getMessage()) {
            die($res->getMessage());
        }

        // workaround since soap doesn't work
        $conn = ACoreServices::I()->getAccountEm()->getConnection();

        $conn->executeQuery(
            "UPDATE account SET email = :email, reg_mail = :email WHERE username = :username",
            array('email' => $user->user_email, 'username' => $user->user_login)
        );
    }
}

// this requires the plugin "Auto Login New User After Registration"
add_action( 'user_register',  function ($user_id) {
    // check if the plugin is enabled and the related fields email and passwrd are available
    if (get_option("alnuar_add_password_fields") == true && isset($_POST['password1'])) {
        $user = get_user_by( 'id', $user_id );
        $user_password = $_POST['password1'];
        create_account_if_not_exists($user, $user_password);
    }
});


// If login but game account doesn't exist
// then create it
add_action('wp_login', function ($user_login, $user) {
    create_account_if_not_exists($user, $_POST['pwd']);
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
            try {
                $accRepo = ACoreServices::I()->getAccountRepo();
                $userInfo = $accRepo->verifyAccount($username, $password);
            } catch (PDOException $e) {
                $userInfo = null;
            } catch (ConnectionException $e) {
                $userInfo = null;
            }

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

/**
 * Helper function used to validate user password based on azerothcore limits
 * for in different Wordpress actions
 *
 */
function validateComplexPassword($errors)
{

    $password = (isset($_POST['pass1']) && trim($_POST['pass1'])) ? $_POST['pass1'] : null;

    if (empty($password) || ($errors->get_error_data('pass')))
        return $errors;

    $passwordValidation = UserValidator::validatePassword($password);

    if ($passwordValidation !== true) {
        $errors->add("pass", "<strong>ERROR</strong>: " . $passwordValidation . ".");
    }

    return $errors;
}

/**
 * AzerothCore supports a limited length password
 * This filter, will generate a random password
 * based on a list of valid characters
 */

add_filter( 'random_password', function (/* $pass */) {
    $characters = UserValidator::PASSWORD_CHARS_LIST;
    $password = '';
    for( $i = 0; $i < UserValidator::PASSWORD_LENGTH; $i++ ) {
        $password .= substr( $characters , wp_rand( 0, strlen( $characters ) - 1 ), 1 );
    }
    return $password;
}, 10, 1 );


/**
 * User extra fields
 */
add_action('show_user_profile', __NAMESPACE__ . '\extra_user_profile_fields');
add_action('edit_user_profile', __NAMESPACE__ . '\extra_user_profile_fields');

function extra_user_profile_fields($user)
{
    $accRepo = ACoreServices::I()->getAccountRepo();
    $gameUser = $accRepo->findOneByUsername($user->user_login);
    if (!$gameUser) {
        return;
    }

    $userExpansion = $gameUser->getExpansion();

    $curUser = \wp_get_current_user();


    $curGameUser = $curUser->ID != $user->ID ? $accRepo->findOneByUsername($user->user_login) : $gameUser;

    if (!in_array($userExpansion,Common::EXPANSIONS)) {
        $userExpansion = Common::EXPANSION_WOTLK;
    }

    ?>
    <h3><?php _e("AzerothCore Fields", "blank"); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="acore-user-game-expansion"><?php _e("Expansion", 'acore-wp-plugin'); ?></label></th>
            <td>
                <select id="acore-user-game-expansion" name="acore-user-game-expansion">
                    <?php
                    foreach (Common::EXPANSIONS as $key => $value) {
                        ?><option value=<?=$value?> <?=$userExpansion == $value ? "selected" : ""?>><?=$key?></option>
                  <?php
                    }
                  ?>
                </select>
                <span class="description"><?php _e("Game expansion to enable", 'acore-wp-plugin'); ?></span>
            </td>
        </tr>
        <?php
            /*
            ?>
            <tr>
                <th><label for="acore-user-account-access"><?php _e("Account Level", 'acore-wp-plugin'); ?></label></th>
                <td>
                    <select id="acore-user-account-access" name="acore-user-account-access">
                        <?php
                        foreach (Common::ACCOUNT_LEVELS as $key => $value) {
                            ?><option value=<?=$value?> <?=$userExpansion == $value ? "selected" : ""?>><?=$key?></option>
                    <?
                        }
                    ?>
                    </select>
                    <span class="description"><?php _e("In-Game account level", 'acore-wp-plugin'); ?></span>
                </td>
            </tr>
            <?php
            */
        ?>
    </table>

    <h3><?php _e("Other fields...", "blank"); // needed to avoid mess them up with wordpress fields ?></h3>
<?php
}

add_action( 'personal_options_update',  __NAMESPACE__ . '\save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update',  __NAMESPACE__ . '\save_extra_user_profile_fields' );

function save_extra_user_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    $user = get_user_by('id', $user_id);
    $accRepo = ACoreServices::I()->getAccountRepo();
    $accMgr = ACoreServices::I()->getAccountEm();
    /**
     * @var ACore\Manager\Auth\Entity\AccountEntity
     */
    $gameUser = $accRepo->findOneByUsername($user->user_login);
    if (!$gameUser) {
        if (isset($_POST['pass1']) && $_POST['pass1'] != '') {

            create_account_if_not_exists($user, $_POST['pass1']);

            $gameUser = $accRepo->findOneByUsername($user->user_login);
            if (!$gameUser) {
                throw new \Exception(__("Game account doesn't exist!","acore-wp-plugin"));
            }
        } else {
            throw new \Exception(__("Game account doesn't exist! Change password to renew your game account.","acore-wp-plugin"));
        }
    }


    $expansion = $_POST['acore-user-game-expansion'];

    if (!$expansion || !in_array($expansion,Common::EXPANSIONS)) {
        $expansion = Common::EXPANSION_WOTLK;
        // throw new \Exception(__("Invalid Expansion!", "acore-wp-plugin"));
    }

    if ($expansion != $gameUser->getExpansion()) {
        $gameUser->setExpansion($expansion);

        $accMgr->persist($gameUser);
        $accMgr->flush();
    }

    // if (false) {
    //     $accessEntity = new AccountAccessEntity();
    //     $accessEntity->setId($gameUser->getId());
    //     $accessEntity->setGmLevel(0);
    //     $accessEntity->setRealmID(-1);

    //     $accMgr->persist($accessEntity);
    //     $accMgr->flush();
    // }
}

function login_checks() {
    ?>
    <script>
        const regex = <?= UserValidator::PASSWORD_VALID_CHARS ?>;

        function errorFactory(id, parent) {
            const elemError = document.createElement("p");
                elemError.style.color = "red";
                elemError.id = id;
                parent.appendChild(elemError)
        }

        function checkError(fieldLength, id, errorText) {
            let error = "";
            if (fieldLength > <?= UserValidator::USERNAME_LENGTH ?>) { // this will be used also for password length
                error = errorText;
            }
            document.querySelector(id).innerHTML = error;

            return error != "";
        }

        function validPasswordChars(password) {
            for (const c of password.split('')) {
                if (!regex.test(c)) {
                    return false;
                }
            }

            return true;
        }

        window.onload = function() {
            // register form
            const registerForm = document.querySelector("#registerform");
            if (registerForm) {
                const username = document.querySelector("#user_login");
                const password = document.querySelector("#password1");

                if (username) {
                    errorFactory("username-error", username.parentElement);
                }
                if (password) {
                    errorFactory("password-error", password.parentElement);
                }

                registerForm.onsubmit = function() {
                    if (username) {
                        const isInvalidUsernameLength = checkError(username.value.length, "#username-error", "Username must have maximum 16 characters!");
                        if (isInvalidUsernameLength) {
                            return false;
                        }
                    }

                    if (password) {
                        if (!validPasswordChars(password.value)) {
                          document.querySelector("#password-error").innerHTML = "The password have to include these characters: " + regex.toString().replaceAll("\\", "");
                          return false;
                        }

                        const isInvalidPasswordLength = checkError(password.value.length, "#password-error", "Password must have maximum 16 characters!");
                        if (isInvalidPasswordLength) {
                            return false;
                        }
                    }

                    return true;
                };
            }

            // reset password form
            const resetPasswordForm = document.querySelector("#resetpassform");
            if (resetPasswordForm) {
                resetPasswordForm.onsubmit = function() {
                    const pass1 = document.querySelector("#pass1");
                    if (pass1) {
                        errorFactory("pass1-error", pass1.parentElement);
                    }

                    if (!validPasswordChars(pass1.value)) {
                      document.querySelector("#pass1-error").innerHTML = "The password have to include these characters: " + regex.toString().replaceAll("\\", "");
                      return false;
                    }

                    const isInvalidPasswordLength = checkError(pass1.value.length, "#pass1-error", "Password must have maximum 16 characters!");
                    if (isInvalidPasswordLength) {
                        return false;
                    }

                    return true;
                }
            }
        };
    </script>
    <?php
}

add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\login_checks' );

