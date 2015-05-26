<?php
/**
 * Copyright (c) 2015, Nosto Solutions Ltd
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
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

/**
 * Main backend controller. Handles account create/connect/delete requests
 * from the account configuration iframe.
 *
 * Extends Shopware_Controllers_Backend_ExtJs.
 *
 * @package Shopware
 * @subpackage Controllers_Backend
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2015 Nosto Solutions Ltd (http://www.nosto.com)
 */
class Shopware_Controllers_Backend_NostoTagging extends Shopware_Controllers_Backend_ExtJs
{
	const DEFAULT_IFRAME_ORIGIN = 'https://my.nosto.com';

	/**
	 * Loads the Nosto ExtJS sub-application for configuring Nosto for the shops.
	 * Default action.
	 */
	public function indexAction()
	{
		$this->View()->loadTemplate('backend/nosto_tagging/app.js');
	}

	/**
	 * Ajax action for getting any settings for the backend app.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function loadSettingsAction()
	{
		$this->View()->assign(array(
			'success' => true,
			'data' => array(
				'postMessageOrigin' => Nosto::getEnvVariable(
					'NOSTO_IFRAME_ORIGIN',
					self::DEFAULT_IFRAME_ORIGIN
				)
			)
		));
	}

	/**
	 * Gets a list of accounts to populate client side Account models form.
	 * These are created for every shop, even if there is no Nosto account
	 * configured for it yet. This is done in order to streamline the
	 * functionality client side.
	 *
	 * This action should only be accessed by the Account store proxy in the
	 * client side application.
	 *
	 * The Account models are formatted as follows:
	 *
	 * [
	 *   {
	 *     'id'       => 1,                     // The Nosto account id if configured, null otherwise
	 *     'name'     => 'shopware-1234567',    // The Nosto account name if configured, null otherwise
	 *     'url'      => 'http://my.nosto.com', // The Nosto account admin url if configured, null otherwise
	 *     'shopId'   => 1,                     // Shopware Shop model id the Nosto account is connected to
	 *     'shopName' => 'English'              // Shopware Shop model name the Nosto account is connected to
	 *   },
	 *   ...
	 * ]
	 */
	public function getAccountsAction()
	{
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$result = $repository->getActiveShops(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
		$identity = Shopware()->Auth()->getIdentity();
		$data = array();

		foreach ($result as $row) {
			$shop = $repository->getActiveById($row['id']);
			if (is_null($shop)) {
				continue;
			}
			$shop->registerResources(Shopware()->Bootstrap());
			$account = $helper->findAccount($shop);
			$accountData = array(
				'url' => $helper->buildAccountIframeUrl($shop, $identity->locale, $account, $identity),
				'shopId' => $shop->getId(),
				'shopName' => $shop->getName(),
			);
			if (!is_null($account)) {
				$accountData['id'] = $account->getId();
				$accountData['name'] = $account->getName();
			}
			$data[] = $accountData;
		}

		$this->View()->assign(array('success' => true, 'data' => $data, 'total' => count($data)));
	}

	/**
	 * Ajax action for creating a new Nosto account and linking it to a Shop.
	 *
	 * This action should only be accessed by the Account model in the client
	 * side application.
	 */
	public function createAccountAction()
	{
		$success = false;
		$data = array();
		$shopId = $this->Request()->getParam('shopId', null);
		$email = $this->Request()->getParam('email', null);
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$shop = $repository->getActiveById($shopId);
		$identity = Shopware()->Auth()->getIdentity();

		if (!is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			try {
				$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
				$account = $helper->createAccount($shop, $identity->locale, $identity, $email);
				Shopware()->Models()->persist($account);
				Shopware()->Models()->flush($account);
				$success = true;
				$data = array(
					'id' => $account->getId(),
					'name' => $account->getName(),
					'url' => $helper->buildAccountIframeUrl(
							$shop,
							$identity->locale,
							$account,
							$identity,
							array(
								'message_type' => NostoMessage::TYPE_SUCCESS,
								'message_code' => NostoMessage::CODE_ACCOUNT_CREATE
							)
						),
					'shopId' => $shop->getId(),
					'shopName' => $shop->getName(),
				);
			} catch (NostoException $e) {
				Shopware()->Pluginlogger()->error($e);
			}
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}

	/**
	 * Ajax action for deleting a Nosto account for a Shop.
	 *
	 * This action should only be accessed by the Account model in the client
	 * side application.
	 */
	public function deleteAccountAction()
	{
		$success = false;
		$data = array();
		$accountId = $this->Request()->getParam('id', null);
		$shopId = $this->Request()->getParam('shopId', null);
		/** @var \Shopware\CustomModels\Nosto\Account\Account $account */
		$account = Shopware()->Models()->find('\Shopware\CustomModels\Nosto\Account\Account', $accountId);
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$shop = $repository->getActiveById($shopId);
		$identity = Shopware()->Auth()->getIdentity();

		if (!is_null($account) && !is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			$helper = new Shopware_Plugins_Frontend_NostoTagging_Components_Account();
			$helper->removeAccount($account);
			$success = true;
			$data = array(
				'url' => $helper->buildAccountIframeUrl(
						$shop,
						$identity->locale,
						null,
						$identity,
						array(
							'message_type' => NostoMessage::TYPE_SUCCESS,
							'message_code' => NostoMessage::CODE_ACCOUNT_DELETE
						)
					),
				'shopId' => $shop->getId(),
				'shopName' => $shop->getName(),
			);
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}

	/**
	 * Ajax action for connecting an account via OAuth and linking it to a shop.
	 *
	 * This action should only be accessed by the Main controller in the client
	 * side application.
	 */
	public function connectAccountAction()
	{
		$success = false;
		$data = array();
		$shopId = $this->Request()->getParam('shopId', null);
		/* @var \Shopware\Models\Shop\Repository $repository */
		$repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
		$shop = $repository->getActiveById($shopId);
		$locale = Shopware()->Auth()->getIdentity()->locale;

		if (!is_null($shop)) {
			$shop->registerResources(Shopware()->Bootstrap());
			$meta = new Shopware_Plugins_Frontend_NostoTagging_Components_Meta_Oauth();
			$meta->loadData($shop, $locale);
			$client = new NostoOAuthClient($meta);
			$success = true;
			$data = array('redirect_url' => $client->getAuthorizationUrl());
		}

		$this->View()->assign(array('success' => $success, 'data' => $data));
	}
}
