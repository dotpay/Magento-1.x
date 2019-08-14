<?php

/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to tech@dotpay.pl so we can send you a copy immediately.
*
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

class Dotpay_Dotpay_Model_Api_Dev extends Dotpay_Dotpay_Model_Api_Api {
    /**
     * Dotpay API type
     */
    const API_VERSION = 'dev';

    /**
     * Name of field with CHK security code for payment form
     */
    const CHK = 'chk';

    /**
     * Status name of rejected operation
     */
    const operationRejected = 'rejected';

    /**
     * Status name of completed operation
     */
    const operationCompleted = 'completed';

    /**
     * Returns data of order which should be gave to Dotpay
     * @param int $id Seller ID
     * @param Mage_Sales_Model_Order $order Object with data of processed order
     * @param int $type Type of payment flow
     * @return array
     */
    public function getPaymentData($id, $order, $type) {
        $billing = $order->getBillingAddress();
        $streetData = $this->getDotStreetAndStreetN1($billing->getStreet1(),$billing->getStreet2());

        /**
         * All available languages which are supported by Dotpay
         */
        $LANGUAGES = array('pl','en','de','it','fr','es','cz','cs','ru','hu','ro','uk','sk');

        $langCode_explode = explode('_', Mage::app()->getLocale()->getLocaleCode());

        if (!in_array($langCode_explode[0], $LANGUAGES)) {
            $langCode = 'en';
          } else{
            $langCode = $langCode_explode[0];
          }

		/**
		 	* fix: for the case when only one field given name and surname
		*/
			if(trim($billing->getLastname()) == ''){
				$NamePrepare = preg_replace('/(\s{2,})/', ' ', $billing->getFirstname());
				$namefix = explode(" ", trim($NamePrepare), 2);

				$firstnameFix = $namefix[0];
				$lastnameFix = $namefix[1];
			}else{
				$firstnameFix = $billing->getFirstname();
				$lastnameFix = $billing->getLastname();
			}

            $MagentoVersion = Mage::getVersion();
            $DPmoduleVersion = Mage::getConfig()->getNode()->modules->Dotpay_Dotpay->version;


        $data = array(
            'id'          => $id,
            'amount'      => round($order->getGrandTotal(), 2),
            'currency'    => $order->getOrderCurrencyCode(),
            'description' => Mage::helper('dotpay')->__('Order ID: %s', $order->getRealOrderId()),
            'lang'        => $langCode,
            'email'       => $billing->getEmail() ? $billing->getEmail() : $order->getCustomerEmail(),
			'firstname'   => $this->NewPersonName($firstnameFix),
			'lastname'    => $this->NewPersonName($lastnameFix),
            'control'     => $order->getRealOrderId().'|Magento v.'.$MagentoVersion.'|DP module v: '.$DPmoduleVersion ,
            'url'         => str_replace('?___SID=U', '', Mage::getUrl('dotpay/processing/status')),
            'urlc'        => str_replace('?___SID=U', '', Mage::getUrl('dotpay/notification')),
            'country'     => $billing->getCountryModel()->getIso2Code(),
            'city'        => $this->NewCity($billing->getCity()),
            'postcode'    => $this->NewPostcode($billing->getPostcode()),
            'street'      => $streetData['street'],
            'street_n1'   => $streetData['street_n1'],
            'phone'       => $this->NewPhone($billing->getTelephone()),
            'api_version' => self::API_VERSION,
            'type'        => $type
        );
        if($type == 0){
            $data['ch_lock'] = 0;
        } else {
            $data['ch_lock'] = 0;
        }

        return $data;
    }

    /**
     * Gets payment data from payment confirmation request and returns it
     * @return array
     */
    public function getConfirmFieldsList() {
        if($this->_confirmFields === null) {
            $this->_confirmFields = array(
                'id' => '',
                'operation_number' => '',
                'operation_type' => '',
                'operation_status' => '',
                'operation_amount' => '',
                'operation_currency' => '',
                'operation_withdrawal_amount' => '',
                'operation_commission_amount' => '',
                'is_completed' => '',
                'operation_original_amount' => '',
                'operation_original_currency' => '',
                'operation_datetime' => '',
                'operation_related_number' => '',
                'control' => '',
                'description' => '',
                'email' => '',
                'p_info' => '',
                'p_email' => '',			
				'credit_card_issuer_identification_number' => '',
				'credit_card_masked_number' => '',
				'credit_card_expiration_year' => '',
				'credit_card_expiration_month' => '',
				'credit_card_brand_codename' => '',
				'credit_card_brand_code' => '',
				'credit_card_unique_identifier' => '',
				'credit_card_id' => '',				
                'channel' => '',
                'channel_country' => '',
                'geoip_country' => '',
                'signature' => ''
            );
            $this->getConfirmValues();
        }
        return $this->_confirmFields;
    }

    /**
     * Returns total amount from payment confirmation
     * @return float
     */
    public function getTotalAmount() {
        return $this->_confirmFields['operation_original_amount'];
    }

    /**
     * Returns operation currency from payment confirmation
     * @return string
     */
    public function getOperationCurrency() {
        return $this->_confirmFields['operation_original_currency'];
    }

	/**
     * Returns payment channel number from payment confirmation
     * @return string
     */
    public function getOperationChannel() {
        return $this->_confirmFields['channel'];
    }


    /**
     * Returns status value from payment confirmation
     * @return string
     */
    public function getStatus() {
        return $this->_confirmFields['operation_status'];
    }

    /**
     * Returns transaction id from payment confirmation
     * @return string
     */
    public function getTransactionId() {
        return $this->_confirmFields['operation_number'];
    }

    /**
     * Checks consistency of payment confirmation
     * @param string $pin Seller PIN
     * @return boolean
     */
    public function checkSignature($pin) {
        $signature =
        $pin.
        $this->_confirmFields['id'].
        $this->_confirmFields['operation_number'].
        $this->_confirmFields['operation_type'].
        $this->_confirmFields['operation_status'].
        $this->_confirmFields['operation_amount'].
        $this->_confirmFields['operation_currency'].
        $this->_confirmFields['operation_withdrawal_amount'].
        $this->_confirmFields['operation_commission_amount'].
        $this->_confirmFields['is_completed'].
        $this->_confirmFields['operation_original_amount'].
        $this->_confirmFields['operation_original_currency'].
        $this->_confirmFields['operation_datetime'].
        $this->_confirmFields['operation_related_number'].
        $this->_confirmFields['control'].
        $this->_confirmFields['description'].
        $this->_confirmFields['email'].
        $this->_confirmFields['p_info'].
        $this->_confirmFields['p_email'].
        $this->_confirmFields['credit_card_issuer_identification_number'].
        $this->_confirmFields['credit_card_masked_number'].
        $this->_confirmFields['credit_card_expiration_year'].
        $this->_confirmFields['credit_card_expiration_month'].
        $this->_confirmFields['credit_card_brand_codename'].
        $this->_confirmFields['credit_card_brand_code'].
        $this->_confirmFields['credit_card_unique_identifier'].
        $this->_confirmFields['credit_card_id'].	
        $this->_confirmFields['channel'].
        $this->_confirmFields['channel_country'].
        $this->_confirmFields['geoip_country'];

	    return ($this->_confirmFields['signature'] == hash('sha256', $signature));
  
    }

    /**
     * Returns CHK for request params
     * @param string $DotpayId Dotpay shop ID
     * @param string $DotpayPin Dotpay PIN
     * @param array $ParametersArray Parameters from request
     * @return string
     */
    public function generateCHK($DotpayId, $DotpayPin, $ParametersArray) {
        if($ParametersArray['type'] == 4) {
            $ParametersArray['bylaw'] = 1;
            $ParametersArray['personal_data'] = 1;
        }
        $ParametersArray['id'] = $DotpayId;
        $ChkParametersChain =
        $DotpayPin.
        (isset($ParametersArray['api_version']) ? $ParametersArray['api_version'] : null).
        (isset($ParametersArray['lang']) ? $ParametersArray['lang'] : null).
        (isset($ParametersArray['id']) ? $ParametersArray['id'] : null).
        (isset($ParametersArray['pid']) ? $ParametersArray['pid'] : null).
        (isset($ParametersArray['amount']) ? $ParametersArray['amount'] : null).
        (isset($ParametersArray['currency']) ? $ParametersArray['currency'] : null).
        (isset($ParametersArray['description']) ? $ParametersArray['description'] : null).
        (isset($ParametersArray['control']) ? $ParametersArray['control'] : null).
        (isset($ParametersArray['channel']) ? $ParametersArray['channel'] : null).
        (isset($ParametersArray['credit_card_brand']) ? $ParametersArray['credit_card_brand'] : null).
        (isset($ParametersArray['ch_lock']) ? $ParametersArray['ch_lock'] : null).
        (isset($ParametersArray['channel_groups']) ? $ParametersArray['channel_groups'] : null).
        (isset($ParametersArray['onlinetransfer']) ? $ParametersArray['onlinetransfer'] : null).
        (isset($ParametersArray['url']) ? $ParametersArray['url'] : null).
        (isset($ParametersArray['type']) ? $ParametersArray['type'] : null).
        (isset($ParametersArray['buttontext']) ? $ParametersArray['buttontext'] : null).
        (isset($ParametersArray['urlc']) ? $ParametersArray['urlc'] : null).
        (isset($ParametersArray['firstname']) ? $ParametersArray['firstname'] : null).
        (isset($ParametersArray['lastname']) ? $ParametersArray['lastname'] : null).
        (isset($ParametersArray['email']) ? $ParametersArray['email'] : null).
        (isset($ParametersArray['street']) ? $ParametersArray['street'] : null).
        (isset($ParametersArray['street_n1']) ? $ParametersArray['street_n1'] : null).
        (isset($ParametersArray['street_n2']) ? $ParametersArray['street_n2'] : null).
        (isset($ParametersArray['state']) ? $ParametersArray['state'] : null).
        (isset($ParametersArray['addr3']) ? $ParametersArray['addr3'] : null).
        (isset($ParametersArray['city']) ? $ParametersArray['city'] : null).
        (isset($ParametersArray['postcode']) ? $ParametersArray['postcode'] : null).
        (isset($ParametersArray['phone']) ? $ParametersArray['phone'] : null).
        (isset($ParametersArray['country']) ? $ParametersArray['country'] : null).
        (isset($ParametersArray['code']) ? $ParametersArray['code'] : null).
        (isset($ParametersArray['p_info']) ? $ParametersArray['p_info'] : null).
        (isset($ParametersArray['p_email']) ? $ParametersArray['p_email'] : null).
        (isset($ParametersArray['n_email']) ? $ParametersArray['n_email'] : null).
        (isset($ParametersArray['expiration_date']) ? $ParametersArray['expiration_date'] : null).
        (isset($ParametersArray['deladdr']) ? $ParametersArray['deladdr'] : null).
        (isset($ParametersArray['recipient_account_number']) ? $ParametersArray['recipient_account_number'] : null).
        (isset($ParametersArray['recipient_company']) ? $ParametersArray['recipient_company'] : null).
        (isset($ParametersArray['recipient_first_name']) ? $ParametersArray['recipient_first_name'] : null).
        (isset($ParametersArray['recipient_last_name']) ? $ParametersArray['recipient_last_name'] : null).
        (isset($ParametersArray['recipient_address_street']) ? $ParametersArray['recipient_address_street'] : null).
        (isset($ParametersArray['recipient_address_building']) ? $ParametersArray['recipient_address_building'] : null).
        (isset($ParametersArray['recipient_address_apartment']) ? $ParametersArray['recipient_address_apartment'] : null).
        (isset($ParametersArray['recipient_address_postcode']) ? $ParametersArray['recipient_address_postcode'] : null).
        (isset($ParametersArray['recipient_address_city']) ? $ParametersArray['recipient_address_city'] : null).
        (isset($ParametersArray['application']) ? $ParametersArray['application'] : null).
        (isset($ParametersArray['application_version']) ? $ParametersArray['application_version'] : null).
        (isset($ParametersArray['warranty']) ? $ParametersArray['warranty'] : null).
        (isset($ParametersArray['bylaw']) ? $ParametersArray['bylaw'] : null).
        (isset($ParametersArray['personal_data']) ? $ParametersArray['personal_data'] : null).
        (isset($ParametersArray['credit_card_number']) ? $ParametersArray['credit_card_number'] : null).
        (isset($ParametersArray['credit_card_expiration_date_year']) ? $ParametersArray['credit_card_expiration_date_year'] : null).
        (isset($ParametersArray['credit_card_expiration_date_month']) ? $ParametersArray['credit_card_expiration_date_month'] : null).
        (isset($ParametersArray['credit_card_security_code']) ? $ParametersArray['credit_card_security_code'] : null).
        (isset($ParametersArray['credit_card_store']) ? $ParametersArray['credit_card_store'] : null).
        (isset($ParametersArray['credit_card_store_security_code']) ? $ParametersArray['credit_card_store_security_code'] : null).
        (isset($ParametersArray['credit_card_customer_id']) ? $ParametersArray['credit_card_customer_id'] : null).
        (isset($ParametersArray['credit_card_id']) ? $ParametersArray['credit_card_id'] : null).
        (isset($ParametersArray['blik_code']) ? $ParametersArray['blik_code'] : null).
        (isset($ParametersArray['credit_card_registration']) ? $ParametersArray['credit_card_registration'] : null).
        (isset($ParametersArray['surcharge_amount']) ? $ParametersArray['surcharge_amount'] : null).
        (isset($ParametersArray['surcharge']) ? $ParametersArray['surcharge'] : null).
        (isset($ParametersArray['surcharge']) ? $ParametersArray['surcharge'] : null).
        (isset($ParametersArray['ignore_last_payment_channel']) ? $ParametersArray['ignore_last_payment_channel'] : null).
        (isset($ParametersArray['vco_call_id']) ? $ParametersArray['vco_call_id'] : null).
        (isset($ParametersArray['vco_update_order_info']) ? $ParametersArray['vco_update_order_info'] : null).
        (isset($ParametersArray['vco_subtotal']) ? $ParametersArray['vco_subtotal'] : null).
        (isset($ParametersArray['vco_shipping_handling']) ? $ParametersArray['vco_shipping_handling'] : null).
        (isset($ParametersArray['vco_tax']) ? $ParametersArray['vco_tax'] : null).
        (isset($ParametersArray['vco_discount']) ? $ParametersArray['vco_discount'] : null).
        (isset($ParametersArray['vco_gift_wrap']) ? $ParametersArray['vco_gift_wrap'] : null).
        (isset($ParametersArray['vco_misc']) ? $ParametersArray['vco_misc'] : null).
        (isset($ParametersArray['vco_promo_code']) ? $ParametersArray['vco_promo_code'] : null).
        (isset($ParametersArray['credit_card_security_code_required']) ? $ParametersArray['credit_card_security_code_required'] : null).
        (isset($ParametersArray['credit_card_operation_type']) ? $ParametersArray['credit_card_operation_type'] : null).
        (isset($ParametersArray['credit_card_avs']) ? $ParametersArray['credit_card_avs'] : null).
        (isset($ParametersArray['credit_card_threeds']) ? $ParametersArray['credit_card_threeds'] : null).
        (isset($ParametersArray['customer']) ? $ParametersArray['customer'] : null).
        (isset($ParametersArray['gp_token']) ? $ParametersArray['gp_token'] : null);

        return hash('sha256', $ChkParametersChain);

    }
}
