<?php

use ACore\Manager\Opts;
use ACore\Manager\ACoreServices;

$user = wp_get_current_user();
$acServices = ACoreServices::I();
$userId = $acServices->getAcoreAccountId();
?>
<div class="wrap">
    <?php
    echo "<h2>" . __('Recruit a Friend', Opts::I()->page_alias) . "</h2>";
    ?>
    <p>Recruit your friends, help them to level up and get very awesome unique prizes.</p>
    <p style="color: red; font-size: 20px;">Recruiting from the same IP address will cause the RAF to be automatically removed and no new RAF can be applied again.</p>
    <div class="row">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-body">
                    <?php
                        if (!$rafPersonalInfo) {
                            $maxRecruitDatetime = (new \DateTime($user->get("user_registered")))->modify('+7days');
                            ?>
                        <?php if ($maxRecruitDatetime >= (new \DateTime())) { ?>
                        <p>You still have until <b><?php echo $maxRecruitDatetime->format('D, d M Y H:i'); ?> [server time]</b> to be recruited by a friend, enter his username here: </p>
                        <form method="post">
                            <p>
                                <input type="text" name="recruited" value="" placeholder="Recruiter code" size="20" required />
                                <input type="submit" name="Submit" class="button-primary" value="Recruit me!" />
                            </p>
                        </form>
                        <?php } else { ?>
                            <p>You were not been recruited by anyone. This is only possible when your account has less than 7 days.</p>
                        <?php } ?>
                    <?php
                    } else {
                        ?>
                            <p>You were recruited by <b>#<?php echo $rafPersonalInfo['recruiter_account']; ?></b> <?php
                            if ($rafPersonalInfo['time_stamp'] > 1) {
                                $deadline = new \Datetime();
                                // TODO Allow custom period for RAF
                                $deadline->setTimestamp($rafPersonalInfo['time_stamp'])->modify('+30days');
                                echo "<td>and is still Active, with deadline at <b>" . $deadline->format('D, d M Y H:i') . "h</b>.</td></tr>";
                            } else if ($rafPersonalInfo['time_stamp'] == 1) {
                                echo "<td>and you have reached the level-limit in time, giving a reward to your recruiter.</td></tr>";
                            } else {
                                echo "<td>but it has been Removed/Expired.</td></tr>";
                            } ?></p>
                    <?php } ?>
                </div>
            </div>
            <div class="card mt-1">
                <div class="card-body">

                <?php if (!$rafRecruitedInfo) {
                    ?>
                    <p>Start recruiting friends now and win unique prices by using your personal code: <b><?php echo $userId ?></b></p>
                <?php
                } else {
                    ?>
                    <h5>Recruiter progress</h4>
                    <hr>
                    <h6>Your personal recruit code is <span class="badge bg-primary"><?php echo $userId; ?></span></h6>
                    <h6>Your personal reward progress is <span class="badge bg-<?php echo $rafPersonalProgress['reward_level'] > 0 ? 'teal' : 'secondary'; ?>"><?php echo min([$rafPersonalProgress['reward_level'], 10]); ?>/10</span></h6>
                    <div class="m-2">
                        <ol class="acore-progress-bar">
                            <li class="<?php if ($rafPersonalProgress['reward_level'] <= 0) { echo 'is-active'; } else if ($rafPersonalProgress['reward_level'] > 0) { echo 'is-complete'; } ?>"><span>0</span></li>
                            <li class="<?php if ($rafPersonalProgress['reward_level'] >= 1) { echo 'is-complete'; } ?>"><span>1</span></li>
                            <li class="<?php if ($rafPersonalProgress['reward_level'] >= 3) { echo 'is-complete'; } else if ($rafPersonalProgress['reward_level'] > 1 && $rafPersonalProgress['reward_level'] < 3) { echo 'is-active'; } ?>"><span>3</span></li>
                            <li class="<?php if ($rafPersonalProgress['reward_level'] >= 10) { echo 'is-complete'; } else if ($rafPersonalProgress['reward_level'] > 3 && $rafPersonalProgress['reward_level'] < 10) { echo 'is-active'; } ?>"><span>10</span></li>
                        </ol>
                    </div>
                    <h6><b>Recruited friends</b></h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Player</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i = 1;
                            foreach ($rafRecruitedInfo as $player) {
                                echo "<tr><th scope=\"row\">" . $i . "</th>";
                                echo "<td>#" . $player['account_id'] . "</td>";
                                if ($player['time_stamp'] > 1) {
                                    $deadline = new \Datetime();
                                    $deadline->setTimestamp($player['time_stamp'])->modify('+30days');
                                    echo "<td>Active <p class=\"text-muted m-0\"><small>[Deadline at <b>" . $deadline->format('D, d M Y H:i') . "h</b>]</small></p></td></tr>";
                                } else if ($player['time_stamp'] == 1) {
                                    echo "<td>Completed</td></tr>";
                                } else {
                                    echo "<td>Removed/Expired</td></tr>";
                                }
                                $i++;
                            }
                            ?>
                            </tbody>
                        </table>
                        <p class="text-muted m-0"><em>All datetimes are server time.</em></p>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
