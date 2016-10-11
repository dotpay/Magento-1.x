<?php

class Dotpay_Dotpay_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract {

  protected $_code          = 'dotpay';
  protected $_formBlockType = 'dotpay/form';

  protected $_order;

  public function getOrder() {
    if (!$this->_order)
      $this->_order = $this->getInfoInstance()->getOrder();
    return $this->_order;
  }

  public function getOrderPlaceRedirectUrl() {
    return Mage::getUrl('dotpay/processing/redirect');
  }

	public function getRedirectUrl() {
		if ($this->getConfigData('test')) 
			return $this->getConfigData('redirect_url_test');
		else 
			return $this->getConfigData('redirect_url');
	}  
  
  public function getRedirectionFormData() {

    $billing = $this->getOrder()->getBillingAddress();

    return array(
      'id'          => $this->getConfigData('id'),
      'amount'      => round($this->getOrder()->getGrandTotal(), 2),
      'currency'    => $this->getOrder()->getOrderCurrencyCode(),
      'description' => Mage::helper('dotpay')->__('Order ID: %s - %s', $this->getOrder()->getRealOrderId(), Mage::app()->getWebsite()->getName()),
      'lang'        => Mage::app()->getLocale()->getLocaleCode(),
      'email'       => $billing->getEmail() ? $billing->getEmail() : $this->getOrder()->getCustomerEmail(),
      'firstname'   => $billing->getFirstname(),
      'lastname'    => $billing->getLastname(),
      'control'     => $this->getOrder()->getRealOrderId(),
      'URL'         => Mage::getUrl('dotpay/processing/status'),
      'URLC'        => Mage::getUrl('dotpay/notification'),
      'country'     => $billing->getCountryModel()->getIso2Code(),
      'city'        => $billing->getCity(),
      'postcode'    => $billing->getPostcode(),
      'street'      => $billing->getStreet(-1),
      'phone'       => $billing->getTelephone(),
      'type'        => 0,
      'api_version' => 'legacy'
	  );
  }
}
