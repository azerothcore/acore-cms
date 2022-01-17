<?php

define('FS_METHOD', 'direct');


require_once ACORE_PATH_PLG . 'src/Deps/class-tgm-plugin-activation.php';

require_once ACORE_PATH_PLG . 'src/Components/AdminPanel/AdminPanel.php';
require_once ACORE_PATH_PLG . 'src/Components/CharactersMenu/CharactersMenu.php';
require_once ACORE_PATH_PLG . 'src/Components/ServerInfo/ServerInfo.php';
require_once ACORE_PATH_PLG . 'src/Components/Tools/ToolsInfo.php';
require_once ACORE_PATH_PLG . 'src/Components/UserPanel/UserMenu.php';

require_once ACORE_PATH_PLG . 'src/Hooks/Various/tgmplugin_activator.php';

require_once ACORE_PATH_PLG . 'src/Hooks/User/Include.php';

require_once ACORE_PATH_PLG . 'src/Hooks/WooCommerce/WooCommerce.php';
