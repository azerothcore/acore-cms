<?php

namespace ACore\Hooks\User;

use ACore\Manager\Common;
use ACore\Manager\ACoreServices;
use ACore\Manager\UserValidator;
use ACore\Manager\Auth\Entity\AccountAccessEntity;
use ACore\Utils\AcoreUtils;
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
        update_user_meta($user_id, 'acore_password_changed_at', current_time('mysql'));
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
    update_user_meta($user->ID, 'acore_password_changed_at', current_time('mysql'));
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

function create_account_if_not_exists($user, $password): void
{    
    try {
        $accRepo = ACoreServices::I()->getAccountRepo();

        if (!$accRepo->findOneByUsername($user->user_login, $password)) {
            create_game_account($user, $password);
        }
    } catch (PDOException $e) {
        AcoreUtils::handle_acore_error(
            'It was not possible to establish a connection with the database. Please check your server settings.',
            function ($message) use($user) {
                AcoreUtils::set_flash_message($message, 'error', $user->ID);
                \wp_redirect(\admin_url('admin.php?page=' . ACORE_SLUG . '-settings'));
            }
        );
        exit;
    } catch (ConnectionException $e) {
        AcoreUtils::handle_acore_error(
            'It was not possible to establish a connection with the database. Please check your server settings.',
            function ($message) use($user) {
                AcoreUtils::set_flash_message($message, 'error', $user->ID);
                \wp_redirect(\admin_url('admin.php?page=' . ACORE_SLUG . '-settings'));
            }
        );
        exit;
    } catch (\Exception $e) {
        AcoreUtils::handle_acore_error($e->getMessage());
    }
}

function create_game_account($user, $password): void
{
    try {
        $soap = ACoreServices::I()->getAccountSoap();
        
        $res = $soap->createAccountFull($user->user_login, $password, $user->user_email, Common::EXPANSION_WOTLK);
        if ($res !== true) {
            throw new \Exception($res->getMessage());
        }

        $res = $soap->setAccountPassword($user->user_login, $password);
        if (!!$res !== true && $res->getMessage()) {
            throw new \Exception($res->getMessage());
        }

        // workaround since soap doesn't work
        update_account_email($user);
    } catch (\Exception $e) {
        AcoreUtils::handle_acore_error($e->getMessage());
    }
}

function update_account_email($user): void
{
    try {
        $conn = ACoreServices::I()->getAccountEm()->getConnection();
        $conn->executeQuery(
            "UPDATE account SET email = :email, reg_mail = :email WHERE username = :username",
            ['email' => $user->user_email, 'username' => $user->user_login]
        );
    } catch (\Exception $e) {
        AcoreUtils::handle_acore_error('Unable to update account email address.');
    }
}

// this requires the plugin "Auto Login New User After Registration"
add_action('user_register',  function ($user_id) {
    // check if the plugin is enabled and the related fields email and passwrd are available
    if (get_option("alnuar_add_password_fields") == true && isset($_POST['password1'])) {
        $user = get_user_by('id', $user_id);
        $user_password = $_POST['password1'];
        create_account_if_not_exists($user, $user_password);
    }
});

// If login but game account doesn't exist
// then create it
add_action('wp_login', function ($user_login, $user) {
    if (wp_is_json_request()) {
        return; // JSON-based login
    }

    if (!key_exists('pwd', $_POST) || !isset($_POST['pwd'])) {
        return; // No password - security fallback
    }

    create_account_if_not_exists($user, $_POST['pwd']);
}, 10, 2);

add_action('graphql_login_after_authenticate', function($user_data, $slug, $input) {
    if (!($user_data instanceof \WP_User)) {
        return;
    }

    if ($slug !== 'password') {
        return;
    }

    if (!isset($input['credentials']['password'])) {
        return;
    }

    create_account_if_not_exists($user_data, $input['credentials']['password']);
}, 10, 3);

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
add_filter('random_password', function ($password) {
    $characters = UserValidator::PASSWORD_CHARS_LIST;
    $password = '';

    for ($i = 0; $i < UserValidator::PASSWORD_LENGTH; $i++) {
        $password .= substr($characters, wp_rand(0, strlen($characters) - 1), 1);
    }

    return $password;
}, 10, 2);


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

    if (!in_array($userExpansion, Common::EXPANSIONS)) {
        $userExpansion = Common::EXPANSION_WOTLK;
    }

?>
    <h3><?php _e("AzerothCore Fields", "blank"); ?></h3>

    <table class="form-table">
        <tr>
            <th><label><?php _e("Expansion", 'acore-wp-plugin'); ?></label></th>
            <td>
                <?php
                $expansions = [
                    Common::EXPANSION_CLASSIC => ['label' => 'Vanilla',               'color' => '#C39361'],
                    Common::EXPANSION_TBC     => ['label' => 'The Burning Crusade',    'color' => '#62C907'],
                    Common::EXPANSION_WOTLK   => ['label' => 'Wrath of the Lich King', 'color' => '#5DACEB'],
                ];
                ?>

                <div class="acore-expansion-wrapper">
                    <div class="acore-expansion-arrow" id="acore-exp-arrow"></div>
                    <div class="acore-expansion-selector">
                        <?php foreach ($expansions as $val => $exp): ?>
                        <label class="acore-expansion-option<?= $userExpansion == $val ? ' is-selected' : '' ?>" style="--exp-color:<?= esc_attr($exp['color']) ?>;">
                            <input type="radio" name="acore-user-game-expansion" value="<?= $val ?>" <?= $userExpansion == $val ? 'checked' : '' ?>>
                            <span class="acore-expansion-label"><?= esc_html($exp['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p class="acore-expansion-warning">
                    &#9888; <strong><?php _e('Warning:', 'acore-wp-plugin'); ?></strong>
                    <?php _e('This will restrict your account to the selected content. For example, selecting Vanilla means you cannot access TBC zones, create Draenei or Blood Elves, make Death Knights, or travel to Northrend.', 'acore-wp-plugin'); ?>
                </p>

                <script>
                (function() {
                    var arrow = document.getElementById('acore-exp-arrow');

                    function updateArrow() {
                        var selected = document.querySelector('.acore-expansion-option.is-selected');
                        var wrapper  = document.querySelector('.acore-expansion-wrapper');
                        if (!selected || !wrapper || !arrow) return;

                        var wRect = wrapper.getBoundingClientRect();
                        var sRect = selected.getBoundingClientRect();
                        var centerX = sRect.left + sRect.width / 2 - wRect.left;
                        var color   = selected.style.getPropertyValue('--exp-color') || '#646970';

                        arrow.style.left           = centerX + 'px';
                        arrow.style.borderTopColor = color;
                    }

                    document.querySelectorAll('.acore-expansion-option input[type="radio"]').forEach(function(radio) {
                        radio.addEventListener('change', function() {
                            document.querySelectorAll('.acore-expansion-option').forEach(function(o) { o.classList.remove('is-selected'); });
                            this.closest('.acore-expansion-option').classList.add('is-selected');
                            updateArrow();
                        });
                    });

                    // Position on load
                    updateArrow();
                })();
                </script>
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

    <h3><?php _e("Other fields...", "blank"); // needed to avoid mess them up with wordpress fields 
        ?></h3>
<?php
}

add_action('personal_options_update',  __NAMESPACE__ . '\save_extra_user_profile_fields');
add_action('edit_user_profile_update',  __NAMESPACE__ . '\save_extra_user_profile_fields');

function save_extra_user_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
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
                throw new \Exception(__("Game account doesn't exist!", "acore-wp-plugin"));
            }
        } else {
            throw new \Exception(__("Game account doesn't exist! Change password to renew your game account.", "acore-wp-plugin"));
        }
    }


    $expansion = isset($_POST['acore-user-game-expansion']) ? intval($_POST['acore-user-game-expansion']) : null;

    if ($expansion === null || !in_array($expansion, Common::EXPANSIONS)) {
        $expansion = Common::EXPANSION_WOTLK;
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

function login_checks()
{
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

add_action('login_enqueue_scripts', __NAMESPACE__ . '\login_checks');

add_action('show_user_profile', __NAMESPACE__ . '\acore_profile_2fa_removal_warning');
add_action('edit_user_profile', __NAMESPACE__ . '\acore_profile_2fa_removal_warning');

function acore_profile_2fa_removal_warning($user) {
    // admin_notices already shows this at the top of plain profile.php — avoid duplicate
    global $pagenow;
    if ($pagenow === 'profile.php' && !isset($_GET['page'])) return;

    $adminLog        = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
    $adminLog        = is_array($adminLog) ? $adminLog : [];
    $lastWebRemoval  = null;
    $lastGameRemoval = null;
    foreach ($adminLog as $entry) {
        if ($entry['type'] === 'website') $lastWebRemoval  = $entry;
        if ($entry['type'] === 'ingame')  $lastGameRemoval = $entry;
    }

    // Check current 2FA state - website
    $totpKey        = get_user_meta($user->ID, 'wp_2fa_totp_key', true);
    $enabledMethods = get_user_meta($user->ID, 'wp_2fa_enabled_methods', true);
    $webActive      = !empty($totpKey) && (
        (is_array($enabledMethods) && in_array('totp', $enabledMethods, true)) ||
        $enabledMethods === 'totp'
    );

    // Check current 2FA state - ingame
    $gameActive = false;
    try {
        $conn   = \ACore\Manager\ACoreServices::I()->getAccountEm()->getConnection();
        $result = $conn->executeQuery('SELECT totp_secret FROM account WHERE username = ?', [strtoupper($user->user_login)]);
        $row    = $result->fetchAssociative();
        $gameActive = $row && $row['totp_secret'] !== null;
    } catch (\Exception $e) {
        // DB unavailable - skip
    }

    $showWebWarning  = $lastWebRemoval  && !$webActive;
    $showGameWarning = $lastGameRemoval && !$gameActive;

    if (!$showWebWarning && !$showGameWarning) return;

    $security_url = admin_url('profile.php?page=' . ACORE_SLUG . '-security');
    ?>
    <div class="notice notice-warning" style="margin:16px 0; padding:10px 14px;">
        <p style="margin:0 0 4px; font-weight:600;">
            <?php _e('Your 2FA was manually removed by a staff member.', 'acore-wp-plugin'); ?>
        </p>
        <?php if ($showWebWarning): ?>
            <p style="margin:4px 0 0; font-size:13px;">
                - <?php printf(
                    __('Website 2FA removed on %1$s by %2$s.', 'acore-wp-plugin'),
                    '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastWebRemoval['timestamp'])) . '</strong>',
                    '<strong>' . esc_html($lastWebRemoval['staff']) . '</strong>'
                ); ?>
            </p>
        <?php endif; ?>
        <?php if ($showGameWarning): ?>
            <p style="margin:4px 0 0; font-size:13px;">
                - <?php printf(
                    __('In-game 2FA removed on %1$s by %2$s.', 'acore-wp-plugin'),
                    '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastGameRemoval['timestamp'])) . '</strong>',
                    '<strong>' . esc_html($lastGameRemoval['staff']) . '</strong>'
                ); ?>
            </p>
        <?php endif; ?>
        <p style="margin:6px 0 0; font-size:13px;">
            <a href="<?= esc_url($security_url) ?>"><?php _e('Go to Security page to re-enable &rarr;', 'acore-wp-plugin'); ?></a>
        </p>
    </div>
    <?php
}

// Remove WP2FA plugin blocks from the standard WordPress profile page only.
// They belong in the dedicated Security sub-page — skip removal when there.
add_action('admin_init', function () {
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    if ($page === ACORE_SLUG . '-security') return; // Security sub-page: leave hooks intact

    global $wp_filter;
    foreach (['show_user_profile', 'edit_user_profile'] as $hook) {
        if (empty($wp_filter[$hook])) continue;
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $key => $cb) {
                $func = $cb['function'];
                $id   = '';
                if (is_array($func) && is_object($func[0]))     $id = get_class($func[0]);
                elseif (is_array($func) && is_string($func[0])) $id = $func[0];
                elseif (is_string($func))                        $id = $func;
                if ($id && (
                    stripos($id, 'WP2FA')      !== false ||
                    stripos($id, 'wp_2fa')     !== false ||
                    stripos($id, 'Two_Factor') !== false
                )) {
                    unset($wp_filter[$hook]->callbacks[$priority][$key]);
                }
            }
        }
    }
}, 99);

// Hide "New Password" fields on the standard profile page
add_filter('show_password_fields', function ($show) {
    global $pagenow;
    if ($pagenow === 'profile.php') return false;
    return $show;
});

// 2FA removal warning at the TOP of Profile > Profile (admin_notices)
add_action('admin_notices', function () {
    global $pagenow;
    if ($pagenow !== 'profile.php') return;
    if (isset($_GET['page'])) return; // sub-pages (Security, etc.) — skip

    $user = wp_get_current_user();
    $adminLog        = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
    $adminLog        = is_array($adminLog) ? $adminLog : [];
    $lastWebRemoval  = null;
    $lastGameRemoval = null;
    foreach ($adminLog as $entry) {
        if ($entry['type'] === 'website') $lastWebRemoval  = $entry;
        if ($entry['type'] === 'ingame')  $lastGameRemoval = $entry;
    }

    $totpKey        = get_user_meta($user->ID, 'wp_2fa_totp_key', true);
    $enabledMethods = get_user_meta($user->ID, 'wp_2fa_enabled_methods', true);
    $webActive      = !empty($totpKey) && (
        (is_array($enabledMethods) && in_array('totp', $enabledMethods, true)) ||
        $enabledMethods === 'totp'
    );
    $gameActive = false;
    try {
        $conn   = \ACore\Manager\ACoreServices::I()->getAccountEm()->getConnection();
        $result = $conn->executeQuery('SELECT totp_secret FROM account WHERE username = ?', [strtoupper($user->user_login)]);
        $row    = $result->fetchAssociative();
        $gameActive = $row && $row['totp_secret'] !== null;
    } catch (\Exception $e) {}

    $showWebWarning  = $lastWebRemoval  && !$webActive;
    $showGameWarning = $lastGameRemoval && !$gameActive;
    if (!$showWebWarning && !$showGameWarning) return;

    $security_url = admin_url('profile.php?page=' . ACORE_SLUG . '-security');
    ?>
    <div class="notice notice-warning" style="padding:10px 14px;">
        <p style="margin:0 0 4px; font-weight:600;"><?php _e('Your 2FA was manually removed by a staff member.', 'acore-wp-plugin'); ?></p>
        <?php if ($showWebWarning): ?>
            <p style="margin:4px 0 0; font-size:13px;">- <?php printf(
                __('Website 2FA removed on %1$s by %2$s. Please re-enable it for account security.', 'acore-wp-plugin'),
                '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastWebRemoval['timestamp'])) . '</strong>',
                '<strong>' . esc_html($lastWebRemoval['staff']) . '</strong>'
            ); ?></p>
        <?php endif; ?>
        <?php if ($showGameWarning): ?>
            <p style="margin:4px 0 0; font-size:13px;">- <?php printf(
                __('In-game 2FA removed on %1$s by %2$s. Please re-enable it for account security.', 'acore-wp-plugin'),
                '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastGameRemoval['timestamp'])) . '</strong>',
                '<strong>' . esc_html($lastGameRemoval['staff']) . '</strong>'
            ); ?></p>
        <?php endif; ?>
        <p style="margin:6px 0 0; font-size:13px;"><a href="<?= esc_url($security_url) ?>"><?php _e('Go to Security page to re-enable &rarr;', 'acore-wp-plugin'); ?></a></p>
    </div>
    <?php
});

add_action('show_user_profile', __NAMESPACE__ . '\acore_profile_recent_connections');

function acore_profile_recent_connections($user) {
    $security_url = admin_url('profile.php?page=' . ACORE_SLUG . '-security');
    $myIp = acore_resolve_client_ip();

    if (isset($_GET['mock_connections'])) {
        $rows = acore_mock_login_history($_GET['mock_connections']);
    } else {
        $rows = acore_get_login_history($user->ID, 10);
    }
    $rows = array_slice($rows, 0, 10);
    ?>
    <h2 class="acore-conn-heading"><span><?php _e('Recent Connections', 'acore-wp-plugin'); ?></span><span class="acore-conn-myip"><?php _e('Your IPv4:', 'acore-wp-plugin'); ?> <?= esc_html($myIp) ?></span></h2>
    <?php if (empty($rows)): ?>
        <p><?php _e('No connections recorded yet.', 'acore-wp-plugin'); ?></p>
    <?php else: ?>
        <p class="acore-conn-note" style="margin:0 0 8px;">
            <?php _e('This only shows the latest 10 logins.', 'acore-wp-plugin'); ?>
            <?php printf(esc_html__('Showing %d entries.', 'acore-wp-plugin'), count($rows)); ?>
        </p>
        <table class="wp-list-table widefat fixed striped acore-conn-table" style="max-width:860px;">
            <thead>
                <tr>
                    <th><?php _e('IP Address', 'acore-wp-plugin'); ?></th>
                    <th><?php _e('Country', 'acore-wp-plugin'); ?></th>
                    <th><?php _e('Date / Time', 'acore-wp-plugin'); ?></th>
                    <th><?php _e('Where', 'acore-wp-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row):
                    $ip      = $row['ip_address'] ?? ($row['ip'] ?? '');
                    $country = $row['country'] ?? '';
                    $when    = $row['login_at'] ?? ($row['timestamp'] ?? '');
                    $src     = (($row['source'] ?? 'website') === 'ingame')
                                ? __('In-game', 'acore-wp-plugin')
                                : __('Website', 'acore-wp-plugin');
                    $cur     = ($ip !== '' && $ip === $myIp);
                ?>
                    <tr<?= $cur ? ' class="acore-conn-current" title="' . esc_attr__('This matches your current IP', 'acore-wp-plugin') . '"' : '' ?>>
                        <td><?= esc_html($ip) ?></td>
                        <td><?= esc_html($country !== '' ? $country : 'Unknown') ?></td>
                        <td><?= esc_html($when !== '' ? acore_format_connection_date($when) : '') ?></td>
                        <td><?= esc_html($src) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:10px;">
            <a href="<?= esc_url($security_url) ?>" class="button"><?php _e('See more', 'acore-wp-plugin'); ?> &rarr;</a>
        </p>
    <?php endif; ?>
    <?php
}
