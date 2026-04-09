<?php
if (!defined('ABSPATH')) exit;

function acp_submit_request_ajax() {
    check_ajax_referer('acp_submit_action', 'acp_submit_nonce');

    $settings = get_option('acp_settings');
    $secret_key = !empty($settings['recaptcha_secret_key']) ? $settings['recaptcha_secret_key'] : '';

    // Verify reCAPTCHA
    if (!empty($secret_key)) {
        $recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
        if (empty($recaptcha_response)) {
            wp_send_json_error(['message' => acp_t('لطفاً کپچا را تایید کنید.', 'Please verify the captcha.', 'Bitte Captcha bestätigen.')]);
        }

        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $verify_response = wp_remote_post($verify_url, [
            'body' => [
                'secret' => $secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]
        ]);

        if (is_wp_error($verify_response)) {
            wp_send_json_error(['message' => acp_t('خطا در ارتباط با گوگل.', 'Error connecting to Google.', 'Fehler bei der Verbindung mit Google.')]);
        }

        $body = json_decode(wp_remote_retrieve_body($verify_response));
        if (!$body->success) {
            wp_send_json_error(['message' => acp_t('کپچا نامعتبر است.', 'Invalid Captcha.', 'Ungültiges Captcha.')]);
        }
    }

    $name = sanitize_text_field($_POST['acp_name']);
    $email = sanitize_email($_POST['acp_email']);
    $phone = sanitize_text_field($_POST['acp_phone']);
    $date = sanitize_text_field($_POST['acp_date']);

    if (empty($name) || empty($phone) || empty($date)) {
        wp_send_json_error(['message' => acp_t('فیلدهای ستاره‌دار الزامی هستند.', 'Required fields are missing.', 'Pflichtfelder fehlen.')]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'acp_requests';

    $inserted = $wpdb->insert(
        $table_name,
        [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'req_date' => $date,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]
    );

    if ($inserted) {
        // Send Emails
        acp_send_emails($name, $email, $phone, $date);

        wp_send_json_success(['message' => acp_t('درخواست شما با موفقیت ثبت شد. به زودی با شما تماس می‌گیریم.', 'Request submitted successfully. We will contact you soon.', 'Anfrage erfolgreich eingereicht. Wir werden Sie in Kürze kontaktieren.')]);
    } else {
        wp_send_json_error(['message' => acp_t('خطا در ذخیره اطلاعات. لطفاً مجدداً تلاش کنید.', 'Error saving data. Please try again.', 'Fehler beim Speichern der Daten. Bitte versuchen Sie es erneut.')]);
    }
}
add_action('wp_ajax_acp_submit_request', 'acp_submit_request_ajax');
add_action('wp_ajax_nopriv_acp_submit_request', 'acp_submit_request_ajax');

function acp_send_emails($name, $email, $phone, $date) {
    $settings = get_option('acp_settings');
    $admin_email = !empty($settings['admin_email']) ? $settings['admin_email'] : get_option('admin_email');
    $site_name = get_bloginfo('name');

    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Admin Email
    $admin_subject = acp_t('درخواست مشاوره جدید از ', 'New Consultation Request from ', 'Neue Beratungsanfrage von ') . $site_name;
    $admin_body = "
    <div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; text-align:right; background:#f4f4f4; padding:20px;'>
        <div style='background:#fff; padding:20px; border-radius:8px; max-width:600px; margin:0 auto; box-shadow:0 4px 10px rgba(0,0,0,0.1);'>
            <h2 style='color:#007cba; border-bottom:2px solid #eee; padding-bottom:10px;'>درخواست مشاوره جدید</h2>
            <p><strong>نام:</strong> $name</p>
            <p><strong>تلفن:</strong> <a href='tel:$phone'>$phone</a></p>
            <p><strong>ایمیل:</strong> $email</p>
            <p><strong>تاریخ درخواستی:</strong> $date</p>
            <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
            <p style='font-size:12px; color:#777;'>جهت مدیریت وضعیت این درخواست به پنل مدیریت وردپرس مراجعه کنید.</p>
        </div>
    </div>";

    // In LTR languages, flip the direction
    $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
    if (strpos($locale, 'fa_') !== 0) {
        $admin_body = str_replace('direction:rtl; text-align:right;', 'direction:ltr; text-align:left;', $admin_body);
        $admin_body = str_replace('درخواست مشاوره جدید', 'New Consultation Request', $admin_body);
        $admin_body = str_replace('نام:', 'Name:', $admin_body);
        $admin_body = str_replace('تلفن:', 'Phone:', $admin_body);
        $admin_body = str_replace('ایمیل:', 'Email:', $admin_body);
        $admin_body = str_replace('تاریخ درخواستی:', 'Requested Date:', $admin_body);
        $admin_body = str_replace('جهت مدیریت وضعیت این درخواست به پنل مدیریت وردپرس مراجعه کنید.', 'Log into WordPress Admin to manage this request.', $admin_body);
    }

    wp_mail($admin_email, $admin_subject, $admin_body, $headers);

    // User Email
    if (!empty($email)) {
        $user_subject = acp_t('درخواست مشاوره شما ثبت شد - ', 'Your Consultation Request is Confirmed - ', 'Ihre Beratungsanfrage ist bestätigt - ') . $site_name;
        $user_body = "
        <div style='font-family:Tahoma, Arial, sans-serif; direction:rtl; text-align:right; background:#f4f4f4; padding:20px;'>
            <div style='background:#fff; padding:20px; border-radius:8px; max-width:600px; margin:0 auto; box-shadow:0 4px 10px rgba(0,0,0,0.1); border-top: 5px solid #007cba;'>
                <h2 style='color:#333;'>سلام $name عزیز،</h2>
                <p>درخواست مشاوره شما برای تاریخ <strong>$date</strong> با موفقیت ثبت شد.</p>
                <p>کارشناسان ما به زودی از طریق شماره تلفن <strong>$phone</strong> با شما تماس خواهند گرفت.</p>
                <br>
                <p>با تشکر،<br>تیم پشتیبانی <strong>$site_name</strong></p>
            </div>
        </div>";

        if (strpos($locale, 'fa_') !== 0) {
            $user_body = str_replace('direction:rtl; text-align:right;', 'direction:ltr; text-align:left;', $user_body);
            $user_body = str_replace("سلام $name عزیز،", "Hello $name,", $user_body);
            $user_body = str_replace("درخواست مشاوره شما برای تاریخ <strong>$date</strong> با موفقیت ثبت شد.", "Your consultation request for <strong>$date</strong> has been successfully received.", $user_body);
            $user_body = str_replace("کارشناسان ما به زودی از طریق شماره تلفن <strong>$phone</strong> با شما تماس خواهند گرفت.", "Our experts will contact you soon at <strong>$phone</strong>.", $user_body);
            $user_body = str_replace("با تشکر،<br>تیم پشتیبانی", "Best regards,<br>Support Team", $user_body);
        }

        wp_mail($email, $user_subject, $user_body, $headers);
    }
}
