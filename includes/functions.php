<?php
if (!defined('ABSPATH')) exit;

// ایجاد گزینه پیش‌فرض
function bpb_default_settings() {
    return [
        'branches' => [
            ['label' => 'شعبه شمال تهران', 'phone' => '', 'color' => '#e63946'],
            ['label' => 'شعبه غرب تهران',  'phone' => '', 'color' => '#f1a208'],
            ['label' => 'شعبه مرکز تهران', 'phone' => '', 'color' => '#52b788'],
            ['label' => 'شعبه شرق تهران',  'phone' => '', 'color' => '#118ab2'],
        ]
    ];
}

register_activation_hook(__FILE__, function() {
    if (!get_option('bpb_settings')) {
        add_option('bpb_settings', bpb_default_settings());
    }
});
