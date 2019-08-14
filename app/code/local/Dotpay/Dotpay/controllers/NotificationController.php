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
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

/**
 * Controller which supports notification from Dotpay server
 */

class Dotpay_Dotpay_NotificationController extends Mage_Core_Controller_Front_Action {
    /**
     * Dotpay server IP
     */
    const DOTPAY_IP = '195.150.9.37';
    
    /**
     * Dotpay office IP
     */
    const OFFICE_IP = '77.79.195.34';
    

    /**
     * Currently processed order
     *
     * @var Mage_Sales_Model_Order Object of current order
     */
    protected $_order;
    
    /**
     *
     * @var Dotpay_Dotpay_Model_Api_Api 
     */
    protected $_api;

    /**
     *
     * @var array
     */
    protected $_fields;
    
    /**
     * Default action, which is executed when Dotpay send URLC notification to shop
     */
    public function indexAction() {
        $this->displayOfficeInformation();
        $this->setApi();
        $this->checkRequest();
        $this->checkCurrency();
        $this->checkAmount();
        // $this->checkEmail();
        if(!$this->api->checkSignature($this->getOrder()->getPayment()->getMethodInstance()->getConfigData('pin'))) {
            die('MAGENTO1 - FAIL SIGNATURE');
        }
        $this->updatePaymentStatus();
    }
    
    /**
     * Updates status of payment thanks to confirmation data
     */
    private function updatePaymentStatus() {
        $payment = $this->getOrder()->getPayment();
        $api = $this->api;
        if ($this->api->getStatus() === $api::operationCompleted) {
            $this->setPaymentStatusCompleted($payment);
        } elseif ($this->api->getStatus() === $api::operationRejected) {
            $this->setPaymentStatusCanceled($payment);
        }
        die('OK');
    }

    /**
     * Sets status of payment as completed
     * @param Mage_Sales_Model_Order_Payment $payment object with payment data
     */
    private function setPaymentStatusCompleted(Mage_Sales_Model_Order_Payment $payment) {
        $order = $this->getOrder();
		
        $order->setTotalPaid($this->api->getTotalAmount())
			 /*     
				  ->sendOrderUpdateEmail(true)
				  ->setIsCustomerNotified(true)
			*/	
		   ->save();
        $lastStatus = $order->getStatus();
        if ($lastStatus !== Mage_Sales_Model_Order::STATE_COMPLETE || $lastStatus !== Mage_Sales_Model_Order::STATE_PROCESSING) {
			 
            $message = Mage::helper('dotpay')->__('The order has been paid by').' <strong>Dotpay</strong>: <br>'.
                       ' - '. Mage::helper('dotpay')->__('Amount').': <strong>'.$this->api->getTotalAmount().' '.$this->api->getOperationCurrency().'</strong><br>'.
                       ' - '. Mage::helper('dotpay')->__('Transaction number').': <strong>'.$this->api->getTransactionId().'</strong><br>'.
                       ' - '. Mage::helper('dotpay')->__('Payment channel').': <strong>'.$this->api->getOperationChannel().'</strong>';
	   
            $order->setTotalPaid($this->api->getTotalAmount())
				//->sendOrderUpdateEmail(true)
				//->setIsCustomerNotified(true)
                  ->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING, $message, true)
                  ->sendOrderUpdateEmail(true)
				  ->setIsCustomerNotified(true)
                  ->save();
            if((bool)Mage::getModel('dotpay/paymentMethod')->getConfigData('invoice') == true) {
                $this->createInvoice($order);
            }
            if (!$payment->getTransaction($this->getTransactionId())) {
                $payment->setTransactionId($this->getTransactionId())
                    ->setCurrencyCode($payment->getOrder()->getBaseCurrencyCode())
                    ->setIsTransactionApproved(true)
                    ->setIsTransactionClosed(true)
                    ->registerCaptureNotification($this->api->getTotalAmount(), true)
                    ->save();
                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false)
                    ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->api->getConfirmFieldsList())
                    ->save();
            }
        }
    }
    
    /**
     * Sets status of payment as canceled
     * @param Mage_Sales_Model_Order_Payment $payment object with payment data
     */
    private function setPaymentStatusCanceled(Mage_Sales_Model_Order_Payment $payment) {
        if (!$payment->getTransaction($this->getTransactionId())) {
            $payment->setTransactionId($this->getTransactionId())
                ->setIsTransactionApproved(true)
                ->setIsTransactionClosed(true)
                ->save();

            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false)
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->api->getConfirmFieldsList())
                ->save();
        }
    }

    /**
     * Returns object with data of current order
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder() {
        if (!$this->_order) {
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($this->api->getControl(1));
            if (!$this->_order) {
                die('MAGENTO1 - FAIL ORDER: not exist');
            }
        }
        return $this->_order;
    }
    
    /**
     * Displays basic information for workers of Dotpay customer service
     */
    protected function displayOfficeInformation() {
        if($_SERVER['REMOTE_ADDR'] == self::OFFICE_IP && $_SERVER['REQUEST_METHOD'] == 'GET') {
            die("--- Dotpay Magento1 ---"."<br>".
                "Active: ".(int)Mage::getModel('dotpay/paymentMethod')->getConfigData('test')."<br><br>".
                "--- System Info ---"."<br>".
                "Magento Version: ". Mage::getVersion() ."<br>".
                "Module Version: ".Mage::getConfig()->getNode()->modules->Dotpay_Dotpay->version."<br>".
                "PHP Version: ".PHP_VERSION."<br><br>".
                "--- Dotpay PLN ---"."<br>".
                "ID: ".Mage::getModel('dotpay/paymentMethod')->getConfigData('id')."<br>".
                "API Version: ".Mage::getModel('dotpay/paymentMethod')->getConfigData('apiversion')."<br>".
                "Test Mode: ".(int)Mage::getModel('dotpay/paymentMethod')->getConfigData('test')."<br>".
                "Email Invoice: ".(int)Mage::getModel('dotpay/paymentMethod')->getConfigData('invoice')."<br>".
                "Widget: ".(int)Mage::getModel('dotpay/paymentMethod')->getConfigData('widget')
            );
        }
    }
    
    /**
     * Sets used API class
     */
    protected function setApi() {
        if(Mage::getModel('dotpay/paymentMethod')->getConfigData('apiversion') == 'dev') {
            $this->api = new Dotpay_Dotpay_Model_Api_Dev();
        } else {
            $this->api = new Dotpay_Dotpay_Model_Api_Legacy();
        }
        $this->api->getConfirmFieldsList();
    }
    
    /**
     * Checks request, if it comes from good source and if its method is correct
     */
    protected function checkRequest() {
        $ipAddress = $this->getClientIp();
        if($ipAddress != self::DOTPAY_IP)
        {
            die("MAGENTO1 - ERROR (REMOTE ADDRESS: ".$this->getClientIp(1).")");
        }
        if($_SERVER['REQUEST_METHOD'] != 'POST') {
            die("MAGENTO1 - ERROR (METHOD <> POST)");
        }
    }
    
    /**
     * Checks, if currency from order is the same as from notification
     */
    protected function checkCurrency() {
        $orderCurrency = $this->getOrder()->getOrderCurrencyCode();
        $receivedCurrency = $this->api->getOperationCurrency();
        if ($orderCurrency !== $receivedCurrency) {
            die('MAGENTO1 - FAIL CURRENCY ('.$orderCurrency.' <> '.$receivedCurrency.')');
        }
    }
    
    /**
     * Checks, if amount from order is the same as from notification
     */
    protected function checkAmount() {
        $amount = round($this->getOrder()->getGrandTotal(), 2);
        $amountOrder = sprintf("%01.2f", $amount);
        if ($amountOrder !== $this->api->getTotalAmount()) {
            die('MAGENTO1 - FAIL AMOUNT');
        }
    }
    
    /**
     * Checks, if email of customer from order is the same as from notification
     */
    protected function checkEmail() {
        $emailBilling = $this->getOrder()->getBillingAddress()->getEmail();
        if ($emailBilling !== $this->api->getEmail()) {
            die('MAGENTO1 - FAIL EMAIL');
        }
    }
    
    /**
     * Returns id of payment transaction
     * @return string
     */
    private function getTransactionId() {
        return $this->api->getTransactionId() ? $this->api->getTransactionId() : microtime(true);
    }

    /**
     * Return ip address from is the confirmation request
     * @return string
     */

    public function getClientIp($list_ip = null)
    {
        $ipaddress = '';
        // CloudFlare support
        if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
            // Validate IP address (IPv4/IPv6)
            if (filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
                $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
                return $ipaddress;
            }
        }
        if (array_key_exists('X-Forwarded-For', $_SERVER)) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['X-Forwarded-For'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ipaddress = $ips[0];
            } else {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($list_ip) && $list_ip != null) {
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                return  $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
                return $_SERVER["HTTP_CF_CONNECTING_IP"];
            } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                return $_SERVER["REMOTE_ADDR"];
            }
        } else {
            return $ipaddress;
        }
    }


    protected function createInvoice($order) {
        if (!$order->canInvoice()) {
            return;
        }
        $invoice = $order->prepareInvoice();
        if (!$invoice->getTotalQty()) {
            return;
        }
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
    	$invoice->sendEmail(true, Mage::helper('dotpay')->__('The invoice has been created.'));
    	$invoice->setEmailSent(true);
        $invoice->register();
        $invoice->save();
    }
}
