<?php


namespace ACore\Hooks\Head;

function getHead()
{
    ob_start();
?>
    <link rel="stylesheet" href="<?php echo ACORE_URL_PLG ?>src/css/style.css">

    <!-- wowhead -->
    <!--<script type="text/javascript" src="//wow.zamimg.com/widgets/power.js"></script><script>var wowhead_tooltips = { "colorlinks": true, "iconizelinks": true, "renamelinks": true }</script>-->

    <!-- wowgaming -->
    <script type="text/javascript" src="https://wowgaming.altervista.org/aowow/static/widgets/power.js"></script>
    <script>
        var aowow_tooltips = {
            "colorlinks": true,
            "iconizelinks": true,
            "renamelinks": true
        }
    </script>
<?php
    echo ob_get_clean();
}

add_action('wp_head', __NAMESPACE__ . '\getHead');


function customLoginLogo() {
    echo '<style type="text/css">
	h1 a {
            background-image: none !important;
        }
	</style>';
}

add_action('login_head', __NAMESPACE__ . '\customLoginLogo');
