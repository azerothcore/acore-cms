<script>const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};</script>
<div class="wrap">
    <h2><?= __('Item Restoration Tool') ?></h2>
    <p>Restore lost items <?= $test ?></p>
    <div class="row">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="text-uppercase">recoverable items</h4>
                    <hr>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="activeCharacter">Choose Character</button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden"></span>
                        </button>
                        <div id="errorBox" class="text-uppercase text-danger"></div>
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
                    <hr>
                    <div class="table-responsive invisible" style="background: #1d2327" id="itemContainer">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-uppercase">Item</th>
                                    <th scope="col" class="text-uppercase"></th>
                                </tr>
                            </thead>
                            <tbody class="text-primary important" id="itemList">
                                <tr>
                                    <td>
                                        <a href="#" data-wowhead="item=2828">Nissa's Remains</a>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info text-uppercase">restore</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-muted m-0"><em>Restored items will be sent to your characters mailbox.</em></p>
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
    const activeCharacter = document.getElementById('activeCharacter');
    const error = document.getElementById('errorBox');
    characters.forEach(character => character.addEventListener('click', selectCharacter));

    // Character Selection
    function selectCharacter() {
        const character = this.getAttribute('cguid');
        const characterName = this.firstChild.innerHTML;
        activeCharacter.innerHTML = characterName;

        fetch('<?= get_rest_url(null, 'acore/v1/item-restore/list/'); ?>' + character)
        .then(function(response) {
            return response.json();
        }).then(function(items) {
            if (!items || !items.length > 0) {
                // TODO - update with a div instead
                error.innerHTML = "No items to restore!";
                return;
            }

            itemContainer.classList.toggle('invisible');
            items.forEach(item => {
                const row = itemList.insertRow();
                const cell = row.insertCell();
                const itemLink = document.createElement('a');
                itemLink.href = "#";
                itemLink.setAttribute('data-wowhead', `item=${item['ItemEntry']}`);
                itemLink.appendChild(document.createTextNode(item['ItemEntry']));
                cell.appendChild(itemLink);
            });

            // TODO - Add restore button
        })
        .finally(() => $WowheadPower.refreshLinks())
        .catch((msg) => {
            error.innerHTML = msg;
        });
    }
</script>
