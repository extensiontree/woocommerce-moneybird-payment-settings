<?php
/*
Plugin Name: Moneybird API integration [Payment method dependent settings]
Plugin URI: https://extensiontree.com/nl/producten/woocommerce-extensies/moneybird-api-koppeling/
Version: 1.1.0
Author: ExtensionTree.com
Author URI: https://extensiontree.com
Description: Adds payment method specific settings to the Moneybird API integration plugin.
Requires at least: 4.4
Tested up to: 6.0
WC requires at least: 2.2
WC tested up to: 6.5
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active( 'woocommerce/woocommerce.php')) {

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

} // if woocommerce active
