<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

final class HDSPIN_Core
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-prize-engine.php';
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-coupon-manager.php';
        // Required unconditionally (not just in is_admin()) because HDSPIN_Admin::get_options()
        // is also read by Frontend and Ajax on regular front-end requests; only the settings-page
        // hook registration in init_hooks() is actually gated to admin context.
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-admin.php';
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-frontend.php';
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-ajax.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        add_filter('woocommerce_email_classes', array($this, 'register_email'));

        HDSPIN_Frontend::get_instance();
        HDSPIN_Ajax::get_instance();

        if (is_admin()) {
            HDSPIN_Admin::get_instance();
        }
    }

    public function register_email($emails)
    {
        // WC_Emails::init() includes its own class-wc-email.php base class immediately
        // before applying this filter, so it's always safe to load our subclass here --
        // loading it any earlier (e.g. at plugins_loaded) would fatal on WC_Email not existing yet.
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-email.php';
        $emails['hdspin_prize_won'] = new HDSPIN_Email();
        return $emails;
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdspin_wc_missing_notice')) {
            return;
        }
        delete_transient('hdspin_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Spin & Win requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-spin-and-win'); ?>
            </p>
        </div>
        <?php
    }
}
