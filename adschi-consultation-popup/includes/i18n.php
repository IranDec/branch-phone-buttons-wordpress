<?php
if (!defined('ABSPATH')) exit;

function acp_t($persian, $english, $german) {
    $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
    if (strpos($locale, 'de_') === 0) {
        return $german;
    } elseif (strpos($locale, 'en_') === 0) {
        return $english;
    }
    return $persian;
}
