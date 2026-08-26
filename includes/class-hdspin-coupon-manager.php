<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * All coupon creation/lookup for Spin & Win lives here. No custom database
 * table is used -- each spin produces at most one shop_coupon post tagged
 * with the subscriber's email, which is also how eligibility is checked.
 */
class HDSPIN_Coupon_Manager
{

    public static function has_email_spun($email)
    {
        // 'any' explicitly excludes 'trash' (a WordPress core convention), but "no prize"
        // spin records are deliberately trashed immediately -- so trash must be listed here.
        $found = get_posts(array(
            'post_type'   => 'shop_coupon',
            'post_status' => array('publish', 'draft', 'pending', 'private', 'trash'),
            'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                array(
                    'key'   => '_hdspin_email',
                    'value' => $email,
                ),
            ),
            'fields'      => 'ids',
            'numberposts' => 1,
        ));

        return !empty($found);
    }

    /**
     * Always records the spin attempt, win or not -- this is what has_email_spun()
     * checks against, so a "no prize" result must still count against the one-spin
     * limit or the wheel could be re-spun indefinitely until something real is won.
     *
     * For a "none" (no prize) segment, the record is a real coupon post but with
     * usage_limit=0 (never actually redeemable even if somehow discovered) and
     * post_status='trash' (so it never clutters WooCommerce > Coupons) -- it exists
     * purely as a tagged marker that this email has already used its spin.
     *
     * @param array $segment      One row from the segments settings array.
     * @param string $email
     * @param int    $expiry_days
     * @return \WC_Coupon|null Null if the record could not be created.
     */
    public static function create_prize_coupon(array $segment, $email, $expiry_days)
    {
        $is_prize = 'none' !== $segment['type'];

        $coupon = new \WC_Coupon();
        $coupon->set_code('SPIN-' . strtoupper(wp_generate_password(8, false, false)));

        if (!$is_prize) {
            $coupon->set_discount_type('percent');
            $coupon->set_amount(0);
            $coupon->set_status('trash');
        } elseif ('free_shipping' === $segment['type']) {
            $coupon->set_discount_type('fixed_cart');
            $coupon->set_amount(0);
            $coupon->set_free_shipping(true);
        } else {
            $coupon->set_discount_type($segment['type']);
            $coupon->set_amount((float) $segment['amount']);
        }

        $coupon->set_individual_use(true);
        $coupon->set_usage_limit($is_prize ? 1 : 0);
        $coupon->set_date_expires(strtotime('+' . absint($expiry_days) . ' days'));
        $coupon->add_meta_data('_hdspin_email', $email, true);
        $coupon->add_meta_data('_hdspin_segment_label', $segment['label'], true);

        $id = $coupon->save();

        return $id ? $coupon : null;
    }
}
