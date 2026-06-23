<?php
    use ACore\Manager\Opts;
    use ACore\Utils\AcoreCharColors;
?>

<script>const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>
<div class="wrap" id="acore-item-restoration-page">
    <h1><?php _e('Item Restoration', Opts::I()->page_alias); ?></h1>
    <div id="item-restore-layout">

        <!-- Sidebar -->
        <div id="item-restore-sidebar">
            <div class="card">
                <div class="card-body">
                    <h3><?php _e('Item Restoration', Opts::I()->page_alias); ?></h3>
                    <p><?php _e('Select a character to view their deleted items. Restored items are sent to the mailbox.', Opts::I()->page_alias); ?></p>
                    <hr>

                    <?php if ($characters): ?>
                        <strong><?php _e('Select Character:', Opts::I()->page_alias); ?></strong>
                        <ul id="acore-characters-item-restore" class="acore-char-list list-unstyled mt-2">
                            <?php foreach ($characters as $char):
                                $clsStyle = AcoreCharColors::rowStyle(intval($char['class']), intval($char['race']));
                            ?>
                                <li>
                                    <button type="button" class="acore-char-row acore-char-card"
                                         data-char-guid="<?= intval($char['guid']) ?>"
                                         data-char-name="<?= esc_attr($char['name']) ?>"
                                         title="<?= esc_attr(sprintf('Show items that can be restored to %s', $char['name'])) ?>"
                                         style="<?= esc_attr($clsStyle) ?>">
                                        <span class="acore-char-name"><?= esc_html($char['name']) ?></span>
                                        <span class="acore-char-meta">
                                            <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char['level'])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char['level']))) ?>">Level <?= intval($char['level']) ?></span>
                                            <img class="race-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char['race']))) ?>" src="<?= ACORE_URL_PLG . 'web/assets/race/' . intval($char['race']) . (intval($char['gender']) == 0 ? 'm' : 'f') . '.webp' ?>">
                                            <img class="class-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char['class']))) ?>" src="<?= ACORE_URL_PLG . 'web/assets/class/' . intval($char['class']) . '.webp' ?>">
                                        </span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('No characters found.', Opts::I()->page_alias); ?></p>
                    <?php endif; ?>

                    <div id="item-restore-loading" style="display:none;" class="text-center my-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Empty state stays in sidebar -->
                    <div id="item-list-no-content" style="display:none;">
                        <hr>
                        <p class="text-muted mb-0"><?php _e('There are no items to recover for the selected character.', Opts::I()->page_alias); ?></p>
                    </div>
                </div>
            </div>
        </div><!-- /item-restore-sidebar -->

        <!-- Content: item cards, hidden until loaded -->
        <div id="item-restore-content" style="display:none;">
            <div class="card">
                <div class="card-body">
                    <div id="item-restore-error" class="text-uppercase text-danger mb-2"></div>
                    <div id="item-restore-success" class="alert alert-success invisible mb-2" role="alert"></div>
                    <h5 class="mb-3"><?php _e('Deleted Items', Opts::I()->page_alias); ?></h5>
                    <div id="item-restore-grid" class="item-restore-grid"></div>
                </div>
            </div>
        </div><!-- /item-restore-content -->

    </div><!-- /item-restore-layout -->
</div>

<script>
(function () {
    var content    = document.getElementById('item-restore-content');
    var grid       = document.getElementById('item-restore-grid');
    var errorBox   = document.getElementById('item-restore-error');
    var successBox = document.getElementById('item-restore-success');
    var noResults  = document.getElementById('item-list-no-content');
    var loading    = document.getElementById('item-restore-loading');

    var listUrl    = '<?= esc_js(get_rest_url(null, ACORE_SLUG . '/v1/item-restore/list/')) ?>';
    var restoreUrl = '<?= esc_js(get_rest_url(null, ACORE_SLUG . '/v1/item-restore')) ?>';
    var restNonce  = '<?= esc_js(wp_create_nonce('wp_rest')) ?>';

    function parseJsonResponse(response) {
        return response.json().catch(function () { return {}; }).then(function (body) {
            if (!response.ok) {
                throw new Error((body && body.message) ? body.message : 'Request failed.');
            }
            return body;
        });
    }

    // wowhead quality class → card border colour
    var wowQualityColors = {
        q0: '#9d9d9d', q1: '#c0c0c0', q2: '#1eff00',
        q3: '#0070dd', q4: '#a335ee', q5: '#ff8000',
        q6: '#e6cc80', q7: '#e6cc80'
    };

    document.querySelectorAll('#acore-characters-item-restore .acore-char-row').forEach(function (row) {
        row.addEventListener('click', function () {
            document.querySelectorAll('#acore-characters-item-restore .acore-char-row').forEach(function (r) {
                r.classList.remove('active');
            });
            row.classList.add('active');
            selectCharacter(row.getAttribute('data-char-guid'), row.getAttribute('data-char-name'));
        });
    });

    function selectCharacter(guid, characterName) {
        resetState();
        loading.style.display = 'block';
        content.style.display = 'none';
        grid.innerHTML = '';

        fetch(listUrl + guid, { headers: { 'Accept': 'application/json', 'X-WP-Nonce': restNonce } })
            .then(parseJsonResponse)
            .then(function (items) {
                loading.style.display = 'none';

                if (!items || !items.length) {
                    noResults.style.display = 'block';
                    return;
                }

                content.style.display = 'block';

                items.forEach(function (item, index) {
                    var card = document.createElement('div');
                    card.className = 'item-restore-card';
                    card.id = 'card' + item['Id'];

                    // Number badge
                    var num = document.createElement('span');
                    num.className = 'item-restore-num';
                    num.textContent = index + 1;
                    num.title = 'Position in your list of restorable items.';

                    // Icon container — wowhead puts the icon as background-image on the <a>
                    var iconWrap = document.createElement('div');
                    iconWrap.className = 'item-restore-icon';

                    var link = document.createElement('a');
                    link.href = 'https://www.wowhead.com/wotlk/item=' + item['ItemEntry'];
                    link.setAttribute('data-wowhead', 'item=' + item['ItemEntry']);

                    iconWrap.appendChild(link);

                    // Restore button
                    var btn = document.createElement('button');
                    btn.className = 'item-restore-btn';
                    btn.type = 'button';
                    btn.textContent = 'Restore';
                    btn.title = 'Send this item back to ' + characterName + ' by in-game mail.';
                    (function (itemId, charName, cardEl, btnEl) {
                        btnEl.addEventListener('click', function () {
                            restoreItem(itemId, charName, cardEl, btnEl);
                        });
                    })(item['Id'], characterName, card, btn);

                    // Delete date
                    var dateEl = document.createElement('span');
                    dateEl.className = 'item-restore-date';
                    dateEl.title = 'Date this item was deleted.';
                    var d = item['DeleteDate'];
                    if (d) {
                        var dStr = String(d);
                        var dt   = /^\d+$/.test(dStr) ? new Date(Number(dStr) * 1000) : new Date(dStr.replace(' ', 'T'));
                        if (!isNaN(dt.getTime())) {
                            var day = dt.getDate();
                            var ord = day % 100 >= 11 && day % 100 <= 13 ? 'th'
                                : day % 10 === 1 ? 'st'
                                : day % 10 === 2 ? 'nd'
                                : day % 10 === 3 ? 'rd' : 'th';
                            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                            var hh = String(dt.getHours()).padStart(2, '0');
                            var mm = String(dt.getMinutes()).padStart(2, '0');
                            dateEl.textContent = day + ord + ' of ' + months[dt.getMonth()] + ', ' + dt.getFullYear() + ' at ' + hh + ':' + mm;
                        } else {
                            dateEl.textContent = String(d);
                        }
                    } else {
                        dateEl.textContent = '';
                    }

                    card.appendChild(num);
                    card.appendChild(iconWrap);
                    card.appendChild(dateEl);
                    card.appendChild(btn);
                    grid.appendChild(card);
                });

                applyWowheadIcons();
            })
            .catch(function (msg) {
                loading.style.display = 'none';
                errorBox.textContent = msg && msg.message ? msg.message : String(msg);
            });
    }

    function restoreItem(id, characterName, cardEl, btnEl) {
        resetState();
        btnEl.disabled = true;
        btnEl.textContent = '...';

        fetch(restoreUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-WP-Nonce': restNonce },
            body: JSON.stringify({ item: id, cname: characterName }),
        })
            .then(parseJsonResponse)
            .then(function (data) {
                if (typeof data === 'string' && data.toLowerCase().includes('mail')) {
                    cardEl.parentElement.removeChild(cardEl);
                    successBox.textContent = data;
                    successBox.classList.remove('invisible');
                    // renumber remaining cards
                    var remaining = grid.querySelectorAll('.item-restore-card');
                    remaining.forEach(function (c, i) {
                        var n = c.querySelector('.item-restore-num');
                        if (n) n.textContent = i + 1;
                    });
                    if (remaining.length === 0) {
                        content.style.display = 'none';
                        noResults.style.display = 'block';
                    }
                } else {
                    errorBox.textContent = typeof data === 'string' ? data : JSON.stringify(data);
                    btnEl.disabled = false;
                    btnEl.textContent = 'Restore';
                }
            })
            .catch(function (err) {
                errorBox.textContent = err && err.message ? err.message : 'An error occurred.';
                btnEl.disabled = false;
                btnEl.textContent = 'Restore';
            });
    }

    function applyWowheadIcons() {
        function doApply() {
            document.querySelectorAll('.item-restore-icon > a').forEach(function (a) {
                // upgrade icon to large JPG
                var bg = a.style.backgroundImage;
                if (bg) {
                    bg = bg.replace(/\/icons\/(tiny|small)\//, '/icons/large/')
                           .replace(/\.gif(["']?\))/, '.jpg$1');
                    a.style.backgroundImage = bg;
                }
                // read quality class (q0-q7) and apply to card border
                var card = a.closest('.item-restore-card');
                if (card) {
                    for (var cls in wowQualityColors) {
                        if (a.classList.contains(cls)) {
                            card.style.borderColor = wowQualityColors[cls];
                            break;
                        }
                    }
                }
            });
        }

        if (typeof $WowheadPower !== 'undefined' && $WowheadPower.refreshLinks) {
            $WowheadPower.refreshLinks();
            setTimeout(doApply, 1500);
        } else {
            var attempts = 0;
            var wait = setInterval(function () {
                attempts++;
                if (typeof $WowheadPower !== 'undefined' && $WowheadPower.refreshLinks) {
                    $WowheadPower.refreshLinks();
                    clearInterval(wait);
                    setTimeout(doApply, 1500);
                } else if (attempts > 20) {
                    clearInterval(wait);
                }
            }, 250);
        }
    }

    function resetState() {
        errorBox.textContent = '';
        successBox.textContent = '';
        successBox.classList.add('invisible');
        noResults.style.display = 'none';
    }
})();
</script>
