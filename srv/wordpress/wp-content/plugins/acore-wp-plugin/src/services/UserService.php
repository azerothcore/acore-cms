<?php

namespace ACore;

use ACore\Defines\Conf;

class UserService
{
    public static function validatePassword($password)
    {
        if (strlen($password) > Conf::PASSWORD_LENGTH) {
            return sprintf(
                __("Password is too long (%s), please use less then %s characters", 'acore_wp_plugin'),
                strlen($password), Conf::PASSWORD_LENGTH
            );
        }

        if (strlen($password) < Conf::PASSWORD_MIN_LENGTH) {
            return sprintf(
                __("Password is too short (%s), please use more then 4 characters", 'acore_wp_plugin'),
                strlen($password)
            );
        }

        $match = [];
        preg_match( Conf::PASSWORD_VALID_CHARS, $password, $match );
        if (!isset($match[0]) || empty($match[0]) || $match[0] !== $password) {
            $invalidChars = str_split(preg_replace(Conf::PASSWORD_VALID_CHARS, '', $password));
            return sprintf(
                __("Password has the following invalid characters: <b>%s</b>", 'acore_wp_plugin'),
                implode(' ', $invalidChars)
            );
        }

        //#### Password looks good
        return true;
    }
}
