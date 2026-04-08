<?php
/*
Plugin Name: Branch Phone Buttons
Plugin URI: https://adschi.com/
Description: دکمه تماس برای شعب مختلف مخصوص موبایل با قابلیت تنظیم رنگ و نمایش تبلیغ در پنل
Version: 1.2
Requires at least: 5.0
Tested up to: 6.5
Author: Mohammad Babaei
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

// AJAX Handler for internal stats
function bpb_record_click_ajax() {
    if (isset($_POST['button_label'])) {
        $label = sanitize_text_field($_POST['button_label']);
        $label = mb_substr($label, 0, 50); // limit length to prevent abuse

        $stats = get_option('bpb_click_stats', []);

        if (!isset($stats[$label])) {
            // Prevent DoS: Max 50 distinct labels allowed
            if (count($stats) >= 50) {
                wp_send_json_error('Too many distinct labels.');
            }
            $stats[$label] = 0;
        }
        $stats[$label]++;

        update_option('bpb_click_stats', $stats);
        wp_send_json_success();
    }
    wp_send_json_error();
}
add_action('wp_ajax_bpb_record_click', 'bpb_record_click_ajax');
add_action('wp_ajax_nopriv_bpb_record_click', 'bpb_record_click_ajax');

define('BPB_PATH', plugin_dir_path(__FILE__));
define('BPB_URL', plugin_dir_url(__FILE__));

require_once BPB_PATH . 'includes/functions.php';
require_once BPB_PATH . 'admin/settings-page.php';

function bpb_enqueue_assets() {
    wp_enqueue_style('bpb-style', BPB_URL . 'assets/css/style.css', [], '1.1');
}
add_action('wp_enqueue_scripts', 'bpb_enqueue_assets');

function bpb_admin_enqueue_assets($hook) {
    if ($hook !== 'toplevel_page_bpb-settings') return;
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'bpb_admin_enqueue_assets');

function bpb_display_buttons() {
    // We handle device checking later based on settings

    // Check Page Builders (Divi Visual Builder, Elementor Preview, Customizer)
    if (isset($_GET['et_fb']) && $_GET['et_fb'] === '1') return; // Divi
    if (isset($_GET['elementor-preview'])) return; // Elementor
    if (is_customize_preview()) return; // WP Customizer

    $options = get_option('bpb_settings', bpb_default_settings());

    // Check Device Rules
    $device = $options['display_device'] ?? 'mobile_only';
    if ($device === 'mobile_only' && !wp_is_mobile()) return;
    if ($device === 'desktop_only' && wp_is_mobile()) return;

    // Check Display Rules
    if (!empty($options['show_only_homepage']) && !is_front_page()) return;
    if (!empty($options['display_pages']) && is_array($options['display_pages'])) {
        if (!is_page($options['display_pages'])) return;
    }
    if (!empty($options['hide_on_woo_checkout']) && function_exists('is_woocommerce')) {
        if (is_cart() || is_checkout()) return;
    }

    $mode = $options['mode'] ?? 'branches';
    $items = [];
    if ($mode === 'branches') {
        $items = $options['branches'] ?? [];
    } else {
        $items = $options['contacts'] ?? [];
    }

    if (empty($items)) return;

    usort($items, function($a, $b) {
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });

    $delay = isset($options['delay']) ? intval($options['delay']) * 1000 : 0;
    $display_style = isset($options['display_style']) ? $options['display_style'] : 'flat';
    $button_shape = isset($options['button_shape']) ? $options['button_shape'] : 'oval';

    $container_class = 'bpb-container bpb-style-' . esc_attr($display_style) . ' bpb-shape-' . esc_attr($button_shape);
    if ($device === 'mobile_only') {
        $container_class .= ' bpb-hide-desktop';
    } elseif ($device === 'desktop_only') {
        $container_class .= ' bpb-hide-mobile';
    }

    // Business Hours Logic
    $current_time = current_time('H:i');
    $start_time = $options['biz_time_start'] ?? '08:00';
    $end_time = $options['biz_time_end'] ?? '17:00';
    $is_biz_hours = ($current_time >= $start_time && $current_time <= $end_time);

    $has_items = false;

    ob_start();
    echo '<div id="bpb-main-container" class="' . $container_class . '" style="display:none;">';
    foreach ($items as $branch) {
        $val = $branch['value'] ?? ($branch['phone'] ?? '');
        if (empty($val)) continue;

        $timing = $branch['timing'] ?? 'always';
        if ($timing === 'biz_hours' && !$is_biz_hours) continue;
        if ($timing === 'off_hours' && $is_biz_hours) continue;

        $has_items = true;

        $style = 'background:' . esc_attr($branch['color'] ?? '') . ';';
        if (!empty($branch['font_size'])) {
            $style .= 'font-size:' . esc_attr($branch['font_size']) . 'px;';
        }

        $type = $branch['type'] ?? 'tel';
        $icon = $branch['icon'] ?? 'phone';
        $animation = $branch['animation'] ?? 'none';
        $label = $branch['label'] ?? '';

        $button_class = 'bpb-button';
        if ($animation === 'shake') $button_class .= ' bpb-anim-shake';
        if ($animation === 'glow') $button_class .= ' bpb-anim-glow';
        if (empty($label)) $button_class .= ' bpb-icon-only';

        $href = '#';
        if ($type === 'tel') $href = 'tel:' . esc_attr($val);
        elseif ($type === 'mailto') $href = 'mailto:' . esc_attr($val);
        elseif ($type === 'whatsapp') $href = 'https://wa.me/' . esc_attr($val);
        elseif ($type === 'telegram') $href = 'https://t.me/' . esc_attr(ltrim($val, '@'));
        elseif ($type === 'link') $href = esc_url($val);

        $icon_svg = bpb_get_icon_svg($icon);
        $safe_label = esc_js($label ?: $type);

        $onclick_parts = [];
        // GA Tracking
        if (!empty($options['enable_ga_tracking'])) {
            $onclick_parts[] = "if(typeof gtag === 'function') { gtag('event', 'click', {'event_category': 'Mobile Buttons', 'event_label': '{$safe_label}'}); }";
        }

        // Internal AJAX tracking via sendBeacon for reliability
        $ajax_url = admin_url('admin-ajax.php');
        $onclick_parts[] = "if(navigator.sendBeacon){ var fd = new FormData(); fd.append('action', 'bpb_record_click'); fd.append('button_label', '{$safe_label}'); navigator.sendBeacon('{$ajax_url}', fd); } else { var xhr = new XMLHttpRequest(); xhr.open('POST', '{$ajax_url}', true); xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); xhr.send('action=bpb_record_click&button_label=' + encodeURIComponent('{$safe_label}')); }";

        $onclick = ' onclick="' . implode(' ', $onclick_parts) . '"';

        echo '<a href="' . $href . '" style="' . $style . '" class="' . esc_attr($button_class) . '"' . $onclick . '>'
           . '<span class="bpb-button-icon">' . $icon_svg . '</span>';
        if (!empty($label)) {
            echo '<span class="bpb-button-label">' . esc_html($label) . '</span>';
        }
        echo '</a>';
    }
    echo '</div>';

    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                var container = document.getElementById("bpb-main-container");
                if(container) {
                    container.style.display = "flex";
                }
            }, ' . $delay . ');
        });
    </script>';
    $output = ob_get_clean();

    if ($has_items) {
        echo $output;
    }
}
add_action('wp_footer', 'bpb_display_buttons');

// Clear cache if LiteSpeed or WP Rocket is active
function bpb_clear_caches() {
    // LiteSpeed
    if (class_exists('LiteSpeed_Cache_API')) {
        do_action('litespeed_purge_all');
    }
    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
}
add_action('update_option_bpb_settings', 'bpb_clear_caches');
