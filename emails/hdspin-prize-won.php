<?php

/**
 * Spin & Win prize email (HTML).
 *
 * @var string $prize_label
 * @var string $coupon_code
 * @var string $expiry_date
 * @var string $email_heading
 * @var string $additional_content
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, not ours to prefix
?>

<p>
    <?php
    printf(
        /* translators: %s: prize label, e.g. "10% OFF" */
        esc_html__('You won: %s', 'hdwebmobile-spin-and-win'),
        '<strong>' . esc_html($prize_label) . '</strong>'
    );
    ?>
</p>

<p style="text-align: center; margin: 24px 0;">
    <span style="display:inline-block;border:2px dashed #05A67D;color:#05A67D;padding:12px 24px;font-size:1.2em;font-weight:bold;letter-spacing:1px;">
        <?php echo esc_html($coupon_code); ?>
    </span>
</p>

<p>
    <?php
    printf(
        /* translators: %s: expiry date */
        esc_html__('Use it at checkout before %s.', 'hdwebmobile-spin-and-win'),
        esc_html($expiry_date)
    );
    ?>
</p>

<?php
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

do_action('woocommerce_email_footer', $email); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook, not ours to prefix
