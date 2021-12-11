<?php
    use ACore\Manager\Opts;
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
                                    <label for="token">
                                        <label>Cred ID</label>
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
                                    <label for="token">
                                        <label for="amount">Amount per result</label>
                                    </th>
                                    <td>
                                        <input type="number" name="amount" id="amount" autocomplete="off" min=0 value=<?php echo $amount; ?> required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="is_winner">Result to reward</label>
                                    </th>
                                    <td>
                                        <select name="is_winner" id="result" required>
                                            <option value=null selected disabled>Select result</option>
                                            <option value=0 <?php if ($isWinner == 0) echo 'selected'; ?>>Looser</option>
                                            <option value=1 <?php if ($isWinner == 1) echo 'selected'; ?>>Winner</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="bracket">Bracket</label>
                                    </th>
                                    <td>
                                        <select name="bracket" id="bracket" required>
                                            <option value=0 <?php if ($bracket == 0) echo 'selected'; ?>>All</option>
                                            <option value=1 <?php if ($bracket == 1) echo 'selected'; ?>>10-19</option>
                                            <option value=2 <?php if ($bracket == 2) echo 'selected'; ?>>20-29</option>
                                            <option value=3 <?php if ($bracket == 3) echo 'selected'; ?>>30-39</option>
                                            <option value=4 <?php if ($bracket == 4) echo 'selected'; ?>>40-49</option>
                                            <option value=5 <?php if ($bracket == 5) echo 'selected'; ?>>50-59</option>
                                            <option value=6 <?php if ($bracket == 6) echo 'selected'; ?>>60-69</option>
                                            <option value=7 <?php if ($bracket == 7) echo 'selected'; ?>>70-79</option>
                                            <option value=8 <?php if ($bracket == 8) echo 'selected'; ?>>80</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="month">Month</label>
                                    </th>
                                    <td>
                                        <select name="month" id="month" required>
                                            <option value=0 <?php if ($month == 0) echo 'selected'; ?> disabled>Select month</option>
                                            <option value=1 <?php if ($month == 1) echo 'selected'; ?>>January</option>
                                            <option value=2 <?php if ($month == 2) echo 'selected'; ?>>February</option>
                                            <option value=3 <?php if ($month == 3) echo 'selected'; ?>>March</option>
                                            <option value=4 <?php if ($month == 4) echo 'selected'; ?>>April</option>
                                            <option value=5 <?php if ($month == 5) echo 'selected'; ?>>May</option>
                                            <option value=6 <?php if ($month == 6) echo 'selected'; ?>>June</option>
                                            <option value=7 <?php if ($month == 7) echo 'selected'; ?>>July</option>
                                            <option value=8 <?php if ($month == 8) echo 'selected'; ?>>August</option>
                                            <option value=9 <?php if ($month == 9) echo 'selected'; ?>>September</option>
                                            <option value=10 <?php if ($month == 10) echo 'selected'; ?>>October</option>
                                            <option value=11 <?php if ($month == 11) echo 'selected'; ?>>November</option>
                                            <option value=12 <?php if ($month == 12) echo 'selected'; ?>>December</option>
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
                                                echo "<option value=$i" . (($i == $year) ? " seleted" : "") . ">$i</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label>Top Extra Rewards</label>
                                    </th>
                                    <td><hr>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="top">Top</label>
                                    </th>
                                    <td>
                                        <select name="top" id="result" required>
                                            <option value=0 <?php if ($top == 0) echo 'selected'; ?>>None</option>
                                            <option value=5 <?php if ($top == 5) echo 'selected'; ?>>Top 5</option>
                                            <option value=10 <?php if ($top == 10) echo 'selected'; ?>>Top 10</option>
                                            <option value=15 <?php if ($top == 15) echo 'selected'; ?>>Top 15</option>
                                            <option value=20 <?php if ($top == 20) echo 'selected'; ?>>Top 20</option>
                                            <option value=25 <?php if ($top == 25) echo 'selected'; ?>>Top 25</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                    <label for="token">
                                        <label for="fixed_amount">Fixed amount</label>
                                    </th>
                                    <td>
                                        <input type="number" name="fixed_amount" id="fixed_amount" autocomplete="off" min=0 value=<?php echo $fixedAmount; ?> required />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                    <label for="token">
                                        <label for="step_amount">Step amount</label>
                                    </th>
                                    <td>
                                        <input type="number" name="step_amount" id="step_amount" autocomplete="off" min=0 value=<?php echo $stepAmount; ?> required />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="submit" name="preview" id="preview" class="button-secondary" value="<?php esc_attr_e('Preview', Opts::I()->page_alias) ?>" />
                                    </td>
                                    <td align="right">
                                        <input type="submit" name="send-rewards" id="send-rewards" class="button-primary" value="<?php esc_attr_e('Send rewards', Opts::I()->page_alias) ?>" />
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
                            $i = $top;
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
                                    $temp = $fixedAmount + ($stepAmount * $i);
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
        jQuery('#preview').on('click', function(e) {
            jQuery('#pvp-rewards').attr('method', 'GET');
        });
        jQuery('#send-rewards').on('click', function(e) {
            jQuery('#pvp-rewards').attr('method', 'POST');
        });
        jQuery('#pvp-rewards').on('submit', function(e) {
            var r = confirm("You sure you want to continue?");
            return r;
        });
    </script>
</div>
