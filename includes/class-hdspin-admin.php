<?php

namespace htrxuan\hdspin;

if (!defined('ABSPATH')) {
    exit;
}

class HDSPIN_Admin
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
        require_once HDSPIN_PLUGIN_DIR . 'includes/class-hdspin-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook)
    {
        if ('woocommerce_page_hdwebmobile' !== $hook) {
            return;
        }

        // Read-only tab selector, same pattern as core's own admin tab UIs -- no state
        // change occurs from reading it, so nonce verification doesn't apply here.
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ('spin-and-win' !== $tab) {
            return;
        }

        wp_enqueue_style('hdspin-admin-css', HDSPIN_PLUGIN_URL . 'assets/css/hdspin-admin.css', array(), HDSPIN_VERSION);
        wp_enqueue_script('hdspin-admin-js', HDSPIN_PLUGIN_URL . 'assets/js/hdspin-admin.js', array('jquery'), HDSPIN_VERSION, true);
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['spin-and-win'] = array(
            'label'  => __('Spin & Win', 'hdwebmobile-spin-and-win'),
            'order'  => 120,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function render_page()
    {
        ?>
        <p><?php esc_html_e('A gamified discount-wheel popup for WooCommerce with real page targeting and server-enforced one spin per email.', 'hdwebmobile-spin-and-win'); ?></p>
        <form method="post" action="options.php">
            <?php
            settings_fields('hdspin_option_group');
            do_settings_sections('hdspin-settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function page_init()
    {
        register_setting(
            'hdspin_option_group',
            'hdspin_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => array(),
            )
        );

        add_settings_section(
            'hdspin_section_general',
            __('General', 'hdwebmobile-spin-and-win'),
            '__return_false',
            'hdspin-settings'
        );

        add_settings_field('enabled', __('Enable Spin & Win', 'hdwebmobile-spin-and-win'), array($this, 'enabled_callback'), 'hdspin-settings', 'hdspin_section_general');

        add_settings_section(
            'hdspin_section_trigger',
            __('Trigger & Targeting', 'hdwebmobile-spin-and-win'),
            array($this, 'print_trigger_section_info'),
            'hdspin-settings'
        );

        add_settings_field('trigger', __('Show popup', 'hdwebmobile-spin-and-win'), array($this, 'trigger_callback'), 'hdspin-settings', 'hdspin_section_trigger');
        add_settings_field('targeting', __('Show on', 'hdwebmobile-spin-and-win'), array($this, 'targeting_callback'), 'hdspin-settings', 'hdspin_section_trigger');
        add_settings_field('frequency_cap_days', __('Don\'t re-show for (days)', 'hdwebmobile-spin-and-win'), array($this, 'frequency_cap_callback'), 'hdspin-settings', 'hdspin_section_trigger');
        add_settings_field('coupon_expiry_days', __('Coupon expires after (days)', 'hdwebmobile-spin-and-win'), array($this, 'coupon_expiry_callback'), 'hdspin-settings', 'hdspin_section_trigger');

        add_settings_section(
            'hdspin_section_segments',
            __('Prize Segments', 'hdwebmobile-spin-and-win'),
            array($this, 'print_segments_section_info'),
            'hdspin-settings'
        );

        add_settings_field('segments', __('Wheel Segments', 'hdwebmobile-spin-and-win'), array($this, 'segments_callback'), 'hdspin-settings', 'hdspin_section_segments');
    }

    public function print_trigger_section_info()
    {
        esc_html_e('Choose when the popup appears and which pages it\'s allowed to show on.', 'hdwebmobile-spin-and-win');
    }

    public function print_segments_section_info()
    {
        esc_html_e('The wheel always has 8 segments. Disable any you don\'t want to use. Weight controls how likely a segment is to be picked relative to the others.', 'hdwebmobile-spin-and-win');
    }

    public static function get_options()
    {
        $defaults = array(
            'enabled'             => 1,
            'segments'            => self::default_segments(),
            'trigger_mode'        => 'delay',
            'trigger_delay_seconds' => 8,
            'targeting_mode'      => 'all',
            'targeting_page_ids'  => array(),
            'frequency_cap_days'  => 7,
            'coupon_expiry_days'  => 7,
        );

        return wp_parse_args(get_option('hdspin_options', array()), $defaults);
    }

    public static function default_segments()
    {
        return array(
            array('enabled' => 1, 'label' => '10% OFF', 'type' => 'percent', 'amount' => 10, 'weight' => 20),
            array('enabled' => 1, 'label' => 'Free Shipping', 'type' => 'free_shipping', 'amount' => 0, 'weight' => 15),
            array('enabled' => 1, 'label' => 'Try Again', 'type' => 'none', 'amount' => 0, 'weight' => 25),
            array('enabled' => 1, 'label' => '15% OFF', 'type' => 'percent', 'amount' => 15, 'weight' => 10),
            array('enabled' => 1, 'label' => '$5 OFF', 'type' => 'fixed_cart', 'amount' => 5, 'weight' => 15),
            array('enabled' => 1, 'label' => 'So Close!', 'type' => 'none', 'amount' => 0, 'weight' => 10),
            array('enabled' => 1, 'label' => '20% OFF', 'type' => 'percent', 'amount' => 20, 'weight' => 3),
            array('enabled' => 1, 'label' => '$10 OFF', 'type' => 'fixed_cart', 'amount' => 10, 'weight' => 2),
        );
    }

    public function sanitize($input)
    {
        $new_input = array();

        $new_input['enabled'] = isset($input['enabled']) ? 1 : 0;

        $allowed_types = array('percent', 'fixed_cart', 'free_shipping', 'none');
        $new_input['segments'] = array();
        for ($i = 0; $i < 8; $i++) {
            $row = isset($input['segments'][$i]) && is_array($input['segments'][$i]) ? $input['segments'][$i] : array();
            $type = isset($row['type']) && in_array($row['type'], $allowed_types, true) ? $row['type'] : 'none';

            $new_input['segments'][$i] = array(
                'enabled' => isset($row['enabled']) ? 1 : 0,
                'label'   => isset($row['label']) ? sanitize_text_field($row['label']) : '',
                'type'    => $type,
                'amount'  => isset($row['amount']) ? wc_format_decimal($row['amount']) : 0,
                'weight'  => isset($row['weight']) ? absint($row['weight']) : 0,
            );
        }

        $allowed_trigger_modes = array('delay', 'exit_intent');
        $new_input['trigger_mode'] = isset($input['trigger_mode']) && in_array($input['trigger_mode'], $allowed_trigger_modes, true)
            ? $input['trigger_mode']
            : 'delay';
        $new_input['trigger_delay_seconds'] = isset($input['trigger_delay_seconds']) ? absint($input['trigger_delay_seconds']) : 8;

        $allowed_targeting_modes = array('all', 'home', 'shop', 'specific');
        $new_input['targeting_mode'] = isset($input['targeting_mode']) && in_array($input['targeting_mode'], $allowed_targeting_modes, true)
            ? $input['targeting_mode']
            : 'all';

        $new_input['targeting_page_ids'] = array();
        if (!empty($input['targeting_page_ids'])) {
            $ids = array_map('trim', explode(',', (string) $input['targeting_page_ids']));
            $new_input['targeting_page_ids'] = array_values(array_filter(array_map('absint', $ids)));
        }

        $new_input['frequency_cap_days'] = isset($input['frequency_cap_days']) ? absint($input['frequency_cap_days']) : 7;
        $new_input['coupon_expiry_days'] = isset($input['coupon_expiry_days']) ? absint($input['coupon_expiry_days']) : 7;

        return $new_input;
    }

    public function enabled_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="checkbox" name="hdspin_options[enabled]" value="1" %s />',
            checked(1, $options['enabled'], false)
        );
    }

    public function trigger_callback()
    {
        $options = self::get_options();
        ?>
        <label>
            <input type="radio" name="hdspin_options[trigger_mode]" value="delay" <?php checked($options['trigger_mode'], 'delay'); ?> class="hdspin-trigger-mode" />
            <?php esc_html_e('After a delay of', 'hdwebmobile-spin-and-win'); ?>
        </label>
        <input type="number" min="1" name="hdspin_options[trigger_delay_seconds]" value="<?php echo esc_attr($options['trigger_delay_seconds']); ?>" class="small-text hdspin-field-delay" />
        <?php esc_html_e('seconds', 'hdwebmobile-spin-and-win'); ?>
        <br />
        <label>
            <input type="radio" name="hdspin_options[trigger_mode]" value="exit_intent" <?php checked($options['trigger_mode'], 'exit_intent'); ?> class="hdspin-trigger-mode" />
            <?php esc_html_e('On exit intent (mouse moves toward closing the tab)', 'hdwebmobile-spin-and-win'); ?>
        </label>
        <?php
    }

    public function targeting_callback()
    {
        $options = self::get_options();
        $modes = array(
            'all'      => __('All pages', 'hdwebmobile-spin-and-win'),
            'home'     => __('Homepage only', 'hdwebmobile-spin-and-win'),
            'shop'     => __('Shop & product pages', 'hdwebmobile-spin-and-win'),
            'specific' => __('Specific page IDs', 'hdwebmobile-spin-and-win'),
        );
        foreach ($modes as $value => $label) {
            printf(
                '<label style="display:block;"><input type="radio" name="hdspin_options[targeting_mode]" value="%1$s" %2$s class="hdspin-targeting-mode" /> %3$s</label>',
                esc_attr($value),
                checked($options['targeting_mode'], $value, false),
                esc_html($label)
            );
        }
        printf(
            '<p class="hdspin-field-page-ids"><input type="text" name="hdspin_options[targeting_page_ids]" value="%s" class="regular-text" placeholder="12, 34, 56" /><br /><span class="description">%s</span></p>',
            esc_attr(implode(', ', $options['targeting_page_ids'])),
            esc_html__('Comma-separated page IDs.', 'hdwebmobile-spin-and-win')
        );
    }

    public function frequency_cap_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="0" name="hdspin_options[frequency_cap_days]" value="%s" class="small-text" />',
            esc_attr($options['frequency_cap_days'])
        );
    }

    public function coupon_expiry_callback()
    {
        $options = self::get_options();
        printf(
            '<input type="number" min="1" name="hdspin_options[coupon_expiry_days]" value="%s" class="small-text" />',
            esc_attr($options['coupon_expiry_days'])
        );
    }

    public function segments_callback()
    {
        $options  = self::get_options();
        $segments = $options['segments'];
        $types    = array(
            'percent'       => __('Percent off', 'hdwebmobile-spin-and-win'),
            'fixed_cart'    => __('Fixed amount off', 'hdwebmobile-spin-and-win'),
            'free_shipping' => __('Free shipping', 'hdwebmobile-spin-and-win'),
            'none'          => __('No prize', 'hdwebmobile-spin-and-win'),
        );
        ?>
        <table class="hdspin-segments widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('On', 'hdwebmobile-spin-and-win'); ?></th>
                    <th><?php esc_html_e('Label', 'hdwebmobile-spin-and-win'); ?></th>
                    <th><?php esc_html_e('Type', 'hdwebmobile-spin-and-win'); ?></th>
                    <th><?php esc_html_e('Amount', 'hdwebmobile-spin-and-win'); ?></th>
                    <th><?php esc_html_e('Weight', 'hdwebmobile-spin-and-win'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($segments as $i => $row) : ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="hdspin_options[segments][<?php echo esc_attr($i); ?>][enabled]" value="1" <?php checked(!empty($row['enabled'])); ?> />
                        </td>
                        <td>
                            <input type="text" name="hdspin_options[segments][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($row['label']); ?>" class="regular-text" />
                        </td>
                        <td>
                            <select name="hdspin_options[segments][<?php echo esc_attr($i); ?>][type]" class="hdspin-segment-type">
                                <?php foreach ($types as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($row['type'], $value); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="hdspin_options[segments][<?php echo esc_attr($i); ?>][amount]" value="<?php echo esc_attr($row['amount']); ?>" class="small-text" />
                        </td>
                        <td>
                            <input type="number" min="0" name="hdspin_options[segments][<?php echo esc_attr($i); ?>][weight]" value="<?php echo esc_attr($row['weight']); ?>" class="small-text" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
