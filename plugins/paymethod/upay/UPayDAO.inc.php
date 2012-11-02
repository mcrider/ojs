<?php

/**
 * @file plugins/paymethod/upay/UPayDAO.inc.php
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UPayDAO
 * @ingroup plugins_paymethod_upay
 *
 * @brief Operations for retrieving and modifying Transactions objects.
 */

import('lib.pkp.classes.db.DAO');

class UPayDAO extends DAO {
	/**
	 * Constructor.
	 */
	function UPayDAO() {
		parent::DAO();
	}

	/**
	 * Insert a payment into the payments table
	 * @param $txnId string
	 * @param $txnType string
	 * @param $payerEmail string
	 * @param $receiverEmail string
	 * @param $itemNumber string
	 * @param $paymentDate datetime
	 * @param $payerId string
	 * @param $receiverId string
	 */
	 function insertTransaction($txnId, $txnType, $paymentDate, $payerId) {
		$ret = $this->update(
			sprintf(
				'INSERT INTO upay_transactions (
					txn_id,
					txn_type,
					payment_date,
					payer_id
				) VALUES (
					?, ?, %s, ?
				)',
				$this->datetimeToDB($paymentDate)
			),
			array(
				(int) $txnId,
				(int) $txnType,
				(int) $payerId
			)
		);

		return true;
	 }

	/**
	 * Check whether a given transaction exists.
 	 * @param $txnId string
	 * @return boolean
	 */
	function transactionExists($txnId) {
		$result =& $this->retrieve(
			'SELECT	count(*) FROM upay_transactions WHERE txn_id = ?',
			array($txnId)
		);

		$returner = false;
		if (isset($result->fields[0]) && $result->fields[0] >= 1) $returner = true;

		$result->Close();
		return $returner;
	}
}

?>
