# Guidelines for Agents

## Translations & i18n
* This plugin relies on a custom translation function (`bpb_t()`) for localization rather than standard WordPress i18n functions (`__()`, `_e()`, etc.).
* The `bpb_t($persian, $english, $german)` function is defined in `includes/functions.php`. Always use this function when adding new user-facing text to support Persian, English, and German correctly.

## Frontend Layout & CSS
* Device-specific display logic is handled via CSS classes rather than PHP functions like `wp_is_mobile()` for better caching compatibility.
* RTL (Right-to-Left) and LTR (Left-to-Right) layout direction for modals and dynamically generated elements should be determined using the WordPress `is_rtl()` function in PHP, outputting explicit wrapper classes (e.g., `.bpb-dir-rtl` and `.bpb-dir-ltr`) or HTML `dir` attributes to ensure correct layout rendering regardless of theme overrides.
