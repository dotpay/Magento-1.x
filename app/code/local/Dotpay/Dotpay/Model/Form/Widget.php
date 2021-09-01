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
*/

/**
 * Model for Dotpay widget field of payment form
 */

class Dotpay_Dotpay_Model_Form_Widget extends Varien_Data_Form_Element_Abstract {
    /**
     * Initialises object which represents Dotpay widget form field
     * @param array $attributes Attributes of form field
     */
    public function __construct($attributes=array()) {
        parent::__construct($attributes);
        $this->setType('dotpay_widget');
    }
    
    /**
     * Returns rendered HTML for element's label
     * @param string $idSuffix Suffix id
     * @return string
     */
    public function getLabelHtml($idSuffix = '') {
        return '';
    }
    
    /**
     * Returns rendered HTML for form element
     * @return string
     */
    public function getElementHtml() {
        return '<p class="dotpay-widget-container"></p>';
    }
}
