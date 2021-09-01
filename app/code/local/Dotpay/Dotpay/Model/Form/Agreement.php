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

/**
 * Model for agreement field of payment form
 */

class Dotpay_Dotpay_Model_Form_Agreement extends Varien_Data_Form_Element_Checkbox {
    /**
     * Returns array with attributes which are had by agreement form field
     * @return array
     */
    public function getHtmlAttributes() {
        $attributes = parent::getHtmlAttributes();
        $attributes[] = 'required';
        return $attributes;
    }
    
    /**
     * Returns full HTML code of form element
     * @return string
     */
    public function getDefaultHtml() {
        $html = $this->getData('default_html');
        if (is_null($html)) {
            $html = ( $this->getNoSpan() === true ) ? '' : '<span class="field-row">'."\n";
            $html.= '<label for="'.$this->getHtmlId() . $idSuffix . '">';
            $html.= $this->getElementHtml();
            $html.= $this->getLabel();
            $html.= ( $this->getRequired() ? ' <span class="required">*</span>' : '' );
            $html.= '</label>';
            $html.= ( $this->getNoSpan() === true ) ? '' : '</span>'."\n";
        }
        return $html;
    }
}
