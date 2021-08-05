<?php

namespace ACore;

use ACore;

require_once 'User.controller.php';

class UserView {

    private $controller;

    /**
     *
     * @param \ACore\UserController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function getRafProgressRender($rafPersonalInfo, $rafPersonalProgress, $rafRecruitedInfo) {
        ob_start();
        $user = wp_get_current_user();
        $acServices = ACoreServices::I();
        $userId = $acServices->getAcoreAccountId();

        ?>

        <style>
            :root {
                --color-white: #fff;
                --color-black: #333;
                --color-gray: #75787b;
                --color-gray-light: #bbb;
                --color-gray-disabled: #e8e8e8;
                --color-blue: #72AEE6;
                --color-blue-dark: #2271B1;
                --font-size-small: .75rem;
                --font-size-default: .875rem;
            }

            * {
                box-sizing: border-box;
            }

            body {
                color: var(--color-black);
            }

            section {
                margin: 2rem;
            }

            .progress-bar {
                display: flex;
                justify-content: space-between;
                list-style: none;
                padding: 0;
                margin: 0 0 1rem 0;
            }
            .progress-bar li {
                flex: 2;
                position: relative;
                padding: 0 0 14px 0;
                font-size: var(--font-size-default);
                line-height: 1.5;
                color: var(--color-blue);
                font-weight: 600;
                white-space: nowrap;
                overflow: visible;
                min-width: 0;
                text-align: center;
                border-bottom: 2px solid var(--color-gray-disabled);
            }
            .progress-bar li:first-child,
            .progress-bar li:last-child {
                flex: 1;
            }
            .progress-bar li:last-child {
                text-align: right;
            }
            .progress-bar li:before {
                content: "";
                display: block;
                width: 8px;
                height: 8px;
                background-color: var(--color-gray-disabled);
                border-radius: 50%;
                border: 2px solid var(--color-white);
                position: absolute;
                left: calc(50% - 6px);
                bottom: -7px;
                z-index: 3;
                transition: all .2s ease-in-out;
            }
            .progress-bar li:first-child:before {
                left: 0;
            }
            .progress-bar li:last-child:before {
                right: 0;
                left: auto;
            }
            .progress-bar span {
                transition: opacity .3s ease-in-out;
            }
            .progress-bar li:not(.is-active) span {
                opacity: 0;
            }
            .progress-bar .is-complete:not(:first-child):after,
            .progress-bar .is-active:not(:first-child):after {
                content: "";
                display: block;
                width: 100%;
                position: absolute;
                bottom: -2px;
                left: -50%;
                z-index: 2;
                border-bottom: 2px solid var(--color-blue);
            }
            .progress-bar li:last-child span {
                width: 200%;
                display: inline-block;
                position: absolute;
                left: -100%;
            }

            .progress-bar .is-complete:last-child:after,
            .progress-bar .is-active:last-child:after {
                width: 200%;
                left: -100%;
            }

            .progress-bar .is-complete:before {
                background-color: var(--color-blue);
            }

            .progress-bar .is-active:before,
            .progress-bar li:hover:before,
            .progress-bar .is-hovered:before {
                background-color: var(--color-white);
                border-color: var(--color-blue);
            }
            .progress-bar li:hover:before,
            .progress-bar .is-hovered:before {
                transform: scale(1.33);
            }

            .progress-bar li:hover span,
            .progress-bar li.is-hovered span {
                opacity: 1;
            }

            .progress-bar:hover li:not(:hover) span {
                opacity: 0;
            }

            .x-ray .progress-bar,
            .x-ray .progress-bar li {
                border: 1px dashed red;
            }

            .progress-bar .has-changes {
                opacity: 1 !important;
            }
            .progress-bar .has-changes:before {
                content: "";
                display: block;
                width: 8px;
                height: 8px;
                position: absolute;
                left: calc(50% - 4px);
                bottom: -20px;
                background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%208%208%22%3E%3Cpath%20fill%3D%22%23ed1c24%22%20d%3D%22M4%200l4%208H0z%22%2F%3E%3C%2Fsvg%3E');
            }

            .notice {
                margin-left: 0;
            }

            .w50 {
                width: 50% !important;
            }
            </style>
        <?php

        if (!$rafPersonalInfo) {
            $maxRecruitDatetime = (new \DateTime($user->get("user_registered")))->modify('+7days');
            ?>
            <div class="notice notice-info w50">
                <p>Hey! You have not been recruited by anyone.</p>
                <?php if ($maxRecruitDatetime >= (new \DateTime())) { ?>
                <p>You still have until <b><?php echo $maxRecruitDatetime->format('Y-m-d H:i'); ?> [server time]</b> to be recruited by a friend, enter his username here: </p>
                <form method="post">
                    <p>
                        <input type="text" name="recruited" value="" placeholder="Recruiter code" size="20" >
                        <input type="submit" name="Submit" class="button-primary" value="Recruit me!" />
                    </p>
                </form>
                <?php } ?>
            </div>
        <?php
        } else {
            $recruiterName = $acServices->getUserNameByUserId($rafPersonalInfo['recruiter_account']);
            ?>
            <div class="notice notice-info w50">
                <p>You were recruited by <b><?php echo $recruiterName; ?></b> <?php
                if ($rafPersonalInfo['time_stamp'] > 1) {
                    echo "<td>and is still Active.</td></tr>";
                } else if ($rafPersonalInfo['time_stamp'] == 1) {
                    echo "<td>and you have reached the level-limit in time, giving a reward to your recruiter.</td></tr>";
                } else {
                    echo "<td>but it has been Removed/Expired.</td></tr>";
                } ?></p>
            </div>
        <?php }

        if (!$rafRecruitedInfo) {
            ?>
            <div class="notice notice-info w50">
                <p>Hey! You don't have recruited anyone yet, what are you waiting for!.</p>
                <p>Invite your friends to play using your personal code: <b><?php echo $userId ?></b></p>
            </div>
        <?php
        } else {
            ?>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div id="postbox-container-1" class="postbox-container w50">
                        <div id="normal-sortables" class="meta-box-sortables">
                            <div id="dashboard_site_health" class="postbox ">
                                <div class="postbox-header"><h2 class="hndle">Recruiter progress</h2>
                                </div>
                                <div class="inside">
                                    <h4>Your personal recruit code is: <b><?php echo $userId; ?></b></h4>
                                    <h4>Your personal reward progress is: <b><?php echo min([$rafPersonalProgress['reward_level'], 10]); ?>/10</b></h4>
                                    <section>
                                        <ol class="progress-bar">
                                            <li class="<?php if ($rafPersonalProgress['reward_level'] <= 0) { echo 'is-active'; } else if ($rafPersonalProgress['reward_level'] > 0) { echo 'is-complete'; } ?>"><span>0</span></li>
                                            <li class="<?php if ($rafPersonalProgress['reward_level'] >= 1) { echo 'is-complete'; } ?>"><span>1</span></li>
                                            <li class="<?php if ($rafPersonalProgress['reward_level'] >= 3) { echo 'is-complete'; } else if ($rafPersonalProgress['reward_level'] > 1 && $rafPersonalProgress['reward_level'] < 3) { echo 'is-active'; } ?>"><span>3</span></li>
                                            <li class="<?php if ($rafPersonalProgress['reward_level'] >= 10) { echo 'is-complete'; } else if ($rafPersonalProgress['reward_level'] > 3 && $rafPersonalProgress['reward_level'] < 10) { echo 'is-active'; } ?>"><span>10</span></li>
                                        </ol>
                                    </section>
                                    <h4>Recruited players</h4>
                                    <table class="wp-list-table widefat fixed striped table-view-list"><thead>
                                        <tr>
                                            <th style='width:50px;'>#</th>
                                            <th>Player</th>
                                            <th>Status</th>
                                        </tr>
                                        </thead><tbody>
                                        <?php
                                        $i = 1;
                                        foreach ($rafRecruitedInfo as $player) {
                                            echo "<tr><td>" . $i . "</td>";
                                            echo "<td>" . $acServices->getUserNameByUserId($player['account_id']) . "</td>";
                                            if ($player['time_stamp'] > 1) {
                                                echo "<td>Active</td></tr>";
                                            } else if ($player['time_stamp'] == 1) {
                                                echo "<td>Completed</td></tr>";
                                            } else {
                                                echo "<td>Removed/Expired</td></tr>";
                                            }
                                            $i++;
                                        }
                                        ?>
                                        </tbody></table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }

        return ob_get_clean();
    }

}
