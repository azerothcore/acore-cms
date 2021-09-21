<?php

namespace ACore;

use ACore\Defines\Conf;

class UserService
{
    public static function validatePassword($Password)
    {
        if (strlen($Password) > Conf::PASSWORD_LENGTH) {
            return sprintf(
                __("Password is too long (%s), please use less then %s characters", 'acore_wp_plugin'),
                strlen($Password), Conf::PASSWORD_LENGTH
            );
        }

        if (strlen($Password) < Conf::PASSWORD_MIN_LENGTH) {
            return sprintf(
                __("Password is too short (%s), please use more then 4 characters", 'acore_wp_plugin'),
                strlen($Password)
            );
        }

        if (!preg_match(Conf::PASSWORD_VALID_CHARS, $password )) {
            $invalidChars = explode('', preg_replace(Conf::PASSWORD_VALID_CHARS, '', $password));
            error_log(print_r($invalidChars));
            return sprintf(
                __("Password has the following invalid characters: [%s]", 'acore_wp_plugin'),
                implode(',', $invalidChars)
            );
        }

        //#### Password looks good
        return true;
    }
}
