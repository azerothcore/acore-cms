<?php
    use ACore\Manager\Opts;
    // Now display the settings editing screen
    $myCredConfs = get_option('mycred_pref_core');
?>

<div class="wrap">
    <h2> <?= __('PvP Rewards', Opts::I()->page_alias)?> </h2>
    <p>Give custom credit rewards to players based on BG win or lose.</p>
    <div class="row">
        <div class="col-sm-4">
            <div class="card p-0">
                <div class="card-body">
                    <h5>Give rewards</h5>
                    <hr>
                    <form name="pvp-rewards" method="post" id="pvp-rewards" class="initial-form hide-if-no-js">
                        <input type="hidden" name="page" value="<?= ACORE_SLUG . '-pvp-rewards'; ?>" />
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="token">Cred ID</label>
                                    </th>
                                    <td>
                                        <?php if (isset($myCredConfs['cred_id'])) {
                                                    echo $myCredConfs['cred_id'] . ( isset($myCredConfs['name']['singular']) ? " (" . $myCredConfs['name']['singular'] . ")" : "");
                                                } else {
                                                    echo "<p>No Cred ID Found. Please check settings.</p>";
                                                    echo '<a href="' . admin_url( 'admin.php?page=' . MYCRED_SLUG . '-settings' ) . '" >' . __( 'MyCred Settings', 'mycred' ) . '</a>';
                                                } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="amount">Amount per result</label>
                                    </th>
                                    <td>
                                        <input type="number" name="amount" id="amount" autocomplete="off" min=0
                                            value="<?php echo $data['amount']; ?>" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="is_winner">Result to reward</label>
                                    </th>
                                    <td>
                                        <select name="is_winner" id="result" required>
                                            <option value="null" selected disabled>Select result</option>
                                            <option value="0" <?php if ($data['isWinner'] == 0) echo 'selected'; ?>>Looser
                                            </option>
                                            <option value="1" <?php if ($data['isWinner'] == 1) echo 'selected'; ?>>Winner
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="bracket">Bracket</label>
                                    </th>
                                    <td>
                                        <select name="bracket" id="bracket" required>
                                            <?php $maxPrefixLevel = 8; // add a setting to set realm max level.
                                                        for ($i = 0; $i <= $maxPrefixLevel; $i++) {
                                                        echo "<option value=\"{$i}\"" . ($data['bracket'] == $i ? ' selected' : "") . ">";
                                                        if ($i == 0) {
                                                            echo "All";
                                                        } elseif ($i == $maxPrefixLevel) {
                                                            echo "{$i}0";
                                                        } else {
                                                            echo "{$i}0-{$i}9";
                                                        }
                                                        echo "</option>";
                                                    }?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="month">Month</label>
                                    </th>
                                    <td>
                                        <select name="month" id="month" required>
                                            <option value="0" <?php if ($data['month'] == 0) echo 'selected'; ?> disabled>
                                                Select month</option>
                                            <?php
                                                    $start    = (new \DateTime('2010-01-01'));
                                                    $end      = (new \DateTime('2011-01-01'));
                                                    $interval = \DateInterval::createFromDateString('1 month');
                                                    $period   = new \DatePeriod($start, $interval, $end);

                                                    foreach ($period as $dt) {
                                                        echo $dt->format("Y-m") . "<br>\n";
                                                        echo "<option value=" . $dt->format("n") . ($data['month'] == $dt->format("n") ? " selected >" : " >" ) .$dt->format("F") . "</option>";
                                                    }?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="year">Year</label>
                                    </th>
                                    <td>
                                        <select name="year" id="year" required>
                                            <?php $year = (int) (new \DateTime())->format('Y');
                                                    for ($i = $year; $i >= 2015; $i--) {
                                                        echo "<option value=\"$i\"" . (($i == $year) ? " seleted" : "") . ">$i</option>";
                                                    }
                                                    ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="limit_rewards">Limit rewards <span
                                                class="dashicons dashicons-info fs-6" data-bs-toggle="tooltip"
                                                title="Limit rewards by a total of characters (use 0 for no limit)."></span></label>
                                    </th>
                                    <td>
                                        <input type="number" name="limit_rewards" id="limit_rewards" autocomplete="off"
                                            min="0" value="<?php echo $data['limitRewards']; ?>" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label>Top Extra Rewards</label>
                                    </th>
                                    <td>
                                        <hr>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="top">Top <span class="dashicons dashicons-info fs-6"
                                                data-bs-toggle="tooltip"
                                                title="Add an extra reward for a specific total of Top characters."></span></label>
                                    </th>
                                    <td>
                                        <select name="top" id="result" required>
                                            <option value="0" <?php if ($data['top'] == 0) echo 'selected'; ?>>None
                                            </option>
                                            <?php for ($i = 1; $i <= 5; $i++) {
                                                        echo "<option value=" . ($i*5) . ($data['top'] == $i*5 ? ' selected' : " ") . ">Top " . ($i*5) . "</option>";
                                                    } ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="fixed_amount">Fixed amount <span
                                                class="dashicons dashicons-info fs-6" data-bs-toggle="tooltip"
                                                title="Give an equal extra reward to the selected Top value."></span></label>
                                    </th>
                                    <td>
                                        <input type="number" name="fixed_amount" id="fixed_amount" autocomplete="off"
                                            min="0" value="<?php echo $data['fixedAmount']; ?>" required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="step_amount">Step amount <span class="dashicons dashicons-info fs-6"
                                                data-bs-toggle="tooltip"
                                                title="Give a decreasing amount based on a step value by the selected Top."></span></label>
                                    </th>
                                    <td>
                                        <input type="number" name="step_amount" id="step_amount" autocomplete="off"
                                            min="0" value="<?php echo $data['stepAmount']; ?>" required />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="submit" name="preview" id="preview" class="button-secondary"
                                            value="<?php esc_attr_e('Preview', Opts::I()->page_alias) ?>" />
                                    </td>
                                    <td align="right">
                                        <input type="submit" name="send-rewards" id="send-rewards"
                                            class="button-primary"
                                            value="<?php esc_attr_e('Send rewards', Opts::I()->page_alias) ?>" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="card p-0">
                <div class="card-body">
                    <h5>PvP Summary</h5>
                    <hr>
                    <?php
                            if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($result) && is_array($result) && count($result) > 0) {?>
                    <p><strong>This is a preview of top 10 players related to the options selected.</strong></p>
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Char name</th>
                                <th>Result Count</th>
                                <th>Points to Obtain</th>
                                <th>Extra Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                    $i = $data['top'];
                                    foreach ($result as $item) {
                                        echo "<tr><td>" . $item['username'] . "</td>";
                                        echo "<td>" . $item['character_name'] . "</td>";
                                        echo "<td>" . $item['total_battle'] . "</td>";
                                        $points = number_format(
                                            $item['points'],
                                            $myCredConfs['format']['decimals'],
                                            $myCredConfs['format']['separators']['decimal'],
                                            $myCredConfs['format']['separators']['thousand']);
                                        echo "<td>" . $points . "</td>";
                                        if ($i > 0) {
                                            $temp = $data['fixedAmount'] + ($data['stepAmount'] * $i);
                                            $points = number_format(
                                                $temp,
                                                $myCredConfs['format']['decimals'],
                                                $myCredConfs['format']['separators']['decimal'],
                                                $myCredConfs['format']['separators']['thousand']);
                                            echo "<td>" . $points . "</td></tr>";
                                            $i--;
                                        } else {
                                            echo "<td>0</td></tr>";
                                        }
                                    }
                                    ?>
                        </tbody>
                    </table>
                    <?php
                            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                echo "<p>No results found.</p>";
                            } else {
                                echo "<p>No results found.</p>";
                            }
                            ?>
                </div>
            </div>
        </div>
    </div>
    <hr />
    <script>
        jQuery('#preview').on('click', function (e) {
            jQuery('#pvp-rewards').attr('method', 'GET');
        });
        jQuery('#send-rewards').on('click', function (e) {
            jQuery('#pvp-rewards').attr('method', 'POST');
        });
        jQuery('#pvp-rewards').on('submit', function (e) {
            return confirm("You sure you want to continue?");
        });
        jQuery(document).on('ready', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</div>