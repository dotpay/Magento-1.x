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
 * Abstract Dotpay API class
 */

abstract class Dotpay_Dotpay_Model_Api_Api {
    /**
     *
     * @var arrya|null Contains fields, which are given during confirmation by Dotpay URLC
     */
    protected $_confirmFields = null;

    /**
     * Returns data of order which should be gave to Dotpay
     */
    abstract public function getPaymentData($id, $order, $type);

    /**
     * Gets payment data from payment confirmation request and returns it
     */
    abstract public function getConfirmFieldsList();

    /**
     * Returns total amount from payment confirmation
     */
    abstract public function getTotalAmount();

    /**
     * Returns operation currency from payment confirmation
     */
    abstract public function getOperationCurrency();

	/**
     * Returns payment channel number from payment confirmation
     */
    abstract public function getOperationChannel();


    /**
     * Returns status value from payment confirmation
     */
    abstract public function getStatus();

    /**
     * Returns transaction id from payment confirmation
     */
    abstract public function getTransactionId();

    /**
     * Checks consistency of payment confirmation
     */
    abstract public function checkSignature($pin);

    /**
     * Returns CHK for request params
     */
    abstract public function generateCHK($DotpayId, $DotpayPin, $ParametersArray);

    /**
     * Returns customer email address from payment confirmation
     * @return string
     */
    public function getEmail() {
        return $this->_confirmFields['email'];
    }

    /**
     * Returns control field from payment confirmation
     * @return string
     */
    public function getControl() {
        return $this->_confirmFields['control'];
    }


    /**
    	 * checks and crops the size of a string
    	 * the $special parameter means an estimate of how many urlencode characters can be used in a given field
    	 * e.q. 'ż' (1 char) -> '%C5%BC' (6 chars)
    	 * replacing removing double or more special characters that appear side by side by space from: firstname, lastname, city, street, p_info...
    	 */
    	public function encoded_substrParams($string, $from, $to, $special=0)
    		{
    			$string2 = preg_replace('/(\s{2,}|\.{2,}|@{2,}|\-{2,}|\/{3,} | \'{2,}|\"{2,}|_{2,})/', ' ', $string);
    			$s = html_entity_decode($string2, ENT_QUOTES, 'UTF-8');
    			$sub = mb_substr($s, $from, $to,'UTF-8');
    			$sum = strlen(urlencode($sub));
    			if($sum  > $to)
    				{
    					$newsize = $to - $special;
    					$sub = mb_substr($s, $from, $newsize,'UTF-8');
    				}
    			return trim($sub);
    		}



    /**
     * Returns array with street and building number
     * @return array
     */
    public function getDotStreetAndStreetN1($street, $street2) {

              $buildingNumber =  $street2;
              preg_match("/\s[\p{L}0-9\s\-_\/]{1,15}$/u", $street, $matches);

                  if(count($matches)>0 && trim($buildingNumber) == '') {

                    $street2 = str_replace($matches[0], '', $street);
                    $street = preg_replace('/[^\p{L}0-9\.\s\-\/_,\n\r]/u','',$street2);

                    $buildingNumber = preg_replace('/[^\p{L}0-9\s\-_\/\n\r]/u','',trim($matches[0]));

                  } else{
                     $street = trim(preg_replace('/[^\p{L}0-9\.\s\-\/_,\n\r]/u','',$street));
                  }

        return array(
            'street' => $this->encoded_substrParams($street,0,100,50),
            'street_n1' => $this->encoded_substrParams($buildingNumber,0,30,24)
        );
    }

    /**
     * prepare data for the firstname and lastname so that it would be consistent with the validation
     */
    public function NewPersonName($value)
    {
        $NewPersonName1 = preg_replace('/[^\p{L}0-9\s\-_]/u',' ',$value);
        return $this->encoded_substrParams($NewPersonName1,0,50,24);
    }



    /**
     * prepare data for the city so that it would be consistent with the validation
     */
    public function NewCity($value)
    	{
    		$NewCity1 = preg_replace('/[^\p{L}0-9\.\s\-\/_,]/u',' ',$value);
    		return $this->encoded_substrParams($NewCity1,0,50,24);
    	}


    /**
  	 * prepare data for the phone so that it would be consistent with the validation
  	 */
      	public function NewPhone($value)
      		{
      			$NewPhone1 = preg_replace('/[^\+\s0-9\-_]/','',$value);
      			return $this->encoded_substrParams($NewPhone1,0,20,6);
      		}



        /**
         * prepare data for the postcode so that it would be consistent with the validation
         */
        public function NewPostcode($value)
        	{
        		$NewPostcode1 = preg_replace('/[^\d\w\s\-]/','',$value);
        		return $this->encoded_substrParams($NewPostcode1,0,20,6);
        	}



    /**
     * Gets values from payment confirmation and saves them into internal variable
     */
    protected function getConfirmValues() {
        foreach ($this->_confirmFields as $k => &$v) {
            $value = Mage::app()->getRequest()->getPost($k);
            if ($value !== '') {
                $v = $value;
            }
        }
    }
}
