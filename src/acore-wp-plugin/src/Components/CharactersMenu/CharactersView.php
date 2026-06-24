<?php

namespace ACore\Components\CharactersMenu;

use ACore\Utils\AcoreCharColors;

class CharactersView {
    private $controller;

    /**
     *
     * @param ACore\Components\CharactersMenu\CharactersController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
    }

    private function formatDuration($seconds) {
        $seconds = abs(intval($seconds));
        if ($seconds >= 31536000) { $n = intdiv($seconds, 31536000); return $n . ' ' . ($n === 1 ? 'year'   : 'years');   }
        if ($seconds >= 2592000)  { $n = intdiv($seconds, 2592000);  return $n . ' ' . ($n === 1 ? 'month'  : 'months');  }
        if ($seconds >= 86400)    { $n = intdiv($seconds, 86400);    return $n . ' ' . ($n === 1 ? 'day'    : 'days');    }
        if ($seconds >= 3600)     { $n = intdiv($seconds, 3600);     return $n . ' ' . ($n === 1 ? 'hour'   : 'hours');   }
        if ($seconds >= 60)       { $n = intdiv($seconds, 60);       return $n . ' ' . ($n === 1 ? 'minute' : 'minutes'); }
        return $seconds . ' ' . ($seconds === 1 ? 'second' : 'seconds');
    }

    private function formatDate($ts) { return date('d-m-Y', intval($ts)); }
    private function formatTime($ts) { return date('H:i',   intval($ts)); }

    public function getHomeRender($characters, $mutetime = 0, $accBanRow = null) {
        $now = time();

        // Account mute
        $isMuted       = $mutetime < 0 || $mutetime > $now;
        $mutePending   = $mutetime < 0;
        $muteRemaining = $mutePending ? abs($mutetime) : max(0, $mutetime - $now);

        // Account ban
        $isAccountBanned = !empty($accBanRow);
        $accBanPerma     = $isAccountBanned && (intval($accBanRow['unbandate']) === 0 || $accBanRow['unbandate'] === $accBanRow['bandate']);
        $accBanRemaining = ($isAccountBanned && !$accBanPerma) ? max(0, intval($accBanRow['unbandate']) - $now) : 0;

        ob_start();
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.5');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        wp_enqueue_script('jquery-ui-sortable');
        ?>
        <div class="wrap">
            <div class="row">
                <div class="col-sm-10">
                    <div class="card">
                        <div class="card-body">
                            <h3>Order Character Screen</h3>
                            <p>Change the order in which the characters appear in your in-game character selection screen, by dragging them, matches in-game position.</p>
                            <?php if ($isAccountBanned || $isMuted): ?>
                                <div class="acore-account-notices">
                                    <?php if ($isAccountBanned): ?>
                                        <div class="acore-notice-pill badge bg-danger">
                                            <span><?= $accBanPerma ? 'Banned' : esc_html('Banned for: ' . $this->formatDuration($accBanRemaining)) ?></span>
                                            <?php if (!$accBanPerma): ?>
                                                <span class="acore-notice-subtext">Ends: <?= esc_html($this->formatDate($accBanRow['unbandate'])) ?> at <?= esc_html($this->formatTime($accBanRow['unbandate'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isMuted): ?>
                                        <div class="acore-notice-pill badge bg-warning">
                                            <span><?= $mutePending ? esc_html('Muted for ' . $this->formatDuration($muteRemaining)) : esc_html('Muted for: ' . $this->formatDuration($muteRemaining)) ?></span>
                                            <?php if ($mutePending): ?><span class="acore-notice-subtext">Starts upon login</span><?php endif; ?>
                                            <?php if (!$mutePending): ?>
                                                <span class="acore-notice-subtext">Ends: <?= esc_html($this->formatDate($mutetime)) ?> at <?= esc_html($this->formatTime($mutetime)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <form action="" method="POST" novalidate="novalidate">
                                <?php wp_nonce_field('acore_character_order', 'acore_character_order_nonce'); ?>

                                <div class="acore-col-headers">
                                    <div class="acore-col-header-selector"></div>
                                    <div class="acore-col-header-cell">Ban / Mute<span class="acore-col-header-fmt">DD-MM-YYYY at HH:MM</span></div>
                                    <div class="acore-col-header-cell"></div>
                                    <div class="acore-col-header-cell"></div>
                                </div>
                                <ul id="acore-characters-order" class="acore-char-list list-unstyled">
                                    <?php $charPos = 0; foreach ($characters as $char) {
                                        $clsStyle      = AcoreCharColors::rowStyle(intval($char["class"]), intval($char["race"]));
                                        $charPos++;
                                        $displayPos    = $char['order'] !== null ? intval($char['order']) + 1 : $charPos;
                                        $banBandate    = ($char['ban_bandate']   !== null && $char['ban_bandate']   !== '') ? intval($char['ban_bandate'])   : null;
                                        $banUnbandate  = ($char['ban_unbandate'] !== null && $char['ban_unbandate'] !== '') ? intval($char['ban_unbandate']) : null;
                                        $charBanned    = $banBandate !== null;
                                        $charBanPerma  = $charBanned && ($banUnbandate === null || $banUnbandate === 0 || $banUnbandate === $banBandate);
                                        $charBanRemain = ($charBanned && !$charBanPerma) ? max(0, $banUnbandate - $now) : 0;
                                    ?>
                                        <li class="acore-char-li-row">
                                            <div class="acore-char-row" style="<?= esc_attr($clsStyle) ?>">
                                                <span class="acore-char-pos"><?= $displayPos ?></span>
                                                <span class="acore-char-name"><?= esc_html($char["name"]) ?></span>
                                                <span class="acore-char-meta">
                                                    <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char["level"])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char["level"]))) ?>">Level <?= intval($char["level"]) ?></span>
                                                    <img class="race-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char["race"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/race/" . intval($char["race"]) . (intval($char["gender"]) == 0 ? "m" : "f") . ".webp") ?>">
                                                    <img class="class-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char["class"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/class/" . intval($char["class"]) . ".webp") ?>">
                                                </span>
                                            </div>
                                            <div class="acore-char-ext-col acore-char-ext-col--ban<?= $charBanned ? ' acore-char-ext-col--banned' : '' ?>">
                                                <?php if ($charBanned): ?>
                                                    <?php if ($charBanPerma): ?>
                                                        <span class="acore-ban-badge">Banned</span>
                                                    <?php else: ?>
                                                        <span class="acore-ban-badge">Banned for: <?= esc_html($this->formatDuration($charBanRemain)) ?></span>
                                                        <span class="acore-ban-date">Ends: <?= esc_html($this->formatDate($banUnbandate)) ?> at <?= esc_html($this->formatTime($banUnbandate)) ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="acore-char-ext-col"></div>
                                            <div class="acore-char-ext-col"></div>
                                            <input type="hidden" name="characterorder[]" value="<?= esc_attr($char["guid"]) ?>">
                                        </li>
                                    <?php } ?>
                                </ul>

                                <?php if (!empty($characters)) { ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; width:40%;">
                                        <input type="submit" name="submit" class="button button-primary" value="Save Order">
                                        <input type="submit" name="acore_reset_order" class="button acore-btn-danger" value="Reset Order">
                                    </div>
                                <?php } ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- .acore-btn-danger styling lives in theme.css (shared light/dark) -->

        <style>
        .acore-char-li-row {
            display: flex;
            align-items: stretch;
            gap: 12px;
            margin-bottom: 8px;
        }
        .acore-char-li-row .acore-char-row {
            flex: 0 0 40%;
            width: 40%;
        }
        .acore-char-ext-col {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
        }
        .acore-account-notices {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .acore-notice-pill {
            display: inline-flex !important;
            flex-direction: column;
            align-items: center;
            gap: 1px;
            padding: 5px 10px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
            white-space: nowrap;
        }
        .acore-notice-pill.bg-danger  { background-color: #dc3545 !important; color: #ffffff !important; }
        .acore-notice-pill.bg-danger  span { color: #ffffff !important; }
        .acore-notice-pill.bg-warning { background-color: #ffc107 !important; color: #000000 !important; }
        .acore-notice-pill.bg-warning span { color: #000000 !important; }
        .acore-notice-pill.bg-warning .acore-notice-subtext { color: rgba(0,0,0,0.65) !important; }
        .acore-notice-subtext {
            font-size: 12px;
            font-weight: 400;
            color: rgba(255,255,255,0.8) !important;
            line-height: 1.3;
        }
        .acore-char-ext-col--ban {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1px;
            text-align: center;
        }
        .acore-char-ext-col--banned {
            background: #dc3545 !important;
            border-radius: 8px;
        }
        .acore-ban-badge {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.3;
            white-space: nowrap;
            color: #ffffff !important;
        }
        .acore-ban-date {
            font-size: 12px;
            line-height: 1.3;
            white-space: nowrap;
            color: rgba(255,255,255,0.85) !important;
        }
        /* Column headers row */
        .acore-col-headers {
            display: flex;
            gap: 0;
            margin-bottom: 4px;
        }
        .acore-col-header-selector {
            flex: 0 0 40%;
        }
        .acore-col-header-cell {
            flex: 1;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: center;
            padding: 0 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1px;
        }
        .acore-col-header-fmt {
            font-size: 10px;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            opacity: 0.65;
        }
        .acore-notice-detail {
            font-size: 11px;
            opacity: 0.75;
            line-height: 1.5;
            text-align: center;
        }
        /* Force Bootstrap badge colours — dark mode overrides them */
        .acore-account-notices .badge.bg-danger  { background-color: #dc3545 !important; color: #ffffff !important; }
        .acore-account-notices .badge.bg-warning { background-color: #ffc107 !important; color: #000000 !important; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                new bootstrap.Tooltip(el);
            });

            $("#acore-characters-order").sortable({
                update: function(event, ui) {
                    var order = [];
                    $("#acore-characters-order li").each(function(index) {
                        var input = $(this).find("input[name='characterorder[]']");
                        order.push(input.val());
                        $(this).find(".acore-char-pos").text(index + 1);
                    });
                }
            });
            $("#acore-characters-order").disableSelection();
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
