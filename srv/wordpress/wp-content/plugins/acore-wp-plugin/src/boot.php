<?php

define('FS_METHOD', 'direct');

require_once ACORE_PATH_PLG . 'src/core/autoload.php';

require_once ACORE_PATH_PLG . 'src/deps/class-tgm-plugin-activation.php';
require_once ACORE_PATH_PLG . 'src/lib/WpClass.php';

require_once ACORE_PATH_PLG . 'src/services/Opts.php';
require_once ACORE_PATH_PLG . 'src/containers/ACoreServices.php';

require_once ACORE_PATH_PLG . 'src/components/AdminPanel/AdminPanel.php';
require_once ACORE_PATH_PLG . 'src/components/CharactersMenu/CharactersMenu.php';
require_once ACORE_PATH_PLG . 'src/components/ServerInfo/ServerInfo.php';

require_once ACORE_PATH_PLG . 'src/hooks/various/tgmplugin_activator.php';

require_once ACORE_PATH_PLG . 'src/hooks/user/include.php';

require_once ACORE_PATH_PLG . 'src/hooks/woocommerce/woocommerce.php';
