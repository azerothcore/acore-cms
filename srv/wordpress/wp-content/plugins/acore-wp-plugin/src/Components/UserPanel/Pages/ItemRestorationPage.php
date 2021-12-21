<?php
    use ACore\Manager\Opts;
?>

<script>const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>
<div class="wrap">
    <h2><?= __('AzerothCore Settings', Opts::I()->page_alias) ?></h2>
    <div class="row">
        <div>
            <div class="card">
                <div class="card-body">
                    <h4 class="text-uppercase">Item restoration service</h4>
                    <hr>
                    <div style="width: 12em">
                        <div class="btn-group">
                            <div class="bg-secondary p-2 text-white border rounded-left" id="activeCharacter">Choose Character</div>
                            <button type="button" class="button-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden"></span>
                            </button>
                            <?php if ($characters): ?>
                                <ul class="dropdown-menu" id="characterList">
                            <?php foreach ($characters as $character): ?>
                                    <li cguid="<?= $character['guid']?>"><a class="dropdown-item" href="#"><?= $character['name']?></a></li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span>No characters found.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="errorBox" class="text-uppercase text-danger"></div>
                    <div id="successBox" class="alert alert-success invisible" role="alert"></div>
                    <p class="text-muted m-0"><em>Restored items will be sent to the selected characters mailbox.</em></p>
                    <hr>
                    <div id="item-list-no-content" class="alert alert-info hidden" role="alert">
                        <span>There is no items to recover for the selected character</span>
                    </div>
                    <div class="table-responsive hidden" id="itemContainer">
                        <table class="table table-bordered table-hover align-middle">
                            <thead style="background: #1d2327; color: #fff;">
                                <tr>
                                    <th scope="col" class="text-uppercase">item name</th>
                                    <th scope="col" class="text-uppercase">action</th>
                                </tr>
                            </thead>
                            <tbody style="background: #2c3338;" id="itemList">
                            <?php
                                for ($i = 0; $i < 5; $i++): 
                            ?>
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
    // Register event listeners & element specifiers
    const characters = document.querySelectorAll('#characterList li');
    const itemContainer = document.getElementById('itemContainer');
    const itemList = document.getElementById('itemList');
    const itemListLoaders = document.querySelectorAll('.loading-item-list');
    const activeCharacter = document.getElementById('activeCharacter');
    const error = document.getElementById('errorBox');
    const success = document.getElementById('successBox');
    const noResults =  document.getElementById('item-list-no-content');
    characters.forEach(character => character.addEventListener('click', selectCharacter));

    // Character Selection
    function selectCharacter() {
        resetState();
        itemListLoaders.forEach(element => element.classList.remove('hidden'));
        itemContainer.classList.remove('hidden');
        const character = this.getAttribute('cguid');
        const characterName = this.firstChild.innerHTML;
        activeCharacter.innerHTML = characterName;

        fetch('<?= get_rest_url(null, 'acore/v1/item-restore/list/'); ?>' + character)
        .then(function(response) {
            return response.json();
        }).then(function(items) {
            if (!items || !items.length > 0) {
                noResults.classList.remove('hidden');
                return;
            }

            items.forEach(item => {
                const row = itemList.insertRow();
                row.id = "row" + item['Id'];

                // Item
                const itemCell = row.insertCell();
                const itemLink = document.createElement('a');
                itemLink.href = "#";
                itemLink.setAttribute('data-wowhead', `item=${item['ItemEntry']}`);
                itemCell.appendChild(itemLink);

                // Button
                const buttonCell = row.insertCell();
                const button = document.createElement('button');
                button.classList.add('button-primary', 'text-uppercase');
                button.setAttribute('type', 'button');
                button.setAttribute('item', item['Id']);
                button.setAttribute('cname', characterName);
                button.appendChild(document.createTextNode('restore'));
                button.addEventListener('click', restoreItem);
                buttonCell.appendChild(button);
            });

            checkHasRecoverableItems();
        })
        .catch((msg) => {
            error.innerHTML = msg;
        })
        .finally(() => {
            $WowheadPower.refreshLinks();
            itemListLoaders.forEach(element => element.classList.add('hidden'));
        });
    }

    // Restore Item
    function restoreItem() {
        resetState();
        const item = this.getAttribute('item');
        const cname = this.getAttribute('cname');

        const loadingDiv = document.createElement('div');
        const loader = document.createElement('span');
        loader.classList.add('placeholder', 'col-12', 'bg-warning');
        loadingDiv.classList.add('placeholder-glow');
        loadingDiv.appendChild(loader);
        this.parentElement.appendChild(loadingDiv);
        this.parentElement.removeChild(this);
        fetch('<?= get_rest_url(null, 'acore/v1/item-restore'); ?>', {
            method: "POST",
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item: item,
                cname: cname,
            }),
        })
        .then(function(response) {
            return response.json();
        }).then(function(data) {
            if (data.toLowerCase().includes('mail')) {
                success.innerHTML = data;
                document.getElementById('row' + item).parentElement.removeChild(document.getElementById('row' + item))
                success.classList.remove('invisible');

                checkHasRecoverableItems();
            } 
            else error.innerHTML = data;
        })
        .catch((msg) => {
            error.innerHTML = msg;
        });        
    }

    function checkHasRecoverableItems() {
        // 5 is minimum count due to placeholders (loaders)
        if (itemList.childElementCount == 5) {
            const row = itemList.insertRow();
            row.id = "no-recoverable-items";
            const spanCell = row.insertCell();
            const infoSpan = document.createElement('span');
            infoSpan.style = "color: #fff";
            infoSpan.innerHTML = "No items to recover for the selected character.";
            spanCell.appendChild(infoSpan);
            
            // Add empty cell as well (to fill otherwise empty whitespace)
            row.insertCell();
        }

        else {
            const infoRow = document.getElementById('no-recoverable-items');
            if (infoRow) {
                infoRow.parentElement.removeChild(infoRow);
            }
        }
    }

    function resetState() {
        success.innerHTML = "";
        error.innerHTML = "";
        success.classList.add('invisible');
        noResults.classList.add('hidden');
    }
</script>
