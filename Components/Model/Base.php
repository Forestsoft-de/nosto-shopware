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
 * Base class for all component models.
 *
 * @package Shopware
 * @subpackage Plugins_Frontend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
abstract class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base
{
	/**
	 * Returns a protected/private property value by invoking it's public getter.
	 *
	 * The getter names are assumed to be the property name in camel case with preceding word "get".
	 *
	 * @param string $name the property name.
	 * @return mixed the property value.
	 * @throws Exception if public getter does not exist.
	 */
	public function __get($name)
	{
		$getter = 'get'.str_replace('_', '', $name);
		if (method_exists($this, $getter)) {
			return $this->{$getter}();
		}
		throw new Exception(sprintf('Property `%s.%s` is not defined.', get_class($this), $name));
	}
}
