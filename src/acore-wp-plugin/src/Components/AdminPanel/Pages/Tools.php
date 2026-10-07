<?php
    use ACore\Manager\Opts;
    $modulesCsv         = get_option('acore_modules_csv', '');
    $installedModules   = $modulesCsv ? array_map('trim', explode(',', $modulesCsv)) : [];
    $hasResurrectionMod = in_array('mod-resurrection-scroll', $installedModules);

    // If module is missing, treat as disabled for UI purposes
    $scrollEnabled = $hasResurrectionMod && Opts::I()->acore_resurrection_scroll == '1';
?>

<style>
    /* .acore-btn-danger styling lives in theme.css (shared light/dark) */
    .acore-days-inactive-disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .acore-days-inactive-disabled input,
    .acore-days-inactive-disabled select {
        pointer-events: none;
    }

    /* Missing module: greyed select + hover tooltip */
    .acore-missing-module-wrap {
        position: relative;
        display: inline-block;
    }
    .acore-missing-module-wrap select[disabled] {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }
    .acore-missing-module-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 0;
        background: #1c2128;
        color: #c9d1d9;
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 4px;
        white-space: nowrap;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    .acore-missing-module-wrap:hover .acore-missing-module-tooltip { display: block; }

    /* Confirm modal (defaults to "No") */
    .acore-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }
    .acore-modal-box {
        background: #fff;
        color: #1d2327;
        max-width: 420px;
        width: calc(100% - 40px);
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }
    body.acore-dark-mode .acore-modal-box {
        background: #1c2128;
        color: #c9d1d9;
    }
    .acore-modal-text {
        font-size: 13px;
        line-height: 1.5;
        white-space: pre-line;
        margin: 0 0 16px;
    }
    .acore-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
</style>

<div class="wrap">
    <h1><?= __('AzerothCore', Opts::I()->page_alias)?></h1>
    <div class="card">
        <div class="card-body">
            <h2>Tools</h2>
            <hr>
            <form method="post">
                <?php wp_nonce_field('acore_tools_save', 'acore_tools_nonce'); ?>
                <div class="row">

                    <!-- Col 1: World Server Integration -->
                    <div class="col-sm-4">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>World Server Integration</h5>
                                <hr>
                                <table class="form-table table table-borderless" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th><label class="acore-help-label" title="Lets players restore recently deleted items to their characters via in-game mail.">Item Restoration Service</label></th>
                                            <td>
                                                <select name="acore_item_restoration" id="acore_item_restoration">
                                                    <option value="0">Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_item_restoration == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php if (!$hasResurrectionMod): ?>
                                                    <span class="acore-missing-module-wrap">
                                                        <label class="acore-help-label" style="color:#d63638;" title="Allows inactive characters to be restored via the Scroll of Resurrection module.">Scroll of Resurrection</label>
                                                        <span class="acore-missing-module-tooltip">Requires module mod-resurrection-scroll</span>
                                                    </span>
                                                <?php else: ?>
                                                    <label class="acore-help-label" title="Allows inactive characters to be restored via the Scroll of Resurrection module.">Scroll of Resurrection</label>
                                                <?php endif; ?>
                                            </th>
                                            <td>
                                                <?php if (!$hasResurrectionMod): ?>
                                                    <input type="hidden" name="acore_resurrection_scroll" value="0">
                                                    <span class="acore-missing-module-wrap">
                                                        <select name="acore_resurrection_scroll" id="acore_resurrection_scroll" disabled>
                                                            <option value="0">Disabled</option>
                                                        </select>
                                                        <span class="acore-missing-module-tooltip">Requires module mod-resurrection-scroll</span>
                                                    </span>
                                                <?php else: ?>
                                                    <select name="acore_resurrection_scroll" id="acore_resurrection_scroll">
                                                        <option value="0">Disabled</option>
                                                        <option value="1" <?php if ($scrollEnabled) echo 'selected'; ?>>Enabled</option>
                                                    </select>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label class="acore-help-label" title="How many days a character must be inactive before a Scroll of Resurrection can target it.">Days Inactive</label></th>
                                            <td>
                                                <span id="acore-days-inactive-wrap"
                                                      class="<?= !$scrollEnabled ? 'acore-days-inactive-disabled' : '' ?>"
                                                      title="<?= !$scrollEnabled ? 'Scroll of Resurrection must be enabled' : '' ?>">
                                                    <input type="number"
                                                           name="acore_resurrection_scroll_days_inactive"
                                                           id="acore_resurrection_scroll_days_inactive"
                                                           min="1"
                                                           value="<?= esc_attr(Opts::I()->acore_resurrection_scroll_days_inactive) ?>"
                                                           <?= !$scrollEnabled ? 'disabled' : '' ?>>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="acore_smartstone_enabled">Smartstone Token Gifting</label>
                                                <p class="description">Requires the mod-chromiecraft-smartstone module. Lets buyers gift name/faction/race/customize services as tokens.</p>
                                            </th>
                                            <td>
                                                <select name="acore_smartstone_enabled" id="acore_smartstone_enabled">
                                                    <option value="0">Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_smartstone_enabled == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Col 2: Web Integration -->
                    <div class="col-sm-4">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>Web Integration</h5>
                                <hr>
                                <table class="form-table table table-borderless" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th><label class="acore-help-label" title="Records each website login (IP, country, date) and the account's last in-game IP for the Recent Connections view.">Security Logging</label></th>
                                            <td>
                                                <select name="acore_security_logging" id="acore_security_logging">
                                                    <option value="0" <?php if (Opts::I()->acore_security_logging != '1') echo 'selected'; ?>>Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_security_logging == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label class="acore-help-label" title="When enabled, login IPs are sent to ip-api.com to resolve their country. Disabled keeps IPs in-house (country shows as Unknown). Backfills older Unknown entries in the background within ip-api's free rate limit.">GeoIP Country Lookup</label></th>
                                            <td>
                                                <input type="hidden" name="acore_geoip_lookup" value="0">
                                                <span id="acore-geoip-wrap"
                                                      class="<?= Opts::I()->acore_security_logging != '1' ? 'acore-days-inactive-disabled' : '' ?>"
                                                      title="<?= Opts::I()->acore_security_logging != '1' ? 'Security Logging must be enabled' : '' ?>">
                                                    <select name="acore_geoip_lookup" id="acore_geoip_lookup" <?= Opts::I()->acore_security_logging != '1' ? 'disabled' : '' ?>>
                                                        <option value="0" <?php if (Opts::I()->acore_geoip_lookup != '1') echo 'selected'; ?>>Disabled</option>
                                                        <option value="1" <?php if (Opts::I()->acore_geoip_lookup == '1') echo 'selected'; ?>>Enabled</option>
                                                    </select>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label class="acore-help-label" title="This sets the settings to show for account bans, account mutes and character bans.">Punishment Info</label></th>
                                            <td>
                                                <select name="acore_punishment_info_enabled" id="acore_punishment_info_enabled">
                                                    <option value="0" <?php if (Opts::I()->acore_punishment_info_enabled != '1') echo 'selected'; ?>>Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_punishment_info_enabled == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                                <div id="acore-punishment-info-row" style="display:flex;gap:16px;margin-top:8px;<?php if (Opts::I()->acore_punishment_info_enabled != '1') echo 'opacity:0.45;pointer-events:none;'; ?>">
                                                    <label style="display:flex;flex-direction:column;align-items:center;gap:4px;font-size:12px;">
                                                        Account Ban
                                                        <input type="hidden" name="acore_punishment_info_account_ban" value="0">
                                                        <input type="checkbox" name="acore_punishment_info_account_ban" value="1" <?php if (Opts::I()->acore_punishment_info_account_ban == '1') echo 'checked'; ?> <?php if (Opts::I()->acore_punishment_info_enabled != '1') echo 'disabled'; ?>>
                                                    </label>
                                                    <label style="display:flex;flex-direction:column;align-items:center;gap:4px;font-size:12px;">
                                                        Account Mute
                                                        <input type="hidden" name="acore_punishment_info_account_mute" value="0">
                                                        <input type="checkbox" name="acore_punishment_info_account_mute" value="1" <?php if (Opts::I()->acore_punishment_info_account_mute == '1') echo 'checked'; ?> <?php if (Opts::I()->acore_punishment_info_enabled != '1') echo 'disabled'; ?>>
                                                    </label>
                                                    <label style="display:flex;flex-direction:column;align-items:center;gap:4px;font-size:12px;">
                                                        Character Ban
                                                        <input type="hidden" name="acore_punishment_info_character_ban" value="0">
                                                        <input type="checkbox" name="acore_punishment_info_character_ban" value="1" <?php if (Opts::I()->acore_punishment_info_character_ban == '1') echo 'checked'; ?> <?php if (Opts::I()->acore_punishment_info_enabled != '1') echo 'disabled'; ?>>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label class="acore-help-label" title="The TOTPMasterSecret from your authserver.conf, hexadecimal and with no prefix. Players turn on In-game 2FA from their Security page: the site generates the key, shows the QR code and writes it to the account itself. Leave this empty if authserver.conf leaves it empty - it has to match, or the server will not be able to read the keys the site writes.">In-game 2FA Master Secret</label></th>
                                            <td>
                                                <input type="password" name="acore_totp_master_secret" id="acore_totp_master_secret"
                                                    value="<?= esc_attr(Opts::I()->acore_totp_master_secret) ?>"
                                                    autocomplete="off" spellcheck="false"
                                                    placeholder="same value as TOTPMasterSecret"
                                                    style="width:100%;max-width:480px;font-family:monospace;">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label class="acore-help-label" title="If disabled, users cannot reuse any of their last 10 passwords when changing it.">Allow Old Passwords</label></th>
                                            <td>
                                                <select name="acore_allow_old_passwords" id="acore_allow_old_passwords">
                                                    <option value="0" <?php if (Opts::I()->acore_allow_old_passwords != '1') echo 'selected'; ?>>Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_allow_old_passwords == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <hr style="margin:12px 0;">

                                <!-- Remove 2FA -->
                                <p style="font-weight:600; margin:0 0 4px; font-size:13px;">Remove 2FA</p>
                                <p style="font-size:12px; color:#646970; margin:0 0 12px;">
                                    Remove Website or In-game 2FA for any account. A warning is shown to the user until they re-enable it.
                                </p>

                                <!-- Website 2FA -->
                                <p style="font-size:12px; font-weight:600; margin:0 0 4px;">Website</p>
                                <div style="display:flex; gap:6px; align-items:center; margin-bottom:6px; flex-wrap:wrap;">
                                    <input type="text" id="acore-2fa-web-user" placeholder="Account name" style="flex:1 1 120px; min-width:80px;">
                                    <button type="button" id="acore-2fa-web-check" class="button button-secondary" style="white-space:nowrap;">Check</button>
                                    <button type="button" id="acore-2fa-web-remove" class="button acore-btn-danger" style="white-space:nowrap;" disabled>Remove</button>
                                </div>
                                <p id="acore-2fa-web-msg" style="font-size:12px; margin:0 0 12px; min-height:18px;"></p>

                                <!-- Backup codes (always visible; greyed until a Website check finds codes) -->
                                <div id="acore-backup-wrap" style="margin:0 0 12px; opacity:0.45;">
                                    <p style="font-size:12px; font-weight:600; margin:0 0 4px;">Backup Codes</p>
                                    <span id="acore-backup-info" style="font-size:12px;">Check a Website account above to view backup codes.</span>
                                    <button type="button" id="acore-backup-remove" class="button acore-btn-danger" style="white-space:nowrap; margin-left:6px;" disabled>Remove backup codes</button>
                                </div>

                                <!-- In-game 2FA -->
                                <p style="font-size:12px; font-weight:600; margin:0 0 4px;">In-Game</p>
                                <div style="display:flex; gap:6px; align-items:center; margin-bottom:6px; flex-wrap:wrap;">
                                    <input type="text" id="acore-2fa-game-user" placeholder="Account name" style="flex:1 1 120px; min-width:80px;">
                                    <button type="button" id="acore-2fa-game-check" class="button button-secondary" style="white-space:nowrap;">Check</button>
                                    <button type="button" id="acore-2fa-game-remove" class="button acore-btn-danger" style="white-space:nowrap;" disabled>Remove</button>
                                </div>
                                <p id="acore-2fa-game-msg" style="font-size:12px; margin:0 0 4px; min-height:18px;"></p>

                            </div><!-- /card-body Web Integration -->
                        </div><!-- /card Web Integration -->
                    </div><!-- /col2 -->

                    <!-- Col 3: Name Unlock Settings -->
                    <div class="col-sm-4">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>Name Unlock Settings</h5>
                                <hr>

                                <span>Allowed banned names table (characters database):</span>
                                <input type="text" name="acore_name_unlock_allowed_banned_names_table"
                                    value="<?= esc_attr(Opts::I()->acore_name_unlock_allowed_banned_names_table) ?>">
                                <br><br>

                                <span>Inactivity Thresholds per Level:</span>
                                <input type="hidden" name="acore_name_unlock_thresholds_present" value="1">
                                <table id="acore-name-unlock-thresholds" class="form-table table table-borderless" role="presentation">
                                    <thead>
                                        <tr>
                                            <th>Max Level (&lt;)</th>
                                            <th>Minimum Days of Inactivity</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <div style="display:flex; gap:6px; margin-top:4px;">
                                    <div id="acore-name-unlock-thresholds-add" class="button">
                                        <span class="dashicons dashicons-plus" style="margin-top:5px;"></span> Add
                                    </div>
                                    <div id="acore-name-unlock-reset" class="button acore-btn-danger" title="Reset Name Unlock to defaults">
                                        <span class="dashicons dashicons-image-rotate" style="margin-top:5px;"></span> Reset
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        /** Render the big D/H/M/S grid (same style as the default cooldown rows). */
                        function acorePdumpCdGrid(string $name, int $total, string $dis = ''): string {
                            $y  = intdiv($total, 31536000);
                            $mo = intdiv($total % 31536000, 2592000);
                            $d  = intdiv($total % 2592000, 86400);
                            $h  = intdiv($total % 86400, 3600);
                            $lbl = '<label style="display:flex;flex-direction:column;align-items:center;gap:3px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin:0;">';
                            return '<div class="acore-pdump-cd-wrap">'
                                . '<input type="hidden" class="acore-pdump-cd-secs" name="' . esc_attr($name) . '" value="' . $total . '">'
                                . '<div class="acore-pdump-cd-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">'
                                . $lbl . '<input type="number" min="0" max="99"  class="acore-cd-y"  style="width:100%;text-align:center;" value="' . $y  . '" ' . $dis . '>Years</label>'
                                . $lbl . '<input type="number" min="0" max="11"  class="acore-cd-mo" style="width:100%;text-align:center;" value="' . $mo . '" ' . $dis . '>Months</label>'
                                . $lbl . '<input type="number" min="0" max="29"  class="acore-cd-d"  style="width:100%;text-align:center;" value="' . $d  . '" ' . $dis . '>Days</label>'
                                . $lbl . '<input type="number" min="0" max="23"  class="acore-cd-h"  style="width:100%;text-align:center;" value="' . $h  . '" ' . $dis . '>Hours</label>'
                                . '</div></div>';
                        }
                        ?>
                        <!-- PDUMP Settings -->
                        <div class="card p-0" style="margin-top:16px;">
                            <div class="card-body">
                                <h5>PDUMP Settings</h5>
                                <hr>
                                <?php
                                    $cdSingle     = max(0, (int) Opts::I()->acore_pdump_cooldown_single);
                                    $cdAll        = max(0, (int) Opts::I()->acore_pdump_cooldown_all);
                                    $pdumpOn      = Opts::I()->acore_pdump_enabled == '1';
                                    $dis          = $pdumpOn ? '' : 'disabled';
                                    $dep          = $pdumpOn ? '' : 'style="opacity:0.45;pointer-events:none;"';
                                    $singleEnabled      = Opts::I()->acore_pdump_single_enabled    != '0';
                                    $allEnabled         = Opts::I()->acore_pdump_all_enabled       != '0';
                                    $blockMaintenance   = Opts::I()->acore_pdump_block_maintenance  != '0';
                                    $minReqEnabled      = Opts::I()->acore_pdump_min_req_enabled         != '0';
                                    $minPlaytime        = max(0, (int) Opts::I()->acore_pdump_min_playtime);
                                    $minAcctAge         = max(0, (int) Opts::I()->acore_pdump_min_acct_age);
                                    $minCharLevel       = max(0, (int) Opts::I()->acore_pdump_min_char_level);
                                    $subEnabled   = Opts::I()->acore_pdump_subscription_enabled == '1';
                                    $subCooldowns = Opts::I()->acore_pdump_subscription_cooldowns;
                                    if (!is_array($subCooldowns)) $subCooldowns = [];
                                    $rbacEnabled   = Opts::I()->acore_pdump_rbac_enabled == '1';
                                    $rbacCooldowns = Opts::I()->acore_pdump_rbac_cooldowns;
                                    if (!is_array($rbacCooldowns)) $rbacCooldowns = [];
                                    $contribEnabled   = Opts::I()->acore_pdump_contributor_enabled == '1';
                                    $contribCooldowns = Opts::I()->acore_pdump_contributor_cooldowns;
                                    if (!is_array($contribCooldowns)) $contribCooldowns = [];
                                ?>
                                <table class="form-table table table-borderless" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th><label class="acore-help-label" title="Allows players to export their characters individually or all at once as a PDUMP file, importable into any AzerothCore server. Custom content (Transmog, Physical Costumes) is NOT included. The following information is automatically anonymised before download: character name, position and hearthstone (reset to faction capital), gold, timestamps, online status, achievement dates, mail contents and sender, item creator/gifter GUIDs, aura caster GUIDs, equipment set names, custom chat channels, and all character and account IDs (replaced with random values).">Enable PDump</label></th>
                                            <td>
                                                <select name="acore_pdump_enabled" id="acore_pdump_enabled">
                                                    <option value="0" <?php if (!$pdumpOn) echo 'selected'; ?>>Disabled</option>
                                                    <option value="1" <?php if ($pdumpOn)  echo 'selected'; ?>>Enabled</option>
                                                </select>
                                                <br>
                                                <label style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-top:6px;cursor:pointer;">
                                                    <input type="hidden" name="acore_pdump_log_enabled" value="0">
                                                    <input type="checkbox" name="acore_pdump_log_enabled" value="1" <?= Opts::I()->acore_pdump_log_enabled != '0' ? 'checked' : '' ?>>
                                                    Enable Logging
                                                </label>
                                            </td>
                                        </tr>
                                        <tr id="acore-pdump-bug-url-row" <?php if (!$pdumpOn) echo 'style="opacity:0.45;pointer-events:none;"'; ?>>
                                            <th><label class="acore-help-label" title="GitHub Issues URL where players are directed to report bugs when a PDUMP export fails. Requires PDUMP to be enabled.">PDUMP Bug Report URL</label></th>
                                            <td>
                                                <input type="url" name="acore_bug_report_url" id="acore_bug_report_url"
                                                    value="<?= esc_attr(Opts::I()->acore_bug_report_url) ?>"
                                                    placeholder="https://github.com/your-org/your-repo/issues/new"
                                                    style="width:100%;max-width:480px;"
                                                    <?= $dis ?>>
                                            </td>
                                        </tr>
                                        <tr class="acore-pdump-dependent" <?= $dep ?>>
                                            <th><label class="acore-help-label" title="Mirrors realmlist.allowedSecurityLevel — the minimum account security required to log in to the server. Accounts with security below this value cannot use PDUMP. Default 0: everyone can use PDUMP. Set to 1 to allow only GMs and above (like a GM-only realm).">Allowed Security Level</label></th>
                                            <td><input type="number" name="acore_pdump_allowed_sec_level" min="0" max="255" value="<?= esc_attr((int) Opts::I()->acore_pdump_allowed_sec_level) ?>" style="width:60px;text-align:center;" <?= $dis ?>></td>
                                        </tr>
                                        <tr class="acore-pdump-dependent" <?= $dep ?>>
                                            <td colspan="2">
                                                <label class="acore-help-label" style="display:flex;align-items:center;gap:6px;cursor:pointer;" title="When the server's realmlist.allowedSecurityLevel is ≥ 1, only GMs can log in (maintenance mode). Enable this to automatically block PDUMP exports while the server is inaccessible to regular players.">
                                                    <input type="hidden" name="acore_pdump_block_maintenance" value="0" <?= $dis ?>>
                                                    <input type="checkbox" name="acore_pdump_block_maintenance" value="1" <?= $blockMaintenance ? 'checked' : '' ?> <?= $dis ?>>
                                                    Block PDUMP during maintenance
                                                </label>
                                            </td>
                                        </tr>
                                        <?php
                                        // ── Minimum Requirements ──────────────────────────────────────────
                                        $lbl5 = 'display:flex;flex-direction:column;align-items:center;gap:3px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin:0;';
                                        $mrDis          = $minReqEnabled ? '' : 'disabled';
                                        $minPtEnabled   = Opts::I()->acore_pdump_min_playtime_enabled   != '0';
                                        $minAgeEnabled  = Opts::I()->acore_pdump_min_acct_age_enabled   != '0';
                                        $minLvlEnabled  = Opts::I()->acore_pdump_min_char_level_enabled != '0';
                                        ?>
                                        <tr class="acore-pdump-dependent" <?= $dep ?>>
                                            <td colspan="2" style="padding-top:12px;border-top:1px solid #30363d;">
                                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                                                    <strong style="font-size:13px;">Minimum Requirements</strong>
                                                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-left:auto;white-space:nowrap;cursor:pointer;">
                                                        <input type="hidden" name="acore_pdump_min_req_enabled" value="0" <?= $dis ?>>
                                                        <input type="checkbox" id="acore-pdump-min-req-enabled-cb" name="acore_pdump_min_req_enabled" value="1" <?= $minReqEnabled ? 'checked' : '' ?> <?= $dis ?>>
                                                        Enabled
                                                    </label>
                                                </div>
                                                <p style="font-size:11px;color:#8b949e;margin:0 0 10px;">Accounts must meet all checked requirements below before PDUMP is available.</p>
                                                <div id="acore-pdump-min-req-fields" <?= !$minReqEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>

                                                    <!-- Min Total Playtime -->
                                                    <div id="acore-pdump-min-pt-wrap" style="margin-bottom:12px;">
                                                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                                            <label style="font-weight:600;font-size:12px;margin:0;">Min Total Playtime <span style="font-weight:400;color:#8b949e;">(sum across all characters)</span></label>
                                                            <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-left:auto;white-space:nowrap;cursor:pointer;">
                                                                <input type="hidden" name="acore_pdump_min_playtime_enabled" value="0">
                                                                <input type="checkbox" id="acore-pdump-min-pt-enabled-cb" name="acore_pdump_min_playtime_enabled" value="1" <?= $minPtEnabled ? 'checked' : '' ?> <?= $dis ?: $mrDis ?>>
                                                                Enabled
                                                            </label>
                                                        </div>
                                                        <div id="acore-pdump-min-pt-grid" <?= !$minPtEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                            <input type="hidden" name="acore_pdump_min_playtime" id="acore_pdump_min_playtime" value="<?= esc_attr($minPlaytime) ?>">
                                                            <?php $y=intdiv($minPlaytime,31536000); $mo=intdiv($minPlaytime%31536000,2592000); $d=intdiv($minPlaytime%2592000,86400); $h=intdiv($minPlaytime%86400,3600); ?>
                                                            <div class="acore-cooldown-inputs" data-target="acore_pdump_min_playtime" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;max-width:360px;">
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="99" class="acore-cd-y"  style="width:100%;text-align:center;" value="<?= $y ?>"  <?= $dis ?: ($mrDis ?: (!$minPtEnabled ? 'disabled' : '')) ?>>Years</label>
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="11" class="acore-cd-mo" style="width:100%;text-align:center;" value="<?= $mo ?>" <?= $dis ?: ($mrDis ?: (!$minPtEnabled ? 'disabled' : '')) ?>>Months</label>
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="29" class="acore-cd-d"  style="width:100%;text-align:center;" value="<?= $d ?>"  <?= $dis ?: ($mrDis ?: (!$minPtEnabled ? 'disabled' : '')) ?>>Days</label>
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="23" class="acore-cd-h"  style="width:100%;text-align:center;" value="<?= $h ?>"  <?= $dis ?: ($mrDis ?: (!$minPtEnabled ? 'disabled' : '')) ?>>Hours</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Min Account Age -->
                                                    <div id="acore-pdump-min-age-wrap" style="margin-bottom:12px;">
                                                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                                            <label style="font-weight:600;font-size:12px;margin:0;">Min Account Age <span style="font-weight:400;color:#8b949e;">(since registration)</span></label>
                                                            <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-left:auto;white-space:nowrap;cursor:pointer;">
                                                                <input type="hidden" name="acore_pdump_min_acct_age_enabled" value="0">
                                                                <input type="checkbox" id="acore-pdump-min-age-enabled-cb" name="acore_pdump_min_acct_age_enabled" value="1" <?= $minAgeEnabled ? 'checked' : '' ?> <?= $dis ?: $mrDis ?>>
                                                                Enabled
                                                            </label>
                                                        </div>
                                                        <div id="acore-pdump-min-age-grid" <?= !$minAgeEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                            <input type="hidden" name="acore_pdump_min_acct_age" id="acore_pdump_min_acct_age" value="<?= esc_attr($minAcctAge) ?>">
                                                            <?php $y=intdiv($minAcctAge,31536000); $mo=intdiv($minAcctAge%31536000,2592000); $d=intdiv($minAcctAge%2592000,86400); $h=intdiv($minAcctAge%86400,3600); ?>
                                                            <div class="acore-cooldown-inputs" data-target="acore_pdump_min_acct_age" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;max-width:360px;">
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="99" class="acore-cd-y"  style="width:100%;text-align:center;" value="<?= $y ?>"  <?= $dis ?: ($mrDis ?: (!$minAgeEnabled ? 'disabled' : '')) ?>>Years</label>
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="11" class="acore-cd-mo" style="width:100%;text-align:center;" value="<?= $mo ?>" <?= $dis ?: ($mrDis ?: (!$minAgeEnabled ? 'disabled' : '')) ?>>Months</label>
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="29" class="acore-cd-d"  style="width:100%;text-align:center;" value="<?= $d ?>"  <?= $dis ?: ($mrDis ?: (!$minAgeEnabled ? 'disabled' : '')) ?>>Days</label>
                                                                <label style="<?= $lbl5 ?>"><input type="number" min="0" max="23" class="acore-cd-h"  style="width:100%;text-align:center;" value="<?= $h ?>"  <?= $dis ?: ($mrDis ?: (!$minAgeEnabled ? 'disabled' : '')) ?>>Hours</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Min Character Level -->
                                                    <div id="acore-pdump-min-lvl-wrap">
                                                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                                            <label style="font-weight:600;font-size:12px;margin:0;">Min Character Level</label>
                                                            <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-left:auto;white-space:nowrap;cursor:pointer;">
                                                                <input type="hidden" name="acore_pdump_min_char_level_enabled" value="0">
                                                                <input type="checkbox" id="acore-pdump-min-lvl-enabled-cb" name="acore_pdump_min_char_level_enabled" value="1" <?= $minLvlEnabled ? 'checked' : '' ?> <?= $dis ?: $mrDis ?>>
                                                                Enabled
                                                            </label>
                                                        </div>
                                                        <div id="acore-pdump-min-lvl-grid" <?= !$minLvlEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                            <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#8b949e;">
                                                                Level (1–255):
                                                                <input type="number" name="acore_pdump_min_char_level" min="1" max="255" value="<?= esc_attr($minCharLevel) ?>" style="width:60px;text-align:center;" <?= $dis ?: ($mrDis ?: (!$minLvlEnabled ? 'disabled' : '')) ?>>
                                                            </label>
                                                        </div>
                                                    </div>

                                                </div>
                                            </td>
                                        </tr>
                                        <?php $cdLbl = 'display:flex;flex-direction:column;align-items:center;gap:3px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin:0;'; ?>
                                        <tr class="acore-pdump-dependent" <?= $dep ?>>
                                            <td colspan="2" style="padding-top:12px;border-top:1px solid #30363d;">
                                                <input type="hidden" name="acore_pdump_cooldown_single" id="acore_pdump_cooldown_single" value="<?= esc_attr($cdSingle) ?>">
                                                <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                                                    <div>
                                                        <label class="acore-help-label" title="How long a player must wait between single character exports. Set all to 0 for no cooldown." style="font-weight:600;margin:0;display:block;">Single Dump Cooldown</label>
                                                        <span style="font-size:11px;color:#8b949e;">(Everyone / Default)</span>
                                                    </div>
                                                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-left:auto;white-space:nowrap;cursor:pointer;">
                                                        <input type="hidden" name="acore_pdump_single_enabled" value="0" <?= $dis ?>>
                                                        <input type="checkbox" id="acore-pdump-single-enabled-cb" name="acore_pdump_single_enabled" value="1" <?= $singleEnabled ? 'checked' : '' ?> <?= $dis ?>>
                                                        Enabled
                                                    </label>
                                                </div>
                                                <div id="acore-pdump-single-cd-grid" <?= !$singleEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                    <div class="acore-cooldown-inputs" data-target="acore_pdump_cooldown_single"
                                                         style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;max-width:360px;">
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="99"  class="acore-cd-y"  style="width:100%;text-align:center;" value="<?= intdiv($cdSingle, 31536000) ?>"           <?= ($dis ?: (!$singleEnabled ? 'disabled' : '')) ?>>Years</label>
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="11"  class="acore-cd-mo" style="width:100%;text-align:center;" value="<?= intdiv($cdSingle % 31536000, 2592000) ?>" <?= ($dis ?: (!$singleEnabled ? 'disabled' : '')) ?>>Months</label>
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="29"  class="acore-cd-d"  style="width:100%;text-align:center;" value="<?= intdiv($cdSingle % 2592000, 86400) ?>"   <?= ($dis ?: (!$singleEnabled ? 'disabled' : '')) ?>>Days</label>
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="23"  class="acore-cd-h"  style="width:100%;text-align:center;" value="<?= intdiv($cdSingle % 86400, 3600) ?>"      <?= ($dis ?: (!$singleEnabled ? 'disabled' : '')) ?>>Hours</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="acore-pdump-dependent" <?= $dep ?>>
                                            <td colspan="2" style="padding-top:8px;">
                                                <input type="hidden" name="acore_pdump_cooldown_all" id="acore_pdump_cooldown_all" value="<?= esc_attr($cdAll) ?>">
                                                <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                                                    <div>
                                                        <label class="acore-help-label" title="How long a player must wait between Export All (zip) downloads. Set all to 0 for no cooldown." style="font-weight:600;margin:0;display:block;">Export All Cooldown</label>
                                                        <span style="font-size:11px;color:#8b949e;">(Everyone / Default)</span>
                                                    </div>
                                                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8b949e;margin-left:auto;white-space:nowrap;cursor:pointer;">
                                                        <input type="hidden" name="acore_pdump_all_enabled" value="0" <?= $dis ?>>
                                                        <input type="checkbox" id="acore-pdump-all-enabled-cb" name="acore_pdump_all_enabled" value="1" <?= $allEnabled ? 'checked' : '' ?> <?= $dis ?>>
                                                        Enabled
                                                    </label>
                                                </div>
                                                <div id="acore-pdump-all-cd-grid" <?= !$allEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                    <div class="acore-cooldown-inputs" data-target="acore_pdump_cooldown_all"
                                                         style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;max-width:360px;">
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="99"  class="acore-cd-y"  style="width:100%;text-align:center;" value="<?= intdiv($cdAll, 31536000) ?>"           <?= ($dis ?: (!$allEnabled ? 'disabled' : '')) ?>>Years</label>
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="11"  class="acore-cd-mo" style="width:100%;text-align:center;" value="<?= intdiv($cdAll % 31536000, 2592000) ?>" <?= ($dis ?: (!$allEnabled ? 'disabled' : '')) ?>>Months</label>
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="29"  class="acore-cd-d"  style="width:100%;text-align:center;" value="<?= intdiv($cdAll % 2592000, 86400) ?>"   <?= ($dis ?: (!$allEnabled ? 'disabled' : '')) ?>>Days</label>
                                                        <label style="<?= $cdLbl ?>"><input type="number" min="0" max="23"  class="acore-cd-h"  style="width:100%;text-align:center;" value="<?= intdiv($cdAll % 86400, 3600) ?>"      <?= ($dis ?: (!$allEnabled ? 'disabled' : '')) ?>>Hours</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- acore-subscriptions -->
                                <div class="acore-pdump-dependent" <?= $dep ?>>
                                    <hr style="margin:12px 0;">
                                    <details>
                                        <summary style="font-weight:700;font-size:15px;cursor:pointer;user-select:none;list-style:none;display:flex;align-items:center;gap:6px;">
                                            <span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px;margin-top:1px;transition:transform .15s;" data-summary-icon></span>
                                            <a href="https://github.com/azerothcore/mod-acore-subscriptions" target="_blank" onclick="event.stopPropagation()">acore-subscriptions</a>
                                        </summary>
                                        <div style="padding-top:8px;">
                                            <p style="font-size:11px;color:#8b949e;margin:0 0 8px;">Cooldowns must be <strong>less</strong> than the default above.</p>
                                            <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:12px;">
                                                <select name="acore_pdump_subscription_enabled" id="acore_pdump_subscription_enabled">
                                                    <option value="0" <?= !$subEnabled ? 'selected' : '' ?>>Disabled</option>
                                                    <option value="1" <?= $subEnabled  ? 'selected' : '' ?>>Enabled</option>
                                                </select>
                                                Enable subscription cooldown overrides
                                            </label>
                                            <div id="acore-pdump-sub-wrap" <?= !$subEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                <input type="hidden" name="acore_pdump_subscription_cooldowns_present" value="1">
                                                <div id="acore-pdump-sub-list">
                                                    <?php foreach ($subCooldowns as $i => $row): ?>
                                                    <div class="acore-pdump-sub-entry" style="border:1px solid #30363d;border-radius:4px;padding:10px;margin-bottom:8px;">
                                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                                                            <label style="font-size:12px;font-weight:600;margin:0;">Level</label>
                                                            <input type="number" name="acore_pdump_subscription_cooldowns[<?= $i ?>][level]" min="0" title="PMPro membership level ID" value="<?= (int)($row['level'] ?? 0) ?>" style="width:60px;text-align:center;">
                                                            <label style="font-size:12px;font-weight:600;margin:0 0 0 4px;">Name</label>
                                                            <input type="text" name="acore_pdump_subscription_cooldowns[<?= $i ?>][name]" value="<?= esc_attr($row['name'] ?? '') ?>" placeholder="optional" style="flex:1;min-width:80px;">
                                                            <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;margin-left:auto;white-space:nowrap;">
                                                                <input type="hidden" name="acore_pdump_subscription_cooldowns[<?= $i ?>][use_default]" value="0">
                                                                <input type="checkbox" class="acore-pdump-use-default" name="acore_pdump_subscription_cooldowns[<?= $i ?>][use_default]" value="1" <?= !empty($row['use_default']) ? 'checked' : '' ?>>
                                                                Use Default
                                                            </label>
                                                            <button type="button" class="button acore-btn-danger acore-pdump-sub-remove" style="padding:2px 6px;" title="Remove"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
                                                        </div>
                                                        <div class="acore-pdump-col-single">
                                                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Single Dump Cooldown</label>
                                                            <?php echo acorePdumpCdGrid("acore_pdump_subscription_cooldowns[$i][single]", (int)($row['single'] ?? 0)); ?>
                                                        </div>
                                                        <div class="acore-pdump-col-all" style="margin-top:10px;">
                                                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Export All Cooldown</label>
                                                            <?php echo acorePdumpCdGrid("acore_pdump_subscription_cooldowns[$i][all]", (int)($row['all'] ?? 0)); ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div style="display:flex;gap:6px;margin-top:4px;">
                                                    <div id="acore-pdump-sub-add" class="button"><span class="dashicons dashicons-plus" style="margin-top:5px;"></span> Add</div>
                                                    <div id="acore-pdump-sub-reset" class="button acore-btn-danger" title="Remove all subscription overrides"><span class="dashicons dashicons-image-rotate" style="margin-top:5px;"></span> Reset</div>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <!-- RBAC -->
                                <div class="acore-pdump-dependent" <?= $dep ?>>
                                    <hr style="margin:12px 0;">
                                    <details>
                                        <summary style="font-weight:700;font-size:15px;cursor:pointer;user-select:none;list-style:none;display:flex;align-items:center;gap:6px;">
                                            <span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px;margin-top:1px;transition:transform .15s;" data-summary-icon></span>
                                            RBAC
                                        </summary>
                                        <div style="padding-top:8px;">
                                            <p style="font-size:11px;color:#8b949e;margin:0 0 8px;">Cooldowns must be <strong>less</strong> than the default above.</p>
                                            <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:12px;">
                                                <select name="acore_pdump_rbac_enabled" id="acore_pdump_rbac_enabled">
                                                    <option value="0" <?= !$rbacEnabled ? 'selected' : '' ?>>Disabled</option>
                                                    <option value="1" <?= $rbacEnabled  ? 'selected' : '' ?>>Enabled</option>
                                                </select>
                                                Enable RBAC cooldown overrides
                                            </label>
                                            <div id="acore-pdump-rbac-wrap" <?= !$rbacEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                <input type="hidden" name="acore_pdump_rbac_cooldowns_present" value="1">
                                                <div id="acore-pdump-rbac-list">
                                                    <?php foreach ($rbacCooldowns as $i => $row): ?>
                                                    <div class="acore-pdump-rbac-entry" style="border:1px solid #30363d;border-radius:4px;padding:10px;margin-bottom:8px;">
                                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                                                            <label style="font-size:12px;font-weight:600;margin:0;">Permission ID</label>
                                                            <input type="number" name="acore_pdump_rbac_cooldowns[<?= $i ?>][perm_id]" min="0" value="<?= (int)($row['perm_id'] ?? 0) ?>" style="width:60px;text-align:center;">
                                                            <label style="font-size:12px;font-weight:600;margin:0 0 0 4px;">Name</label>
                                                            <input type="text" name="acore_pdump_rbac_cooldowns[<?= $i ?>][perm_name]" value="<?= esc_attr($row['perm_name'] ?? '') ?>" placeholder="optional" style="flex:1;min-width:80px;">
                                                            <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;margin-left:auto;white-space:nowrap;">
                                                                <input type="hidden" name="acore_pdump_rbac_cooldowns[<?= $i ?>][use_default]" value="0">
                                                                <input type="checkbox" class="acore-pdump-use-default" name="acore_pdump_rbac_cooldowns[<?= $i ?>][use_default]" value="1" <?= !empty($row['use_default']) ? 'checked' : '' ?>>
                                                                Use Default
                                                            </label>
                                                            <button type="button" class="button acore-btn-danger acore-pdump-rbac-remove" style="padding:2px 6px;" title="Remove"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
                                                        </div>
                                                        <div class="acore-pdump-col-single">
                                                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Single Dump Cooldown</label>
                                                            <?php echo acorePdumpCdGrid("acore_pdump_rbac_cooldowns[$i][single]", (int)($row['single'] ?? 0)); ?>
                                                        </div>
                                                        <div class="acore-pdump-col-all" style="margin-top:10px;">
                                                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Export All Cooldown</label>
                                                            <?php echo acorePdumpCdGrid("acore_pdump_rbac_cooldowns[$i][all]", (int)($row['all'] ?? 0)); ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div style="display:flex;gap:6px;margin-top:4px;">
                                                    <div id="acore-pdump-rbac-add" class="button"><span class="dashicons dashicons-plus" style="margin-top:5px;"></span> Add</div>
                                                    <div id="acore-pdump-rbac-reset" class="button acore-btn-danger" title="Remove all RBAC overrides"><span class="dashicons dashicons-image-rotate" style="margin-top:5px;"></span> Reset</div>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <!-- mod-contributors -->
                                <div class="acore-pdump-dependent" <?= $dep ?>>
                                    <hr style="margin:12px 0;">
                                    <details>
                                        <summary style="font-weight:700;font-size:15px;cursor:pointer;user-select:none;list-style:none;display:flex;align-items:center;gap:6px;">
                                            <span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px;margin-top:1px;transition:transform .15s;" data-summary-icon></span>
                                            <a href="https://github.com/chromiecraft/mod-contributors" target="_blank" onclick="event.stopPropagation()">mod-contributors</a>
                                        </summary>
                                        <div style="padding-top:8px;">
                                            <p style="font-size:11px;color:#8b949e;margin:0 0 8px;">Cooldowns must be <strong>less</strong> than the default above.</p>
                                            <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:12px;">
                                                <select name="acore_pdump_contributor_enabled" id="acore_pdump_contributor_enabled">
                                                    <option value="0" <?= !$contribEnabled ? 'selected' : '' ?>>Disabled</option>
                                                    <option value="1" <?= $contribEnabled  ? 'selected' : '' ?>>Enabled</option>
                                                </select>
                                                Enable contributor cooldown overrides
                                            </label>
                                            <div id="acore-pdump-contrib-wrap" <?= !$contribEnabled ? 'style="opacity:0.45;pointer-events:none;"' : '' ?>>
                                                <input type="hidden" name="acore_pdump_contributor_cooldowns_present" value="1">
                                                <div id="acore-pdump-contrib-list">
                                                    <?php foreach ($contribCooldowns as $i => $row): ?>
                                                    <div class="acore-pdump-contrib-entry" style="border:1px solid #30363d;border-radius:4px;padding:10px;margin-bottom:8px;">
                                                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                                                            <label style="font-size:12px;font-weight:600;margin:0;">Level</label>
                                                            <input type="number" name="acore_pdump_contributor_cooldowns[<?= $i ?>][level]" min="1" max="4" value="<?= max(1, min(4, (int)($row['level'] ?? 1))) ?>" style="width:50px;text-align:center;" title="1 Bronze · 2 Silver · 3 Gold · 4 Platinum">
                                                            <label style="font-size:12px;font-weight:600;margin:0 0 0 4px;">Name</label>
                                                            <input type="text" name="acore_pdump_contributor_cooldowns[<?= $i ?>][name]" value="<?= esc_attr($row['name'] ?? '') ?>" placeholder="optional" style="flex:1;min-width:80px;">
                                                            <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;margin-left:auto;white-space:nowrap;">
                                                                <input type="hidden" name="acore_pdump_contributor_cooldowns[<?= $i ?>][use_default]" value="0">
                                                                <input type="checkbox" class="acore-pdump-use-default" name="acore_pdump_contributor_cooldowns[<?= $i ?>][use_default]" value="1" <?= !empty($row['use_default']) ? 'checked' : '' ?>>
                                                                Use Default
                                                            </label>
                                                            <button type="button" class="button acore-btn-danger acore-pdump-contrib-remove" style="padding:2px 6px;" title="Remove"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>
                                                        </div>
                                                        <div class="acore-pdump-col-single">
                                                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Single Dump Cooldown</label>
                                                            <?php echo acorePdumpCdGrid("acore_pdump_contributor_cooldowns[$i][single]", (int)($row['single'] ?? 0)); ?>
                                                        </div>
                                                        <div class="acore-pdump-col-all" style="margin-top:10px;">
                                                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Export All Cooldown</label>
                                                            <?php echo acorePdumpCdGrid("acore_pdump_contributor_cooldowns[$i][all]", (int)($row['all'] ?? 0)); ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div style="display:flex;gap:6px;margin-top:4px;">
                                                    <div id="acore-pdump-contrib-add" class="button"><span class="dashicons dashicons-plus" style="margin-top:5px;"></span> Add</div>
                                                    <div id="acore-pdump-contrib-reset" class="button acore-btn-danger" title="Remove all contributor overrides"><span class="dashicons dashicons-image-rotate" style="margin-top:5px;"></span> Reset</div>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                            </div>
                        </div><!-- /PDUMP Settings -->
                    </div><!-- /col3 -->

                </div><!-- /row -->

                <!-- PDUMP Export Log -->
                <?php
                global $wpdb;
                $logTable = $wpdb->prefix . 'acore_pdump_log';
                $logExists = $wpdb->get_var("SHOW TABLES LIKE '{$logTable}'") === $logTable;
                $logRows   = $logExists ? $wpdb->get_results(
                    "SELECT l.*, u.user_login FROM `{$logTable}` l
                     LEFT JOIN `{$wpdb->users}` u ON u.ID = l.user_id
                     ORDER BY l.exported_at DESC LIMIT 200"
                ) : [];
                ?>
                <div class="card p-0" style="margin-top:16px;">
                    <div class="card-body">
                        <h5>PDUMP Export Log</h5>
                        <hr>
                        <?php if (!$logExists || empty($logRows)): ?>
                            <p style="font-size:12px;color:#646970;margin:0;">No exports recorded yet.</p>
                        <?php else: ?>
                            <div style="max-height:400px;overflow-y:auto;font-size:12px;font-family:monospace;line-height:1.8;">
                                <?php foreach ($logRows as $entry):
                                    $chars   = json_decode($entry->characters, true) ?: [];
                                    $when    = date('d-m-Y \a\t H:i:s', strtotime($entry->exported_at));
                                    $user    = esc_html($entry->user_login ?: 'user#' . $entry->user_id);
                                    $ip      = esc_html($entry->ip ?: '?');
                                    if ($entry->type === 'single' && !empty($chars)):
                                        $c = $chars[0];
                                        $charStr = esc_html("{$c['name']} ({$c['guid']}) Lv{$c['level']} {$c['race']} {$c['class']}");
                                        echo "<div><strong>{$user}</strong> at {$when} from {$ip} &mdash; exported <em>{$charStr}</em></div>";
                                    else:
                                        $parts = [];
                                        foreach ($chars as $i => $c) {
                                            $parts[] = esc_html(($i + 1) . ' - ' . $c['name'] . ' (' . $c['guid'] . ') Lv' . $c['level'] . ' ' . $c['race'] . ' ' . $c['class']);
                                        }
                                        echo "<div><strong>{$user}</strong> at {$when} from {$ip} &mdash; exported ALL (" . implode('), (', $parts) . ')</div>';
                                    endif;
                                endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Login History (admin lookup) -->
                <div class="card p-0" style="margin-top:16px;">
                    <div class="card-body">
                        <h5>User Login History</h5>
                        <hr>
                        <p style="font-size:12px; color:#646970; margin:0 0 8px;">
                            Look up the recorded login IP history for any account (the same list the user sees on their Security page).
                        </p>
                        <div style="display:flex; gap:6px; align-items:center; margin-bottom:10px; flex-wrap:wrap;">
                            <input type="text" id="acore-history-user" placeholder="Account name" style="flex:0 1 220px;">
                            <button type="button" id="acore-history-lookup" class="button button-secondary">Look up</button>
                        </div>
                        <p id="acore-history-msg" style="font-size:12px; margin:0 0 8px; min-height:18px;"></p>
                        <table id="acore-history-table" class="wp-list-table widefat fixed striped" style="display:none; max-width:760px;">
                            <thead>
                                <tr><th>IPv4 Address</th><th>Country</th><th>Date / Time</th><th>Where</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p style="margin-top:8px;">
                            <button type="button" id="acore-history-more" class="button" style="display:none;">See more</button>
                        </p>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>">
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Reusable confirm modal (default button is "No") -->
<div id="acore-confirm-modal" class="acore-modal-overlay" style="display:none;">
    <div class="acore-modal-box">
        <p id="acore-confirm-modal-text" class="acore-modal-text"></p>
        <div class="acore-modal-actions">
            <button type="button" id="acore-confirm-yes" class="button acore-btn-danger">Yes</button>
            <button type="button" id="acore-confirm-no" class="button button-secondary">No</button>
        </div>
    </div>
</div>

<script>
(function($){
    var restBase = '<?= esc_js(rest_url(ACORE_SLUG . '/v1/')) ?>';
    var nonce    = '<?= esc_js(wp_create_nonce('wp_rest')) ?>';

    /* Re-enable the Days Inactive input when scroll is toggled on */
    $('#acore_resurrection_scroll').on('change', function(){
        var on = $(this).val() === '1';
        var $wrap = $('#acore-days-inactive-wrap');
        $wrap.toggleClass('acore-days-inactive-disabled', !on)
             .attr('title', on ? '' : 'Scroll of Resurrection must be enabled');
        $('#acore_resurrection_scroll_days_inactive').prop('disabled', !on);
    });

    /* Punishment Info checkboxes depend on master toggle */
    $('#acore_punishment_info_enabled').on('change', function(){
        var on = $(this).val() === '1';
        var $row = $('#acore-punishment-info-row');
        $row.css({ opacity: on ? '' : '0.45', 'pointer-events': on ? '' : 'none' });
        $row.find('input[type="checkbox"]').prop('disabled', !on);
    });

    /* PDUMP Bug Report URL + cooldown rows depend on PDUMP being enabled */
    $('#acore_pdump_enabled').on('change', function(){
        var on = $(this).val() === '1';
        $('#acore-pdump-bug-url-row').css({ opacity: on ? '' : '0.45', 'pointer-events': on ? '' : 'none' });
        $('#acore_bug_report_url').prop('disabled', !on);
        $('.acore-pdump-dependent').css({ opacity: on ? '' : '0.45', 'pointer-events': on ? '' : 'none' });
        $('.acore-pdump-dependent input:not(.acore-pdump-cd-secs), .acore-pdump-dependent select, .acore-pdump-dependent textarea').prop('disabled', !on);
        if (on) {
            $('.acore-pdump-use-default:checked').each(function() { acorePdumpApplyUseDefault($(this)); });
            acoreApplyPdumpTypeToggle($('#acore-pdump-single-enabled-cb').prop('checked'), 'single');
            acoreApplyPdumpTypeToggle($('#acore-pdump-all-enabled-cb').prop('checked'), 'all');
        }
    });

    /* Cooldown y/mo/d/h inputs → hidden seconds field (1 year=365d, 1 month=30d) */
    function acoreCdUpdate($wrap) {
        var y  = parseInt($wrap.find('.acore-cd-y').val(),  10) || 0;
        var mo = parseInt($wrap.find('.acore-cd-mo').val(), 10) || 0;
        var d  = parseInt($wrap.find('.acore-cd-d').val(),  10) || 0;
        var h  = parseInt($wrap.find('.acore-cd-h').val(),  10) || 0;
        var total = y * 31536000 + mo * 2592000 + d * 86400 + h * 3600;
        var target = $wrap.data('target');
        $('#' + target).val(total);
    }

    $('.acore-cooldown-inputs').each(function() {
        acoreCdUpdate($(this));
        $(this).on('input', 'input[type="number"]', function() {
            acoreCdUpdate($(this).closest('.acore-cooldown-inputs'));
        });
    });

    /* ── Subscription / RBAC override cooldowns ──────────────────────────
       Cooldowns in override entries must be STRICTLY LESS than the default.
    */

    function acorePdumpDefaultSingle() { return parseInt($('#acore_pdump_cooldown_single').val(), 10) || 0; }
    function acorePdumpDefaultAll()    { return parseInt($('#acore_pdump_cooldown_all').val(),    10) || 0; }

    /* Format a seconds value as a human-readable duration string */
    function acoreFormatCd(secs) {
        if (!secs) return 'no cooldown';
        var y  = Math.floor(secs / 31536000);
        var mo = Math.floor((secs % 31536000) / 2592000);
        var d  = Math.floor((secs % 2592000) / 86400);
        var h  = Math.floor((secs % 86400) / 3600);
        var parts = [];
        if (y)  parts.push(y  + 'y');
        if (mo) parts.push(mo + 'mo');
        if (d)  parts.push(d  + 'd');
        if (h)  parts.push(h  + 'h');
        return parts.join(' ') || 'no cooldown';
    }

    /* Sync Y/Mo/D/H → hidden .acore-pdump-cd-secs within a .acore-pdump-cd-wrap */
    function acorePdumpCdWrapUpdate($wrap) {
        var y  = parseInt($wrap.find('.acore-cd-y').val(),  10) || 0;
        var mo = parseInt($wrap.find('.acore-cd-mo').val(), 10) || 0;
        var d  = parseInt($wrap.find('.acore-cd-d').val(),  10) || 0;
        var h  = parseInt($wrap.find('.acore-cd-h').val(),  10) || 0;
        $wrap.find('.acore-pdump-cd-secs').val(y * 31536000 + mo * 2592000 + d * 86400 + h * 3600);
    }

    /* Reindex [i] in name attributes after add/remove */
    function acorePdumpSubReindex() {
        $('#acore-pdump-sub-list .acore-pdump-sub-entry').each(function(i) {
            $(this).find('input').each(function() {
                var n = $(this).attr('name');
                if (n) $(this).attr('name', n.replace(/\[\d+\]/, '[' + i + ']'));
            });
        });
    }
    function acorePdumpRbacReindex() {
        $('#acore-pdump-rbac-list .acore-pdump-rbac-entry').each(function(i) {
            $(this).find('input').each(function() {
                var n = $(this).attr('name');
                if (n) $(this).attr('name', n.replace(/\[\d+\]/, '[' + i + ']'));
            });
        });
    }

    /* Build the big Y/Mo/D/H grid HTML (matches the PHP acorePdumpCdGrid output) */
    function acorePdumpMakeCdGrid(name, total) {
        total = total || 0;
        var y  = Math.floor(total / 31536000);
        var mo = Math.floor((total % 31536000) / 2592000);
        var d  = Math.floor((total % 2592000) / 86400);
        var h  = Math.floor((total % 86400) / 3600);
        var lbl = '<label style="display:flex;flex-direction:column;align-items:center;gap:3px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin:0;">';
        return '<div class="acore-pdump-cd-wrap">'
            + '<input type="hidden" class="acore-pdump-cd-secs" name="' + name + '" value="' + total + '">'
            + '<div class="acore-pdump-cd-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">'
            + lbl + '<input type="number" class="acore-cd-y"  min="0" max="99"  value="' + y  + '" style="width:100%;text-align:center;">Years</label>'
            + lbl + '<input type="number" class="acore-cd-mo" min="0" max="11"  value="' + mo + '" style="width:100%;text-align:center;">Months</label>'
            + lbl + '<input type="number" class="acore-cd-d"  min="0" max="29"  value="' + d  + '" style="width:100%;text-align:center;">Days</label>'
            + lbl + '<input type="number" class="acore-cd-h"  min="0" max="23"  value="' + h  + '" style="width:100%;text-align:center;">Hours</label>'
            + '</div></div>';
    }

    /* Build a full subscription entry block */
    function acorePdumpMakeSubEntry(i) {
        return '<div class="acore-pdump-sub-entry" style="border:1px solid #30363d;border-radius:4px;padding:10px;margin-bottom:8px;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">'
            + '<label style="font-size:12px;font-weight:600;margin:0;">Level</label>'
            + '<input type="number" name="acore_pdump_subscription_cooldowns[' + i + '][level]" min="0" title="PMPro membership level ID" value="0" style="width:60px;text-align:center;">'
            + '<label style="font-size:12px;font-weight:600;margin:0 0 0 4px;">Name</label>'
            + '<input type="text" name="acore_pdump_subscription_cooldowns[' + i + '][name]" value="" placeholder="optional" style="flex:1;min-width:80px;">'
            + '<label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;margin-left:auto;white-space:nowrap;">'
            + '<input type="hidden" name="acore_pdump_subscription_cooldowns[' + i + '][use_default]" value="0">'
            + '<input type="checkbox" class="acore-pdump-use-default" name="acore_pdump_subscription_cooldowns[' + i + '][use_default]" value="1" checked> Use Default</label>'
            + '<button type="button" class="button acore-btn-danger acore-pdump-sub-remove" style="padding:2px 6px;" title="Remove"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>'
            + '</div>'
            + '<div class="acore-pdump-col-single">'
            + '<label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Single Dump Cooldown</label>'
            + acorePdumpMakeCdGrid('acore_pdump_subscription_cooldowns[' + i + '][single]', 0)
            + '</div>'
            + '<div class="acore-pdump-col-all" style="margin-top:10px;">'
            + '<label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Export All Cooldown</label>'
            + acorePdumpMakeCdGrid('acore_pdump_subscription_cooldowns[' + i + '][all]', 0)
            + '</div>'
            + '</div>';
    }

    /* Build a full RBAC entry block (data: {perm_id, perm_name} optional) */
    function acorePdumpMakeRbacEntry(i, data) {
        data = data || {};
        var permId   = data.perm_id   !== undefined ? data.perm_id   : 0;
        var permName = data.perm_name !== undefined ? data.perm_name : '';
        return '<div class="acore-pdump-rbac-entry" style="border:1px solid #30363d;border-radius:4px;padding:10px;margin-bottom:8px;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">'
            + '<label style="font-size:12px;font-weight:600;margin:0;">Permission ID</label>'
            + '<input type="number" name="acore_pdump_rbac_cooldowns[' + i + '][perm_id]" min="0" value="' + permId + '" style="width:60px;text-align:center;">'
            + '<label style="font-size:12px;font-weight:600;margin:0 0 0 4px;">Name</label>'
            + '<input type="text" name="acore_pdump_rbac_cooldowns[' + i + '][perm_name]" value="' + permName.replace(/"/g, '&quot;') + '" placeholder="optional" style="flex:1;min-width:80px;">'
            + '<label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;margin-left:auto;white-space:nowrap;">'
            + '<input type="hidden" name="acore_pdump_rbac_cooldowns[' + i + '][use_default]" value="0">'
            + '<input type="checkbox" class="acore-pdump-use-default" name="acore_pdump_rbac_cooldowns[' + i + '][use_default]" value="1" checked> Use Default</label>'
            + '<button type="button" class="button acore-btn-danger acore-pdump-rbac-remove" style="padding:2px 6px;" title="Remove"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>'
            + '</div>'
            + '<div class="acore-pdump-col-single">'
            + '<label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Single Dump Cooldown</label>'
            + acorePdumpMakeCdGrid('acore_pdump_rbac_cooldowns[' + i + '][single]', 0)
            + '</div>'
            + '<div class="acore-pdump-col-all" style="margin-top:10px;">'
            + '<label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Export All Cooldown</label>'
            + acorePdumpMakeCdGrid('acore_pdump_rbac_cooldowns[' + i + '][all]', 0)
            + '</div>'
            + '</div>';
    }

    /* Add / remove / reset */
    $('#acore-pdump-sub-add').on('click', function() {
        var i = $('#acore-pdump-sub-list .acore-pdump-sub-entry').length;
        $('#acore-pdump-sub-list').append(acorePdumpMakeSubEntry(i));
        acorePdumpApplyUseDefault($('#acore-pdump-sub-list .acore-pdump-use-default').last());
    });
    $('#acore-pdump-sub-list').on('click', '.acore-pdump-sub-remove', function() {
        var $entry = $(this).closest('.acore-pdump-sub-entry');
        $entry.find('.acore-pdump-cd-wrap').each(function() { acorePdumpCdWrapUpdate($(this)); });
        var level  = $entry.find('input[name*="[level]"]').val() || '?';
        var name   = $entry.find('input[name*="[name]"]').val();
        var label  = 'Level ' + level + (name ? ' · ' + name : '');
        var single = acoreFormatCd(parseInt($entry.find('.acore-pdump-cd-secs').eq(0).val(), 10) || 0);
        var all    = acoreFormatCd(parseInt($entry.find('.acore-pdump-cd-secs').eq(1).val(), 10) || 0);
        acoreConfirm('You\'re about to remove "' + label + '"\nSingle: ' + single + ' · Export All: ' + all, function() {
            $entry.remove();
            acorePdumpSubReindex();
        });
    });
    $('#acore-pdump-sub-reset').on('click', function() {
        var rows = [];
        $('#acore-pdump-sub-list .acore-pdump-sub-entry').each(function() {
            var level = $(this).find('input[name*="[level]"]').val();
            var name  = $(this).find('input[name*="[name]"]').val();
            rows.push('• Level ' + level + (name ? ' · ' + name : ''));
        });
        var msg = 'You\'re about to remove all subscription overrides.';
        if (rows.length) msg += '\n\n' + rows.join('\n');
        acoreConfirm(msg, function() {
            $('#acore-pdump-sub-list').empty();
        });
    });

    $('#acore-pdump-rbac-add').on('click', function() {
        var i = $('#acore-pdump-rbac-list .acore-pdump-rbac-entry').length;
        $('#acore-pdump-rbac-list').append(acorePdumpMakeRbacEntry(i));
        acorePdumpApplyUseDefault($('#acore-pdump-rbac-list .acore-pdump-use-default').last());
    });
    $('#acore-pdump-rbac-list').on('click', '.acore-pdump-rbac-remove', function() {
        var $entry = $(this).closest('.acore-pdump-rbac-entry');
        $entry.find('.acore-pdump-cd-wrap').each(function() { acorePdumpCdWrapUpdate($(this)); });
        var permId = $entry.find('input[name*="[perm_id]"]').val() || '?';
        var name   = $entry.find('input[name*="[perm_name]"]').val();
        var label  = 'ID ' + permId + (name ? ' · ' + name : '');
        var single = acoreFormatCd(parseInt($entry.find('.acore-pdump-cd-secs').eq(0).val(), 10) || 0);
        var all    = acoreFormatCd(parseInt($entry.find('.acore-pdump-cd-secs').eq(1).val(), 10) || 0);
        acoreConfirm('You\'re about to remove "' + label + '"\nSingle: ' + single + ' · Export All: ' + all, function() {
            $entry.remove();
            acorePdumpRbacReindex();
        });
    });
    $('#acore-pdump-rbac-reset').on('click', function() {
        var rbacDefaults = [
            { perm_id: 195, perm_name: 'Player' },
            { perm_id: 194, perm_name: 'Moderator' },
            { perm_id: 193, perm_name: 'Gamemaster' },
            { perm_id: 192, perm_name: 'Administrator' },
        ];
        var lines = rbacDefaults.map(function(d) { return '• ID ' + d.perm_id + ' · ' + d.perm_name; });
        acoreConfirm('Reset RBAC overrides to defaults?\n\n' + lines.join('\n'), function() {
            $('#acore-pdump-rbac-list').empty();
            $.each(rbacDefaults, function(i, d) {
                $('#acore-pdump-rbac-list').append(acorePdumpMakeRbacEntry(i, d));
            });
            $('#acore-pdump-rbac-list .acore-pdump-use-default').each(function() { acorePdumpApplyUseDefault($(this)); });
        });
    });

    /* ── mod-contributors ─────────────────────────────────────────── */
    function acorePdumpContribReindex() {
        $('#acore-pdump-contrib-list .acore-pdump-contrib-entry').each(function(i) {
            $(this).find('input').each(function() {
                var n = $(this).attr('name');
                if (n) $(this).attr('name', n.replace(/\[\d+\]/, '[' + i + ']'));
            });
        });
    }

    /* Build a full contributor entry block (data: {level, name} optional) */
    function acorePdumpMakeContribEntry(i, data) {
        data = data || {};
        var level = data.level !== undefined ? data.level : 1;
        var name  = data.name  !== undefined ? data.name  : '';
        return '<div class="acore-pdump-contrib-entry" style="border:1px solid #30363d;border-radius:4px;padding:10px;margin-bottom:8px;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">'
            + '<label style="font-size:12px;font-weight:600;margin:0;">Level</label>'
            + '<input type="number" name="acore_pdump_contributor_cooldowns[' + i + '][level]" min="1" max="4" value="' + level + '" style="width:50px;text-align:center;" title="1 Bronze · 2 Silver · 3 Gold · 4 Platinum">'
            + '<label style="font-size:12px;font-weight:600;margin:0 0 0 4px;">Name</label>'
            + '<input type="text" name="acore_pdump_contributor_cooldowns[' + i + '][name]" value="' + name.replace(/"/g, '&quot;') + '" placeholder="optional" style="flex:1;min-width:80px;">'
            + '<label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;margin-left:auto;white-space:nowrap;">'
            + '<input type="hidden" name="acore_pdump_contributor_cooldowns[' + i + '][use_default]" value="0">'
            + '<input type="checkbox" class="acore-pdump-use-default" name="acore_pdump_contributor_cooldowns[' + i + '][use_default]" value="1" checked> Use Default</label>'
            + '<button type="button" class="button acore-btn-danger acore-pdump-contrib-remove" style="padding:2px 6px;" title="Remove"><span class="dashicons dashicons-trash" style="margin-top:4px;"></span></button>'
            + '</div>'
            + '<div class="acore-pdump-col-single">'
            + '<label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Single Dump Cooldown</label>'
            + acorePdumpMakeCdGrid('acore_pdump_contributor_cooldowns[' + i + '][single]', 0)
            + '</div>'
            + '<div class="acore-pdump-col-all" style="margin-top:10px;">'
            + '<label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px;">Export All Cooldown</label>'
            + acorePdumpMakeCdGrid('acore_pdump_contributor_cooldowns[' + i + '][all]', 0)
            + '</div>'
            + '</div>';
    }

    $('#acore-pdump-contrib-add').on('click', function() {
        var i = $('#acore-pdump-contrib-list .acore-pdump-contrib-entry').length;
        $('#acore-pdump-contrib-list').append(acorePdumpMakeContribEntry(i));
        acorePdumpApplyUseDefault($('#acore-pdump-contrib-list .acore-pdump-use-default').last());
    });
    $('#acore-pdump-contrib-list').on('click', '.acore-pdump-contrib-remove', function() {
        var $entry = $(this).closest('.acore-pdump-contrib-entry');
        $entry.find('.acore-pdump-cd-wrap').each(function() { acorePdumpCdWrapUpdate($(this)); });
        var level  = $entry.find('input[name*="[level]"]').val() || '?';
        var name   = $entry.find('input[name*="[name]"]').val();
        var label  = 'Level ' + level + (name ? ' · ' + name : '');
        var single = acoreFormatCd(parseInt($entry.find('.acore-pdump-cd-secs').eq(0).val(), 10) || 0);
        var all    = acoreFormatCd(parseInt($entry.find('.acore-pdump-cd-secs').eq(1).val(), 10) || 0);
        acoreConfirm('You\'re about to remove "' + label + '"\nSingle: ' + single + ' · Export All: ' + all, function() {
            $entry.remove();
            acorePdumpContribReindex();
        });
    });
    $('#acore-pdump-contrib-reset').on('click', function() {
        var contribDefaults = [
            { level: 1, name: 'Bronze' },
            { level: 2, name: 'Silver' },
            { level: 3, name: 'Gold' },
            { level: 4, name: 'Platinum' },
        ];
        var lines = contribDefaults.map(function(d) { return '• Level ' + d.level + ' · ' + d.name; });
        acoreConfirm('Reset contributor overrides to defaults?\n\n' + lines.join('\n'), function() {
            $('#acore-pdump-contrib-list').empty();
            $.each(contribDefaults, function(i, d) {
                $('#acore-pdump-contrib-list').append(acorePdumpMakeContribEntry(i, d));
            });
            $('#acore-pdump-contrib-list .acore-pdump-use-default').each(function() { acorePdumpApplyUseDefault($(this)); });
        });
    });

    /* Toggle subscription / RBAC / contributor wraps */
    $('#acore_pdump_subscription_enabled').on('change', function() {
        var on = $(this).val() === '1';
        $('#acore-pdump-sub-wrap').css({ opacity: on ? '' : '0.45', 'pointer-events': on ? '' : 'none' });
    });
    $('#acore_pdump_rbac_enabled').on('change', function() {
        var on = $(this).val() === '1';
        $('#acore-pdump-rbac-wrap').css({ opacity: on ? '' : '0.45', 'pointer-events': on ? '' : 'none' });
    });
    $('#acore_pdump_contributor_enabled').on('change', function() {
        var on = $(this).val() === '1';
        $('#acore-pdump-contrib-wrap').css({ opacity: on ? '' : '0.45', 'pointer-events': on ? '' : 'none' });
    });

    /* ── Single / All enabled toggles ───────────────────────────────────── */
    function acoreApplyPdumpTypeToggle(isOn, type) {
        var $col = $('.acore-pdump-col-' + type);
        $col.css({ opacity: isOn ? '' : '0.45', 'pointer-events': isOn ? '' : 'none' });
        $col.find('input:not(.acore-pdump-cd-secs)').prop('disabled', !isOn);
        var $grid = $('#acore-pdump-' + type + '-cd-grid');
        $grid.css({ opacity: isOn ? '' : '0.45', 'pointer-events': isOn ? '' : 'none' });
        $grid.find('input:not(.acore-pdump-cd-secs)').prop('disabled', !isOn);
    }
    $('#acore-pdump-single-enabled-cb').on('change', function() {
        acoreApplyPdumpTypeToggle($(this).prop('checked'), 'single');
    });
    $('#acore-pdump-all-enabled-cb').on('change', function() {
        acoreApplyPdumpTypeToggle($(this).prop('checked'), 'all');
    });
    /* Init on page load */
    acoreApplyPdumpTypeToggle($('#acore-pdump-single-enabled-cb').prop('checked'), 'single');
    acoreApplyPdumpTypeToggle($('#acore-pdump-all-enabled-cb').prop('checked'), 'all');

    /* ── Min Requirements enable toggle ─────────────────────────────────── */
    function acoreApplyMinReqToggle(isOn) {
        var $fields = $('#acore-pdump-min-req-fields');
        $fields.css({ opacity: isOn ? '' : '0.45', 'pointer-events': isOn ? '' : 'none' });
        $fields.find('input').prop('disabled', !isOn);
    }
    $('#acore-pdump-min-req-enabled-cb').on('change', function() {
        acoreApplyMinReqToggle($(this).prop('checked'));
    });
    acoreApplyMinReqToggle($('#acore-pdump-min-req-enabled-cb').prop('checked'));

    /* ── Per-requirement toggles ─────────────────────────────────────────── */
    function acoreApplyMinSubToggle(cbId, gridId) {
        var isOn = $(cbId).prop('checked');
        var $grid = $(gridId);
        $grid.css({ opacity: isOn ? '' : '0.45', 'pointer-events': isOn ? '' : 'none' });
        $grid.find('input').prop('disabled', !isOn);
    }
    $('#acore-pdump-min-pt-enabled-cb').on('change',  function() { acoreApplyMinSubToggle('#acore-pdump-min-pt-enabled-cb',  '#acore-pdump-min-pt-grid');  });
    $('#acore-pdump-min-age-enabled-cb').on('change', function() { acoreApplyMinSubToggle('#acore-pdump-min-age-enabled-cb', '#acore-pdump-min-age-grid'); });
    $('#acore-pdump-min-lvl-enabled-cb').on('change', function() { acoreApplyMinSubToggle('#acore-pdump-min-lvl-enabled-cb', '#acore-pdump-min-lvl-grid'); });
    acoreApplyMinSubToggle('#acore-pdump-min-pt-enabled-cb',  '#acore-pdump-min-pt-grid');
    acoreApplyMinSubToggle('#acore-pdump-min-age-enabled-cb', '#acore-pdump-min-age-grid');
    acoreApplyMinSubToggle('#acore-pdump-min-lvl-enabled-cb', '#acore-pdump-min-lvl-grid');

    /* ── Collapsible section chevron rotation ────────────────────────────── */
    $(document).on('toggle', 'details', function() {
        var $icon = $(this).children('summary').find('[data-summary-icon]');
        $icon.css('transform', this.open ? 'rotate(90deg)' : '');
    });

    /* Sync hidden seconds whenever Y/Mo/D/H change inside override entries */
    $('#acore-pdump-sub-list, #acore-pdump-rbac-list, #acore-pdump-contrib-list').on('input', '.acore-cd-y, .acore-cd-mo, .acore-cd-d, .acore-cd-h', function() {
        acorePdumpCdWrapUpdate($(this).closest('.acore-pdump-cd-wrap'));
    });

    /* "Use Default Cooldown" checkbox — grey out / disable the CD grids */
    function acorePdumpApplyUseDefault($cb) {
        var $entry    = $cb.closest('.acore-pdump-sub-entry, .acore-pdump-rbac-entry, .acore-pdump-contrib-entry');
        var isDefault = $cb.is(':checked');
        $entry.find('.acore-pdump-cd-wrap').css('opacity', isDefault ? '0.45' : '');
        $entry.find('.acore-pdump-cd-grid input').prop('disabled', isDefault);
    }
    /* Apply on change */
    $('#acore-pdump-sub-list, #acore-pdump-rbac-list, #acore-pdump-contrib-list').on('change', '.acore-pdump-use-default', function() {
        acorePdumpApplyUseDefault($(this));
    });
    /* Apply on page load for PHP-rendered checked checkboxes */
    $('.acore-pdump-use-default:checked').each(function() {
        acorePdumpApplyUseDefault($(this));
    });

    /* Validate on submit: override cooldowns must be < default */
    $('input[name="Submit"]').closest('form').on('submit', function(e) {
        /* Sync all wrap hidden fields first */
        $('.acore-pdump-cd-wrap').each(function() { acorePdumpCdWrapUpdate($(this)); });

        var singleOn  = $('#acore-pdump-single-enabled-cb').prop('checked');
        var allOn     = $('#acore-pdump-all-enabled-cb').prop('checked');
        var defSingle = singleOn ? acorePdumpDefaultSingle() : 0;
        var defAll    = allOn    ? acorePdumpDefaultAll()    : 0;
        var errors = [];

        $('#acore-pdump-sub-list .acore-pdump-sub-entry').each(function(i) {
            if ($(this).find('.acore-pdump-use-default').is(':checked')) return;
            var $secs  = $(this).find('.acore-pdump-cd-secs');
            var single = parseInt($secs.eq(0).val(), 10) || 0;
            var all    = parseInt($secs.eq(1).val(), 10) || 0;
            if (defSingle > 0 && single >= defSingle) errors.push('Subscription entry ' + (i+1) + ': Single Dump must be less than the default (' + defSingle + 's).');
            if (defAll    > 0 && all    >= defAll)    errors.push('Subscription entry ' + (i+1) + ': Export All must be less than the default ('    + defAll    + 's).');
        });

        $('#acore-pdump-rbac-list .acore-pdump-rbac-entry').each(function(i) {
            if ($(this).find('.acore-pdump-use-default').is(':checked')) return;
            var $secs  = $(this).find('.acore-pdump-cd-secs');
            var single = parseInt($secs.eq(0).val(), 10) || 0;
            var all    = parseInt($secs.eq(1).val(), 10) || 0;
            if (defSingle > 0 && single >= defSingle) errors.push('RBAC entry ' + (i+1) + ': Single Dump must be less than the default (' + defSingle + 's).');
            if (defAll    > 0 && all    >= defAll)    errors.push('RBAC entry ' + (i+1) + ': Export All must be less than the default ('    + defAll    + 's).');
        });

        $('#acore-pdump-contrib-list .acore-pdump-contrib-entry').each(function(i) {
            if ($(this).find('.acore-pdump-use-default').is(':checked')) return;
            var $secs  = $(this).find('.acore-pdump-cd-secs');
            var single = parseInt($secs.eq(0).val(), 10) || 0;
            var all    = parseInt($secs.eq(1).val(), 10) || 0;
            if (defSingle > 0 && single >= defSingle) errors.push('Contributor entry ' + (i+1) + ': Single Dump must be less than the default (' + defSingle + 's).');
            if (defAll    > 0 && all    >= defAll)    errors.push('Contributor entry ' + (i+1) + ': Export All must be less than the default ('    + defAll    + 's).');
        });

        if (errors.length) {
            e.preventDefault();
            alert('Please fix the following before saving:\n\n' + errors.join('\n'));
        }
    });

    /* GeoIP depends on Security Logging being enabled */
    $('#acore_security_logging').on('change', function(){
        var on = $(this).val() === '1';
        $('#acore-geoip-wrap').toggleClass('acore-days-inactive-disabled', !on)
             .attr('title', on ? '' : 'Security Logging must be enabled');
        $('#acore_geoip_lookup').prop('disabled', !on);
    });

    /* ── 2FA helpers ──────────────────────────────────────────────────── */
    function ajaxPost(endpoint, body) {
        return $.ajax({
            url: restBase + endpoint,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(body),
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', nonce); }
        });
    }

    function wire2fa(type, $userInput, $check, $remove, $msg, onCheck) {
        var checkedUsername = '';

        $userInput.on('input', function(){
            checkedUsername = '';
            $remove.prop('disabled', true);
        });

        $check.on('click', function(){
            var username = $userInput.val().trim();
            if (!username) { $msg.css('color','#d63638').text('Enter an account name first.'); return; }
            $check.prop('disabled', true).text('Checking…');
            $remove.prop('disabled', true);
            ajaxPost('admin/2fa-check', { type: type, username: username })
                .done(function(data){
                    if (!data.active) {
                        var txt = '2FA is not active for ' + data.username + '.';
                        if (data.last_removal) {
                            var lr  = data.last_removal;
                            var who = (lr.by === 'self') ? 'the user themselves' : (lr.staff || 'an administrator');
                            txt += ' Last removed on ' + lr.date + ' by ' + who;
                            if (lr.by === 'self' && lr.ip) { txt += ' (IP ' + lr.ip + ')'; }
                            txt += '.';
                        }
                        checkedUsername = '';
                        $msg.css('color','#d63638').text(txt);
                        $remove.prop('disabled', true);
                    } else {
                        checkedUsername = data.username || username;
                        $msg.css('color','#238636').text('2FA is active for ' + data.username + '.');
                        $remove.prop('disabled', false);
                    }
                    if (typeof onCheck === 'function') onCheck(data, checkedUsername);
                })
                .fail(function(xhr){
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || JSON.stringify(xhr.responseJSON)) : 'Error.';
                    $msg.css('color','#d63638').text(err);
                    $remove.prop('disabled', true);
                })
                .always(function(){
                    $check.prop('disabled', false).text('Check');
                });
        });

        $remove.on('click', function(){
            var username = checkedUsername;
            if (!username) return;
            if (!confirm('Remove ' + type + ' 2FA for ' + username + '? This cannot be undone.')) return;
            $remove.prop('disabled', true).text('Removing…');
            $check.prop('disabled', true);
            ajaxPost('admin/2fa-remove', { type: type, username: username })
                .done(function(data){
                    $msg.css('color','#238636')
                        .text('Removed on ' + data.date + ' by ' + data.staff + '. User will see a warning until they re-enable 2FA.');
                    $remove.prop('disabled', true);
                })
                .fail(function(xhr){
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || JSON.stringify(xhr.responseJSON)) : 'Error.';
                    $msg.css('color','#d63638').text(err);
                    $remove.prop('disabled', false);
                })
                .always(function(){
                    $check.prop('disabled', false).text('Check');
                    if ($remove.text() === 'Removing…') $remove.text('Remove');
                });
        });
    }

    var webCheckedUsername = '';
    wire2fa('website', $('#acore-2fa-web-user'),  $('#acore-2fa-web-check'),  $('#acore-2fa-web-remove'),  $('#acore-2fa-web-msg'), function(data, username){
        webCheckedUsername = username || '';
        var count = parseInt(data.backup_codes, 10) || 0;
        if (count > 0 && webCheckedUsername) {
            $('#acore-backup-wrap').css('opacity', '1');
            $('#acore-backup-info').css('color','#646970').text(count + ' unused backup code' + (count === 1 ? '' : 's') + ' remaining.');
            $('#acore-backup-remove').prop('disabled', false);
        } else {
            $('#acore-backup-wrap').css('opacity', '0.45');
            $('#acore-backup-info').css('color','#646970').text(count > 0 ? 'Verify the account again to manage backup codes.' : 'No backup codes generated for this account.');
            $('#acore-backup-remove').prop('disabled', true);
        }
    });
    $('#acore-2fa-web-user').on('input', function(){ webCheckedUsername = ''; $('#acore-backup-remove').prop('disabled', true); });
    wire2fa('ingame',  $('#acore-2fa-game-user'), $('#acore-2fa-game-check'), $('#acore-2fa-game-remove'), $('#acore-2fa-game-msg'));

    /* Remove backup codes (uses the Website account-name input) */
    $('#acore-backup-remove').on('click', function(){
        var username = webCheckedUsername;
        if (!username) { return; }
        var $btn = $(this);
        acoreConfirm('Remove all backup codes for ' + username + '? They will need to generate new ones.', function(){
            $btn.prop('disabled', true).text('Removing…');
            ajaxPost('admin/backup-codes-remove', { username: username })
                .done(function(data){
                    $('#acore-backup-info').css('color','#238636').text('Backup codes removed on ' + data.date + '. The user has been notified.');
                })
                .fail(function(xhr){
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || 'Error.') : 'Error.';
                    $('#acore-backup-info').css('color','#d63638').text(err);
                    $btn.prop('disabled', false);
                })
                .always(function(){ $btn.text('Remove backup codes'); });
        });
    });

    /* User Login History lookup */
    var acoreHistory = { username: '', page: 0, total: 0, shown: 0 };

    function acoreHistoryFetch(page) {
        var $tbl = $('#acore-history-table'), $tb = $tbl.find('tbody'),
            $msg = $('#acore-history-msg'), $more = $('#acore-history-more');
        return ajaxPost('admin/login-history', {
                username: acoreHistory.username,
                page:     page
            })
            .done(function(data){
                var rows = data.history || [];
                if (page === 1) { $tb.empty(); acoreHistory.shown = 0; acoreHistory.total = data.total || 0; }
                if (page === 1 && !rows.length) {
                    $tbl.hide(); $more.hide();
                    $msg.css('color','#646970').text('No login history recorded for ' + data.username + '.');
                    return;
                }
                rows.forEach(function(r){
                    $('<tr>').append(
                        $('<td>').text(r.ip),
                        $('<td>').text(r.country),
                        $('<td>').text(r.date),
                        $('<td>').text(r.where)
                    ).appendTo($tb);
                });
                acoreHistory.shown += rows.length;
                acoreHistory.page   = data.page || page;
                acoreHistory.total  = data.total || acoreHistory.total;
                $tbl.show();
                $msg.css('color','#646970').text('Showing ' + acoreHistory.shown + ' of ' + acoreHistory.total + ' for ' + data.username + '.');
                $more.toggle(!!data.has_more);
            })
            .fail(function(xhr){
                if (page === 1) { $tbl.hide(); $more.hide(); }
                var err = xhr.responseJSON ? (xhr.responseJSON.message || 'Error.') : 'Error.';
                $msg.css('color','#d63638').text(err);
            });
    }

    $('#acore-history-lookup').on('click', function(){
        var username = $('#acore-history-user').val().trim();
        if (!username) { $('#acore-history-msg').css('color','#d63638').text('Enter an account name first.'); return; }
        acoreHistory.username = username;
        $('#acore-history-msg').css('color','#646970').text('');
        var $btn = $(this).prop('disabled', true).text('Looking up…');
        acoreHistoryFetch(1).always(function(){ $btn.prop('disabled', false).text('Look up'); });
    });

    $('#acore-history-more').on('click', function(){
        var $b = $(this).prop('disabled', true).text('Loading…');
        acoreHistoryFetch(acoreHistory.page + 1).always(function(){ $b.prop('disabled', false).text('See more'); });
    });

    /* ── Name Unlock Thresholds ───────────────────────────────────────── */
    const deleteThreshold = (ev) => {
        const $btn = $(ev.target).closest('.acore-btn-danger');
        const $tr  = $btn.closest('tr');
        const level = $tr.find('input').eq(0).val() || '?';
        const days  = $tr.find('input').eq(1).val() || '?';
        acoreConfirm('You\'re about to remove "Level ' + level + ' · ' + days + ' days"', function () {
            $tr.remove();
            let i = 0;
            $('#acore-name-unlock-thresholds tbody tr').each(function () {
                const previ = $(this).data('i');
                $(this).data('i', i);
                $(this).find(`input[name="acore_name_unlock_thresholds[${previ}][0]"]`).attr('name', `acore_name_unlock_thresholds[${i}][0]`);
                $(this).find(`input[name="acore_name_unlock_thresholds[${previ}][1]"]`).attr('name', `acore_name_unlock_thresholds[${i}][1]`);
                i++;
            });
        });
    };

    const addThreshold = (i = undefined, level = '', days = '') => {
        if (i === undefined) {
            const $trs = $('#acore-name-unlock-thresholds tbody tr');
            i = $trs.length ? $($trs[$trs.length - 1]).data('i') + 1 : 0;
        }
        const $tr = $('<tr>').appendTo('#acore-name-unlock-thresholds tbody');
        $tr.data('i', i);
        let $td = $('<td>').appendTo($tr);
        $('<input>', { type: 'number', name: `acore_name_unlock_thresholds[${i}][0]`, min: 1, max: 256 }).val(level).appendTo($td);
        $td = $('<td>').appendTo($tr);
        $('<input>', { type: 'number', name: `acore_name_unlock_thresholds[${i}][1]`, min: 1 }).val(days).appendTo($td);
        $td = $('<td>').appendTo($tr);
        const $btnDel = $(`<div class="button acore-btn-danger">`).appendTo($td);
        $btnDel.append(`<span class="dashicons dashicons-trash"></span>`);
        $btnDel.on('click', deleteThreshold);
    };

    $('#acore-name-unlock-thresholds-add').on('click', () => addThreshold());

    /* ── Confirm modal (Yes / No; doing nothing = no action) ─────────── */
    function acoreConfirm(message, onConfirm) {
        const $overlay = $('#acore-confirm-modal');
        $('#acore-confirm-modal-text').text(message);
        $overlay.css('display', 'flex');
        // Nothing is auto-focused: if the user does nothing (Escape / click
        // outside), nothing happens - they must explicitly click Yes or No.

        const close = () => {
            $overlay.hide();
            $('#acore-confirm-yes').off('click.acoreConfirm');
            $('#acore-confirm-no').off('click.acoreConfirm');
            $overlay.off('click.acoreConfirm');
            $(document).off('keydown.acoreConfirm');
        };

        $('#acore-confirm-yes').on('click.acoreConfirm', function () {
            close();
            onConfirm();
        });
        $('#acore-confirm-no').on('click.acoreConfirm', close);
        // Click outside the box = No.
        $overlay.on('click.acoreConfirm', function (e) {
            if (e.target === this) close();
        });
        // Escape = No.
        $(document).on('keydown.acoreConfirm', function (e) {
            if (e.key === 'Escape') close();
        });
    }

    /* ── Reset Name Unlock to Defaults ──────────────────────────────── */
    $('#acore-name-unlock-reset').on('click', function () {
        var rows = [];
        $('#acore-name-unlock-thresholds tbody tr').each(function () {
            var level = $(this).find('input').eq(0).val();
            var days  = $(this).find('input').eq(1).val();
            rows.push('• Level ' + level + ' · ' + days + ' days');
        });
        var msg = 'You\'re about to reset Name Unlock to defaults.';
        if (rows.length) msg += '\n\nThe following thresholds will be removed:\n' + rows.join('\n');
        msg += '\n\nThe banned names table will also be cleared. Continue?';
        acoreConfirm(msg, function () {
                $('input[name="acore_name_unlock_allowed_banned_names_table"]').val('');
                $('#acore-name-unlock-thresholds tbody tr').remove();
                $('input[name="Submit"]').closest('form').submit();
            }
        );
    });

    <?php
    $thresholds = Opts::I()->acore_name_unlock_thresholds;
    if (!is_array($thresholds)) {
        $thresholds = [];
    }
    foreach ($thresholds as $i => $threshold) {
        $level = isset($threshold[0]) ? filter_var($threshold[0], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 256]]) : false;
        $days  = isset($threshold[1]) ? filter_var($threshold[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($level !== false && $days !== false) {
            echo 'addThreshold(' . wp_json_encode((int) $i) . ', ' . wp_json_encode($level) . ', ' . wp_json_encode($days) . ');';
        }
    } ?>

})(jQuery);
</script>
