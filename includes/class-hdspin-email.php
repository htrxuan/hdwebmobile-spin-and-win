<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

class HDSPIN_Email extends \WC_Email
{

    public function __construct()
    {
        $this->id             = 'hdspin_prize_won';
        $this->customer_email = true;
        $this->title          = __('Spin & Win Prize', 'hdwebmobile-spin-and-win');
        $this->description    = __('Backup copy of a Spin & Win discount code, sent alongside the on-screen reveal.', 'hdwebmobile-spin-and-win');
        $this->template_html  = 'hdspin-prize-won.php';
        $this->template_plain = 'plain/hdspin-prize-won.php';
        $this->template_base  = HDSPIN_PLUGIN_DIR . 'emails/';
        $this->placeholders   = array(
            '{prize_label}' => '',
            '{coupon_code}' => '',
            '{expiry_date}' => '',
        );

        parent::__construct();
    }

    public function get_default_subject()
    {
        return __('Your Spin & Win prize: {prize_label}', 'hdwebmobile-spin-and-win');
    }

    public function get_default_heading()
    {
        return __('Here\'s your code, just in case!', 'hdwebmobile-spin-and-win');
    }

    /**
     * @param string $email
     * @param string $prize_label
     * @param string $coupon_code
     * @param string $expiry_date Formatted date string.
     * @return bool
     */
    public function trigger($email, $prize_label, $coupon_code, $expiry_date)
    {
        $this->setup_locale();

        $this->recipient                      = $email;
        $this->placeholders['{prize_label}']  = $prize_label;
        $this->placeholders['{coupon_code}']  = $coupon_code;
        $this->placeholders['{expiry_date}']  = $expiry_date;

        if (!$this->is_enabled() || !$this->get_recipient()) {
            $this->restore_locale();
            return false;
        }

        $sent = $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

        $this->restore_locale();

        return $sent;
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'prize_label'        => $this->placeholders['{prize_label}'],
                'coupon_code'        => $this->placeholders['{coupon_code}'],
                'expiry_date'        => $this->placeholders['{expiry_date}'],
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'prize_label'        => $this->placeholders['{prize_label}'],
                'coupon_code'        => $this->placeholders['{coupon_code}'],
                'expiry_date'        => $this->placeholders['{expiry_date}'],
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_default_additional_content()
    {
        return __('This code is for one-time use and tied to the email address you spun with.', 'hdwebmobile-spin-and-win');
    }
}
