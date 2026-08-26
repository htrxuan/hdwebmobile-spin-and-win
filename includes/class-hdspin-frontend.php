<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

class HDSPIN_Frontend
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
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
        add_action('wp_footer', array($this, 'render_popup_markup'));
    }

    private function is_targeted_page()
    {
        $options = HDSPIN_Admin::get_options();

        if (empty($options['enabled'])) {
            return false;
        }

        switch ($options['targeting_mode']) {
            case 'home':
                return is_front_page();
            case 'shop':
                return function_exists('is_shop') && (is_shop() || is_product() || is_product_category() || is_product_taxonomy());
            case 'specific':
                return !empty($options['targeting_page_ids']) && is_page($options['targeting_page_ids']);
            case 'all':
            default:
                return true;
        }
    }

    public function maybe_enqueue_assets()
    {
        if (!$this->is_targeted_page()) {
            return;
        }

        $options = HDSPIN_Admin::get_options();

        wp_enqueue_style('hdspin-frontend-css', HDSPIN_PLUGIN_URL . 'assets/css/hdspin-frontend.css', array(), HDSPIN_VERSION);
        wp_enqueue_script('hdspin-frontend-js', HDSPIN_PLUGIN_URL . 'assets/js/hdspin-frontend.js', array(), HDSPIN_VERSION, true);

        wp_localize_script('hdspin-frontend-js', 'hdspinParams', array(
            'ajax_url'              => admin_url('admin-ajax.php'),
            'nonce'                 => wp_create_nonce('hdspin_frontend_nonce'),
            'trigger_mode'          => $options['trigger_mode'],
            'trigger_delay_seconds' => (int) $options['trigger_delay_seconds'],
            'frequency_cap_days'    => (int) $options['frequency_cap_days'],
            'i18n'                  => array(
                'invalid_email' => __('Please enter a valid email address.', 'hdwebmobile-spin-and-win'),
                'error'         => __('Something went wrong. Please try again.', 'hdwebmobile-spin-and-win'),
                'no_prize'      => __('So close! Try again next time.', 'hdwebmobile-spin-and-win'),
                'use_by'        => __('Use it before', 'hdwebmobile-spin-and-win'),
                'copied'        => __('Copied!', 'hdwebmobile-spin-and-win'),
            ),
        ));
    }

    public function render_popup_markup()
    {
        if (!$this->is_targeted_page()) {
            return;
        }

        $options  = HDSPIN_Admin::get_options();
        $segments = $options['segments'];
        ?>
        <div id="hdspin-overlay" class="hdspin-overlay" style="display:none;">
            <div class="hdspin-modal">
                <button type="button" class="hdspin-close" aria-label="<?php esc_attr_e('Close', 'hdwebmobile-spin-and-win'); ?>">&times;</button>

                <div class="hdspin-wheel-wrap">
                    <div class="hdspin-pointer"></div>
                    <div class="hdspin-wheel" id="hdspin-wheel">
                        <?php foreach ($segments as $i => $segment) : ?>
                            <span class="hdspin-wheel__label hdspin-wheel__label--<?php echo esc_attr($i); ?>">
                                <?php echo esc_html($segment['label']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form id="hdspin-form" class="hdspin-form">
                    <p class="hdspin-form__intro">
                        <?php esc_html_e('Enter your email for a chance to win a discount!', 'hdwebmobile-spin-and-win'); ?>
                    </p>
                    <input type="email" name="email" class="hdspin-form__email" placeholder="<?php esc_attr_e('Your email address', 'hdwebmobile-spin-and-win'); ?>" autocomplete="email" required="required" />
                    <input type="text" name="hdspin_hp" class="hdspin-honeypot" tabindex="-1" autocomplete="off" />
                    <button type="submit" class="hdspin-form__submit"><?php esc_html_e('Spin the Wheel', 'hdwebmobile-spin-and-win'); ?></button>
                    <p class="hdspin-form__message" role="status"></p>
                </form>

                <div class="hdspin-result" style="display:none;">
                    <p class="hdspin-result__label"></p>
                    <p class="hdspin-result__code-row">
                        <span class="hdspin-result__code"></span>
                        <button type="button" class="hdspin-result__copy" aria-label="<?php esc_attr_e('Copy code', 'hdwebmobile-spin-and-win'); ?>" title="<?php esc_attr_e('Copy code', 'hdwebmobile-spin-and-win'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                    </p>
                    <p class="hdspin-result__copied" role="status" aria-live="polite"></p>
                    <p class="hdspin-result__expiry"></p>
                </div>
            </div>
        </div>
        <?php
    }
}
