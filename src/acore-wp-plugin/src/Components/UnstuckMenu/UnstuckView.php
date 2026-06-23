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
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.5');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        wp_enqueue_script('jquery');
        wp_enqueue_script('acore-unstuck-js', ACORE_URL_PLG . 'web/assets/unstuck/unstuck.js', array('jquery'), null, true);
        wp_localize_script('acore-unstuck-js', 'unstuckData', array(
            'restUrl' => rest_url(ACORE_SLUG . '/v1/unstuck'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ));

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
                                $clsStyle = AcoreCharColors::rowStyle(intval($char["class"]), intval($char["race"]));
                            ?>
                                <li>
                                    <div class="acore-char-row" style="<?= esc_attr($clsStyle) ?>">
                                        <span class="acore-char-name"><?= esc_html($char["name"]) ?></span>
                                        <span class="acore-char-meta">
                                            <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char["level"])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char["level"]))) ?>">Level <?= intval($char["level"]) ?></span>
                                            <img class="race-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char["race"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/race/" . intval($char["race"]) . (intval($char["gender"]) == 0 ? "m" : "f") . ".webp") ?>">
                                            <img class="class-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char["class"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/class/" . intval($char["class"]) . ".webp") ?>">
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

<?php
        return ob_get_clean();
    }
}
