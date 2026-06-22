<?php

namespace ACore\Components\MailReturnMenu;

use ACore\Utils\AcoreCharColors;


class MailReturnView
{
    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function getMailReturnRender($chars)
    {
        ob_start();

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.5');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        wp_enqueue_script('jquery');
        wp_enqueue_script('power-js', 'https://wow.zamimg.com/widgets/power.js', array(), null, false);
        wp_enqueue_script('acore-mail-return-js', ACORE_URL_PLG . 'web/assets/mail-return/mail-return.js', array('jquery'), '2.3', true);
        wp_localize_script('acore-mail-return-js', 'mailReturnData', [
            'mailsUrl'  => rest_url(ACORE_SLUG . '/v1/mail-return/list'),
            'returnUrl' => rest_url(ACORE_SLUG . '/v1/mail-return'),
            'assetsUrl' => ACORE_URL_PLG . 'web/assets/',
            'nonce'     => wp_create_nonce('wp_rest'),
        ]);

?>

        <script>const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>
        <div class="wrap" id="acore-mail-return-page">
            <div id="mail-return-layout">

                <!-- Col 1: character selector - always visible, same width -->
                <div id="mail-return-sidebar">
                    <div class="card">
                        <div class="card-body">
                            <h3>Mail Return</h3>
                            <p>You can return sent mails that have not yet been read by the recipient in this page. Select the character, the sent mail and hit return.</p>
                            <hr>

                            <strong>Select Character:</strong>
                            <ul id="acore-characters-mail" class="acore-char-list list-unstyled mt-2">
                                <?php foreach ($chars as $char) {
                                    $clsStyle = AcoreCharColors::rowStyle(intval($char['class']), intval($char['race']));
                                ?>
                                    <li>
                                        <button type="button" class="acore-char-row acore-char-card" data-char-guid="<?= intval($char['guid']) ?>" style="<?= esc_attr($clsStyle) ?>">
                                            <span class="acore-char-name"><?= esc_html($char['name']) ?></span>
                                            <span class="acore-char-meta">
                                                <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char['level'])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char['level']))) ?>">Level <?= intval($char['level']) ?></span>
                                                <img class="race-icon" height="32" width="32" alt="<?= esc_attr(AcoreCharColors::getRaceName(intval($char['race']))) ?>" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char['race']))) ?>" src="<?= ACORE_URL_PLG . 'web/assets/race/' . intval($char['race']) . (intval($char['gender']) == 0 ? 'm' : 'f') . '.webp' ?>">
                                                <img class="class-icon" height="32" width="32" alt="<?= esc_attr(AcoreCharColors::getClassName(intval($char['class']))) ?>" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char['class']))) ?>" src="<?= ACORE_URL_PLG . 'web/assets/class/' . intval($char['class']) . '.webp' ?>">
                                            </span>
                                        </button>
                                    </li>
                                <?php } ?>
                            </ul>

                            <div id="mail-return-loading" style="display:none;" class="text-center my-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>

                            <!-- Empty state lives inside the card -->
                            <div id="mail-return-empty-wrap" style="display:none;">
                                <hr>
                                <h5>Unread Sent Mails</h5>
                                <p id="mail-return-empty" class="text-muted">No unread sent mails found for this character.</p>
                            </div>
                        </div>
                    </div>
                </div><!-- /mail-return-sidebar -->

                <!-- Cols 2-3: mail list - hidden until mails are loaded by JS -->
                <div id="mail-return-content">
                    <div class="card">
                        <div class="card-body">
                            <h5 id="mail-return-heading">Unread Sent Mails</h5>
                            <ul id="mail-return-items" class="list-unstyled mb-0"></ul>
                        </div>
                    </div>
                </div><!-- /mail-return-content -->

            </div><!-- /mail-return-layout -->
        </div><!-- /wrap -->

        <?php
        return ob_get_clean();
    }
}
