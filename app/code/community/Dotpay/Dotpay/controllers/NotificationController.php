<?php

class Dotpay_Dotpay_NotificationController extends Mage_Core_Controller_Front_Action {

  private function isDataIntegrity($pin) {

    $sellerAccount = new Dotpay_Model_SellerAccount;
    $sellerAccount->
      setId($this->getRequest()->getPost('id'))->
      setPin($pin);

    $customer = new Dotpay_Model_Customer;
    $customer->
      setEmail($this->getRequest()->getPost('email'));

    $transaction = new Dotpay_Model_Transaction;
    $transaction->
      setAmount($this->getRequest()->getPost('amount'))->
      setDescription($this->getRequest()->getPost('description'))->
      setControl($this->getRequest()->getPost('control'))->
      setCode($this->getRequest()->getPost('code'))->
      setSellerAccount($sellerAccount)->
      setCustomer($customer);

    $transactionConfirmation = new Dotpay_Model_TransactionConfirmation;
    $transactionConfirmation->
      setStatus($this->getRequest()->getPost('status'))->
      setTId($this->getRequest()->getPost('t_id'))->
      setOriginalAmount($this->getRequest()->getPost('original_amount'))->
      setTStatus($this->getRequest()->getPost('t_status'))->
      setService($this->getRequest()->getPost('service'))->
      setUsername($this->getRequest()->getPost('username'))->
      setPassword($this->getRequest()->getPost('password'))->
      setTransaction($transaction);

    if ($this->getRequest()->getPost('md5') == $transactionConfirmation->computeMd5())
      return TRUE;

    return FALSE;
  }

  public function indexAction() {

    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($this->getRequest()->getPost('control'));
    if (!$order->getId())
      die('ERR');

    if (!$this->isDataIntegrity($order->getPayment()->getMethodInstance()->getConfigData('pin')))
      die('ERR');

    list($amount, $currency) = explode(' ', $this->getRequest()->getPost('orginal_amount'));
    if (!($order->getOrderCurrencyCode() == $currency && round($order->getGrandTotal(), 2) == $amount))
      die('ERR');

    if ($this->getRequest()->getPost('t_status') == 2) {
      $order->addStatusToHistory(
        Mage_Sales_Model_Order::STATE_PROCESSING,
        Mage::helper('dotpay')->__('The payment has been accepted.'));
      try {
        if (version_compare(Mage::getVersion(), '1.9.1', '>=')){
          $order->queueNewOrderEmail();
        } else {
          $order->sendNewOrderEmail();
        }
      } catch (Exception $e) {
        Mage::logException($e);
      }
    } elseif ($this->getRequest()->getPost('t_status') == 3) {
      $order->cancel();
      $order->addStatusToHistory(
        Mage_Sales_Model_Order::STATE_CANCELED,
        Mage::helper('dotpay')->__('The order has been canceled.'));
    }

    $order->save();

    die('OK');
  }
}
