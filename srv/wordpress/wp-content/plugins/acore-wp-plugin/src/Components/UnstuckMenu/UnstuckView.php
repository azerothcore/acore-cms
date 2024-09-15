<?php

namespace ACore\Components\UnstuckMenu;


class UnstuckView
{
    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function getUnstuckmenuRender($chars)
    {
        ob_start();

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');

        // Enqueue a dummy script for localization
        wp_enqueue_script('dummy-script', plugins_url('dummy.js', __FILE__), [], null, true);
        // Localize script to pass REST API nonce and URL
        wp_localize_script('dummy-script', 'myData', [
            'nonce' => wp_create_nonce('wp_rest'), // Pass REST API nonce
            'restUrl' => get_rest_url(null, ACORE_SLUG . '/v1/unstuck') // Pass the REST URL
        ]);

        // Add inline script with the localization data
        wp_add_inline_script('dummy-script', '
            jQuery(document).ready(function() {
                jQuery(".unstuck-button").on("click", function() {
                    const charName = jQuery(this).data("char-name");
                    jQuery.ajax({
                        type: "POST",
                        url: myData.restUrl, // Use the localized REST URL
                        data: {
                            charName: charName
                        },
                        headers: {
                            "X-WP-Nonce": myData.nonce // Add the nonce to the request headers
                        },
                        xhrFields: {
                            withCredentials: true // Ensure cookies are sent
                        },
                        success: function(response) {
                            console.log(response);
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                        }
                    });
                });
            });
        ');

?>

        <div class="wrap">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Unstuck</h3>
                        <p>Unstuck your characters and teleport them to the home location of each race</p>
                        <hr>
                        <ul id="acore-characters-unstuck" class="list-unstyled">
                            <?php foreach ($chars as $char) {
                                $currentTime = time();
                                $isDisabled = ($char["time"] > $currentTime);
                                $tooltipText = $isDisabled ? 'Unstuck is on cooldown' : ''; // Tooltip text if disabled
                            ?>
                                <li>
                                    <div class="menu-item-bar">
                                        <div class="menu-item-handle">
                                            <span class="item-title menu-item-title"><?= $char["name"] ?></span>
                                            <span class="item-controls">
                                                <span class="item-type">
                                                    level <?= $char["level"] ?>&ensp;
                                                    <img src="<?= ACORE_URL_PLG . "web/assets/race/" . $char["race"] . ($char["gender"] == 0 ? "m" : "f") . ".webp"; ?>">
                                                    <img src="<?= ACORE_URL_PLG . "web/assets/class/" . $char["class"] . ".webp"; ?>">
                                                </span>
                                                <button
                                                    class="unstuck-button"
                                                    data-char-name="<?= $char["name"] ?>"
                                                    <?= $isDisabled ? 'disabled' : '' ?>
                                                    title="<?= $tooltipText ?>">
                                                    Unstuck
                                                </button>
                                        </div>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
