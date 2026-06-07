<?php
    use ACore\Manager\Opts;
    use ACore\Utils\AcoreCharColors;
?>

<script>const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>
<div class="wrap" id="acore-item-restoration-page">
    <h1><?php _e('Item Restoration', Opts::I()->page_alias); ?></h1>
    <div class="row acore-item-restoration-row">
        <div class="col-lg-8 col-md-10 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="text-uppercase">Item Restoration Service</h4>
                    <p class="text-muted mb-3"><em>Restored items will be sent to the selected character's mailbox.</em></p>
                    <hr>

                    <?php if ($characters): ?>
                        <strong><?php _e('Select Character:', Opts::I()->page_alias); ?></strong>
                        <ul id="acore-characters-item-restore" class="acore-char-list list-unstyled mt-2 mb-3">
                            <?php foreach ($characters as $char):
                                $clsStyle = AcoreCharColors::rowStyle(intval($char['class']), intval($char['race']));
                            ?>
                                <li>
                                    <div class="acore-char-row acore-char-card"
                                         data-char-guid="<?= intval($char['guid']) ?>"
                                         data-char-name="<?= esc_attr($char['name']) ?>"
                                         style="<?= esc_attr($clsStyle) ?>"
                                         role="button">
                                        <span class="acore-char-name"><?= esc_html($char['name']) ?></span>
                                        <span class="acore-char-meta">
                                            <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char['level'])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char['level']))) ?>">Level <?= intval($char['level']) ?></span>
                                            <img class="race-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char['race']))) ?>" src="<?= ACORE_URL_PLG . 'web/assets/race/' . intval($char['race']) . (intval($char['gender']) == 0 ? 'm' : 'f') . '.webp' ?>">
                                            <img class="class-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char['class']))) ?>" src="<?= ACORE_URL_PLG . 'web/assets/class/' . intval($char['class']) . '.webp' ?>">
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php _e('No characters found.', Opts::I()->page_alias); ?></p>
                    <?php endif; ?>

                    <div id="errorBox" class="text-uppercase text-danger"></div>
                    <div id="successBox" class="alert alert-success invisible" role="alert"></div>

                    <div id="item-list-no-content" class="alert alert-info hidden" role="alert">
                        <span><?php _e('There are no items to recover for the selected character.', Opts::I()->page_alias); ?></span>
                    </div>

                    <div class="table-responsive hidden" id="itemContainer">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-uppercase"><?php _e('Item Name', Opts::I()->page_alias); ?></th>
                                    <th scope="col" class="text-uppercase"><?php _e('Action', Opts::I()->page_alias); ?></th>
                                </tr>
                            </thead>
                            <tbody id="itemList">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <tr class="loading-item-list hidden">
                                        <td class="placeholder-glow"><p><span class="placeholder col-12 bg-secondary"></span></p></td>
                                        <td><p class="placeholder-glow"><span class="placeholder col-12"></span></p></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var itemContainer   = document.getElementById('itemContainer');
    var itemList        = document.getElementById('itemList');
    var itemListLoaders = document.querySelectorAll('.loading-item-list');
    var errorBox        = document.getElementById('errorBox');
    var successBox      = document.getElementById('successBox');
    var noResults       = document.getElementById('item-list-no-content');

    var listUrl    = '<?= get_rest_url(null, 'acore/v1/item-restore/list/') ?>';
    var restoreUrl = '<?= get_rest_url(null, 'acore/v1/item-restore') ?>';

    // Character row selection
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
        itemListLoaders.forEach(function (el) { el.classList.remove('hidden'); });
        itemContainer.classList.remove('hidden');

        // Clear previous non-loader rows
        Array.from(itemList.children).forEach(function (row) {
            if (!row.classList.contains('loading-item-list')) itemList.removeChild(row);
        });

        fetch(listUrl + guid)
            .then(function (r) { return r.json(); })
            .then(function (items) {
                if (!items || !items.length) {
                    noResults.classList.remove('hidden');
                    return;
                }
                items.forEach(function (item) {
                    var row      = itemList.insertRow();
                    row.id       = 'row' + item['Id'];

                    var itemCell = row.insertCell();
                    var link     = document.createElement('a');
                    link.href    = '#';
                    link.setAttribute('data-wowhead', 'item=' + item['ItemEntry']);
                    itemCell.appendChild(link);

                    var btnCell  = row.insertCell();
                    var btn      = document.createElement('button');
                    btn.className   = 'button-primary text-uppercase';
                    btn.type        = 'button';
                    btn.setAttribute('item', item['Id']);
                    btn.setAttribute('cname', characterName);
                    btn.textContent = 'Restore';
                    btn.addEventListener('click', restoreItem);
                    btnCell.appendChild(btn);
                });
                checkHasRecoverableItems();
            })
            .catch(function (msg) { errorBox.innerHTML = msg; })
            .finally(function () {
                if (typeof $WowheadPower !== 'undefined') $WowheadPower.refreshLinks();
                itemListLoaders.forEach(function (el) { el.classList.add('hidden'); });
            });
    }

    function restoreItem() {
        resetState();
        var item  = this.getAttribute('item');
        var cname = this.getAttribute('cname');

        var loader = document.createElement('div');
        loader.className = 'placeholder-glow';
        var span = document.createElement('span');
        span.className = 'placeholder col-12 bg-warning';
        loader.appendChild(span);
        this.parentElement.appendChild(loader);
        this.parentElement.removeChild(this);

        fetch(restoreUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ item: item, cname: cname }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.toLowerCase().includes('mail')) {
                    successBox.innerHTML = data;
                    var rowEl = document.getElementById('row' + item);
                    if (rowEl) rowEl.parentElement.removeChild(rowEl);
                    successBox.classList.remove('invisible');
                    checkHasRecoverableItems();
                } else {
                    errorBox.innerHTML = data;
                }
            })
            .catch(function (err) {
                loader.parentElement.appendChild(btn);
                loader.parentElement.removeChild(loader);
                errorBox.innerHTML = err && err.message ? err.message : 'An error occurred.';
            });
    }

    function checkHasRecoverableItems() {
        var rows = document.querySelectorAll('#item-restoration-table tbody tr:not(.hidden)');
        if (rows.length === 0) {
            var emptyRow = document.getElementById('item-restoration-empty');
            if (emptyRow) emptyRow.classList.remove('hidden');
        }
    }

    function resetState() {
        errorBox.innerHTML   = '';
        successBox.innerHTML = '';
        successBox.classList.add('invisible');
        noResults.classList.add('hidden');
    }

    // Load items on character select
    document.querySelectorAll('.acore-char-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.acore-char-card').forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            selectCharacter(this.dataset.charGuid, this.dataset.charName);
        });
    });
})();
</script>
