jQuery(document).ready(function () {
    jQuery("#mail-return-char-select").on("change", function () {
        var charGuid = jQuery(this).val();
        var mailList = jQuery("#mail-return-list");
        var mailItems = jQuery("#mail-return-items");
        var emptyMsg = jQuery("#mail-return-empty");
        var loading = jQuery("#mail-return-loading");

        if (!charGuid) {
            mailList.hide();
            return;
        }

        loading.show();
        mailList.hide();
        mailItems.empty();

        jQuery.ajax({
            type: "GET",
            url: mailReturnData.mailsUrl + "/" + charGuid,
            headers: { "X-WP-Nonce": mailReturnData.nonce },
            xhrFields: { withCredentials: true },
            success: function (mails) {
                loading.hide();
                mailList.show();

                if (mails.length === 0) {
                    emptyMsg.show();
                    return;
                }

                emptyMsg.hide();

                mails.forEach(function (mail) {
                    var sentDate = new Date(mail.deliver_time * 1000).toLocaleString();
                    var genderSuffix = mail.receiver_gender == 0 ? "m" : "f";
                    var raceIcon = mailReturnData.assetsUrl + "race/" + mail.receiver_race + genderSuffix + ".webp";
                    var classIcon = mailReturnData.assetsUrl + "class/" + mail.receiver_class + ".webp";

                    // Build items HTML
                    var itemsHtml = "";
                    if (mail.items && mail.items.length > 0) {
                        itemsHtml = '<div class="mail-items">';
                        mail.items.forEach(function (item, idx) {
                            if (idx > 0) itemsHtml += "&ensp;";
                            var qty = item.count > 1 ? " x" + item.count : "";
                            itemsHtml += '<a href="https://www.wowhead.com/wotlk/item=' + item.itemEntry + '" data-wowhead="item=' + item.itemEntry + '">' + item.item_name + '</a>' + qty;
                        });
                        itemsHtml += "</div>";
                    }

                    // Build money HTML
                    var moneyHtml = "";
                    if (mail.money > 0) {
                        moneyHtml = '<div class="mail-money">Money: ' + formatMoney(mail.money) + '</div>';
                    }

                    var entry = jQuery('<li>').append(
                        jQuery('<div>').addClass('mail-entry').append(
                            // Header row: subject + return button
                            jQuery('<div>').addClass('mail-header').append(
                                jQuery('<span>').addClass('mail-subject').text(mail.subject || "(No Subject)"),
                                jQuery('<button>')
                                    .addClass('mail-return-button')
                                    .attr('data-mail-id', mail.id)
                                    .attr('data-char-guid', charGuid)
                                    .text('Return')
                            ),
                            // Recipient bar with icons
                            jQuery('<div>').addClass('mail-recipient').append(
                                jQuery('<span>').text('To:'),
                                jQuery('<img>').attr({ src: raceIcon, alt: 'race' }),
                                jQuery('<img>').attr({ src: classIcon, alt: 'class' }),
                                jQuery('<span>').addClass('recipient-name').text(mail.receiver_name),
                                jQuery('<span>').css('color', '#646970').text('(Level ' + mail.receiver_level + ')')
                            ),
                            // Sent date
                            jQuery('<div>').addClass('mail-meta').text('Sent: ' + sentDate + '  |  Expires: ' + new Date(mail.expire_time * 1000).toLocaleString()),
                            // Items
                            jQuery(itemsHtml),
                            // Money
                            jQuery(moneyHtml)
                        )
                    );

                    mailItems.append(entry);
                });

                // Refresh Wowhead tooltips for the newly added links
                if (typeof $WowheadPower !== "undefined" && $WowheadPower.refreshLinks) {
                    $WowheadPower.refreshLinks();
                }
            },
            error: function (xhr, status, error) {
                loading.hide();
                mailList.show();
                emptyMsg.text("Failed to load mails.").show();
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
