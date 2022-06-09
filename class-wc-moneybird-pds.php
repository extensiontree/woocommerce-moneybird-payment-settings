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
                'title'             => 'Workflow ['.$gateway->title .']',
                'type'              => 'select',
                'options'           => $this->form_fields['workflow_id']['options']
            );
            $this->form_fields['document_style_id_'.$code] = array(
                'title'             => 'Huisstijl ['.$gateway->title .']',
                'type'              => 'select',
                'options'           => $this->form_fields['document_style_id']['options']
            );
            $this->form_fields['revenue_ledger_account_id_'.$code] = array(
                'title'             => 'Omzetcategorie ['.$gateway->title .']',
                'type'              => 'select',
                'options'           => $this->form_fields['products_ledger_account_id']['options']
            );
        }

        return parent::admin_options();
    }
}
