<?php
if (!defined('ABSPATH')) exit;

// Simple locale detection for translations
function bpb_t($persian, $english, $german) {
    $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
    if (strpos($locale, 'de_') === 0) {
        return $german;
    } elseif (strpos($locale, 'en_') === 0) {
        return $english;
    }
    return $persian;
}

add_action('admin_menu', function() {
    add_menu_page(
        bpb_t('تنظیمات دکمه تماس', 'Call Button Settings', 'Anruf-Button-Einstellungen'),
        bpb_t('تماس شعب', 'Branch Contact', 'Filialkontakt'),
        'manage_options',
        'bpb-settings',
        'bpb_render_settings_page',
        'dashicons-phone'
    );
});

function bpb_render_settings_page() {
    if (isset($_POST['bpb_save']) && check_admin_referer('bpb_settings_action', 'bpb_settings_nonce')) {
        update_option('bpb_settings', $_POST['bpb_settings']);
        echo '<div class="updated"><p>' . esc_html(bpb_t('تنظیمات ذخیره شد.', 'Settings saved.', 'Einstellungen gespeichert.')) . '</p></div>';
    }

    if (isset($_POST['bpb_reset_stats']) && check_admin_referer('bpb_settings_action', 'bpb_settings_nonce')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bpb_clicks';
        $wpdb->query("TRUNCATE TABLE $table_name");
        echo '<div class="updated"><p>' . esc_html(bpb_t('آمار کلیک‌ها بازنشانی شد.', 'Click stats reset.', 'Klickstatistiken zurückgesetzt.')) . '</p></div>';
    }

    $settings = get_option('bpb_settings', bpb_default_settings());
    // Fetch advanced stats
    global $wpdb;
    $table_name = $wpdb->prefix . 'bpb_clicks';

    // Ensure table exists just in case
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        bpb_install_db();
    }

    $date_filter = isset($_POST['bpb_date_filter']) ? sanitize_text_field($_POST['bpb_date_filter']) : '30days';
    $date_clause = '';

    if ($date_filter === 'today') {
        $date_clause = "WHERE DATE(click_time) = CURDATE()";
    } elseif ($date_filter === 'yesterday') {
        $date_clause = "WHERE DATE(click_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($date_filter === '7days') {
        $date_clause = "WHERE click_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } else { // 30days
        $date_clause = "WHERE click_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Queries
    $stats_buttons = $wpdb->get_results("
        SELECT button_label,
               COUNT(*) as total_clicks,
               COUNT(DISTINCT user_uuid) as unique_clicks
        FROM $table_name
        $date_clause
        GROUP BY button_label
        ORDER BY total_clicks DESC
    ");

    $stats_sources = $wpdb->get_results("
        SELECT source,
               COUNT(*) as total_clicks
        FROM $table_name
        $date_clause
        GROUP BY source
        ORDER BY total_clicks DESC
    ");

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(bpb_t('تنظیمات دکمه تماس شعب', 'Branch Phone Button Settings', 'Filial-Anruf-Button-Einstellungen')); ?> - نسخه 1.4</h1>
        <form method="post">
            <?php wp_nonce_field('bpb_settings_action', 'bpb_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('حالت نمایش', 'Display Mode', 'Anzeigemodus')); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="bpb_settings[mode]" value="branches" <?php checked($settings['mode'] ?? 'branches', 'branches'); ?>>
                            <?php echo esc_html(bpb_t('حالت شعب (چند شعبه)', 'Branches Mode (Multiple Branches)', 'Filialmodus (Mehrere Filialen)')); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="bpb_settings[mode]" value="contacts" <?php checked($settings['mode'] ?? 'branches', 'contacts'); ?>>
                            <?php echo esc_html(bpb_t('حالت راه‌های ارتباطی (تماس، ایمیل و ...)', 'Contacts Mode (Call, Email, etc.)', 'Kontaktmodus (Anruf, E-Mail usw.)')); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('نمایش در دستگاه‌ها', 'Display Devices', 'Anzeigegeräte')); ?></th>
                    <td>
                        <select name="bpb_settings[display_device]">
                            <option value="mobile_only" <?php selected($settings['display_device'] ?? 'mobile_only', 'mobile_only'); ?>><?php echo esc_html(bpb_t('فقط در موبایل', 'Mobile Only', 'Nur Handy')); ?></option>
                            <option value="desktop_only" <?php selected($settings['display_device'] ?? 'mobile_only', 'desktop_only'); ?>><?php echo esc_html(bpb_t('فقط در دسکتاپ', 'Desktop Only', 'Nur Desktop')); ?></option>
                            <option value="all" <?php selected($settings['display_device'] ?? 'mobile_only', 'all'); ?>><?php echo esc_html(bpb_t('همه دستگاه‌ها', 'All Devices', 'Alle Geräte')); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('شکل دکمه‌ها', 'Button Shape', 'Tastenform')); ?></th>
                    <td>
                        <select name="bpb_settings[button_shape]">
                            <option value="oval" <?php selected($settings['button_shape'] ?? 'oval', 'oval'); ?>><?php echo esc_html(bpb_t('بیضی', 'Oval', 'Oval')); ?></option>
                            <option value="circle" <?php selected($settings['button_shape'] ?? 'oval', 'circle'); ?>><?php echo esc_html(bpb_t('دایره', 'Circle', 'Kreis')); ?></option>
                            <option value="rectangle" <?php selected($settings['button_shape'] ?? 'oval', 'rectangle'); ?>><?php echo esc_html(bpb_t('مستطیل', 'Rectangle', 'Rechteck')); ?></option>
                            <option value="rounded" <?php selected($settings['button_shape'] ?? 'oval', 'rounded'); ?>><?php echo esc_html(bpb_t('مستطیل گوشه گرد', 'Rounded Rectangle', 'Abgerundetes Rechteck')); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('صفحات نمایش', 'Display Pages', 'Anzeigeseiten')); ?></th>
                    <td>
                        <?php
                        $pages = get_pages();
                        $selected_pages = $settings['display_pages'] ?? [];
                        if (empty($pages)) {
                            echo '<p>' . esc_html(bpb_t('صفحه‌ای یافت نشد.', 'No pages found.', 'Keine Seiten gefunden.')) . '</p>';
                        } else {
                            echo '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';
                            foreach ($pages as $page) {
                                $checked = in_array($page->ID, $selected_pages) ? 'checked' : '';
                                echo '<label style="display:block;"><input type="checkbox" name="bpb_settings[display_pages][]" value="' . esc_attr($page->ID) . '" ' . $checked . '> ' . esc_html($page->post_title) . '</label>';
                            }
                            echo '</div>';
                            echo '<p class="description">' . esc_html(bpb_t('اگر هیچکدام انتخاب نشوند، در تمام صفحات نمایش داده می‌شود (مگر اینکه تیک صفحه اصلی زده شده باشد).', 'If none selected, it will show on all pages (unless Show only on homepage is checked).', 'Wenn nichts ausgewählt ist, wird es auf allen Seiten angezeigt (es sei denn, Nur auf Startseite anzeigen ist aktiviert).')) . '</p>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('تأخیر نمایش (ثانیه)', 'Display Delay (sec)', 'Anzeigeverzögerung (Sek.)')); ?></th>
                    <td>
                        <input type="number" name="bpb_settings[delay]" value="<?php echo esc_attr($settings['delay'] ?? 0); ?>" min="0" />
                        <p class="description"><?php echo esc_html(bpb_t('تعداد ثانیه‌هایی که طول می‌کشد تا دکمه‌ها نمایش داده شوند. 0 برای نمایش فوری.', 'Seconds until buttons are displayed. 0 for instant display.', 'Sekunden bis die Schaltflächen angezeigt werden. 0 für sofortige Anzeige.')); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('نحوه نمایش', 'Display Style', 'Anzeigestil')); ?></th>
                    <td>
                        <select name="bpb_settings[display_style]">
                            <option value="flat" <?php selected($settings['display_style'] ?? 'flat', 'flat'); ?>><?php echo esc_html(bpb_t('چسبیده به پایین (کامل)', 'Sticky Bottom (Full)', 'Unten anheften (Vollständig)')); ?></option>
                            <option value="floating" <?php selected($settings['display_style'] ?? 'flat', 'floating'); ?>><?php echo esc_html(bpb_t('شناور (گرد)', 'Floating (Round)', 'Schwebend (Rund)')); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('قوانین نمایش (صفحات)', 'Display Rules (Pages)', 'Anzeigeregeln (Seiten)')); ?></th>
                    <td>
                        <select name="bpb_settings[display_location]">
                            <option value="all" <?php selected($settings['display_location'] ?? 'all', 'all'); ?>><?php echo esc_html(bpb_t('همه صفحات', 'All Pages', 'Alle Seiten')); ?></option>
                            <option value="homepage" <?php selected($settings['display_location'] ?? 'all', 'homepage'); ?>><?php echo esc_html(bpb_t('فقط صفحه اصلی', 'Homepage Only', 'Nur Startseite')); ?></option>
                            <option value="specific" <?php selected($settings['display_location'] ?? 'all', 'specific'); ?>><?php echo esc_html(bpb_t('صفحات خاص (در زیر انتخاب کنید)', 'Specific Pages (Select below)', 'Bestimmte Seiten (unten auswählen)')); ?></option>
                        </select>
                        <br><br>
                        <label>
                            <input type="checkbox" name="bpb_settings[hide_on_woo_checkout]" value="1" <?php checked($settings['hide_on_woo_checkout'] ?? 0, 1); ?>>
                            <?php echo esc_html(bpb_t('عدم نمایش در صفحه سبد خرید و پرداخت ووکامرس', 'Hide on WooCommerce Cart & Checkout', 'Ausblenden auf WooCommerce Warenkorb & Kasse')); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('ساعات کاری اداری', 'Business Hours', 'Geschäftszeiten')); ?></th>
                    <td>
                        <?php echo esc_html(bpb_t('از', 'From', 'Von')); ?> <input type="time" name="bpb_settings[biz_time_start]" value="<?php echo esc_attr($settings['biz_time_start'] ?? '08:00'); ?>" />
                        <?php echo esc_html(bpb_t('تا', 'To', 'Bis')); ?> <input type="time" name="bpb_settings[biz_time_end]" value="<?php echo esc_attr($settings['biz_time_end'] ?? '17:00'); ?>" />
                        <p class="description"><?php echo esc_html(bpb_t('بر اساس منطقه زمانی وردپرس (تنظیمات > عمومی). می‌توانید دکمه‌ها را محدود به این ساعات کنید.', 'Based on WordPress timezone (Settings > General). You can limit buttons to these hours.', 'Basierend auf der WordPress-Zeitzone (Einstellungen > Allgemein). Sie können die Schaltflächen auf diese Zeiten beschränken.')); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(bpb_t('رهگیری آمار', 'Analytics Tracking', 'Analyse-Tracking')); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="bpb_settings[enable_ga_tracking]" value="1" <?php checked($settings['enable_ga_tracking'] ?? 0, 1); ?>>
                            <?php echo esc_html(bpb_t('ثبت کلیک‌ها در گوگل آنالیتیکس (Gtag)', 'Log clicks in Google Analytics (Gtag)', 'Klicks in Google Analytics erfassen (Gtag)')); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <hr>

            <div id="bpb-branches-wrapper">
                <h2><?php echo esc_html(bpb_t('دکمه‌های حالت شعب', 'Branch Mode Buttons', 'Schaltflächen für den Filialmodus')); ?></h2>
                <table class="form-table" id="bpb-branches-table">
                    <tbody>
                        <?php
                        $branches = isset($settings['branches']) ? $settings['branches'] : [];
                        foreach ($branches as $i => $branch):
                            $type = $branch['type'] ?? 'tel';
                            $icon = $branch['icon'] ?? 'phone';
                            $timing = $branch['timing'] ?? 'always';
                            // Migrate old "phone" key to "value"
                            $val = $branch['value'] ?? ($branch['phone'] ?? '');
                        ?>
                            <tr class="bpb-branch-row">
                                <th><span class="dashicons dashicons-move" style="cursor:move;"></span> <?php echo esc_html(bpb_t('دکمه', 'Button', 'Schaltfläche')); ?></th>
                                <td>
                                    <?php echo esc_html(bpb_t('نام', 'Name', 'Name')); ?>: <input type="text" name="bpb_settings[branches][<?php echo $i ?>][label]" value="<?php echo esc_attr($branch['label']) ?>" />

                                    <?php echo esc_html(bpb_t('نوع اتصال', 'Connection Type', 'Verbindungstyp')); ?>:
                                    <select name="bpb_settings[branches][<?php echo $i ?>][type]">
                                        <option value="tel" <?php selected($type, 'tel'); ?>><?php echo esc_html(bpb_t('تلفن', 'Phone', 'Telefon')); ?></option>
                                        <option value="mailto" <?php selected($type, 'mailto'); ?>><?php echo esc_html(bpb_t('ایمیل', 'Email', 'E-Mail')); ?></option>
                                        <option value="whatsapp" <?php selected($type, 'whatsapp'); ?>><?php echo esc_html(bpb_t('واتس‌اپ', 'WhatsApp', 'WhatsApp')); ?></option>
                                        <option value="telegram" <?php selected($type, 'telegram'); ?>><?php echo esc_html(bpb_t('تلگرام', 'Telegram', 'Telegram')); ?></option>
                                        <option value="link" <?php selected($type, 'link'); ?>><?php echo esc_html(bpb_t('لینک دلخواه', 'Custom Link', 'Benutzerdefinierter Link')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('مقدار', 'Value', 'Wert')); ?>: <input type="text" name="bpb_settings[branches][<?php echo $i ?>][value]" value="<?php echo esc_attr($val) ?>" />

                                    <?php echo esc_html(bpb_t('آیکون', 'Icon', 'Symbol')); ?>:
                                    <select name="bpb_settings[branches][<?php echo $i ?>][icon]">
                                        <option value="phone" <?php selected($icon, 'phone'); ?>><?php echo esc_html(bpb_t('تلفن', 'Phone', 'Telefon')); ?></option>
                                        <option value="email" <?php selected($icon, 'email'); ?>><?php echo esc_html(bpb_t('ایمیل', 'Email', 'E-Mail')); ?></option>
                                        <option value="whatsapp" <?php selected($icon, 'whatsapp'); ?>><?php echo esc_html(bpb_t('واتس‌اپ', 'WhatsApp', 'WhatsApp')); ?></option>
                                        <option value="telegram" <?php selected($icon, 'telegram'); ?>><?php echo esc_html(bpb_t('تلگرام', 'Telegram', 'Telegram')); ?></option>
                                        <option value="link" <?php selected($icon, 'link'); ?>><?php echo esc_html(bpb_t('لینک', 'Link', 'Link')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('زمان نمایش', 'Display Time', 'Anzeigezeit')); ?>:
                                    <select name="bpb_settings[branches][<?php echo $i ?>][timing]">
                                        <option value="always" <?php selected($timing, 'always'); ?>><?php echo esc_html(bpb_t('همیشه', 'Always', 'Immer')); ?></option>
                                        <option value="biz_hours" <?php selected($timing, 'biz_hours'); ?>><?php echo esc_html(bpb_t('فقط در ساعات کاری', 'Business Hours Only', 'Nur Geschäftszeiten')); ?></option>
                                        <option value="off_hours" <?php selected($timing, 'off_hours'); ?>><?php echo esc_html(bpb_t('فقط خارج از ساعات کاری', 'Off Hours Only', 'Nur außerhalb der Geschäftszeiten')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('انیمیشن', 'Animation', 'Animation')); ?>:
                                    <?php $anim = $branch['animation'] ?? 'none'; ?>
                                    <select name="bpb_settings[branches][<?php echo $i ?>][animation]">
                                        <option value="none" <?php selected($anim, 'none'); ?>><?php echo esc_html(bpb_t('بدون انیمیشن', 'None', 'Keine')); ?></option>
                                        <option value="shake" <?php selected($anim, 'shake'); ?>><?php echo esc_html(bpb_t('لرزش', 'Shake', 'Schütteln')); ?></option>
                                        <option value="glow" <?php selected($anim, 'glow'); ?>><?php echo esc_html(bpb_t('درخشش', 'Glow', 'Glühen')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('رنگ', 'Color', 'Farbe')); ?>: <input type="text" class="bpb-color-picker" name="bpb_settings[branches][<?php echo $i ?>][color]" value="<?php echo esc_attr($branch['color']) ?>" />
                                    <?php echo esc_html(bpb_t('سایز فونت', 'Font Size', 'Schriftgröße')); ?>: <input type="number" name="bpb_settings[branches][<?php echo $i ?>][font_size]" value="<?php echo esc_attr($branch['font_size'] ?? 14) ?>" style="width: 60px;" />

                                    <button type="button" class="button bpb-remove-row"><?php echo esc_html(bpb_t('حذف', 'Remove', 'Entfernen')); ?></button>

                                    <input type="hidden" name="bpb_settings[branches][<?php echo $i ?>][order]" value="<?php echo $i ?>" class="bpb-order-field">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" id="bpb-add-branch-row" class="button bpb-add-row" data-target="branches"><?php echo esc_html(bpb_t('افزودن دکمه جدید (شعب)', 'Add New Branch Button', 'Neuen Filial-Button hinzufügen')); ?></button></p>
            </div>

            <div id="bpb-contacts-wrapper">
                <h2><?php echo esc_html(bpb_t('دکمه‌های حالت راه‌های ارتباطی', 'Contacts Mode Buttons', 'Schaltflächen für den Kontaktmodus')); ?></h2>
                <table class="form-table" id="bpb-contacts-table">
                    <tbody>
                        <?php
                        $contacts = isset($settings['contacts']) ? $settings['contacts'] : [];
                        foreach ($contacts as $i => $contact):
                            $type = $contact['type'] ?? 'tel';
                            $icon = $contact['icon'] ?? 'phone';
                            $timing = $contact['timing'] ?? 'always';
                            $val = $contact['value'] ?? ($contact['phone'] ?? '');
                        ?>
                            <tr class="bpb-branch-row">
                                <th><span class="dashicons dashicons-move" style="cursor:move;"></span> <?php echo esc_html(bpb_t('دکمه', 'Button', 'Schaltfläche')); ?></th>
                                <td>
                                    <?php echo esc_html(bpb_t('نام', 'Name', 'Name')); ?>: <input type="text" name="bpb_settings[contacts][<?php echo $i ?>][label]" value="<?php echo esc_attr($contact['label']) ?>" />

                                    <?php echo esc_html(bpb_t('نوع اتصال', 'Connection Type', 'Verbindungstyp')); ?>:
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][type]">
                                        <option value="tel" <?php selected($type, 'tel'); ?>><?php echo esc_html(bpb_t('تلفن', 'Phone', 'Telefon')); ?></option>
                                        <option value="mailto" <?php selected($type, 'mailto'); ?>><?php echo esc_html(bpb_t('ایمیل', 'Email', 'E-Mail')); ?></option>
                                        <option value="whatsapp" <?php selected($type, 'whatsapp'); ?>><?php echo esc_html(bpb_t('واتس‌اپ', 'WhatsApp', 'WhatsApp')); ?></option>
                                        <option value="telegram" <?php selected($type, 'telegram'); ?>><?php echo esc_html(bpb_t('تلگرام', 'Telegram', 'Telegram')); ?></option>
                                        <option value="link" <?php selected($type, 'link'); ?>><?php echo esc_html(bpb_t('لینک دلخواه', 'Custom Link', 'Benutzerdefinierter Link')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('مقدار', 'Value', 'Wert')); ?>: <input type="text" name="bpb_settings[contacts][<?php echo $i ?>][value]" value="<?php echo esc_attr($val) ?>" />

                                    <?php echo esc_html(bpb_t('آیکون', 'Icon', 'Symbol')); ?>:
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][icon]">
                                        <option value="phone" <?php selected($icon, 'phone'); ?>><?php echo esc_html(bpb_t('تلفن', 'Phone', 'Telefon')); ?></option>
                                        <option value="email" <?php selected($icon, 'email'); ?>><?php echo esc_html(bpb_t('ایمیل', 'Email', 'E-Mail')); ?></option>
                                        <option value="whatsapp" <?php selected($icon, 'whatsapp'); ?>><?php echo esc_html(bpb_t('واتس‌اپ', 'WhatsApp', 'WhatsApp')); ?></option>
                                        <option value="telegram" <?php selected($icon, 'telegram'); ?>><?php echo esc_html(bpb_t('تلگرام', 'Telegram', 'Telegram')); ?></option>
                                        <option value="link" <?php selected($icon, 'link'); ?>><?php echo esc_html(bpb_t('لینک', 'Link', 'Link')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('زمان نمایش', 'Display Time', 'Anzeigezeit')); ?>:
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][timing]">
                                        <option value="always" <?php selected($timing, 'always'); ?>><?php echo esc_html(bpb_t('همیشه', 'Always', 'Immer')); ?></option>
                                        <option value="biz_hours" <?php selected($timing, 'biz_hours'); ?>><?php echo esc_html(bpb_t('فقط در ساعات کاری', 'Business Hours Only', 'Nur Geschäftszeiten')); ?></option>
                                        <option value="off_hours" <?php selected($timing, 'off_hours'); ?>><?php echo esc_html(bpb_t('فقط خارج از ساعات کاری', 'Off Hours Only', 'Nur außerhalb der Geschäftszeiten')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('انیمیشن', 'Animation', 'Animation')); ?>:
                                    <?php $anim = $contact['animation'] ?? 'none'; ?>
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][animation]">
                                        <option value="none" <?php selected($anim, 'none'); ?>><?php echo esc_html(bpb_t('بدون انیمیشن', 'None', 'Keine')); ?></option>
                                        <option value="shake" <?php selected($anim, 'shake'); ?>><?php echo esc_html(bpb_t('لرزش', 'Shake', 'Schütteln')); ?></option>
                                        <option value="glow" <?php selected($anim, 'glow'); ?>><?php echo esc_html(bpb_t('درخشش', 'Glow', 'Glühen')); ?></option>
                                    </select>

                                    <?php echo esc_html(bpb_t('رنگ', 'Color', 'Farbe')); ?>: <input type="text" class="bpb-color-picker" name="bpb_settings[contacts][<?php echo $i ?>][color]" value="<?php echo esc_attr($contact['color']) ?>" />
                                    <?php echo esc_html(bpb_t('سایز فونت', 'Font Size', 'Schriftgröße')); ?>: <input type="number" name="bpb_settings[contacts][<?php echo $i ?>][font_size]" value="<?php echo esc_attr($contact['font_size'] ?? 14) ?>" style="width: 60px;" />

                                    <button type="button" class="button bpb-remove-row"><?php echo esc_html(bpb_t('حذف', 'Remove', 'Entfernen')); ?></button>

                                    <input type="hidden" name="bpb_settings[contacts][<?php echo $i ?>][order]" value="<?php echo $i ?>" class="bpb-order-field">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" id="bpb-add-contact-row" class="button bpb-add-row" data-target="contacts"><?php echo esc_html(bpb_t('افزودن دکمه جدید (راه‌های ارتباطی)', 'Add New Contact Button', 'Neuen Kontakt-Button hinzufügen')); ?></button></p>
            </div>

            <input type="submit" name="bpb_save" class="button button-primary" value="<?php echo esc_attr(bpb_t('ذخیره تنظیمات', 'Save Settings', 'Einstellungen speichern')); ?>">
        </form>

        <hr>
        <h2><?php echo esc_html(bpb_t('آمار کلیک‌ها (پیشرفته)', 'Click Stats (Advanced)', 'Klickstatistiken (Erweitert)')); ?></h2>

        <form method="post" style="margin-bottom: 15px;">
            <?php wp_nonce_field('bpb_settings_action', 'bpb_settings_nonce'); ?>
            <select name="bpb_date_filter">
                <option value="today" <?php selected($date_filter, 'today'); ?>><?php echo esc_html(bpb_t('امروز', 'Today', 'Heute')); ?></option>
                <option value="yesterday" <?php selected($date_filter, 'yesterday'); ?>><?php echo esc_html(bpb_t('دیروز', 'Yesterday', 'Gestern')); ?></option>
                <option value="7days" <?php selected($date_filter, '7days'); ?>><?php echo esc_html(bpb_t('۷ روز گذشته', 'Last 7 Days', 'Letzte 7 Tage')); ?></option>
                <option value="30days" <?php selected($date_filter, '30days'); ?>><?php echo esc_html(bpb_t('۳۰ روز گذشته', 'Last 30 Days', 'Letzte 30 Tage')); ?></option>
            </select>
            <input type="submit" class="button" value="<?php echo esc_attr(bpb_t('فیلتر', 'Filter', 'Filter')); ?>">
        </form>

        <div style="display:flex; gap: 20px; flex-wrap: wrap;">
            <div>
                <h3><?php echo esc_html(bpb_t('آمار دکمه‌ها', 'Button Stats', 'Button-Statistiken')); ?></h3>
                <table class="widefat striped" style="max-width: 400px; min-width: 300px;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(bpb_t('نام دکمه', 'Button Name', 'Button-Name')); ?></th>
                            <th><?php echo esc_html(bpb_t('کلیک‌های یکتا', 'Unique Clicks', 'Eindeutige Klicks')); ?></th>
                            <th><?php echo esc_html(bpb_t('کلیک‌های کل', 'Total Clicks', 'Gesamtklicks')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats_buttons)): ?>
                            <tr><td colspan="3"><?php echo esc_html(bpb_t('داده‌ای یافت نشد.', 'No data found.', 'Keine Daten gefunden.')); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($stats_buttons as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->button_label); ?></td>
                                    <td><?php echo intval($row->unique_clicks); ?></td>
                                    <td><?php echo intval($row->total_clicks); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div>
                <h3><?php echo esc_html(bpb_t('منابع ورودی', 'Traffic Sources', 'Verkehrsquellen')); ?></h3>
                <table class="widefat striped" style="max-width: 400px; min-width: 300px;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(bpb_t('منبع', 'Source', 'Quelle')); ?></th>
                            <th><?php echo esc_html(bpb_t('تعداد کلیک', 'Clicks', 'Klicks')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats_sources)): ?>
                            <tr><td colspan="2"><?php echo esc_html(bpb_t('داده‌ای یافت نشد.', 'No data found.', 'Keine Daten gefunden.')); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($stats_sources as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->source); ?></td>
                                    <td><?php echo intval($row->total_clicks); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <br>
        <form method="post" onsubmit="return confirm('<?php echo esc_js(bpb_t('آیا از پاک شدن آمار مطمئن هستید؟', 'Are you sure you want to clear the stats?', 'Sind Sie sicher, dass Sie die Statistiken löschen möchten?')); ?>');">
            <?php wp_nonce_field('bpb_settings_action', 'bpb_settings_nonce'); ?>
            <input type="submit" name="bpb_reset_stats" class="button button-secondary" value="<?php echo esc_attr(bpb_t('بازنشانی آمار', 'Reset Stats', 'Statistiken zurücksetzen')); ?>">
        </form>

        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
        <script>
            // Translation strings for JS
            var bpb_i18n = {
                btn_new: '<?php echo esc_js(bpb_t("جدید", "New", "Neu")); ?>',
                btn_label: '<?php echo esc_js(bpb_t("دکمه", "Button", "Schaltfläche")); ?>',
                name: '<?php echo esc_js(bpb_t("نام", "Name", "Name")); ?>',
                conn_type: '<?php echo esc_js(bpb_t("نوع اتصال", "Connection Type", "Verbindungstyp")); ?>',
                phone: '<?php echo esc_js(bpb_t("تلفن", "Phone", "Telefon")); ?>',
                email: '<?php echo esc_js(bpb_t("ایمیل", "Email", "E-Mail")); ?>',
                whatsapp: '<?php echo esc_js(bpb_t("واتس‌اپ", "WhatsApp", "WhatsApp")); ?>',
                telegram: '<?php echo esc_js(bpb_t("تلگرام", "Telegram", "Telegram")); ?>',
                custom_link: '<?php echo esc_js(bpb_t("لینک دلخواه", "Custom Link", "Benutzerdefinierter Link")); ?>',
                value: '<?php echo esc_js(bpb_t("مقدار", "Value", "Wert")); ?>',
                icon: '<?php echo esc_js(bpb_t("آیکون", "Icon", "Symbol")); ?>',
                link: '<?php echo esc_js(bpb_t("لینک", "Link", "Link")); ?>',
                display_time: '<?php echo esc_js(bpb_t("زمان نمایش", "Display Time", "Anzeigezeit")); ?>',
                animation: '<?php echo esc_js(bpb_t("انیمیشن", "Animation", "Animation")); ?>',
                anim_none: '<?php echo esc_js(bpb_t("بدون انیمیشن", "None", "Keine")); ?>',
                anim_shake: '<?php echo esc_js(bpb_t("لرزش (تکان خوردن)", "Shake", "Schütteln")); ?>',
                anim_glow: '<?php echo esc_js(bpb_t("درخشش (نور دور دکمه)", "Glow", "Glühen")); ?>',
                always: '<?php echo esc_js(bpb_t("همیشه", "Always", "Immer")); ?>',
                biz_hours: '<?php echo esc_js(bpb_t("فقط در ساعات کاری", "Business Hours Only", "Nur Geschäftszeiten")); ?>',
                off_hours: '<?php echo esc_js(bpb_t("فقط خارج از ساعات کاری", "Off Hours Only", "Nur außerhalb der Geschäftszeiten")); ?>',
                color: '<?php echo esc_js(bpb_t("رنگ", "Color", "Farbe")); ?>',
                font_size: '<?php echo esc_js(bpb_t("سایز فونت", "Font Size", "Schriftgröße")); ?>',
                remove: '<?php echo esc_js(bpb_t("حذف", "Remove", "Entfernen")); ?>'
            };

            jQuery(document).ready(function($){
                // Mode switcher
                function updateModeDisplay() {
                    var mode = $('input[name="bpb_settings[mode]"]:checked').val();
                    if(mode === 'branches') {
                        $('#bpb-branches-wrapper').show();
                        $('#bpb-contacts-wrapper').hide();
                    } else {
                        $('#bpb-branches-wrapper').hide();
                        $('#bpb-contacts-wrapper').show();
                    }
                }

                $('input[name="bpb_settings[mode]"]').change(updateModeDisplay);
                updateModeDisplay();

                // Initialize Color Picker
                $('.bpb-color-picker').wpColorPicker();

                function initSortable(tableId) {
                    var tableBody = document.getElementById(tableId).getElementsByTagName('tbody')[0];
                    new Sortable(tableBody, {
                        animation: 150,
                        handle: '.dashicons-move',
                        onUpdate: function () {
                            updateOrders(tableId);
                        }
                    });
                }

                initSortable('bpb-branches-table');
                initSortable('bpb-contacts-table');

                function updateOrders(tableId) {
                    var rows = $('#' + tableId).find('.bpb-branch-row');
                    rows.each(function(index) {
                        $(this).find('.bpb-order-field').val(index);
                    });
                }

                $('.bpb-add-row').click(function(e) {
                    e.preventDefault();
                    var target = $(this).data('target');
                    var tableId = 'bpb-' + target + '-table';
                    var newIndex = Date.now(); // Safe unique index

                    var html = `
                        <tr class="bpb-branch-row">
                            <th><span class="dashicons dashicons-move" style="cursor:move;"></span> ${bpb_i18n.btn_label}</th>
                            <td>
                                ${bpb_i18n.name}: <input type="text" name="bpb_settings[${target}][${newIndex}][label]" value="${bpb_i18n.btn_new}" />

                                ${bpb_i18n.conn_type}:
                                <select name="bpb_settings[${target}][${newIndex}][type]">
                                    <option value="tel">${bpb_i18n.phone}</option>
                                    <option value="mailto">${bpb_i18n.email}</option>
                                    <option value="whatsapp">${bpb_i18n.whatsapp}</option>
                                    <option value="telegram">${bpb_i18n.telegram}</option>
                                    <option value="link">${bpb_i18n.custom_link}</option>
                                </select>

                                ${bpb_i18n.value}: <input type="text" name="bpb_settings[${target}][${newIndex}][value]" value="" />

                                ${bpb_i18n.icon}:
                                <select name="bpb_settings[${target}][${newIndex}][icon]">
                                    <option value="phone">${bpb_i18n.phone}</option>
                                    <option value="email">${bpb_i18n.email}</option>
                                    <option value="whatsapp">${bpb_i18n.whatsapp}</option>
                                    <option value="telegram">${bpb_i18n.telegram}</option>
                                    <option value="link">${bpb_i18n.link}</option>
                                </select>

                                ${bpb_i18n.display_time}:
                                <select name="bpb_settings[${target}][${newIndex}][timing]">
                                    <option value="always">${bpb_i18n.always}</option>
                                    <option value="biz_hours">${bpb_i18n.biz_hours}</option>
                                    <option value="off_hours">${bpb_i18n.off_hours}</option>
                                </select>

                                ${bpb_i18n.animation}:
                                <select name="bpb_settings[${target}][${newIndex}][animation]">
                                    <option value="none">${bpb_i18n.anim_none}</option>
                                    <option value="shake">${bpb_i18n.anim_shake}</option>
                                    <option value="glow">${bpb_i18n.anim_glow}</option>
                                </select>

                                ${bpb_i18n.color}: <input type="text" class="bpb-color-picker" name="bpb_settings[${target}][${newIndex}][color]" value="#000000" />
                                ${bpb_i18n.font_size}: <input type="number" name="bpb_settings[${target}][${newIndex}][font_size]" value="14" style="width: 60px;" />

                                <button type="button" class="button bpb-remove-row">${bpb_i18n.remove}</button>

                                <input type="hidden" name="bpb_settings[${target}][${newIndex}][order]" value="${newIndex}" class="bpb-order-field">
                            </td>
                        </tr>
                    `;

                    $('#' + tableId + ' tbody').append(html);
                    // re-init color picker on new element
                    $('#' + tableId + ' tbody tr:last-child .bpb-color-picker').wpColorPicker();
                    updateOrders(tableId);
                });

                $(document).on('click', '.bpb-remove-row', function() {
                    var tableId = $(this).closest('table').attr('id');
                    $(this).closest('tr').remove();
                    updateOrders(tableId);
                });
            });
        </script>
        <hr>
        <div style="background:#fef3c7; padding:15px; border:1px solid #fcd34d;">
            <strong><?php echo esc_html(bpb_t('تبلیغ ویژه', 'Special Offer', 'Sonderangebot')); ?>:</strong> <?php echo esc_html(bpb_t('اجرای کمپین گوگل ادز برای کسب‌وکار شما با سایت با ما', 'Run Google Ads campaigns for your business with SiteBaMa', 'Führen Sie Google Ads-Kampagnen für Ihr Unternehmen mit SiteBaMa durch')); ?> - <a href="https://siteebama.com" target="_blank"><?php echo esc_html(bpb_t('اطلاعات بیشتر', 'More info', 'Weitere Informationen')); ?></a>
        </div>
    </div>
    <?php
}
