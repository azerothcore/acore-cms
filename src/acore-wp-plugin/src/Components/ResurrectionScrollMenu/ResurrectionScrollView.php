<?php

namespace ACore\Components\ResurrectionScrollMenu;

class ResurrectionScrollView
{
    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function getRender($data)
    {
        ob_start();

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');

        // Determine account status
        $hasData = $data && isset($data['scrollData']) && $data['scrollData'];
        $isExpired = $hasData && $data['scrollData']['Expired'] == 1;
        $endDate = $hasData ? (int) $data['scrollData']['EndDate'] : null;
        $now = time();
        $isActive = $hasData && !$isExpired && $endDate > $now;
        $lastLogout = $data && isset($data['lastLogout']) ? $data['lastLogout'] : null;
        $daysInactive = $data ? (int) $data['daysInactive'] : 0;
        $isEligible = $data ? $data['isEligible'] : false;

        if ($isActive) {
            $statusLabel = 'Active';
            $statusClass = 'success';
        } elseif ($isExpired || ($hasData && $endDate <= $now)) {
            $statusLabel = 'Expired';
            $statusClass = 'secondary';
        } else {
            $statusLabel = 'Not Enrolled';
            $statusClass = 'warning';
        }

?>

        <div class="wrap">
            <h2>Scroll of Resurrection</h2>

            <div class="row">
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Your Account Status</h5>
                            <hr>
                            <?php if (!$data) { ?>
                                <div class="alert alert-danger mb-0">Unable to retrieve account information.</div>
                            <?php } else { ?>
                                <table class="table table-bordered table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="width: 40%;">Scroll Status</th>
                                            <td><span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                        </tr>
                                        <tr>
                                            <th>Eligibility</th>
                                            <td>
                                                <?php if ($isActive) { ?>
                                                    <span class="badge bg-success">Enrolled</span>
                                                <?php } elseif ($isEligible) { ?>
                                                    <span class="badge bg-info">Eligible</span>
                                                    <span class="text-muted">- Log in to the game server to activate</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-secondary">Not Eligible</span>
                                                    <span class="text-muted">- Requires <?= $daysInactive ?> days of inactivity<?php
                                                        if ($lastLogout) {
                                                            $daysSinceLogout = (int) ((time() - $lastLogout) / 86400);
                                                            $daysRemaining = $daysInactive - $daysSinceLogout;
                                                            if ($daysRemaining > 0) {
                                                                echo ' (' . $daysRemaining . ' days remaining)';
                                                            }
                                                        }
                                                    ?></span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Last Character Logout</th>
                                            <td>
                                                <?php if ($lastLogout) {
                                                    $logoutDate = new \DateTime();
                                                    $logoutDate->setTimestamp($lastLogout);
                                                    $daysSince = (int) ((time() - $lastLogout) / 86400);
                                                    echo esc_html($logoutDate->format('M j, Y - H:i')) . ' <span class="text-muted">(' . $daysSince . ' days ago)</span>';
                                                } else {
                                                    echo '<span class="text-muted">No characters found</span>';
                                                } ?>
                                            </td>
                                        </tr>
                                        <?php if ($hasData) { ?>
                                            <tr>
                                                <th>Bonus Expires</th>
                                                <td>
                                                    <?php
                                                        $expireDate = new \DateTime();
                                                        $expireDate->setTimestamp($endDate);
                                                        echo esc_html($expireDate->format('M j, Y - H:i'));
                                                        if ($isActive) {
                                                            $daysLeft = (int) ceil(($endDate - $now) / 86400);
                                                            echo ' <span class="text-muted">(' . $daysLeft . ' days remaining)</span>';
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Overview</h5>
                            <hr>
                            <p>
                                The <strong>Scroll of Resurrection</strong> is a comeback incentive for inactive players.
                                If your account has been inactive for an extended period, you will automatically receive
                                <strong>rested XP bonuses</strong> when you log in, helping you catch up faster.
                            </p>
                            <p>
                                The bonus grants a <strong>full rested XP pool</strong> for your current level each time
                                you log in or level up, and lasts for a limited duration from your first eligible login.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>How It Works</h5>
                            <hr>
                            <ol>
                                <li>When you log in, the system checks how long your account has been inactive.</li>
                                <li>If you have been away for long enough, your account becomes eligible for the bonus.</li>
                                <li>You receive a <strong>full rested XP pool</strong> on every login and level-up.</li>
                                <li>The bonus lasts for a set number of days from your first eligible login.</li>
                                <li>Once the duration expires, the bonus stops automatically.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Player Commands</h5>
                            <hr>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Command</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>.rscroll info</code></td>
                                        <td>View your scroll eligibility status, last logout date, and bonus expiration</td>
                                    </tr>
                                    <tr>
                                        <td><code>.rscroll disable</code></td>
                                        <td>Toggle the rested XP bonus on or off for your character (already earned XP is kept)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php
        return ob_get_clean();
    }
}
