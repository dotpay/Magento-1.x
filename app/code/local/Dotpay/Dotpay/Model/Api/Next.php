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
*  @copyright PayPro S.A.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

class Dotpay_Dotpay_Model_Api_Next extends Dotpay_Dotpay_Model_Api_Api {
    /**
     * Dotpay API type
     */
    const API_VERSION = 'next';

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
        $LANGUAGES = array(
                            'pl',
                            'en',
                            'de',
                            'it',
                            'fr',
                            'es',
                            'cz',
                            'cs',
                            'ru',
                            'hu',
                            'ro',
                            'uk',
                            'lt',
                            'lv'
                        );

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
            $is_dp_proxy = (int)Mage::getModel('dotpay/paymentMethod')->getConfigData('dproxy_migrated');


        $data = array(
            'id'          => (string)$id,
            'amount'      => (string)round($order->getGrandTotal(), 2),
            'currency'    => (string)$order->getOrderCurrencyCode(),
            'description' => (string)Mage::helper('dotpay')->__('Order ID: %s', $order->getRealOrderId()),
            'lang'        => (string)$langCode,
            'email'       => (string)$billing->getEmail() ? $billing->getEmail() : $order->getCustomerEmail(),
			'firstname'   => (string)$this->NewPersonName($firstnameFix),
			'lastname'    => (string)$this->NewPersonName($lastnameFix),
            'control'     => (string)$order->getRealOrderId().'|Magento v.'.$MagentoVersion.'|DP module v: '.$DPmoduleVersion.' dp-p24 migrated: '.$is_dp_proxy ,
            'url'         => (string)str_replace('?___SID=U', '', Mage::getUrl('dotpay/processing/status')),
            'urlc'        => (string)str_replace('?___SID=U', '', Mage::getUrl('dotpay/notification')),
            'api_version' => (string)self::API_VERSION,
            'type'        => (string)$type,
            'ignore_last_payment_channel' => '1'  
        );
        
        if($type == 0){
          //  $data['bylaw'] = '0';
            $data['personal_data'] = '0';
        } else {
         //   $data['bylaw'] = '1';
            $data['personal_data'] = '1';
        }

        if( null != trim($this->NewPhone($billing->getTelephone())) )
        {
            $data["phone"] = (string)$this->NewPhone($billing->getTelephone());
        }
        if( null != trim($streetData['street_n1']) )
        {
            $data["street_n1"] = (string)$streetData['street_n1'];
        }
        if( null != trim($streetData['street']) )
        {
            $data["street"] = (string)$streetData['street'];
        }
        if( null != trim($this->NewPostcode($billing->getPostcode())) )
        {
            $data["postcode"] = (string)$this->NewPostcode($billing->getPostcode());
        }
        if( null != trim($this->NewCity($billing->getCity())) )
        {
            $data["city"] = (string)$this->NewCity($billing->getCity());
        }
        if( null != trim($billing->getCountryModel()->getIso2Code()) )
        {
            $data["country"] = (string)$billing->getCountryModel()->getIso2Code();
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
				'payer_bank_account_name' => '',
				'payer_bank_account' => '',
				'payer_transfer_title' => '',
				'blik_voucher_pin' => '',
				'blik_voucher_amount' => '',
				'blik_voucher_amount_used' => '',
				'channel_reference_id' => '',
				'operation_seller_code' => '',
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
        $this->_confirmFields['geoip_country'].	
        $this->_confirmFields['payer_bank_account_name'].
        $this->_confirmFields['payer_bank_account'].
        $this->_confirmFields['payer_transfer_title'].
        $this->_confirmFields['blik_voucher_pin'].
        $this->_confirmFields['blik_voucher_amount'].
        $this->_confirmFields['blik_voucher_amount_used'].
        $this->_confirmFields['channel_reference_id'].
        $this->_confirmFields['operation_seller_code'];

	    return ($this->_confirmFields['signature'] == hash('sha256', $signature));
  
    }

    /**
     * Returns CHK for request params
     * @param string $DotpayPin Dotpay PIN
     * @param array $ParametersArray Parameters from request
     * @return string
     */
    
    
    ## function: counts the checksum from the defined array of all parameters

    public function generateCHK($tmp=null, $DotpayPin, $ParametersArray)
    {
        if($ParametersArray['type'] == '4') {
            $ParametersArray['bylaw'] = '1';
            //$ParametersArray['personal_data'] = '1';
        }

            //sorting the parameter list
            ksort($ParametersArray);
            
            // Display the semicolon separated list
            $paramList = implode(';', array_keys($ParametersArray));
            
            //adding the parameter 'paramList' with sorted list of parameters to the array
            $ParametersArray['paramsList'] = $paramList;
            
            //re-sorting the parameter list
            ksort($ParametersArray);
            
            //json encoding  
            $json = json_encode($ParametersArray, JSON_UNESCAPED_SLASHES);
    
        return hash_hmac('sha256', $json, $DotpayPin, false);
       
    }



}
