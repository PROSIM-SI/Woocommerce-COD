<?php
/*
Plugin Name: Dobierka - Extra poplatok
Plugin URI: https://prosimsi.sk/
Description: Woocommerce - Pridáva extra poplatok pre dobierku
Version: 1.3
Author: Prosimsi
Author URI: https://prosimsi.sk/kontakt
Text Domain: dobierka-extra-poplatky-woocommerce
WC requires at least: 3.0.0
WC tested up to: 5.7.1
PHP version: 7.4 - 8.2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Check for WooCommerce dependency
add_action('admin_init', 'check_woocommerce_dependency');
function check_woocommerce_dependency() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and active.');
    }
}

// Add settings fields in the Payment Gateways section for COD
add_filter('woocommerce_get_settings_checkout', 'extra_cod_fee_settings');
function extra_cod_fee_settings($settings) {
    if (isset($_GET['section']) && sanitize_text_field($_GET['section']) === 'cod') {
        $settings[] = array(
            'title' => __('Extra poplatok za dobierku', 'woocommerce'),
            'type' => 'title',
            'desc' => __('Definujte extra poplatok za spôsob platby na dobierku (COD).', 'woocommerce'),
            'id' => 'extra_cod_fee_settings'
        );

        $settings[] = array(
            'title' => __('Názov poplatku', 'woocommerce'),
            'desc' => __('Zadajte štítok pre extra poplatok zobrazený pri pokladni a košíku.', 'woocommerce'),
            'id' => 'extra_cod_fee_label',
            'type' => 'text',
            'default' => __('Poplatok za dobierku', 'woocommerce')
        );

        $settings[] = array(
            'title' => __('Výška poplatku', 'woocommerce'),
            'desc' => __('Zadajte sumu za príplatok. Akékoľvek kladné číslo vrátane desatinných miest.', 'woocommerce'),
            'id' => 'extra_cod_fee_amount',
            'type' => 'text',
            'default' => '1.50',
            'css' => 'width:100px;',
            'desc_tip' => true
        );

        $settings[] = array('type' => 'sectionend', 'id' => 'extra_cod_fee_settings');
    }

    return $settings;
}

// Add the extra fee to the order during checkout
add_action('woocommerce_cart_calculate_fees', 'apply_extra_cod_fee', 20, 1);
function apply_extra_cod_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $chosen_gateway = WC()->session->get('chosen_payment_method');
    $fee_label = get_option('extra_cod_fee_label');
    $fee_amount = get_option('extra_cod_fee_amount');

    if ($chosen_gateway === 'cod' && is_numeric($fee_amount) && floatval($fee_amount) >= 0.1) {
        $cart->add_fee(esc_html($fee_label), floatval($fee_amount), false);
    }
}

// Ensure the fee is updated when payment method changes
add_action('woocommerce_review_order_before_payment', 'update_fees_on_payment_method_change');
function update_fees_on_payment_method_change() {
    WC()->cart->calculate_fees();
}
