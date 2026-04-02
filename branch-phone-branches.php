<?php
/*
Plugin Name: Branch Phone Buttons
Plugin URI: https://adschi.com/
Description: دکمه تماس برای شعب مختلف مخصوص موبایل با قابلیت تنظیم رنگ و نمایش تبلیغ در پنل
Version: 1.1
Author: Mohammad Babaei
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

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
    if (!wp_is_mobile()) return;

    $options = get_option('bpb_settings', bpb_default_settings());

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
    $container_class = 'bpb-container bpb-style-' . esc_attr($display_style);

    echo '<div id="bpb-main-container" class="' . $container_class . '" style="display:none;">';
    foreach ($items as $branch) {
        $val = $branch['value'] ?? ($branch['phone'] ?? '');
        if (empty($val)) continue;

        $style = 'background:' . esc_attr($branch['color'] ?? '') . ';';
        if (!empty($branch['font_size'])) {
            $style .= 'font-size:' . esc_attr($branch['font_size']) . 'px;';
        }

        $type = $branch['type'] ?? 'tel';
        $icon = $branch['icon'] ?? 'phone';

        $href = '#';
        if ($type === 'tel') $href = 'tel:' . esc_attr($val);
        elseif ($type === 'mailto') $href = 'mailto:' . esc_attr($val);
        elseif ($type === 'whatsapp') $href = 'https://wa.me/' . esc_attr($val);
        elseif ($type === 'telegram') $href = 'https://t.me/' . esc_attr(ltrim($val, '@'));
        elseif ($type === 'link') $href = esc_url($val);

        $icon_svg = bpb_get_icon_svg($icon);

        echo '<a href="' . $href . '" style="' . $style . '" class="bpb-button">'
           . '<span class="bpb-button-icon">' . $icon_svg . '</span>'
           . '<span class="bpb-button-label">' . esc_html($branch['label'] ?? '') . '</span>'
           . '</a>';
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
