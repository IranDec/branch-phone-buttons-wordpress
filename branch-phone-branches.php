<?php
/*
Plugin Name: Branch Phone Buttons
Plugin URI: https://adschi.com/
Description: دکمه تماس برای شعب مختلف مخصوص موبایل با قابلیت تنظیم رنگ و نمایش تبلیغ در پنل
Version: 1.0
Author: Mohammad Babaei
*/

if (!defined('ABSPATH')) exit;

define('BPB_PATH', plugin_dir_path(__FILE__));
define('BPB_URL', plugin_dir_url(__FILE__));

require_once BPB_PATH . 'includes/functions.php';
require_once BPB_PATH . 'admin/settings-page.php';

function bpb_enqueue_assets() {
    wp_enqueue_style('bpb-style', BPB_URL . 'assets/css/style.css', [], '1.0');
}
add_action('wp_enqueue_scripts', 'bpb_enqueue_assets');

function bpb_display_buttons() {
    if (!wp_is_mobile()) return;

    $options = get_option('bpb_settings');
    if (empty($options['branches'])) return;

    $branches = $options['branches'];
    usort($branches, function($a, $b) {
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });

    echo '<div class="bpb-container">';
    foreach ($branches as $branch) {
        if (empty($branch['phone'])) continue;
        echo '<a href="tel:' . esc_attr($branch['phone']) . '" style="background:' . esc_attr($branch['color']) . '" class="bpb-button">'
           . '<i class="bpb-icon-phone"></i>' . esc_html($branch['label']) . '</a>';
    }
    echo '</div>';
}
add_action('wp_footer', 'bpb_display_buttons');
// Clear cache if LiteSpeed is active
function bcbp_clear_litespeed_cache() {
    if (class_exists('LiteSpeed_Cache_API')) {
        do_action('litespeed_purge_all');
    }
}
add_action('update_option_bcbp_branches', 'bcbp_clear_litespeed_cache');
add_action('update_option_bcbp_bottom_spacing', 'bcbp_clear_litespeed_cache');
