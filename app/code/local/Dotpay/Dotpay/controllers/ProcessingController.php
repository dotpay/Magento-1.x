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

class Dotpay_Dotpay_ProcessingController extends Mage_Core_Controller_Front_Action {
    /**
     * Returns object with data of current checkout
     * @return Mage_Checkout_Model_Session
     */
    private function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Action, which is executed when Dotpay payment form is displayed
     */
    public function redirectAction() {
        $orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        // $order->sendNewOrderEmail();
		 if ($this->isRefreshProcessingPayment($order) === true && $order->getEmailSent() !== '1') {
                $order->sendNewOrderEmail();
			}else{		
			 // Mage::getSingleton('core/session')->addNotice('Faulty redirect. Notification has not been sent');			
			}
		
        $order->save();
        $this->_getCheckout()->setDotpayQuoteId($this->_getCheckout()->getQuoteId());
        
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('dotpay')->__('Payment is under processing...'));
        $this->_getCheckout()->unsQuoteId();
        $this->_getCheckout()->unsRedirectUrl();
        $this->renderLayout();
    }
    
    /**
     * Action, which is executed, when customer want to retry his payment
     */
    public function retryAction() {
        $orderIncrementId = $this->getRequest()->getParam('orderid');
        $protectCode = $this->getRequest()->getParam('code');
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        if (md5($order->getCustomerEmail()) != $protectCode) {
            die('CORRUPTED DATA');
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('dotpay')->__('Payment is under processing...'));
        $this->getLayout()->getBlock('dotpay.redirect')->setOrderDotId($orderIncrementId);
        $this->renderLayout();
    }
    
    /**
     * Action, which is executed during coming back to shop site after making a payment
     * @return null
     */
    public function statusAction() {
        if(!$this->getRequest()->getParam('status') && !$this->getRequest()->getParam('error_code')) {
            return $this->norouteAction();
        }
        $resultAction = $this->getResultInBackPage().'Action';
        $this->$resultAction();
    }
    
    /**
     * Executed, when payment status parameter is OK
     */
    protected function successAction() {
        $this->_getCheckout()->setQuoteId($this->_getCheckout()->getDotpayQuoteId(TRUE));
        $this->_getCheckout()->getQuote()->setIsActive(FALSE)->save();
        $this->_redirect('checkout/onepage/success');
    }
    
    /**
     * Executed, when any error occured and that was indicated by error-code parameter
     */
    public function cancelAction() {
        $this->_getCheckout()->setQuoteId($this->_getCheckout()->getDotpayQuoteId(TRUE));
        $orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $retryUrl = Mage::getUrl('dotpay/processing/retry', array(
            'orderid' => $orderIncrementId,
            'code' => md5($order->getCustomerEmail())
        ));
        $this->_getCheckout()->addError(Mage::helper('dotpay')->__('The order has not been paid. You can try again by <a href="%s">clicking here</a>.', $retryUrl));
        $this->_redirect('checkout/cart');
    }
    
    /**
     * Action, which is executed, when payment form ask for signature for current order
     * @return Mage_Core_Controller_Response_Http
     */
    public function signatureAction() {
        if($this->getRequest()->getParam('order') === null ) {
            die('BAD ORDER');
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getRequest()->getParam('order'));
        $model = $order->getPayment()->getMethodInstance();
        if($model->getConfigData('apiversion') == 'next') {
            $api = new Dotpay_Dotpay_Model_Api_Next();
        } else {
            $api = new Dotpay_Dotpay_Model_Api_Legacy();
        }
        $data = $api->getPaymentData($model->getConfigData('id'), $order, $model->getConfigData('widget')?4:0);
        $data['channel'] = $this->getRequest()->getParam('channel');
        $signature = $api->generateCHK($model->getConfigData('id'), $model->getConfigData('pin'), $data);
        return $this->getResponse()->setHeader('Content-Type', 'text/plain')->setBody($signature);
    }
    
    /**
     * Returns string with result of payment for correct redirecting after payment
     * @return string
     */
    private function getResultInBackPage() {
        if($this->getRequest()->getParam('status') == 'OK' && !$this->getRequest()->getParam('error_code')) {
            return 'success';
        } else {
            return 'cancel';
        }
    }
	
		 /**
     * Check if the site payment processing has been refreshed
     * @param Mage_Sales_Model_Order $order
     */
    protected function isRefreshProcessingPayment($order)
    {
		
       if(!isset($order) || $order->getPayment()->getMethodInstance() === null ) {
			return false;
            // die('BAD ORDER');
        }
		$method = $order->getPayment()->getMethodInstance();
        
        if ($method && $method->getOrderPlaceRedirectUrl() != '' && $method->getOrderPlaceRedirectUrl() !== false)
			{
				return true;
			}else{
				return false;
			}
    }
	
	
	
}
