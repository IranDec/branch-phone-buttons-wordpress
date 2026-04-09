<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page(
        acp_t('فرم مشاوره', 'Consultation Form', 'Beratungsformular'),
        acp_t('درخواست مشاوره', 'Consultation Requests', 'Beratungsanfragen'),
        'manage_options',
        'acp-requests',
        'acp_render_crm_page',
        'dashicons-clipboard',
        25
    );

    add_submenu_page(
        'acp-requests',
        acp_t('تنظیمات', 'Settings', 'Einstellungen'),
        acp_t('تنظیمات', 'Settings', 'Einstellungen'),
        'manage_options',
        'acp-settings',
        'acp_render_settings_page'
    );
});

function acp_render_settings_page() {
    if (isset($_POST['acp_save']) && check_admin_referer('acp_settings_action', 'acp_settings_nonce')) {
        update_option('acp_settings', $_POST['acp_settings']);
        echo '<div class="updated"><p>' . esc_html(acp_t('تنظیمات ذخیره شد.', 'Settings saved.', 'Einstellungen gespeichert.')) . '</p></div>';
    }

    $settings = get_option('acp_settings', [
        'form_title' => acp_t('درخواست مشاوره', 'Request a Consultation', 'Beratung anfordern'),
        'admin_email' => get_option('admin_email'),
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
    ]);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(acp_t('تنظیمات فرم مشاوره', 'Consultation Form Settings', 'Einstellungen des Beratungsformulars')); ?></h1>
        <p><?php echo esc_html(acp_t('برای نمایش پاپ‌آپ، کلاس زیر را به دکمه یا لینک خود در المنتور، دیوی و غیره اضافه کنید:', 'To display the popup, add the following CSS class to your button or link in Elementor, Divi, etc.:', 'Um das Popup anzuzeigen, fügen Sie die folgende CSS-Klasse zu Ihrer Schaltfläche oder Ihrem Link in Elementor, Divi usw. hinzu:')); ?> <br><code style="font-size:16px; user-select:all;">acp-trigger-popup</code></p>
        <form method="post">
            <?php wp_nonce_field('acp_settings_action', 'acp_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html(acp_t('عنوان فرم', 'Form Title', 'Formulartitel')); ?></th>
                    <td><input type="text" name="acp_settings[form_title]" class="regular-text" value="<?php echo esc_attr($settings['form_title']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html(acp_t('ایمیل دریافت کننده (مدیر)', 'Admin Email', 'Admin E-Mail')); ?></th>
                    <td><input type="email" name="acp_settings[admin_email]" class="regular-text" value="<?php echo esc_attr($settings['admin_email']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Google reCAPTCHA v2 (Checkbox) Site Key</th>
                    <td><input type="text" name="acp_settings[recaptcha_site_key]" class="regular-text" value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Google reCAPTCHA v2 (Checkbox) Secret Key</th>
                    <td><input type="text" name="acp_settings[recaptcha_secret_key]" class="regular-text" value="<?php echo esc_attr($settings['recaptcha_secret_key']); ?>"></td>
                </tr>
            </table>
            <p><input type="submit" name="acp_save" class="button button-primary" value="<?php echo esc_attr(acp_t('ذخیره', 'Save', 'Speichern')); ?>"></p>
        </form>
    </div>
    <?php
}

function acp_render_crm_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acp_requests';

    // Handle updates
    if (isset($_POST['acp_update_req']) && check_admin_referer('acp_crm_action', 'acp_crm_nonce')) {
        $id = intval($_POST['req_id']);
        $status = sanitize_text_field($_POST['status']);
        $note = sanitize_textarea_field($_POST['admin_note']);

        $wpdb->update($table_name, ['status' => $status, 'admin_note' => $note], ['id' => $id]);
        echo '<div class="updated"><p>' . esc_html(acp_t('وضعیت بروز شد.', 'Status updated.', 'Status aktualisiert.')) . '</p></div>';
    }

    if (isset($_POST['acp_delete_req']) && check_admin_referer('acp_crm_action', 'acp_crm_nonce')) {
        $id = intval($_POST['req_id']);
        $wpdb->delete($table_name, ['id' => $id]);
        echo '<div class="updated"><p>' . esc_html(acp_t('درخواست حذف شد.', 'Request deleted.', 'Anfrage gelöscht.')) . '</p></div>';
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(acp_t('درخواست‌های مشاوره', 'Consultation Requests', 'Beratungsanfragen')); ?></h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo esc_html(acp_t('نام', 'Name', 'Name')); ?></th>
                    <th><?php echo esc_html(acp_t('ایمیل / تلفن', 'Email / Phone', 'E-Mail / Telefon')); ?></th>
                    <th><?php echo esc_html(acp_t('تاریخ درخواستی', 'Requested Date', 'Gewünschtes Datum')); ?></th>
                    <th><?php echo esc_html(acp_t('وضعیت', 'Status', 'Status')); ?></th>
                    <th><?php echo esc_html(acp_t('یادداشت مدیر', 'Admin Note', 'Admin-Notiz')); ?></th>
                    <th><?php echo esc_html(acp_t('تاریخ ثبت', 'Submitted At', 'Eingereicht am')); ?></th>
                    <th><?php echo esc_html(acp_t('عملیات', 'Actions', 'Aktionen')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($results)): ?>
                    <tr><td colspan="8"><?php echo esc_html(acp_t('هیچ درخواستی وجود ندارد.', 'No requests found.', 'Keine Anfragen gefunden.')); ?></td></tr>
                <?php else: foreach($results as $row): ?>
                    <tr>
                        <td><?php echo intval($row->id); ?></td>
                        <td><?php echo esc_html($row->name); ?></td>
                        <td><?php echo esc_html($row->email . ' / ' . $row->phone); ?></td>
                        <td><?php echo esc_html($row->req_date); ?></td>
                        <td>
                            <?php
                            if ($row->status === 'pending') echo '<span style="color:orange;font-weight:bold;">' . acp_t('در انتظار', 'Pending', 'Ausstehend') . '</span>';
                            elseif ($row->status === 'called') echo '<span style="color:green;font-weight:bold;">' . acp_t('تماس گرفته شد', 'Called', 'Angerufen') . '</span>';
                            elseif ($row->status === 'call_later') echo '<span style="color:blue;font-weight:bold;">' . acp_t('تماس مجدد', 'Call Later', 'Später anrufen') . '</span>';
                            ?>
                        </td>
                        <td><?php echo esc_html($row->admin_note); ?></td>
                        <td><?php echo esc_html($row->created_at); ?></td>
                        <td>
                            <button class="button action-edit-req" data-id="<?php echo $row->id; ?>" data-status="<?php echo esc_attr($row->status); ?>" data-note="<?php echo esc_attr($row->admin_note); ?>"><?php echo esc_html(acp_t('ویرایش', 'Edit', 'Bearbeiten')); ?></button>

                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(acp_t('آیا مطمئن هستید؟', 'Are you sure?', 'Sind Sie sicher?')); ?>');">
                                <?php wp_nonce_field('acp_crm_action', 'acp_crm_nonce'); ?>
                                <input type="hidden" name="req_id" value="<?php echo $row->id; ?>">
                                <button type="submit" name="acp_delete_req" class="button" style="color:red;"><?php echo esc_html(acp_t('حذف', 'Delete', 'Löschen')); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div id="acp-edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999;">
        <div style="background:#fff; width:400px; margin: 100px auto; padding: 20px; border-radius: 5px;">
            <h2><?php echo esc_html(acp_t('بروزرسانی وضعیت', 'Update Status', 'Status aktualisieren')); ?></h2>
            <form method="post">
                <?php wp_nonce_field('acp_crm_action', 'acp_crm_nonce'); ?>
                <input type="hidden" name="req_id" id="acp-edit-id">
                <p>
                    <label><?php echo esc_html(acp_t('وضعیت', 'Status', 'Status')); ?></label><br>
                    <select name="status" id="acp-edit-status" style="width:100%;">
                        <option value="pending"><?php echo esc_html(acp_t('در انتظار', 'Pending', 'Ausstehend')); ?></option>
                        <option value="called"><?php echo esc_html(acp_t('تماس گرفته شد', 'Called', 'Angerufen')); ?></option>
                        <option value="call_later"><?php echo esc_html(acp_t('تماس مجدد', 'Call Later', 'Später anrufen')); ?></option>
                    </select>
                </p>
                <p>
                    <label><?php echo esc_html(acp_t('یادداشت مدیر', 'Admin Note', 'Admin-Notiz')); ?></label><br>
                    <textarea name="admin_note" id="acp-edit-note" style="width:100%; height:80px;"></textarea>
                </p>
                <p>
                    <input type="submit" name="acp_update_req" class="button button-primary" value="<?php echo esc_attr(acp_t('ذخیره', 'Save', 'Speichern')); ?>">
                    <button type="button" class="button" onclick="document.getElementById('acp-edit-modal').style.display='none';"><?php echo esc_html(acp_t('لغو', 'Cancel', 'Abbrechen')); ?></button>
                </p>
            </form>
        </div>
    </div>
    <script>
        document.querySelectorAll('.action-edit-req').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('acp-edit-id').value = this.getAttribute('data-id');
                document.getElementById('acp-edit-status').value = this.getAttribute('data-status');
                document.getElementById('acp-edit-note').value = this.getAttribute('data-note');
                document.getElementById('acp-edit-modal').style.display = 'block';
            });
        });
    </script>
    <?php
}
