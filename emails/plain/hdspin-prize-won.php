<?php

/**
 * Spin & Win prize email (plain text).
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

echo esc_html(wp_strip_all_tags($email_heading)) . "\n\n";

printf(
    /* translators: %s: prize label */
    esc_html__('You won: %s', 'hdwebmobile-spin-and-win'),
    esc_html($prize_label)
);
echo "\n\n";

echo esc_html($coupon_code) . "\n\n";

printf(
    /* translators: %s: expiry date */
    esc_html__('Use it at checkout before %s.', 'hdwebmobile-spin-and-win'),
    esc_html($expiry_date)
);
echo "\n\n";

if ($additional_content) {
    echo esc_html(wp_strip_all_tags($additional_content)) . "\n";
}
