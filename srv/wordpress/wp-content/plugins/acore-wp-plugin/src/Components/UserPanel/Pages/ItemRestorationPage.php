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
                        <button type="button" class="btn btn-primary">Choose Character</button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Character Name</a></li>
                            <li><a class="dropdown-item" href="#">Character Name</a></li>
                            <li><a class="dropdown-item" href="#">Character Name</a></li>
                        </ul>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-uppercase">Item</th>
                                    <th scope="col" class="text-uppercase"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-primary">
                                    <td>
                                        <a href="#" data-wowhead="item=2828" class="text-primary important">Nissa's Remains</a>
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
