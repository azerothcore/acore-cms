<?php
    use ACore\Manager\Opts;
?>

<div class="wrap">
    <h1><?= __('AzerothCore', Opts::I()->page_alias)?></h1>
    <div class="card w-50">
        <div class="card-body">
            <h2>Tools</h2>
            <form method="post">
                <div class="col-sm-6">
                    <div class="card p-0">
                        <div class="card-body">
                            <h5>
                                Worldserver integration
                            </h5>
                            <hr>
                            <table class="form-table table table-borderless" role="presentation">
                                <tbody>
                                    <tr>
                                        <th>
                                            <label for="acore_item_restoration">Restore Items Service</label>
                                        </th>
                                        <td>
                                            <select name="acore_item_restoration" id="acore_item_restoration">
                                                <option value="0">Disabled</option>
                                                <option value="1" <?php if (Opts::I()->acore_item_restoration == '1') echo 'selected'; ?>>Enabled</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>" />
                </div>
            </form>
        </div>
    </div>
</div>
