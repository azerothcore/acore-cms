<?php

namespace ACore;

use ACore\Defines\Conf;

class UserService
{
    public static function validatePassword($Password)
    {
        if (strlen($Password) > Conf::PASSWORD_LENGTH) {
            return sprintf(__("Password is too long (%s), please use less then %s characters", 'acore_wp_plugin'), strlen($Password), Conf::PASSWORD_LENGTH);
        }

        if (strlen($Password) < 4) {
            return sprintf(__("Password is too short (%s), please use more then 4 characters", 'acore_wp_plugin'), strlen($Password));
        }

        //#### Password looks good
        return true;
    }
}
