<?php

namespace ACore\Components\UnstuckMenu;

use ACore\Utils\AcoreCharColors;


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
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.4');
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
                        <ul id="acore-characters-unstuck" class="acore-char-list list-unstyled">
                            <?php foreach ($chars as $char) {
                                $currentTime = time();
                                $isDisabled = ($char["time"] > $currentTime);

                                $tooltipText = $isDisabled ? 'Unstuck is on cooldown' : ''; // Tooltip text if disabled
                                $remainingCDTime = $isDisabled ? $char["time"] - $currentTime : 0;
                                $endTime = $isDisabled ? $char["time"] : 0;
                                $clsStyle = AcoreCharColors::rowStyle(intval($char["class"]));
                            ?>
                                <li>
                                    <div class="acore-char-row" style="<?= esc_attr($clsStyle) ?>">
                                        <span class="acore-char-name"><?= esc_html($char["name"]) ?></span>
                                        <span class="acore-char-meta">
                                            <span class="acore-level">Level <?= intval($char["level"]) ?></span>
                                            <img height="32" width="32" src="<?= ACORE_URL_PLG . "web/assets/race/" . $char["race"] . ($char["gender"] == 0 ? "m" : "f") . ".webp"; ?>">
                                            <img height="32" width="32" src="<?= ACORE_URL_PLG . "web/assets/class/" . $char["class"] . ".webp"; ?>">
                                            <button
                                                class="unstuck-button"
                                                data-char-name="<?= esc_attr($char["name"]) ?>"
                                                <?= $isDisabled ? 'disabled' : '' ?>
                                                title="<?= esc_attr($tooltipText) ?>">
                                                <img src="<?php echo ACORE_URL_PLG . 'web/assets/unstuck/hearthstone.jpg'; ?>" alt="Unstuck">
                                            </button>
                                            <span class="countdown" id="countdown-<?= esc_attr($char['name']) ?>" data-end-time="<?= $endTime ?>">
                                                <?= $isDisabled ? gmdate("H:i:s", $remainingCDTime) : ''; ?>
                                            </span>
                                        </span>
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
