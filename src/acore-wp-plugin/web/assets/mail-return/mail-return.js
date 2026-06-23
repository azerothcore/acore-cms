jQuery(document).ready(function () {
    jQuery("#acore-characters-mail").on("click", ".acore-char-card", function () {
        var card = jQuery(this);
        var charGuid = card.data("char-guid");
        var mailItems = jQuery("#mail-return-items");
        var emptyWrap = jQuery("#mail-return-empty-wrap");
        var emptyMsg  = jQuery("#mail-return-empty");
        var loading   = jQuery("#mail-return-loading");
        var content   = jQuery("#mail-return-content");

        jQuery(".acore-char-card").removeClass("active");
        card.addClass("active");

        loading.show();
        mailItems.empty();
        emptyMsg.text("No unread sent mails found for this character.");
        emptyWrap.hide();
        content.hide();

        jQuery.ajax({
            type: "GET",
            url: mailReturnData.mailsUrl + "/" + charGuid,
            headers: { "X-WP-Nonce": mailReturnData.nonce },
            xhrFields: { withCredentials: true },
            success: function (mails) {
                loading.hide();

                if (mails.length === 0) {
                    emptyWrap.show();
                    return;
                }

                content.show();

                // Class border-left colors (mirrors AcoreCharColors.php light values)
                var classColors = {
                    1: '#C69B6D', 2: '#F48CBA', 3: '#AAD372', 4: '#C8A800',
                    5: '#909090', 6: '#C41E3A', 7: '#0070DD', 8: '#3FC7EB',
                    9: '#8788EE', 11: '#FF7C0A'
                };

                // Expansion colors for level display (mirrors User.php / AcoreCharColors.php)
                function expansionColor(level) {
                    if (level <= 60) return '#C39361'; // Vanilla
                    if (level <= 70) return '#62C907'; // TBC
                    return '#5DACEB';                   // Wrath
                }

                // Race names (mirrors AcoreCharColors::RACE_NAMES)
                var raceNames = {
                    1: 'Human', 2: 'Orc', 3: 'Dwarf', 4: 'Night Elf', 5: 'Undead',
                    6: 'Tauren', 7: 'Gnome', 8: 'Troll', 10: 'Blood Elf', 11: 'Draenei'
                };
                var classNames = {
                    1: 'Warrior', 2: 'Paladin', 3: 'Hunter', 4: 'Rogue', 5: 'Priest',
                    6: 'Death Knight', 7: 'Shaman', 8: 'Mage', 9: 'Warlock', 11: 'Druid'
                };

                // Faction colors (mirrors AcoreCharColors::FACTION_COLORS)
                var allianceRaces = [1, 3, 4, 7, 11];
                function factionClr(raceId) {
                    return allianceRaces.indexOf(raceId) !== -1 ? '#3FACF4' : '#FF653D';
                }

                mails.forEach(function (mail) {
                    var genderSuffix = mail.receiver_gender == 0 ? "m" : "f";
                    var raceIcon  = mailReturnData.assetsUrl + "race/"  + mail.receiver_race  + genderSuffix + ".webp";
                    var classIcon = mailReturnData.assetsUrl + "class/" + mail.receiver_class + ".webp";
                    var clsColor  = classColors[mail.receiver_class] || '#646970';
                    var faction   = factionClr(mail.receiver_race);
                    var raceName  = raceNames[mail.receiver_race]  || 'Unknown';
                    var clsName   = classNames[mail.receiver_class] || 'Unknown';

                    // Subject line — with optional CoD label
                    var subjectText = mail.subject || "(No Subject)";
                    var subjectHtml = jQuery('<div>').text(subjectText).html();
                    var codHtml = (mail.cod && parseInt(mail.cod) > 0)
                        ? ' <span class="mail-cod-label">· <strong>CoD</strong>: Cash on Delivery</span>'
                        : '';

                    // WoW item quality → border color
                    var qualityColors = {
                        0: '#9d9d9d', // Poor (gray)
                        1: '#c0c0c0', // Common (white → light grey for dark bg visibility)
                        2: '#1eff00', // Uncommon (green)
                        3: '#0070dd', // Rare (blue)
                        4: '#a335ee', // Epic (purple)
                        5: '#ff8000', // Legendary (orange)
                        6: '#e6cc80', // Artifact
                        7: '#e6cc80', // Heirloom
                    };

                    // Items grid — quality-colored border, quantity always shown
                    var itemsHtml = '';
                    if (mail.items && mail.items.length > 0) {
                        itemsHtml = '<div class="mail-items-grid">';
                        mail.items.forEach(function (item) {
                            var safeName    = jQuery('<div>').text(item.item_name).html();
                            var qualityClr  = qualityColors[item.item_quality] || '#9d9d9d';
                            itemsHtml += '<div class="mail-item-slot" style="border-color:' + qualityClr + '" title="' + safeName + '">'
                                + '<a href="https://www.wowhead.com/wotlk/item=' + item.itemEntry
                                + '" data-wowhead="item=' + item.itemEntry + '" title="' + safeName + '">' + safeName + '</a>'
                                + '<span class="mail-item-qty">' + item.count + '</span>'
                                + '</div>';
                        });
                        itemsHtml += '</div>';
                    }

                    // Level badge: same expansion badge as character selector
                    var lvlExp = mail.receiver_level <= 60 ? 'vanilla' : (mail.receiver_level <= 70 ? 'tbc' : 'wrath');
                    var levelBadge = jQuery('<span>')
                        .addClass('acore-level')
                        .attr('data-exp', lvlExp)
                        .text('LEVEL ' + mail.receiver_level);

                    var entry = jQuery('<li>').append(
                        jQuery('<div>').addClass('mail-entry').css({
                            // Faction color on top/right/bottom; class color on left — mirrors char row design
                            'border-top':    '2px solid ' + faction,
                            'border-right':  '2px solid ' + faction,
                            'border-bottom': '2px solid ' + faction,
                            'border-left':   '4px solid ' + clsColor,
                        }).append(
                            // Row 1: To (recipient) + Return button — bordered with class color
                            jQuery('<div>').addClass('mail-header')
                                .css('border-left-color', clsColor)
                                .append(
                                    jQuery('<div>').addClass('mail-recipient').append(
                                        jQuery('<span>').addClass('mail-to-label').text('To:'),
                                        jQuery('<img>').attr({ src: raceIcon,  height: 28, width: 28, alt: raceName, title: raceName }).addClass('mail-recipient-icon').css('border-color', faction),
                                        jQuery('<img>').attr({ src: classIcon, height: 28, width: 28, alt: clsName,  title: clsName  }).addClass('mail-recipient-icon').css('border-color', clsColor),
                                        jQuery('<span>').addClass('recipient-name').text(mail.receiver_name),
                                        levelBadge
                                    ),
                                    jQuery('<button>')
                                        .addClass('mail-return-button')
                                        .attr('data-mail-id', mail.id)
                                        .attr('data-char-guid', charGuid)
                                        .text('Return')
                                ),
                            // Row 2: subject + CoD label
                            jQuery('<div>').addClass('mail-subject').html('<span class="mail-subject-label">Title:</span> <em><strong>' + subjectHtml + '</strong></em>' + codHtml),
                            // Row 3: item icon grid
                            jQuery(itemsHtml)
                        )
                    );

                    mailItems.append(entry);
                });

                // Refresh Wowhead tooltips + upgrade icons to large JPG
                function upgradeWowheadIcons() {
                    document.querySelectorAll('.mail-item-slot > a').forEach(function (a) {
                        var bg = a.style.backgroundImage;
                        if (bg) {
                            bg = bg.replace(/\/icons\/(tiny|small)\//, '/icons/large/')
                                   .replace(/\.gif(["']?\))/, '.jpg$1');
                            a.style.backgroundImage = bg;
                        }
                    });
                }
                if (typeof $WowheadPower !== "undefined" && $WowheadPower.refreshLinks) {
                    $WowheadPower.refreshLinks();
                    setTimeout(upgradeWowheadIcons, 1500);
                } else {
                    // power.js loads async — wait for it
                    var attempts = 0;
                    var wait = setInterval(function () {
                        attempts++;
                        if (typeof $WowheadPower !== "undefined" && $WowheadPower.refreshLinks) {
                            $WowheadPower.refreshLinks();
                            clearInterval(wait);
                            setTimeout(upgradeWowheadIcons, 1500);
                        } else if (attempts > 20) {
                            clearInterval(wait);
                        }
                    }, 250);
                }
            },
            error: function (xhr, status, error) {
                loading.hide();
                emptyMsg.text("Failed to load mails.");
                emptyWrap.show();
                console.error("Failed to load mails:", error);
            }
        });
    });

    jQuery(document).on("click", ".mail-return-button", function () {
        var button = jQuery(this);
        var mailId = button.data("mail-id");
        var charGuid = button.data("char-guid");

        if (!confirm("Are you sure you want to return this mail?")) {
            return;
        }

        button.prop("disabled", true).text("Returning...");

        jQuery.ajax({
            type: "POST",
            url: mailReturnData.returnUrl,
            data: { charGuid: charGuid, mailId: mailId },
            headers: { "X-WP-Nonce": mailReturnData.nonce },
            xhrFields: { withCredentials: true },
            success: function (response) {
                button.closest("li").fadeOut(300, function () {
                    jQuery(this).remove();
                    if (jQuery("#mail-return-items li").length === 0) {
                        jQuery("#mail-return-empty").text("No unread sent mails found for this character.").show();
                    }
                });
            },
            error: function (xhr, status, error) {
                button.prop("disabled", false).text("Return");
                var errMsg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.data?.message || JSON.stringify(xhr.responseJSON)) : error;
                alert("Failed to return mail: " + errMsg);
                console.error("Mail return failed:", xhr.responseJSON, error);
            }
        });
    });

    function formatMoney(copper) {
        var gold = Math.floor(copper / 10000);
        var silver = Math.floor((copper % 10000) / 100);
        var cop = copper % 100;
        var parts = [];
        if (gold > 0) parts.push(gold + "g");
        if (silver > 0) parts.push(silver + "s");
        if (cop > 0) parts.push(cop + "c");
        return parts.join(" ") || "0c";
    }
});
