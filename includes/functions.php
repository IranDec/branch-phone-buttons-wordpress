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

// ایجاد گزینه پیش‌فرض
function bpb_default_settings() {
    return [
        'mode' => 'branches',
        'delay' => 0,
        'display_style' => 'flat',
        'display_location' => 'all',
        'devices' => ['mobile', 'tablet', 'desktop'],
        'label_position' => 'side',
        'enable_homepage_override' => 0,
        'hide_on_woo_checkout' => 1,
        'enable_ga_tracking' => 1,
        'biz_time_start' => '08:00',
        'biz_time_end' => '17:00',
        'display_device' => 'mobile_only',
        'display_pages' => [],
        'button_shape' => 'oval',
        'phone_behavior' => 'direct',
        'email_behavior' => 'direct',
        'branches' => [
            ['label' => 'شعبه شمال تهران', 'value' => '', 'type' => 'tel', 'icon' => 'phone', 'color' => '#e63946', 'timing' => 'always', 'animation' => 'none'],
            ['label' => 'شعبه غرب تهران',  'value' => '', 'type' => 'tel', 'icon' => 'phone', 'color' => '#f1a208', 'timing' => 'always', 'animation' => 'none'],
            ['label' => 'شعبه مرکز تهران', 'value' => '', 'type' => 'tel', 'icon' => 'phone', 'color' => '#52b788', 'timing' => 'always', 'animation' => 'none'],
            ['label' => 'شعبه شرق تهران',  'value' => '', 'type' => 'tel', 'icon' => 'phone', 'color' => '#118ab2', 'timing' => 'always', 'animation' => 'none'],
        ],
        'contacts' => [
            ['label' => 'تماس', 'value' => '', 'type' => 'tel', 'icon' => 'phone', 'color' => '#e63946', 'timing' => 'biz_hours', 'animation' => 'none'],
            ['label' => 'پیامگیر',  'value' => '', 'type' => 'telegram', 'icon' => 'telegram', 'color' => '#118ab2', 'timing' => 'off_hours', 'animation' => 'none'],
        ]
    ];
}

function bpb_get_icon_svg($icon) {
    switch ($icon) {
        case 'email':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
        case 'whatsapp':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.76.46 3.44 1.35 4.95L2 22l5.31-1.39c1.45.8 3.09 1.22 4.73 1.22 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.46 14.18c-.26.74-1.53 1.4-2.12 1.47-.53.07-1.19.22-3.83-.87-3.18-1.32-5.23-4.57-5.39-4.78-.15-.21-1.28-1.7-1.28-3.24s.81-2.31 1.09-2.61c.28-.29.62-.37.82-.37.21 0 .42 0 .61.01.21.01.48-.08.75.56.28.66.97 2.37 1.06 2.56.08.19.14.41.01.66-.12.25-.19.41-.37.62-.19.21-.4.46-.56.62-.18.17-.37.35-.16.71.21.36.93 1.54 2 2.5 1.38 1.23 2.54 1.61 2.9 1.78.36.17.57.14.78-.1.21-.24.9-1.05 1.15-1.41.24-.36.48-.3.82-.18.34.12 2.14 1.01 2.5 1.19.36.18.61.28.69.44.09.15.09.89-.17 1.63z"/></svg>';
        case 'telegram':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M11.94 2a10 10 0 0 0-10 10 10 10 0 0 0 10 10 10 10 0 0 0 10-10 10 10 0 0 0-10-10zm5.66 7.42l-1.92 9.04c-.14.64-.52.8-1.05.51l-2.91-2.15-1.4 1.35c-.16.16-.29.29-.59.29l.21-2.97 5.4-4.88c.24-.21-.05-.33-.37-.11L9.3 14.64l-2.88-.9c-.63-.2-.64-.63.13-.93l11.23-4.32c.52-.19 1.02.12.82.93z"/></svg>';
        case 'link':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>';
        case 'phone':
        default:
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M6.62 10.79c1.44 2.83 3.76 5.15 6.59 6.59l2.2-2.2c.28-.28.67-.36 1.02-.25 1.12.37 2.32.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
    }
}

// Add a shortcode to display buttons inside pages/posts manually
add_shortcode('bpb_buttons', 'bpb_buttons_shortcode_handler');
function bpb_buttons_shortcode_handler($atts) {
    ob_start();
    // Temporarily trick the display function into rendering by hooking it here if we refactor or just call a rendering helper
    // To keep it simple, we'll implement a static inline style wrapper for shortcodes
    if (function_exists('bpb_display_buttons_html')) {
        bpb_display_buttons_html(true);
    }
    return ob_get_clean();
}

register_activation_hook(__FILE__, function() {
    if (!get_option('bpb_settings')) {
        add_option('bpb_settings', bpb_default_settings());
    }
});

function bpb_install_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bpb_clicks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        button_label varchar(255) NOT NULL,
        click_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        user_uuid varchar(64) NOT NULL,
        source varchar(100) NOT NULL,
        PRIMARY KEY  (id),
        KEY click_time (click_time)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


function bpb_check_db_version() {
    if (get_option('bpb_db_version') !== '1.0') {
        bpb_install_db();
        update_option('bpb_db_version', '1.0');
    }
}
add_action('plugins_loaded', 'bpb_check_db_version');
