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
        <h1>تنظیمات دکمه تماس شعب</h1>
        <form method="post">
            <table class="form-table" id="bpb-branches-table">
                <tbody>
                    <?php foreach ($settings['branches'] as $i => $branch): ?>
                        <tr class="bpb-branch-row">
                            <th><span class="dashicons dashicons-move"></span> شعبه <?= $i + 1 ?></th>
                            <td>
                                نام: <input type="text" name="bpb_settings[branches][<?= $i ?>][label]" value="<?= esc_attr($branch['label']) ?>" />
                                شماره: <input type="text" name="bpb_settings[branches][<?= $i ?>][phone]" value="<?= esc_attr($branch['phone']) ?>" />
                                رنگ: <input type="color" name="bpb_settings[branches][<?= $i ?>][color]" value="<?= esc_attr($branch['color']) ?>" />
                                سایز فونت: <input type="number" name="bpb_settings[branches][<?= $i ?>][font_size]" value="<?= esc_attr($branch['font_size'] ?? 14) ?>" />
                                <input type="hidden" name="bpb_settings[branches][<?= $i ?>][order]" value="<?= $i ?>" class="bpb-order-field">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <input type="submit" name="bpb_save" class="button button-primary" value="ذخیره تنظیمات">
        </form>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var tableBody = document.getElementById('bpb-branches-table').getElementsByTagName('tbody')[0];
                new Sortable(tableBody, {
                    animation: 150,
                    handle: '.dashicons-move',
                    onUpdate: function () {
                        var rows = tableBody.getElementsByClassName('bpb-branch-row');
                        for (var i = 0; i < rows.length; i++) {
                            rows[i].getElementsByClassName('bpb-order-field')[0].value = i;
                        }
                    }
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
