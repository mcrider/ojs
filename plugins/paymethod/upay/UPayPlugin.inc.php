<?php

/**
 * @file plugins/paymethod/upay/UPayPlugin.inc.php
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UPayPlugin
 * @ingroup plugins_paymethod_upay
 *
 * @brief UPay Paymethod plugin class
 */

import('classes.plugins.PaymethodPlugin');

class UPayPlugin extends PaymethodPlugin {
	/**
	 * Constructor
	 */
	function UPayPlugin() {
		parent::PaymethodPlugin();
	}

	/**
	 * Get the Plugin's internal name
	 * @return String
	 */
	function getName() {
		return 'uPay';
	}

	/**
	 * Get the Plugin's display name
	 * @return String
	 */	
	function getDisplayName() {
		return __('plugins.paymethod.upay.displayName');
	}

	/**
	 * Get a description of the plugin
	 * @return String
	 */
	function getDescription() {
		return __('plugins.paymethod.upay.description');
	}   

	/**
	 * Register plugin
	 * @return bool
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
			$this->addLocaleData();
			$this->import('UPayDAO');
			$uPayDao = new UPayDAO();
			DAORegistry::registerDAO('UPayDAO', $uPayDao);
			return true;
		}
		return false;
	}

	/**
	 * Get an array of the fields in the settings form
	 * @return array
	 */
	function getSettingsFormFieldNames() {
		return array('upayurl', 'siteId', 'transId');
	}

	/**
	 * return if required Curl is installed
	 * @return bool
	 */	
	function isCurlInstalled() {
		return (function_exists('curl_init'));
	}

	/**
	 * Check if plugin is configured and ready for use
	 * @return bool
	 */
	function isConfigured() {
		$journal =& Request::getJournal();
		if (!$journal) return false;

		// Make sure CURL support is included.
		if (!$this->isCurlInstalled()) return false;

		// Make sure that all settings form fields have been filled in
		foreach ($this->getSettingsFormFieldNames() as $settingName) {
			$setting = $this->getSetting($journal->getId(), $settingName);
			if (empty($setting)) return false;
		}
		return true;
	}

	/**
	 * Display the settings form
	 * @param $params
	 * @param $smarty Smarty
	 */
	function displayPaymentSettingsForm(&$params, &$smarty) {
		$smarty->assign('isCurlInstalled', $this->isCurlInstalled());
		return parent::displayPaymentSettingsForm($params, $smarty);
	}

	/**
	 * Display the payment form
	 * @param $queuedPaymentId int
	 * @param $queuedPayment QueuedPayment
	 * @param $request PKPRequest
	 */
	function displayPaymentForm($queuedPaymentId, &$queuedPayment) {
		if (!$this->isConfigured()) return false;
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$journal =& Request::getJournal();
		$user =& Request::getUser();

		$txnType = $queuedPayment->getType();
		$amt = $queuedPayment->getAmount();
		$userId = $user->getId();
		$params = array(
			'UPAY_SITE_ID' => $this->getSetting($journal->getId(), 'siteId'),
			'EXT_TRANS_ID'=>  $queuedPaymentId,
			'EXT_TRANS_ID_LABEL' => $journal->getLocalizedTitle(),
			'AMT' => $queuedPayment->getAmount(),
			'BILL_EMAIL_ADDRESS' => $user->getEmail(),
			'SUCCESS_LINK' => Request::url(null, 'payment', 'plugin', array($this->getName(), 'verified')) . "?queuedPaymentId=$queuedPaymentId&txn_type=$txnType&amt=$amt&payer_id=$userId&payment_date=" . date('Ymd'),
			'CANCEL_LINK' =>Request::url(null, 'payment', 'plugin', array($this->getName(), 'cancel')),
		);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('params', $params);
		$templateMgr->assign('upayFormUrl', $this->getSetting($journal->getId(), 'upayurl'));
		$templateMgr->display($this->getTemplatePath() . 'paymentForm.tpl');
		return true;
	}

	/**
	 * Handle incoming requests/notifications
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function handle($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		$journal =& $request->getJournal();
		if (!$journal) return parent::handle($args, $request);

		// Just in case we need to contact someone
		import('classes.mail.MailTemplate');
		// Prefer technical support contact
		$contactName = $journal->getSetting('supportName');
		$contactEmail = $journal->getSetting('supportEmail');
		if (!$contactEmail) { // Fall back on primary contact
			$contactName = $journal->getSetting('contactName');
			$contactEmail = $journal->getSetting('contactEmail');
		}
		$mail = new MailTemplate('UPAY_INVESTIGATE_PAYMENT');
		$mail->setFrom($contactEmail, $contactName);
		$mail->addRecipient($contactEmail, $contactName);

		$paymentStatus = $request->getUserVar('payment_status');

		switch (array_shift($args)) {
			case 'verified':
				// Build a confirmation transaction.
				$uPayDao =& DAORegistry::getDAO('UPayDAO');
				$transactionId = $request->getUserVar('EXT_TRANS_ID');
				if ($uPayDao->transactionExists($transactionId)) {
					// A duplicate transaction was received; notify someone.
					$mail->assignParams(array(
						'journalName' => $journal->getLocalizedTitle(),
						'postInfo' => print_r($_POST, true),
						'additionalInfo' => "Duplicate transaction ID: $transactionId",
						'serverVars' => print_r($_SERVER, true)
					));
					$mail->send();
					exit();
				} else {
					// New transaction succeeded. Record it.
					$uPayDao->insertTransaction(
						$transactionId,
						$request->getUserVar('txn_type'),
						$request->getUserVar('payment_date'),
						$request->getUserVar('payer_id')
					);
					$queuedPaymentId = $request->getUserVar('queuedPaymentId');

					import('classes.payment.ojs.OJSPaymentManager');
					$ojsPaymentManager = new OJSPaymentManager($request);

					// Verify the cost and user details as per UPay spec.
					$queuedPayment =& $ojsPaymentManager->getQueuedPayment($queuedPaymentId);
					if (!$queuedPayment) {
						// The queued payment entry is missing. Complain.
						$mail->assignParams(array(
							'journalName' => $journal->getLocalizedTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' => "Missing queued payment ID: $queuedPaymentId",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						exit();
					}

				/*	//NB: if/when upay subscriptions are enabled, these checks will have to be adjusted
					// because subscription prices may change over time
					if (
						(($queuedAmount = $queuedPayment->getAmount()) != ($grantedAmount = $request->getUserVar('mc_gross')) && $queuedAmount > 0) ||
						($queuedCurrency = $queuedPayment->getCurrencyCode()) != ($grantedCurrency = $request->getUserVar('mc_currency')) ||
						($grantedEmail = $request->getUserVar('receiver_email')) != ($queuedEmail = $this->getSetting($journal->getId(), 'selleraccount'))
					) {
						// The integrity checks for the transaction failed. Complain.
						$mail->assignParams(array(
							'journalName' => $journal->getLocalizedTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' =>
								"Granted amount: $grantedAmount\n" .
								"Queued amount: $queuedAmount\n" .
								"Granted currency: $grantedCurrency\n" .
								"Queued currency: $queuedCurrency\n" .
								"Granted to UPay account: $grantedEmail\n" .
								"Configured UPay account: $queuedEmail",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						exit();
					}

					// Update queued amount if amount set by user (e.g. donation)
					if ($queuedAmount == 0 && $grantedAmount > 0) {
						$queuedPaymentDao =& DAORegistry::getDAO('QueuedPaymentDAO');
						$queuedPayment->setAmount($grantedAmount);
						$queuedPayment->setCurrencyCode($grantedCurrency);
						$queuedPaymentDao->updateQueuedPayment($queuedPaymentId, $queuedPayment);
					}*/

					// Fulfill the queued payment.
					if ($ojsPaymentManager->fulfillQueuedPayment($queuedPayment, $this->getName())) {
						Request::redirect(null);
					} else {
						
						// If we're still here, it means the payment couldn't be fulfilled.
						$mail->assignParams(array(
							'journalName' => $journal->getLocalizedTitle(),
							'postInfo' => print_r($_POST, true),
							'additionalInfo' => "Queued payment ID $queuedPaymentId could not be fulfilled.",
							'serverVars' => print_r($_SERVER, true)
						));
						$mail->send();
						Request::redirect(null);
					}
					
				}
				break;
			case 'cancel':
				Handler::setupTemplate();
				$templateMgr->assign(array(
					'currentUrl' => $request->url(null, 'index'),
					'pageTitle' => 'plugins.paymethod.upay.purchase.cancelled.title',
					'message' => 'plugins.paymethod.upay.purchase.cancelled',
					'backLink' => $request->getUserVar('ojsReturnUrl'),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('common/message.tpl');
				exit();
				break;
		}
		parent::handle($args, $request); // Don't know what to do with it
	}

	/**
	 * @see Plugin::getInstallSchemaFile
	 */
	function getInstallSchemaFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'schema.xml');
	}

	/**
	 * @see getIntsallEmailTemplatesFile
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	/**
	 * @see getInstallEmailTemplateDataFile
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}

?>
