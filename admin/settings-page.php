<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page('تنظیمات دکمه تماس', 'تماس شعب', 'manage_options', 'bpb-settings', 'bpb_render_settings_page');
});

function bpb_render_settings_page() {
    if (isset($_POST['bpb_save'])) {
        update_option('bpb_settings', $_POST['bpb_settings']);
        echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
    }

    $settings = get_option('bpb_settings', bpb_default_settings());
    ?>
    <div class="wrap">
        <h1>تنظیمات دکمه تماس شعب (نسخه 1.1)</h1>
        <form method="post">

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
