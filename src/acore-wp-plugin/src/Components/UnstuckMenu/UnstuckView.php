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
        wp_enqueue_script('jquery');
        wp_enqueue_script('acore-unstuck-js', ACORE_URL_PLG . 'web/assets/unstuck/unstuck.js', array('jquery'), null, true);

?>

        <div class="wrap">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Unstuck</h3>
                        <p>Unstuck your characters and teleport them to the hearthstone location</p>
                        <hr>
                        <ul id="acore-characters-unstuck" class="list-unstyled">
                            <?php foreach ($chars as $char) {
                                $currentTime = time();
                                $isDisabled = ($char["time"] > $currentTime);

                                $tooltipText = $isDisabled ? 'Unstuck is on cooldown' : ''; // Tooltip text if disabled
                                $remainingCDTime = $isDisabled ? $char["time"] - $currentTime : 0;
                                $endTime = $isDisabled ? $char["time"] : 0;
                            ?>
                                <li>
                                    <div class="menu-item-bar">
                                        <div class="menu-item-handle">
                                            <span class="item-title menu-item-title"><?= $char["name"] ?></span>
                                            <span class="item-controls">
                                                <span class="item-type">
                                                    level <?= $char["level"] ?>&ensp;
                                                    <img height="32" width="32" src="<?= ACORE_URL_PLG . "web/assets/race/" . $char["race"] . ($char["gender"] == 0 ? "m" : "f") . ".webp"; ?>">
                                                    <img height="32" width="32" src="<?= ACORE_URL_PLG . "web/assets/class/" . $char["class"] . ".webp"; ?>">
                                                </span>
                                                <button
                                                    class="unstuck-button"
                                                    data-char-name="<?= $char["name"] ?>"
                                                    <?= $isDisabled ? 'disabled' : '' ?>
                                                    title="<?= $tooltipText ?>">
                                                    <img src="<?php echo ACORE_URL_PLG . 'web/assets/unstuck/hearthstone.jpg'; ?>" alt="Unstuck">
                                                </button>
                                                <span class="countdown" id="countdown-<?= $char['name'] ?>" data-end-time="<?= $endTime ?>">
                                                    <?= $isDisabled ? gmdate("H:i:s", $remainingCDTime) : ''; ?>
                                                </span>
                                        </div>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <script>
            var unstuckData = {
                nonce: "<?php echo wp_create_nonce('wp_rest'); ?>",
                restUrl: "<?php echo get_rest_url(null, ACORE_SLUG . '/v1/unstuck'); ?>"
            };
        </script>
<?php
        return ob_get_clean();
    }
}
