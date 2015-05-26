<?php
/**
 * Shopware 4, 5
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Model for order status information. This is used when compiling the info
 * about an order that is sent to Nosto.
 *
 * Extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base.
 * Implements NostoOrderStatusInterface.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderStatusInterface
{
	/**
	 * @var string the order status code.
	 */
	protected $_code;

	/**
	 * @var string the order status label.
	 */
	protected $_label;

	/**
	 * Populates the order status with data from the order model.
	 *
	 * @param Shopware\Models\Order\Order $order the order model.
	 */
	public function loadData(Shopware\Models\Order\Order $order)
	{
		$description = $order->getOrderStatus()->getDescription();
		$this->_code = $this->convertDescriptionToCode($description);
		$this->_label = $description;
	}

	/**
	 * Converts a human readable status description to a machine readable code,
	 * i.e. converts the description to a lower case alphanumeric string.
	 *
	 * @param string $description the description to convert.
	 * @return string the status code.
	 */
	protected function convertDescriptionToCode($description)
	{
		$pattern = array('/[^a-zA-Z0-9]+/', '/_+/', '/^_+/', '/_+$/');
		$replacement = array('_', '_', '', '');
		return strtolower(preg_replace($pattern, $replacement, $description));
	}

	/**
	 * @inheritdoc
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel()
	{
		return $this->_label;
	}
} 