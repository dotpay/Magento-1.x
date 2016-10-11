<?php

class Dotpay_Dotpay_NotificationController extends Mage_Core_Controller_Front_Action
{
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSING_REALIZATION_WAITING = 'processing_realization_waiting';
    const STATUS_PROCESSING_REALIZATION = 'processing_realization';

    /**
     * Currently processed order
     *
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     *
     * @var array
     */
    protected $_fields = array(
        'id' => '',
        'operation_number' => '',
        'operation_type' => '',
        'operation_status' => '',
        'operation_amount' => '',
        'operation_currency' => '',
        'operation_withdrawal_amount' => '',
        'operation_commission_amount' => '',
        'operation_original_amount' => '',
        'operation_original_currency' => '',
        'operation_datetime' => '',
        'operation_related_number' => '',
        'control' => '',
        'description' => '',
        'email' => '',
        'p_info' => '',
        'p_email' => '',
        'channel' => '',
        'channel_country' => '',
        'geoip_country' => '',
        'signature' => ''
    );


    public function indexAction()
    {
        $this->getPostParams();
        $this->getOrder();
        $this->checkCurrency();
        $this->checkAmount();
        $this->checkEmail();
        $this->checkSignature();
        $this->updatePaymentStatus();
    }

    private function updatePaymentStatus()
    {
        $payment = $this->_order->getPayment();

        if ($this->_fields['operation_status'] === self::STATUS_COMPLETED) {
            $this->setPaymentStatusCompleted($payment);
        } elseif ($this->_fields['operation_status'] === self::STATUS_REJECTED) {
            $this->setPaymentStatusCanceled($payment);
        }
        die('OK');
    }

    private function setPaymentStatusCompleted(Mage_Sales_Model_Order_Payment $payment)
    {
        if (!$payment->getTransaction($this->getTransactionId())) {
            $payment->setTransactionId($this->getTransactionId())
                ->setCurrencyCode($payment->getOrder()->getBaseCurrencyCode())
                ->setIsTransactionApproved(true)
                ->setIsTransactionClosed(true)
                ->registerCaptureNotification($this->_fields['operation_amount'], true)
                ->save();

            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false)
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->_fields)
                ->save();
        }

        $lastStatus = $this->_order->getStatus();
        if ($lastStatus !== Mage_Sales_Model_Order::STATE_COMPLETE || $lastStatus !== Mage_Sales_Model_Order::STATE_PROCESSING) {
            $this->_order
                ->sendOrderUpdateEmail(true)
                ->save();
        }
    }

    private function setPaymentStatusCanceled(Mage_Sales_Model_Order_Payment $payment)
    {
        if (!$payment->getTransaction($this->getTransactionId())) {
            $payment->setTransactionId($this->getTransactionId())
                ->setIsTransactionApproved(true)
                ->setIsTransactionClosed(true)
                ->save();

            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false)
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $this->_fields)
                ->save();
        }
    }

    protected function getPostParams()
    {
        foreach ($this->_fields as $k => &$v) {
            $value = $this->getRequest()->getPost($k);
            if ($value !== '') {
                $v = $value;
            }
        }
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($this->_fields['control']);
        if (!$this->_order) {
            die('FAIL ORDER: not exist');
        }
        return $this->_order;
    }

    protected function checkCurrency()
    {
        $currencyOrder = $this->_order->getOrderCurrencyCode();
        $currencyResponse = $this->_fields['operation_original_currency'];
        if ($currencyOrder !== $currencyResponse) {
            die('FAIL CURRENCY');
        }
    }

    protected function checkAmount()
    {
        $amount = round($this->_order->getGrandTotal(), 2);
        $amountOrder = sprintf("%01.2f", $amount);
        $amountResponse = $this->_fields['operation_original_amount'];
        if ($amountOrder !== $amountResponse) {
            die('FAIL AMOUNT');
        }
    }

    protected function checkEmail()
    {
        $emailBilling = $this->_order->getBillingAddress()->getEmail();
        $emailResponse = $this->_fields['email'];
        if ($emailBilling !== $emailResponse) {
            die('FAIL EMAIL');
        }
    }

    protected function checkSignature()
    {
        $hashDotpay = $this->_fields['signature'];
        $hashCalculate = $this->calculateSignature();
        if ($hashDotpay !== $hashCalculate) {
            die('FAIL SIGNATURE');
        }
    }

    protected function calculateSignature()
    {
        $string = '';
        $string .= $this->_order->getPayment()->getMethodInstance()->getConfigData('pin');
        foreach ($this->_fields as $k => $v) {
            if ($k != "signature") {
                $string .= $v;
            }
        }
        return hash('sha256', $string);
    }

    private function getTransactionId()
    {
        return $this->_fields['operation_number'] ? $this->_fields['operation_number'] : microtime(true);
    }
}
