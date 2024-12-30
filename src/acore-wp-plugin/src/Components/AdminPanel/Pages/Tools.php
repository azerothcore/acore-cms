<?php
    use ACore\Manager\Opts;
?>

<style>
    .acore-btn-danger {
        border-color: #d63638 !important;
        color: #d63638 !important;
        background: #f7f6f6 !important;
    }

    .acore-btn-danger .dashicons {
        margin-top: 3px;
    }
</style>

<div class="wrap">
    <h1><?= __('AzerothCore', Opts::I()->page_alias)?></h1>
    <div class="card">
        <div class="card-body">
            <h2>Tools</h2>
            <hr>
            <form method="post">
                <div class="row">
                    <div class="col-sm-3">
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
                                                <label for="acore_item_restoration">Item Restoration Service</label>
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

                    <div class="col-sm-3">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>
                                    Name Unlock Settings
                                </h5>
                                <hr>

                                <span>Allowed banned names table (characters database):</span>
                                <input type="text" name="acore_name_unlock_allowed_banned_names_table"
                                    value="<?= Opts::I()->acore_name_unlock_allowed_banned_names_table ?>">
                                <br>
                                <br>

                                <span>Inactivity Thresholds per Level:</span>
                                <table id="acore-name-unlock-thresholds" class="form-table table table-borderless" role="presentation">
                                    <thead>
                                        <tr>
                                            <th>Max Level (<)</th>
                                            <th>Minimum Days of Inactivity</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <div id="acore-name-unlock-thresholds-add" class="button"><span class="dashicons dashicons-plus" style="margin-top: 5px;"></span> Add</div>
                            </div>
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

<script>
    const deleteThreshold = (ev) => {
        const $ = jQuery;
        const $btn = $(ev.target);
        const $tr = $btn.closest("tr");
        $tr.remove();

        const $trs = $("#acore-name-unlock-thresholds tbody tr");
        let i = 0;
        for (const tr of $trs) {
            const previ = $(tr).data("i");
            $(tr).data("i", i);
            $(tr).find(`input[name="acore_name_unlock_thresholds[${previ}][0]"]`).attr("name", `acore_name_unlock_thresholds[${i}][0]`);
            $(tr).find(`input[name="acore_name_unlock_thresholds[${previ}][1]"]`).attr("name", `acore_name_unlock_thresholds[${i}][1]`);
            ++i;
        }
    };

    const addThreshold = (i = undefined, level = "", days = "") => {
        const $ = jQuery;

        if (i === undefined) {
            const $trs = $("#acore-name-unlock-thresholds tbody tr");
            i = $($trs[$trs.length - 1]).data("i") + 1;
        }

        const $tr = $("<tr>").appendTo("#acore-name-unlock-thresholds tbody");
        $tr.data("i", i);
        let $td = $("<td>").appendTo($tr);
        $(`<input type="number" name="acore_name_unlock_thresholds[${i}][0]" min="1" max="256" value="${level}">`).appendTo($td);
        $td = $("<td>").appendTo($tr);
        $(`<input type="number" name="acore_name_unlock_thresholds[${i}][1]" min="1" value="${days}">`).appendTo($td);
        $td = $("<td>").appendTo($tr);
        const $btnDel = $(`<div class="button acore-btn-danger acore-name-unlock-thresholds-delete">`).appendTo($td);
        $btnDel.append(`<span class="dashicons dashicons-trash"></span>`);
        $btnDel.on("click", deleteThreshold);
    };

    jQuery("#acore-name-unlock-thresholds-add").on("click", () => addThreshold());

    <?php foreach (Opts::I()->acore_name_unlock_thresholds as $i => $threshold) {
        if ($threshold[0] != "" && $threshold[1] != "") {
            echo "addThreshold($i, $threshold[0], $threshold[1]);";
        }
    } ?>
</script>
