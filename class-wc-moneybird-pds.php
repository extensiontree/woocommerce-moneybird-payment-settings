<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_MoneyBird_PDS extends WC_MoneyBird2 {

    function init_form_fields() {
        parent::init_form_fields();

        if (!isset($this->form_fields['workflow_id'])) {
            return;
        }

        // Add extra form fields for payment-specific styles & workflows.
        $this->form_fields['pds'] = array(
            'title'       => 'Betaalmethode-afhankelijke instellingen',
            'type'        => 'title',
            'description' => '',
        );
        $gateways = (array) get_option('woocommerce_gateway_order');
        if ($gateways) {
            foreach ($gateways as $code => $order) {
                $this->form_fields['workflow_id_'.$code] = array('type' => 'hidden');
                $this->form_fields['document_style_id_'.$code] = array('type' => 'hidden');
                $this->form_fields['revenue_ledger_account_id_'.$code] = array('type' => 'hidden');
                $this->form_fields['register_payment_'.$code] = array('type' => 'hidden');
                $this->form_fields['send_invoice_'.$code] = array('type' => 'hidden');
            }
        }
    }

    function admin_options() {
        // Add settings for active payment gateways.
        // We add the settings here instead of in $this->init_form_fields()
        // since the external payment gateways are not yet loaded when
        // $this->init_form_fields() is called.

        $payment_gateways = WC()->payment_gateways->payment_gateways();

        foreach ($payment_gateways as $code => $gateway) {
            $this->form_fields['workflow_id_'.$code] = array(
                'title'             => $gateway->title . ' - Workflow',
                'type'              => 'select',
                'options'           => $this->form_fields['workflow_id']['options']
            );
            $this->form_fields['document_style_id_'.$code] = array(
                'title'             => $gateway->title . ' - Huisstijl',
                'type'              => 'select',
                'options'           => $this->form_fields['document_style_id']['options']
            );
            $this->form_fields['revenue_ledger_account_id_'.$code] = array(
                'title'             => $gateway->title . ' - Omzetcategorie',
                'type'              => 'select',
                'options'           => $this->form_fields['products_ledger_account_id']['options']
            );
            $this->form_fields['register_payment_'.$code] = array(
                'title'             => $gateway->title . ' - ' . __('Register payments', 'woocommerce_moneybird'),
                'label'             => __('Automatically mark Moneybird invoice as paid if the WooCommerce order is paid.', 'woocommerce_moneybird'),
                'type'              => 'select',
                'options'           => array('' => __('Follow global setting'), 'no' => __('No'), 'yes' => __('Yes')),
                'default'           => ''
            );
            $this->form_fields['send_invoice_'.$code] = array(
                'title'             => $gateway->title . ' - ' . $this->form_fields['send_invoice']['title'],
                'description'       => $this->form_fields['send_invoice']['description'],
                'desc_tip'          => true,
                'type'              => 'select',
                'options'           => array_merge(array('' => __('Follow global setting')), $this->form_fields['send_invoice']['options']),
                'default'           => ''
            );
        }

        return parent::admin_options();
    }
}
