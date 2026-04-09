<?php
if (!defined('ABSPATH')) exit;

function acp_enqueue_frontend_scripts() {
    $settings = get_option('acp_settings');
    $site_key = !empty($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';

    if (!empty($site_key)) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
    }
}
add_action('wp_enqueue_scripts', 'acp_enqueue_frontend_scripts');

function acp_render_popup_html() {
    $settings = get_option('acp_settings');
    $title = !empty($settings['form_title']) ? $settings['form_title'] : acp_t('درخواست مشاوره', 'Request a Consultation', 'Beratung anfordern');
    $site_key = !empty($settings['recaptcha_site_key']) ? $settings['recaptcha_site_key'] : '';
    ?>
    <style>
        /* Lightweight Popup CSS */
        #acp-popup-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 999999; backdrop-filter: blur(3px);
            align-items: center; justify-content: center;
        }
        #acp-popup-box {
            background: #fff; padding: 30px; border-radius: 12px; width: 90%; max-width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative; direction: rtl;
            font-family: inherit;
        }
        #acp-popup-close {
            position: absolute; top: 15px; left: 15px; cursor: pointer; font-size: 24px;
            line-height: 1; color: #666;
        }
        .acp-form-group { margin-bottom: 15px; text-align: right; }
        .acp-form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .acp-form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .acp-btn { width: 100%; padding: 12px; background: #007cba; color: #fff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; transition: 0.3s; }
        .acp-btn:hover { background: #005a8c; }
        #acp-msg { margin-top: 15px; font-size: 14px; text-align: center; display: none; padding: 10px; border-radius: 4px;}
        .acp-success { background: #d4edda; color: #155724; }
        .acp-error { background: #f8d7da; color: #721c24; }
    </style>

    <div id="acp-popup-overlay">
        <div id="acp-popup-box">
            <span id="acp-popup-close">&times;</span>
            <h2 style="margin-top:0; text-align:center; font-size: 22px;"><?php echo esc_html($title); ?></h2>
            <form id="acp-form">
                <?php wp_nonce_field('acp_submit_action', 'acp_submit_nonce'); ?>
                <div class="acp-form-group">
                    <label><?php echo esc_html(acp_t('نام و نام خانوادگی', 'Full Name', 'Vollständiger Name')); ?> *</label>
                    <input type="text" name="acp_name" required>
                </div>
                <div class="acp-form-group">
                    <label><?php echo esc_html(acp_t('ایمیل', 'Email', 'E-Mail')); ?></label>
                    <input type="email" name="acp_email">
                </div>
                <div class="acp-form-group">
                    <label><?php echo esc_html(acp_t('شماره تماس', 'Phone Number', 'Telefonnummer')); ?> *</label>
                    <input type="tel" name="acp_phone" required>
                </div>
                <div class="acp-form-group">
                    <label><?php echo esc_html(acp_t('تاریخ درخواستی برای مشاوره', 'Requested Date', 'Gewünschtes Datum')); ?> *</label>
                    <input type="date" name="acp_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <?php if (!empty($site_key)): ?>
                    <div class="acp-form-group" style="display:flex; justify-content:center;">
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="acp-btn" id="acp-submit-btn"><?php echo esc_html(acp_t('ثبت درخواست', 'Submit Request', 'Anfrage absenden')); ?></button>
                <div id="acp-msg"></div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var overlay = document.getElementById('acp-popup-overlay');
            var closeBtn = document.getElementById('acp-popup-close');
            var triggers = document.querySelectorAll('.acp-trigger-popup, .acp-trigger-popup a');

            triggers.forEach(function(trigger) {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    overlay.style.display = 'flex';
                });
            });

            closeBtn.addEventListener('click', function() {
                overlay.style.display = 'none';
            });

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    overlay.style.display = 'none';
                }
            });

            var form = document.getElementById('acp-form');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('acp-submit-btn');
                var msg = document.getElementById('acp-msg');
                btn.disabled = true;
                btn.innerText = '<?php echo esc_js(acp_t("در حال ارسال...", "Sending...", "Senden...")); ?>';
                msg.style.display = 'none';

                var formData = new FormData(form);
                formData.append('action', 'acp_submit_request');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    msg.style.display = 'block';
                    if (data.success) {
                        msg.className = 'acp-success';
                        msg.innerText = data.data.message;
                        form.reset();
                        if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
                        setTimeout(() => { overlay.style.display = 'none'; msg.style.display = 'none'; }, 3000);
                    } else {
                        msg.className = 'acp-error';
                        msg.innerText = data.data.message;
                        if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
                    }
                    btn.disabled = false;
                    btn.innerText = '<?php echo esc_js(acp_t("ثبت درخواست", "Submit Request", "Anfrage absenden")); ?>';
                })
                .catch(error => {
                    msg.style.display = 'block';
                    msg.className = 'acp-error';
                    msg.innerText = '<?php echo esc_js(acp_t("خطای شبکه رخ داد.", "Network error occurred.", "Netzwerkfehler aufgetreten.")); ?>';
                    btn.disabled = false;
                    btn.innerText = '<?php echo esc_js(acp_t("ثبت درخواست", "Submit Request", "Anfrage absenden")); ?>';
                });
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'acp_render_popup_html');
