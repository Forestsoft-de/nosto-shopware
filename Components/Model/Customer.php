<?php
/**
 * Copyright (c) 2016, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2016 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Model for customer information. This is used when compiling the info about
 * customers that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Customer extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * @var string the customer first name.
	 */
	protected $_firstName;

	/**
	 * @var string the customer last name.
	 */
	protected $_lastName;

	/**
	 * @var string the customer email address.
	 */
	protected $_email;

	/**
	 * Loads customer data from the logged in customer.
	 *
	 * @param \Shopware\Models\Customer\Customer $customer the customer model.
	 */
	public function loadData(\Shopware\Models\Customer\Customer $customer)
	{
		if ($customer->getBilling() instanceof \Shopware\Models\Customer\Billing) {
			$this->_firstName = $customer->getBilling()->getFirstName();
			$this->_lastName = $customer->getBilling()->getLastName();
		}
		$this->_email = $customer->getEmail();

		Enlight()->Events()->notify(
			__CLASS__ . '_AfterLoad',
			array(
				'nostoCustomer' => $this,
				'customer'      => $customer,
			)
		);
	}

	/**
	 * @return string
	 */
	public function getFirstName()
	{
		return $this->_firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName()
	{
		return $this->_lastName;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->_email;
	}

	/**
	 * Sets the firstname of the customer.
	 *
	 * The name must be a non-empty string.
	 *
	 * Usage:
	 * $object->setFirstName('John');
	 *
	 * @param string $firstName the firstname.
	 *
	 * @return $this Self for chaining
	 */
	public function setFirstName($firstName)
	{
		$this->_firstName = $firstName;

		return $this;
	}

	/**
	 * Sets the lastname of the customer.
	 *
	 * The name must be a non-empty string.
	 *
	 * Usage:
	 * $object->setLastName('Doe');
	 *
	 * @param string $lastName the lastname.
	 *
	 * @return $this Self for chaining
	 */
	public function setLastName($lastName)
	{
		$this->_lastName = $lastName;

		return $this;
	}

	/**
	 * Sets the email of the customer.
	 *
	 * The email must be a non-empty string.
	 *
	 * Usage:
	 * $object->setEmail('john@doe.com');
	 *
	 * @param string $email the email.
	 *
	 * @return $this Self for chaining
	 */
	public function setEmail($email)
	{
		$this->_email = $email;

		return $this;
	}
}
