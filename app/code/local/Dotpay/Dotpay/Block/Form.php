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

/**
 * Block which allows render Dotpay plugin on payments checkout page
 */
class Dotpay_Dotpay_Block_Form extends Mage_Payment_Block_Form {

    /*
     * Initializes start values
     */
    protected function _construct() {
        $blockClassName = Mage::getConfig()->getBlockClassName('core/template');
        $header = new $blockClassName;
        $header->setTemplate('dotpay/dotpay/header.phtml')
               ->assign('dotpay_logo', $this->getPaymentLogoSrc());
        $this->setTemplate('dotpay/dotpay/form.phtml')
            ->setMethodLabelAfterHtml($header->renderView());
        parent::_construct();
    }

    /**
     * Returns order object with details of current order
     * @return Mage_Sales_Model_Order Order object
     */
    protected function _getOrder() {
        if ($this->getOrder()) {
            return $this->getOrder();
        }
        if ($orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        }
    }

    /**
     * Returns src of payment image if it exists
     * @return string|boolean
     */
    public function getPaymentImageSrc() {
        $pathData = array('images', 'dotpay', 'dotpay', 'Dotpay_channels.png');
        if (!file_exists(Mage::getDesign()->getFilename(implode(DS, $pathData), array('_type' => 'skin')))) {
            return false;
        }
        return $this->getSkinUrl(implode('/', $pathData));
    }

    /**
     * Returns src of payment logo if it exists
     * @return string|boolean
     */
    public function getPaymentLogoSrc() {
        $pathData = array('images', 'dotpay', 'dotpay', 'dotpay_logo.png');
        $filename = Mage::getDesign()->getFilename(implode(DS, $pathData), array('_type' => 'skin'));
        if (!file_exists($filename)) {
            return false;
        }
        return $this->getSkinUrl(implode('/', $pathData));
    }
}
