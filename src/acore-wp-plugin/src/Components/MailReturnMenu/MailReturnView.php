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
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.4');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        wp_enqueue_script('jquery');
        wp_enqueue_script('acore-mail-return-js', ACORE_URL_PLG . 'web/assets/mail-return/mail-return.js', array('jquery'), null, true);

?>

        <div class="wrap">
            <div class="col-sm-5">
                <div class="card">
                    <div class="card-body">
                        <h3>Mail Return</h3>
                        <p>You can return sent mails that have not yet been read by the recipient in this page. Select the character, the sent mail and hit return.</p>
                        <hr>

                        <strong>Select Character:</strong>
                        <ul id="acore-characters-mail" class="acore-char-list list-unstyled mt-2">
                            <?php foreach ($chars as $char) {
                                $clsStyle = AcoreCharColors::rowStyle(intval($char['class']));
                            ?>
                                <li>
                                    <div class="acore-char-row acore-char-card" data-char-guid="<?= intval($char['guid']) ?>" style="<?= esc_attr($clsStyle) ?>">
                                        <span class="acore-char-name"><?= esc_html($char['name']) ?></span>
                                        <span class="acore-char-meta">
                                            <span class="acore-level">Level <?= intval($char['level']) ?></span>
                                            <img height="32" width="32" src="<?= ACORE_URL_PLG . 'web/assets/race/' . intval($char['race']) . (intval($char['gender']) == 0 ? 'm' : 'f') . '.webp' ?>">
                                            <img height="32" width="32" src="<?= ACORE_URL_PLG . 'web/assets/class/' . intval($char['class']) . '.webp' ?>">
                                        </span>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>

                        <div id="mail-return-loading" style="display:none;" class="text-center my-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <div id="mail-return-list" style="display:none;">
                            <h5>Unread Sent Mails</h5>
                            <ul id="mail-return-items" class="list-unstyled"></ul>
                            <p id="mail-return-empty" style="display:none;" class="text-muted">No unread sent mails found for this character.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            var mailReturnData = {
                nonce: "<?php echo esc_js(wp_create_nonce('wp_rest')); ?>",
                mailsUrl: "<?php echo esc_url(get_rest_url(null, ACORE_SLUG . '/v1/mail-return/list')); ?>",
                returnUrl: "<?php echo esc_url(get_rest_url(null, ACORE_SLUG . '/v1/mail-return')); ?>",
                assetsUrl: "<?php echo esc_url(ACORE_URL_PLG . 'web/assets/'); ?>"
            };
        </script>
        <script>var whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>
        <script src="https://wow.zamimg.com/js/tooltips.js"></script>
<?php
        return ob_get_clean();
    }
}
