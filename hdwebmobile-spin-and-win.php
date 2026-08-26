<?php

/**
 * Plugin Name: HDWebmobile Spin & Win
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-spin-and-win/
 * Description: A gamified discount-wheel popup for WooCommerce. Real page targeting, one spin per email enforced server-side, and the winning code shown on screen immediately.
 * Version: 1.0.1
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-spin-and-win
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('HDSPIN_VERSION', '1.0.1');
define('HDSPIN_PLUGIN_FILE', __FILE__);
define('HDSPIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDSPIN_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-activator.php';

register_activation_hook(HDSPIN_PLUGIN_FILE, array(HDSPIN_Activator::class, 'activate'));
add_action('before_woocommerce_init', array(HDSPIN_Activator::class, 'declare_hpos_compatibility'));

add_action('plugins_loaded', function () {
    require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-core.php';
    HDSPIN_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(HDSPIN_PLUGIN_FILE), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" style="color:#d54e21;font-weight:bold;">' . __('Donate', 'hdwebmobile-spin-and-win') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
