<?php

class Dotpay_Dotpay_Block_Form extends Mage_Payment_Block_Form {

  protected function _construct() {
    parent::_construct();
    $this->setTemplate('dotpay/dotpay/form.phtml');
  }

  public function getPaymentImageSrc() {
    $pathData = array('images', 'dotpay', 'dotpay', 'logotypy_kanalow.png');
    if (!file_exists(Mage::getDesign()->getFilename(implode(DS, $pathData), array('_type' => 'skin'))))
      return FALSE;
    return $this->getSkinUrl(implode('/', $pathData));
  }
}