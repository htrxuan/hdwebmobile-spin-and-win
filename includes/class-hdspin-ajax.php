<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

class HDSPIN_Ajax
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
        add_action('wp_ajax_hdspin_spin', array($this, 'spin'));
        add_action('wp_ajax_nopriv_hdspin_spin', array($this, 'spin'));
    }

    public function spin()
    {
        check_ajax_referer('hdspin_frontend_nonce', 'nonce');

        // Honeypot: real visitors never fill this hidden field.
        if (!empty($_POST['hdspin_hp'])) {
            wp_send_json_error(array('message' => __('Invalid submission.', 'hdwebmobile-spin-and-win')));
        }

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'hdwebmobile-spin-and-win')));
        }

        if (HDSPIN_Coupon_Manager::has_email_spun($email)) {
            wp_send_json_error(array(
                'message' => __('This email has already been used to spin.', 'hdwebmobile-spin-and-win'),
                'code'    => 'already_spun',
            ));
        }

        $options  = HDSPIN_Admin::get_options();
        $segments = $options['segments'];

        $index = HDSPIN_Prize_Engine::pick_segment($segments);

        if (-1 === $index) {
            wp_send_json_error(array('message' => __('Spin & Win is not configured yet.', 'hdwebmobile-spin-and-win')));
        }

        $segment     = $segments[$index];
        $is_prize    = 'none' !== $segment['type'];
        $code        = null;
        $expiry_date = '';

        // Always record the spin (win or not) so has_email_spun() correctly blocks
        // re-spinning after a "no prize" result too -- see HDSPIN_Coupon_Manager.
        $coupon = HDSPIN_Coupon_Manager::create_prize_coupon($segment, $email, $options['coupon_expiry_days']);

        if ($is_prize && $coupon) {
            $code        = $coupon->get_code();
            $expiry_date = $coupon->get_date_expires() ? $coupon->get_date_expires()->date_i18n(get_option('date_format')) : '';

            $emails = WC()->mailer()->get_emails();
            if (isset($emails['hdspin_prize_won'])) {
                $emails['hdspin_prize_won']->trigger($email, $segment['label'], $code, $expiry_date);
            }
        }

        wp_send_json_success(array(
            'segment_index' => $index,
            'label'         => $segment['label'],
            'code'          => $code,
            'expiry_date'   => $expiry_date,
        ));
    }
}
