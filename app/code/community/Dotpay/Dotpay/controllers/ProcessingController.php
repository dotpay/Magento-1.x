<?php

class Dotpay_Dotpay_ProcessingController extends Mage_Core_Controller_Front_Action {

  private function _getCheckout() {
    return Mage::getSingleton('checkout/session');
  }

  public function redirectAction() {
    $this->_getCheckout()->setDotpayQuoteId($this->_getCheckout()->getQuoteId());
    $this->getResponse()->setBody($this->getLayout()->createBlock('dotpay/redirect')->toHtml());
    $this->_getCheckout()->unsQuoteId();
    $this->_getCheckout()->unsRedirectUrl();
  }

  public function statusAction() {
    if(!$status = $this->getRequest()->getParam('status'))
      return $this->norouteAction();
    $this->_redirect('dotpay/processing/'.($this->getRequest()->getParam('status') == 'OK' ? 'success' : 'cancel'));
  }

  public function successAction() {
    $this->_getCheckout()->setQuoteId($this->_getCheckout()->getDotpayQuoteId(TRUE));
    $this->_getCheckout()->getQuote()->setIsActive(FALSE)->save();
    $this->_redirect('checkout/onepage/success');
  }

  public function cancelAction() {
    $this->_getCheckout()->setQuoteId($this->_getCheckout()->getDotpayQuoteId(TRUE));
    $this->_getCheckout()->addError(Mage::helper('dotpay')->__('The order has been canceled.'));
    $this->_redirect('checkout/cart');
  }
}