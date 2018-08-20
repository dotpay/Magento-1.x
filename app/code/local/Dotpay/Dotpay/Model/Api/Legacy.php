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

class Dotpay_Dotpay_Model_Api_Legacy extends Dotpay_Dotpay_Model_Api_Api {
    /**
     * Dotpay API type
     */
    const API_VERSION = 'legacy';

    /**
     * Name of field with CHK security code for payment form
     */
    const CHK = 'CHK';

    /**
     * Status name of rejected operation
     */
    const operationRejected = 3;

    /**
     * Status name of completed operation
     */
    const operationCompleted = 2;

    /**
     * Returns data of order which should be gave to Dotpay
     * @param int $id Seller ID
     * @param Mage_Sales_Model_Order $order Object with data of processed order
     * @param int $type Type of payment flow
     * @return array
     */
    public function getPaymentData($id, $order, $type) {
        $billing = $order->getBillingAddress();
        $streetData = self::getDotStreetAndStreetN1($billing->getStreet1(),$billing->getStreet2());

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

        return array(
            'id'          => $id,
            'amount'      => round($order->getGrandTotal(), 2),
            'currency'    => $order->getOrderCurrencyCode(),
            'description' => Mage::helper('dotpay')->__('Order ID: %s', $order->getRealOrderId()),
            'url'         => str_replace('?___SID=U', '', Mage::getUrl('dotpay/processing/status')),
            'urlc'        => str_replace('?___SID=U', '', Mage::getUrl('dotpay/notification')),
            'type'        => 0,
            'control'     => $order->getRealOrderId(),
			      'firstname'   => $this->NewPersonName($firstnameFix),
			      'lastname'    => $this->NewPersonName($lastnameFix),
            'email'       => $billing->getEmail() ? $billing->getEmail() : $order->getCustomerEmail(),
            'phone'       => $billing->getTelephone(),
            'street'      => $streetData['street'],
            'street_n1'   => $streetData['street_n1'],
            'postcode'    => $billing->getPostcode(),
            'city'        => $billing->getCity(),
            'country'     => $billing->getCountryModel()->getIso2Code(),
            'api_version' => self::API_VERSION
        );
    }

    /**
     * Gets payment data from payment confirmation request and returns it
     * @return array
     */
    public function getConfirmFieldsList() {
        if($this->_confirmFields === null) {
            $this->_confirmFields = array(
			    'id' => '',
                'control' => '',
                't_id' => '',
                'amount' => '',
                'orginal_amount' => '',
                'channel' => '',
                'email' => '',
                'service' => '',
                'code' => '',
                'username' => '',
                'password' => '',
                't_status' => '',
				        'md5' => ''
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
        $fullAmount = explode(' ', $this->_confirmFields['amount']);
        return $fullAmount[0];
    }

    /**
     * Returns operation currency from payment confirmation
     * @return string
     */
    public function getOperationCurrency() {
        $fullAmount = explode(' ', $this->_confirmFields['orginal_amount']);
        return $fullAmount[1];
    }

    /**
     * Returns payment channel number from payment confirmation
     * @return int
     */
    public function getOperationChannel() {
        return $this->_confirmFields['channel'];
    }



    /**
     * Returns status value from payment confirmation
     * @return int
     */
    public function getStatus() {
        return $this->_confirmFields['t_status'];
    }

    /**
     * Returns transaction id from payment confirmation
     * @return string
     */
    public function getTransactionId() {
        return $this->_confirmFields['t_id'];
    }

    /**
     * Checks consistency of payment confirmation
     * @param string $pin Seller PIN
     * @return boolean
     */
    public function checkSignature($pin) {
        $signature =
        $pin.":".
        $this->_confirmFields['id'].":".
        $this->_confirmFields['control'].":".
        $this->_confirmFields['t_id'].":".
        $this->_confirmFields['amount'].":".
        $this->_confirmFields['email'].":".
        $this->_confirmFields['service'].":".
        $this->_confirmFields['code'].":".
        $this->_confirmFields['username'].":".
        $this->_confirmFields['password'].":".
        $this->_confirmFields['t_status'];
	return ($this->_confirmFields['md5'] == hash('md5', $signature));
    }

    /**
     * Returns CHK for request params
     * @param string $DotpayId Dotpay shop ID
     * @param string $DotpayPin Dotpay PIN
     * @param array $ParametersArray Parameters from request
     * @return string
     */
    public function generateCHK($DotpayId, $DotpayPin, $ParametersArray) {
        $ChkParametersChain =
        $DotpayId.
        (isset($ParametersArray['amount']) ?
        $ParametersArray['amount'] : null).
        (isset($ParametersArray['currency']) ?
        $ParametersArray['currency'] : null).
        (isset($ParametersArray['description']) ?
        $ParametersArray['description'] : null).
        (isset($ParametersArray['control']) ?
        $ParametersArray['control'] : null).
        $DotpayPin;
        return hash('md5', $ChkParametersChain);
    }
}
