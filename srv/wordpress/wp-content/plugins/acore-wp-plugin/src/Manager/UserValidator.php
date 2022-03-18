<?php

namespace ACore\Manager;

use ACore\Manager;

class UserValidator {

    const PASSWORD_MIN_LENGTH = 4;
    const PASSWORD_LENGTH = 16;
    const USERNAME_LENGTH = 16;
    const PASSWORD_VALID_CHARS = "/[a-zA-Z0-9\!\#\$%\&'\(\)\*\+,\-\.\/\:;\<\=\>\?@\[\]\^_`\{\}~]+/";
    const PASSWORD_CHARS_LIST = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	/**
	 * Static-only class.
	 */
	private function __construct() {}

    public static function validatePassword($password)
    {
        if (strlen($password) > UserValidator::PASSWORD_LENGTH) {
            return sprintf(
                __("Password is too long (%s), please use less then %s characters", 'acore_wp_plugin'),
                strlen($password), PASSWORD_LENGTH
            );
        }

        if (strlen($password) < UserValidator::PASSWORD_MIN_LENGTH) {
            return sprintf(
                __("Password is too short (%s), please use more then 4 characters", 'acore_wp_plugin'),
                strlen($password)
            );
        }

        $match = [];
        preg_match( UserValidator::PASSWORD_VALID_CHARS, $password, $match );
        if (!isset($match[0]) || empty($match[0]) || $match[0] !== $password) {
            $invalidChars = str_split(preg_replace(UserValidator::PASSWORD_VALID_CHARS, '', $password));
            return sprintf(
                __("Password has the following invalid characters: <b>%s</b>", 'acore_wp_plugin'),
                implode(' ', $invalidChars)
            );
        }

        // password looks good
        return true;
    }
}
