<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page('تنظیمات دکمه تماس', 'تماس شعب', 'manage_options', 'bpb-settings', 'bpb_render_settings_page');
});

function bpb_render_settings_page() {
    if (isset($_POST['bpb_save']) && check_admin_referer('bpb_settings_action', 'bpb_settings_nonce')) {
        update_option('bpb_settings', $_POST['bpb_settings']);
        echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
    }

    if (isset($_POST['bpb_reset_stats']) && check_admin_referer('bpb_settings_action', 'bpb_settings_nonce')) {
        update_option('bpb_click_stats', []);
        echo '<div class="updated"><p>آمار کلیک‌ها بازنشانی شد.</p></div>';
    }

    $settings = get_option('bpb_settings', bpb_default_settings());
    $stats = get_option('bpb_click_stats', []);
    ?>
    <div class="wrap">
        <h1>تنظیمات دکمه تماس شعب (نسخه 1.2)</h1>
        <form method="post">
            <?php wp_nonce_field('bpb_settings_action', 'bpb_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">حالت نمایش</th>
                    <td>
                        <label>
                            <input type="radio" name="bpb_settings[mode]" value="branches" <?php checked($settings['mode'] ?? 'branches', 'branches'); ?>>
                            حالت شعب (چند شعبه)
                        </label><br>
                        <label>
                            <input type="radio" name="bpb_settings[mode]" value="contacts" <?php checked($settings['mode'] ?? 'branches', 'contacts'); ?>>
                            حالت راه‌های ارتباطی (تماس، ایمیل و ...)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">تأخیر نمایش (ثانیه)</th>
                    <td>
                        <input type="number" name="bpb_settings[delay]" value="<?php echo esc_attr($settings['delay'] ?? 0); ?>" min="0" />
                        <p class="description">تعداد ثانیه‌هایی که طول می‌کشد تا دکمه‌ها نمایش داده شوند. 0 برای نمایش فوری.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">نحوه نمایش</th>
                    <td>
                        <select name="bpb_settings[display_style]">
                            <option value="flat" <?php selected($settings['display_style'] ?? 'flat', 'flat'); ?>>چسبیده به پایین (کامل)</option>
                            <option value="floating" <?php selected($settings['display_style'] ?? 'flat', 'floating'); ?>>شناور (گرد)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">قوانین نمایش (صفحات)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="bpb_settings[show_only_homepage]" value="1" <?php checked($settings['show_only_homepage'] ?? 0, 1); ?>>
                            فقط در صفحه اصلی نمایش داده شود
                        </label><br>
                        <label>
                            <input type="checkbox" name="bpb_settings[hide_on_woo_checkout]" value="1" <?php checked($settings['hide_on_woo_checkout'] ?? 0, 1); ?>>
                            عدم نمایش در صفحه سبد خرید و پرداخت ووکامرس
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">ساعات کاری اداری</th>
                    <td>
                        از <input type="time" name="bpb_settings[biz_time_start]" value="<?php echo esc_attr($settings['biz_time_start'] ?? '08:00'); ?>" />
                        تا <input type="time" name="bpb_settings[biz_time_end]" value="<?php echo esc_attr($settings['biz_time_end'] ?? '17:00'); ?>" />
                        <p class="description">بر اساس منطقه زمانی وردپرس (تنظیمات > عمومی). می‌توانید دکمه‌ها را محدود به این ساعات کنید.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">رهگیری آمار (Analytics)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="bpb_settings[enable_ga_tracking]" value="1" <?php checked($settings['enable_ga_tracking'] ?? 0, 1); ?>>
                            ثبت کلیک‌ها در گوگل آنالیتیکس (Gtag)
                        </label>
                    </td>
                </tr>
            </table>

            <hr>

            <div id="bpb-branches-wrapper">
                <h2>دکمه‌های حالت شعب</h2>
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
                                <th><span class="dashicons dashicons-move" style="cursor:move;"></span> دکمه</th>
                                <td>
                                    نام: <input type="text" name="bpb_settings[branches][<?php echo $i ?>][label]" value="<?php echo esc_attr($branch['label']) ?>" />

                                    نوع اتصال:
                                    <select name="bpb_settings[branches][<?php echo $i ?>][type]">
                                        <option value="tel" <?php selected($type, 'tel'); ?>>تلفن</option>
                                        <option value="mailto" <?php selected($type, 'mailto'); ?>>ایمیل</option>
                                        <option value="whatsapp" <?php selected($type, 'whatsapp'); ?>>واتس‌اپ</option>
                                        <option value="telegram" <?php selected($type, 'telegram'); ?>>تلگرام</option>
                                        <option value="link" <?php selected($type, 'link'); ?>>لینک دلخواه</option>
                                    </select>

                                    مقدار (شماره/لینک): <input type="text" name="bpb_settings[branches][<?php echo $i ?>][value]" value="<?php echo esc_attr($val) ?>" />

                                    آیکون:
                                    <select name="bpb_settings[branches][<?php echo $i ?>][icon]">
                                        <option value="phone" <?php selected($icon, 'phone'); ?>>تلفن</option>
                                        <option value="email" <?php selected($icon, 'email'); ?>>ایمیل</option>
                                        <option value="whatsapp" <?php selected($icon, 'whatsapp'); ?>>واتس‌اپ</option>
                                        <option value="telegram" <?php selected($icon, 'telegram'); ?>>تلگرام</option>
                                        <option value="link" <?php selected($icon, 'link'); ?>>لینک</option>
                                    </select>

                                    زمان نمایش:
                                    <select name="bpb_settings[branches][<?php echo $i ?>][timing]">
                                        <option value="always" <?php selected($timing, 'always'); ?>>همیشه</option>
                                        <option value="biz_hours" <?php selected($timing, 'biz_hours'); ?>>فقط در ساعات کاری</option>
                                        <option value="off_hours" <?php selected($timing, 'off_hours'); ?>>فقط خارج از ساعات کاری</option>
                                    </select>

                                    رنگ: <input type="text" class="bpb-color-picker" name="bpb_settings[branches][<?php echo $i ?>][color]" value="<?php echo esc_attr($branch['color']) ?>" />
                                    سایز فونت: <input type="number" name="bpb_settings[branches][<?php echo $i ?>][font_size]" value="<?php echo esc_attr($branch['font_size'] ?? 14) ?>" style="width: 60px;" />

                                    <button type="button" class="button bpb-remove-row">حذف</button>

                                    <input type="hidden" name="bpb_settings[branches][<?php echo $i ?>][order]" value="<?php echo $i ?>" class="bpb-order-field">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" id="bpb-add-branch-row" class="button bpb-add-row" data-target="branches">افزودن دکمه جدید (شعب)</button></p>
            </div>

            <div id="bpb-contacts-wrapper">
                <h2>دکمه‌های حالت راه‌های ارتباطی</h2>
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
                                <th><span class="dashicons dashicons-move" style="cursor:move;"></span> دکمه</th>
                                <td>
                                    نام: <input type="text" name="bpb_settings[contacts][<?php echo $i ?>][label]" value="<?php echo esc_attr($contact['label']) ?>" />

                                    نوع اتصال:
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][type]">
                                        <option value="tel" <?php selected($type, 'tel'); ?>>تلفن</option>
                                        <option value="mailto" <?php selected($type, 'mailto'); ?>>ایمیل</option>
                                        <option value="whatsapp" <?php selected($type, 'whatsapp'); ?>>واتس‌اپ</option>
                                        <option value="telegram" <?php selected($type, 'telegram'); ?>>تلگرام</option>
                                        <option value="link" <?php selected($type, 'link'); ?>>لینک دلخواه</option>
                                    </select>

                                    مقدار (شماره/لینک): <input type="text" name="bpb_settings[contacts][<?php echo $i ?>][value]" value="<?php echo esc_attr($val) ?>" />

                                    آیکون:
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][icon]">
                                        <option value="phone" <?php selected($icon, 'phone'); ?>>تلفن</option>
                                        <option value="email" <?php selected($icon, 'email'); ?>>ایمیل</option>
                                        <option value="whatsapp" <?php selected($icon, 'whatsapp'); ?>>واتس‌اپ</option>
                                        <option value="telegram" <?php selected($icon, 'telegram'); ?>>تلگرام</option>
                                        <option value="link" <?php selected($icon, 'link'); ?>>لینک</option>
                                    </select>

                                    زمان نمایش:
                                    <select name="bpb_settings[contacts][<?php echo $i ?>][timing]">
                                        <option value="always" <?php selected($timing, 'always'); ?>>همیشه</option>
                                        <option value="biz_hours" <?php selected($timing, 'biz_hours'); ?>>فقط در ساعات کاری</option>
                                        <option value="off_hours" <?php selected($timing, 'off_hours'); ?>>فقط خارج از ساعات کاری</option>
                                    </select>

                                    رنگ: <input type="text" class="bpb-color-picker" name="bpb_settings[contacts][<?php echo $i ?>][color]" value="<?php echo esc_attr($contact['color']) ?>" />
                                    سایز فونت: <input type="number" name="bpb_settings[contacts][<?php echo $i ?>][font_size]" value="<?php echo esc_attr($contact['font_size'] ?? 14) ?>" style="width: 60px;" />

                                    <button type="button" class="button bpb-remove-row">حذف</button>

                                    <input type="hidden" name="bpb_settings[contacts][<?php echo $i ?>][order]" value="<?php echo $i ?>" class="bpb-order-field">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" id="bpb-add-contact-row" class="button bpb-add-row" data-target="contacts">افزودن دکمه جدید (راه‌های ارتباطی)</button></p>
            </div>

            <input type="submit" name="bpb_save" class="button button-primary" value="ذخیره تنظیمات">
        </form>

        <hr>
        <h2>آمار کلیک‌ها (داخلی)</h2>
        <table class="widefat striped" style="max-width: 400px;">
            <thead>
                <tr>
                    <th>نام دکمه</th>
                    <th>تعداد کلیک</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stats)): ?>
                    <tr><td colspan="2">هنوز هیچ کلیکی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($stats as $lbl => $count): ?>
                        <tr>
                            <td><?php echo esc_html($lbl); ?></td>
                            <td><?php echo intval($count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <form method="post" onsubmit="return confirm('آیا از پاک شدن آمار مطمئن هستید؟');">
            <?php wp_nonce_field('bpb_settings_action', 'bpb_settings_nonce'); ?>
            <input type="submit" name="bpb_reset_stats" class="button button-secondary" value="بازنشانی آمار">
        </form>

        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
        <script>
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
                            <th><span class="dashicons dashicons-move" style="cursor:move;"></span> دکمه</th>
                            <td>
                                نام: <input type="text" name="bpb_settings[${target}][${newIndex}][label]" value="جدید" />

                                نوع اتصال:
                                <select name="bpb_settings[${target}][${newIndex}][type]">
                                    <option value="tel">تلفن</option>
                                    <option value="mailto">ایمیل</option>
                                    <option value="whatsapp">واتس‌اپ</option>
                                    <option value="telegram">تلگرام</option>
                                    <option value="link">لینک دلخواه</option>
                                </select>

                                مقدار (شماره/لینک): <input type="text" name="bpb_settings[${target}][${newIndex}][value]" value="" />

                                آیکون:
                                <select name="bpb_settings[${target}][${newIndex}][icon]">
                                    <option value="phone">تلفن</option>
                                    <option value="email">ایمیل</option>
                                    <option value="whatsapp">واتس‌اپ</option>
                                    <option value="telegram">تلگرام</option>
                                    <option value="link">لینک</option>
                                </select>

                                زمان نمایش:
                                <select name="bpb_settings[${target}][${newIndex}][timing]">
                                    <option value="always">همیشه</option>
                                    <option value="biz_hours">فقط در ساعات کاری</option>
                                    <option value="off_hours">فقط خارج از ساعات کاری</option>
                                </select>

                                رنگ: <input type="text" class="bpb-color-picker" name="bpb_settings[${target}][${newIndex}][color]" value="#000000" />
                                سایز فونت: <input type="number" name="bpb_settings[${target}][${newIndex}][font_size]" value="14" style="width: 60px;" />

                                <button type="button" class="button bpb-remove-row">حذف</button>

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
            <strong>تبلیغ ویژه:</strong> اجرای کمپین گوگل ادز برای کسب‌وکار شما با سایت با ما - <a href="https://siteebama.com" target="_blank">اطلاعات بیشتر</a>
        </div>
    </div>
    <?php
}
