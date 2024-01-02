<?php
/*
Plugin Name: Moneybird API integration [Payment method dependent settings]
Plugin URI: https://extensiontree.com/nl/producten/woocommerce-extensies/moneybird-api-koppeling/
Version: 1.5.0
Author: ExtensionTree.com
Author URI: https://extensiontree.com
Description: Adds payment method specific settings to the Moneybird API integration plugin.
Requires at least: 4.4
Tested up to: 6.4
WC requires at least: 2.2
WC tested up to: 8.4
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

PucFactory::buildUpdateChecker(
	'https://github.com/extensiontree/woocommerce-moneybird-payment-settings/',
	__FILE__,
	'woocommerce-moneybird-payment-settings'
);

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (is_plugin_active( 'woocommerce-moneybird/woocommerce-moneybird.php')) {
    function insert_woocommerce_moneybird_pds_integration($integrations) {
        if (in_array('WC_MoneyBird2', $integrations)) {
            $integrations[array_search('WC_MoneyBird2', $integrations)] = 'WC_MoneyBird_PDS';
        }

        return $integrations;
    }

    add_filter('woocommerce_integrations', 'insert_woocommerce_moneybird_pds_integration', 20);

    function woocommerce_moneybird_pds_init() {
        require_once('class-wc-moneybird-pds.php');
    }

    add_action('plugins_loaded', 'woocommerce_moneybird_pds_init', 20);
}

function modify_invoice_based_on_payment_gateway($invoice, $order) {
    $gateway = $order->get_payment_method();
    if (!$gateway) {
        return $invoice;
    }

    $mb = WC()->integrations->integrations['moneybird2'];

    // Change workflow?
    if (isset($mb->settings['workflow_id_'.$gateway])) {
        $workflow_id = $mb->settings['workflow_id_'.$gateway];
        if ($workflow_id != 'auto') {
            $invoice['workflow_id'] = $workflow_id;
        } else {
            if (isset($invoice['workflow_id'])) {
                unset($invoice['workflow_id']);
            }
        }
    }

    // Change document style?
    if (isset($mb->settings['document_style_id_'.$gateway])) {
        $invoice['document_style_id'] = $mb->settings['document_style_id_'.$gateway];
    }

    // Change revenue ledger?
    if (isset($mb->settings['revenue_ledger_account_id_'.$gateway])) {
        $rev_account_id = $mb->settings['revenue_ledger_account_id_'.$gateway];

        if (!empty($rev_account_id) && ($rev_account_id != 'auto')) {
            for ($i=0; $i<count($invoice['details_attributes']); $i++) {
                $invoice['details_attributes'][$i]['ledger_account_id'] = substr($rev_account_id, 1);
            }
        }
    }

    return $invoice;
}

add_filter('woocommerce_moneybird_invoice', 'modify_invoice_based_on_payment_gateway', 10, 2);

function wcmb_pds_maybe_block_payment($register_payment, $order) {
    $order_type = is_callable(array($order, 'get_type')) ? $order->get_type() : 'shop_order';
    if ($order_type == 'shop_order_refund') {
        return $register_payment;
    }
    $gateway = $order->get_payment_method();
    if (!$gateway) {
        return $register_payment;
    }
    $wcmb = WCMB();
    if (isset($wcmb->settings['register_payment_'.$gateway])) {
        $register = $wcmb->settings['register_payment_'.$gateway];
        if ($register == 'yes') {
            $register_payment = true;
        } elseif ($register == 'no') {
            $register_payment = false;
        }
    }

    return $register_payment;
}

add_filter('woocommerce_moneybird_register_payment', 'wcmb_pds_maybe_block_payment', 10, 2);

function wcmb_pds_modify_sendmode($sendmode, $order, $saved_invoice) {
    $order_type = is_callable(array($order, 'get_type')) ? $order->get_type() : 'shop_order';
    if ($order_type == 'shop_order_refund') {
        return $sendmode;
    }
    $gateway = $order->get_payment_method();
    if (!$gateway) {
        return $sendmode;
    }
    $wcmb = WCMB();
    if (isset($wcmb->settings['send_invoice_'.$gateway])) {
        $override = $wcmb->settings['send_invoice_'.$gateway];
        if (!empty($override)) {
            $sendmode = $override;
        } 
    }

    return $sendmode;
}

add_filter('woocommerce_moneybird_sendmode', 'wcmb_pds_modify_sendmode', 10, 3);
