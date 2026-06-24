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

    public function getHomeRender($characters, $mutetime = 0, $accBanRow = null, $serverRevision = '', $serverRevisionUrl = '', $bugReportUrl = '', $pdumpEnabled = false) {
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
                            <p>Change the order in which your characters appear in the in-game selection screen by dragging them into your preferred position.</p>
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
                                    <div class="acore-col-header-cell">
                                        <?php if ($pdumpEnabled): ?>
                                        <button type="button" class="button button-primary acore-export-all-btn">Export All</button>
                                        <?php endif; ?>
                                    </div>
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
                                                    <img class="race-icon" height="32" width="32" alt="<?= esc_attr(AcoreCharColors::getRaceName(intval($char["race"]))) ?>" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char["race"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/race/" . intval($char["race"]) . (intval($char["gender"]) == 0 ? "m" : "f") . ".webp") ?>">
                                                    <img class="class-icon" height="32" width="32" alt="<?= esc_attr(AcoreCharColors::getClassName(intval($char["class"]))) ?>" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char["class"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/class/" . intval($char["class"]) . ".webp") ?>">
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
                                            <div class="acore-char-ext-col">
                                                <?php if ($pdumpEnabled): ?>
                                                <button type="button" class="button button-primary acore-export-btn" data-char-guid="<?= esc_attr($char['guid']) ?>" data-char-name="<?= esc_attr($char['name']) ?>">Export</button>
                                                <?php endif; ?>
                                            </div>
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

        <!-- PDUMP confirm modal -->
        <div id="acore-pdump-modal" class="acore-pdump-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="acore-pdump-modal-title">
            <div class="acore-pdump-box">
                <h4 id="acore-pdump-modal-title">⚠️ Character Export (PDUMP)</h4>
                <p id="acore-pdump-modal-body"></p>
                <div id="acore-pdump-error" style="display:none;">
                    <p id="acore-pdump-error-intro" style="font-size:13px;margin:0 0 10px;"></p>
                    <details id="acore-pdump-error-details" style="display:none;">
                        <summary style="font-size:12px;cursor:pointer;margin-bottom:6px;">Click to see the error and share on GitHub</summary>
                        <pre id="acore-pdump-error-msg" style="font-size:11px;white-space:pre-wrap;word-break:break-all;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:8px;margin:0;"></pre>
                    </details>
                </div>
                <div class="acore-pdump-actions">
                    <button type="button" class="button button-primary" id="acore-pdump-confirm">Confirm</button>
                    <button type="button" class="button" id="acore-pdump-cancel">Cancel</button>
                </div>
            </div>
        </div>

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
        /* PDUMP modal */
        .acore-pdump-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .acore-pdump-box {
            background: #ffffff;
            color: #1d2327;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 24px 28px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.2);
        }
        .acore-pdump-box h4 {
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 700;
            color: #1d2327;
        }
        .acore-pdump-box p {
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 20px;
            color: #1d2327;
        }
        .acore-pdump-box a { color: #2271b1; }
        .acore-pdump-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        /* Dark mode overrides */
        body.acore-dark-mode .acore-pdump-box {
            background: #1e1e1e;
            color: #e0e0e0;
            border-color: #3a3a3a;
        }
        body.acore-dark-mode .acore-pdump-box h4,
        body.acore-dark-mode .acore-pdump-box p { color: #e0e0e0; }
        body.acore-dark-mode .acore-pdump-box a { color: #72aee6; }
        body.acore-dark-mode #acore-pdump-error-details summary { color: #e0e0e0; }
        body.acore-dark-mode #acore-pdump-error-msg { background: #2a2a2a !important; border-color: #3a3a3a !important; color: #f28b82 !important; }
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

            var acorePdumpRevision    = <?= json_encode($serverRevision ?: '') ?>;
            var acorePdumpRevisionUrl = <?= json_encode($serverRevisionUrl ?: '') ?>;
            var acorePdumpBugReportUrl = <?= json_encode($bugReportUrl ?: '') ?>;
            var acorePdumpRestBase    = <?= json_encode(get_rest_url(null, ACORE_SLUG . '/v1/pdump/')) ?>;
            var acorePdumpNonce       = <?= json_encode(wp_create_nonce('wp_rest')) ?>;
            var acorePdumpModal       = document.getElementById('acore-pdump-modal');
            var acorePdumpBody        = document.getElementById('acore-pdump-modal-body');
            var acorePdumpConfirm     = document.getElementById('acore-pdump-confirm');
            var acorePdumpOnConfirm   = null;

            function acorePdumpRevisionLink() {
                if (!acorePdumpRevision) return '(unknown revision)';
                if (acorePdumpRevisionUrl) {
                    return '<a href="' + acorePdumpRevisionUrl + '" target="_blank" rel="noopener">' + acorePdumpRevision + '</a>';
                }
                return acorePdumpRevision;
            }

            var acorePdumpError        = document.getElementById('acore-pdump-error');
            var acorePdumpErrorIntro   = document.getElementById('acore-pdump-error-intro');
            var acorePdumpErrorDetails = document.getElementById('acore-pdump-error-details');
            var acorePdumpErrorMsg     = document.getElementById('acore-pdump-error-msg');

            function acoreShowPdumpModal(message, onConfirm) {
                acorePdumpBody.innerHTML = message;
                acorePdumpError.style.display = 'none';
                acorePdumpErrorIntro.innerHTML = '';
                acorePdumpErrorMsg.textContent = '';
                acorePdumpErrorDetails.style.display = 'none';
                acorePdumpErrorDetails.removeAttribute('open');
                acorePdumpConfirm.style.display = '';
                acorePdumpOnConfirm = onConfirm;
                acorePdumpModal.style.display = 'flex';
            }

            function acoreShowPdumpError(msg, detail) {
                acorePdumpBody.innerHTML = '';
                // Show the human-readable message + technical detail (if any) in the <pre>
                acorePdumpErrorMsg.textContent = detail ? (msg + '\n\n' + detail) : msg;

                if (acorePdumpBugReportUrl) {
                    acorePdumpErrorIntro.innerHTML =
                        'There was an error, the PDUMP was not successful, it seems to be a bug, please report it on '
                        + '<a href="' + acorePdumpBugReportUrl + '" target="_blank" rel="noopener">GitHub</a>.';
                    acorePdumpErrorDetails.style.display = 'block';
                } else {
                    acorePdumpErrorIntro.textContent =
                        'There was an error, and it seems there is no URL for you to report to. '
                        + 'Talk to the administrator to fix this, in AzerothCore → Tools → PDUMP Bug Report URL.';
                    acorePdumpErrorDetails.style.display = 'none';
                }

                acorePdumpError.style.display = 'block';
                acorePdumpConfirm.style.display = 'none';
                acorePdumpOnConfirm = null;
                acorePdumpModal.style.display = 'flex';
            }

            function acoreClosePdumpModal() {
                acorePdumpModal.style.display = 'none';
                acorePdumpOnConfirm = null;
            }

            /**
             * Fetch the dump for one character GUID and trigger a browser download.
             * Uses fetch() + Blob so errors are surfaced without a page navigation.
             */
            function acoreDownloadDump(guid, charName) {
                fetch(acorePdumpRestBase + guid, {
                    headers: { 'X-WP-Nonce': acorePdumpNonce }
                }).then(function(resp) {
                    if (!resp.ok) {
                        return resp.json().then(function(body) {
                            var data   = (body && body.data) ? body.data : body;
                            var msg    = (data && data.message) ? data.message : 'Export failed (HTTP ' + resp.status + ').';
                            var detail = (data && data.detail)  ? data.detail  : null;
                            var err    = new Error(msg);
                            err.detail = detail;
                            throw err;
                        });
                    }
                    return resp.blob();
                }).then(function(blob) {
                    var filename = charName.toUpperCase() + '_' + new Date().toISOString().slice(0,10) + '.dump';
                    var url  = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href     = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function() { URL.revokeObjectURL(url); }, 10000);
                }).catch(function(err) {
                    acoreShowPdumpError(
                        err && err.message ? err.message : String(err),
                        err && err.detail  ? err.detail  : null
                    );
                });
            }

            document.getElementById('acore-pdump-cancel').addEventListener('click', acoreClosePdumpModal);
            acorePdumpConfirm.addEventListener('click', function() {
                if (typeof acorePdumpOnConfirm === 'function') acorePdumpOnConfirm();
                acoreClosePdumpModal();
            });
            acorePdumpModal.addEventListener('click', function(e) {
                if (e.target === acorePdumpModal) acoreClosePdumpModal();
            });

            $(document).on('click', '.acore-export-btn', function() {
                var guid = $(this).data('char-guid');
                var name = $(this).data('char-name');
                var upper = name.toUpperCase();
                var msg  = 'You\'re about to PDUMP a.k.a make a copy of your character <strong>' + upper + '</strong> that can be used in AzerothCore. '
                         + 'This will <strong>NOT COPY</strong> any custom contents, for example modules like Transmog or Custom Items like Physical Costumes. '
                         + '<br><br>This Character Dump was extracted from this version:<br>' + acorePdumpRevisionLink()
                         + '<br><br>The following information will be anonymised or omitted from the dump: '
                         + '<strong>CHARACTER NAME</strong> &bull; <strong>POSITION</strong> &bull; <strong>HEARTHSTONE LOCATION</strong> &bull; '
                         + '<strong>GOLD</strong> &bull; <strong>TIMESTAMPS</strong> &bull; <strong>ONLINE STATUS</strong> &bull; '
                         + '<strong>ACHIEVEMENT DATES</strong> &bull; <strong>MAIL CONTENTS &amp; SENDER</strong> &bull; '
                         + '<strong>ITEM CREATOR/GIFTER</strong> &bull; <strong>AURA CASTER</strong> &bull; '
                         + '<strong>EQUIPMENT SET NAMES</strong> &bull; <strong>CUSTOM CHAT CHANNELS</strong> &bull; '
                         + '<strong>CHARACTER &amp; ACCOUNT IDs</strong> (replaced with random values).';
                acoreShowPdumpModal(msg, function() {
                    acoreDownloadDump(guid, name);
                });
            });

            $(document).on('click', '.acore-export-all-btn', function() {
                var chars = [];
                $('.acore-export-btn').each(function() {
                    chars.push({ guid: $(this).data('char-guid'), name: $(this).data('char-name') });
                });
                var nameList = chars.map(function(c) { return '<strong>' + c.name.toUpperCase() + '</strong>'; }).join(', ');
                var msg = 'You\'re about to PDUMP a.k.a make a copy of <strong>ALL</strong> your characters: ' + nameList + '. '
                        + 'This will <strong>NOT COPY</strong> any custom contents, for example modules like Transmog or Custom Items like Physical Costumes. '
                        + '<br><br>This Character Dump was extracted from this version:<br>' + acorePdumpRevisionLink()
                        + '<br><br>The following information will be anonymised or omitted from the dump: '
                        + 'character name, position, hearthstone location, gold, timestamps, online status, '
                        + 'achievement dates, mail contents and sender, item creator/gifter, custom chat channels, '
                        + 'and all character &amp; account IDs (replaced with random values).';
                acoreShowPdumpModal(msg, function() {
                    // Download sequentially with a small delay so the browser doesn't block multiple downloads
                    chars.forEach(function(c, i) {
                        setTimeout(function() { acoreDownloadDump(c.guid, c.name); }, i * 800);
                    });
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
