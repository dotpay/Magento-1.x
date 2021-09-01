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

class Dotpay_Dotpay_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract {
    /**
     *
     * @var string Payment method code
     */
    protected $_code = 'dotpay';
    
    /**
     *
     * @var string Block type for method form generation
     */
    protected $_formBlockType = 'dotpay_dotpay/form';
    
    /**
     *
     * @var Mage_Sales_Model_Order Object with current order data
     */
    protected $_order;
    
    /**
     *
     * @var array|boolean Cache with channels data for agreements
     */
    protected $_agreements = false;
    
    /**
     * Returns object with current order data
     * @return Mage_Sales_Model_Order
     */
    public function getOrder() {
        if(!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }
    
    /**
     * Returns url with Dotpay redirect controller
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('dotpay/processing/redirect');
    }
    
    /**
     * Returns url with Dotpay server which is a target of payment form
     * @return string
     */
    public function getRedirectUrl() {
        if (!$this->getConfigData('test') && $this->getConfigData('apiversion') == 'next') {
            return $this->getConfigData('redirect_url');
        } else if ($this->getConfigData('apiversion') == 'next') {
            return $this->getConfigData('redirect_url_test');
        } else {
            return $this->getConfigData('redirect_url_legacy');
        }
    }
    
    /**
     * Returns text of agreements
     * @param string $what Type of agreement
     * @return string
     */
    public function getAgreements($what) {
        if($this->_agreements === false) {
            $this->_agreements = $this->downloadAgreements();
        }
        
        $resultStr = '';
        if(isset($this->_agreements['forms']) && is_array($this->_agreements['forms'])) {
            foreach ($this->_agreements['forms'] as $forms) {
                if(isset($forms['fields']) && is_array($forms['fields'])) {
                    foreach ($forms['fields'] as $forms1) {
                        if($forms1['name'] == $what) {
                            $resultStr = $forms1['description_html'];
                        }
                    }
                }
            }
        }
        
        return $resultStr;
    }
    
    /**
     * Returns data with channels info from Dotpay server
     * @return array
     */
    private function downloadAgreements($amount=null) {
        $dotpayUrl = $this->getRedirectUrl();
        $paymentCurrency = $this->getOrder()->getOrderCurrencyCode();
        $dotpayId = $this->getConfigData('id');
        
		if (isset($amount)){
			$orderAmount = $amount;
		}else{
			$orderAmount = round($this->getOrder()->getGrandTotal(), 2);
		}
		
        $langCode = explode('_', Mage::app()->getLocale()->getLocaleCode());
        $dotpayLang = $langCode[0];
        
        $curlUrl = "{$dotpayUrl}payment_api/v1/channels/";
        $curlUrl .= "?currency={$paymentCurrency}";
        $curlUrl .= "&id={$dotpayId}";
        $curlUrl .= "&amount={$orderAmount}";
        $curlUrl .= "&lang={$dotpayLang}";
        
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_URL, $curlUrl);
            curl_setopt($curl, CURLOPT_REFERER, $curlUrl);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $resultJson = curl_exec($curl);
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if($curl) {
            curl_close($curl);
        }
        
        if($resultJson !== false) {
            return json_decode($resultJson, true);
        } else {
            return array();
        }
    }
	
	/**
     * Returns channel data, if payment channel is active for order data
     * @param type $id channel id
     * @return array|false
     */
    public function getChannelData($id) {
    $resultJson = $this->downloadAgreements('1000.00');
        if(false !== $resultJson) {
            if (isset($resultJson['channels']) && is_array($resultJson['channels'])) {
                foreach ($resultJson['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id']==$id) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }
	
	
    
    /**
     * Returns values of fields of Dotpay payment hidden form
     * @return array
     */
    public function getRedirectionFormData() {
        if($this->getConfigData('apiversion') == 'next') {
            $api = new Dotpay_Dotpay_Model_Api_Next();
        } else {
            $api = new Dotpay_Dotpay_Model_Api_Legacy();
        }
        $data = $api->getPaymentData($this->getConfigData('id'), $this->getOrder(), $this->getConfigData('widget')?4:0);
        if($this->getConfigData('apiversion') != 'next' || !$this->getConfigData('widget')) {
            $data[$api::CHK] = $api->generateCHK($this->getConfigData('id'), $this->getConfigData('pin'), $data);
        } else {//choose payment channel before calculation CHK
            $data[$api::CHK] = null;
        }
        return $data;
    }
}
