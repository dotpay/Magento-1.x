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

class Dotpay_Dotpay_Block_Redirect extends Mage_Core_Block_Template {
    /**
     *
     * @var Dotpay_Dotpay_Model_PaymentMethod Object of main Dotpay payment model
     */
    private $methodInstance = null;

    /**
     *
     * @var int Current order id
     */
    private $orderId = null;

    /**
     * Returns object of Dotpay payment model
     * @return Dotpay_Dotpay_Model_PaymentMethod Object of main Dotpay payment model
     */
    public function getMethodInstance() {
        if($this->methodInstance === null) {
            $this->methodInstance = $this->_getOrder()->getPayment()->getMethodInstance();
        }
        return $this->methodInstance;
    }

    /**
     * Sets order id, which should be used by this block
     * @param int $orderId Order id
     * @return \Dotpay_Dotpay_Block_Redirect
     */
    public function setOrderDotId($orderId) {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Returns order object with details of current order
     * @return Mage_Sales_Model_Order Order object
     */
    protected function _getOrder() {
        if($this->orderId !== null) {
            return Mage::getModel('sales/order')->loadByIncrementId($this->orderId);
        }
        if ($this->getOrder()) {
            return $this->getOrder();
        }
        if ($orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        }
    }

    /**
     * Return current order for template
     * @return Mage_Sales_Model_Order
     */
    public function getCurrentOrder() {
        return $this->_getOrder();
    }

    /**
     * Returns url of controller, which is responsible for generating CHK signature
     * @return string
     */
    public function getSignatureUrl() {
        return Mage::getUrl('dotpay/processing/signature');
    }

    /**
     * Checks, if test mode is switch on
     * @return boolean
     */
    public function isTestMode() {
        return ($this->getMethodInstance()->getConfigData('test'));
    }


    /**
     * Checks, if "Server does not use a proxy" is switch on
     * @return boolean
     */
    public function isNonproxyMode() {
        return ($this->getMethodInstance()->getConfigData('nonproxy'));
    }


    /**
     * Checks if this account was migrated from dotpay to Przelewy24 Api
     * @return boolean
     */
    public function isMigratedtoP24() {
        return ($this->getMethodInstance()->getConfigData('dproxy_migrated'));
    }


    /**
     * Checks, if widget mode is switch on
     * @return boolean
     */
    public function isWidgetMode() {
        return ($this->getMethodInstance()->getConfigData('widget'));
    }

    /**
     * Returns id of order, which is set in this block
     * @return int
     */
    public function getOrderId() {
        return $this->_getOrder()->getRealOrderId();
    }

    /**
     * Return language id selected by client
     * @return string
     */
    public function getLangCode() {
        $langCode = explode('_', Mage::app()->getLocale()->getLocaleCode());
        return $langCode[0];
    }

    /**
     * Returns form, which can be sent to Dotpay server
     * @return \Varien_Data_Form Form with payment data
     */
    public function getForm() {
        $form = new Varien_Data_Form;
        $form->setId('dotpay_dotpay_redirection_form')
             ->setName('dotpay_dotpay_redirection_form')
             ->setAction($this->getMethodInstance()->getRedirectUrl())
             ->setMethod('post')
             ->setUseContainer(TRUE);

        foreach ($this->getMethodInstance()->getRedirectionFormData() as $name => $value) {
            $form->addField($name, 'hidden', array('name' => $name, 'value' => $value));
        }

        if($this->isWidgetMode()) {
            $form->addType('dotpay_widget','Dotpay_Dotpay_Model_Form_Widget');
            $form->addType('dotpay_agreement','Dotpay_Dotpay_Model_Form_Agreement');
            $form->addField('dpwidget', 'dotpay_widget', array());

            $bylaw = $this->getMethodInstance()->getAgreements('bylaw');
            if(trim($bylaw) == '') {
                $bylaw = 'I accept PayPro S.A. <a title="regulations of payments" target="_blank" href="https://ssl.dotpay.pl/t2/cloudfs1/magellan_media/regulations_of_payments">Regulations of Payments</a>.';
            }

            $personalData = $this->getMethodInstance()->getAgreements('personal_data');
            if(trim($personalData) == '') {
                $personalData = 'I acknowledge that in order to implement the payment process the Administrator of my personal data is PayPro S.A. (KRS 0000347935), 60-198 Poznań (Poland), Pastelowa 8, +48616006170, <a href="mailto:bok@dotpay.pl">bok@dotpay.pl</a>, see <a title="regulations of payments" target="_blank" href="https://ssl.dotpay.pl/t2/cloudfs1/magellan_media/rodo_en">the full text of the information clause</a>.';
            }

            $form->addField('bylaw_label', 'dotpay_agreement', array(
                'label' => $bylaw,
                'name' => 'bylaw',
                'value' => 1,
                'checked' => 1,
                'required' => true,
            ));

           /* 
                $form->addField('personal_data', 'hidden', array(
                    'name' => 'personal_data',
                    'value' => 1,
                ));
            */
            $form->addField('personal_data_label', 'note', array(
                'text' => '<p><em>'.$personalData.'</em></p>',
            ));

            $form->addField('submit', 'submit', array(
              'class'  => 'button',
              'value'  => Mage::helper('dotpay')->__('Pay for order'),
              'tabindex' => 1,
			        'style' => 'display: block; margin-top: 20px;'
            ));
        }

        return $form;
    }

    /**
     * Returns seller id
     * @return int
     */
    public function getSellerId() {
        return $this->getMethodInstance()->getConfigData('id');
    }
}
