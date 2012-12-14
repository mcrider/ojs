<?php

/**
 * @file classes/mail/log/EmailLogDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailLogDAO
 * @ingroup mail_log
 * @see EmailLogEntry, EmailLog
 *
 * @brief Class for inserting/accessing journal email log entries.
 */


import ('classes.mail.log.EmailLogEntry');

class EmailLogDAO extends DAO {
	/**
	 * Retrieve a log entry by ID.
	 * @param $logId int
	 * @param $journalId int optional
	 * @return EmailLogEntry
	 */
	function &getLogEntry($logId, $journalId = null) {
		if (isset($journalId)) {
			$result =& $this->retrieve(
				'SELECT * FROM email_log WHERE log_id = ? AND journal_id = ?',
				array($logId, $journalId)
			);
		} else {
			$result =& $this->retrieve(
				'SELECT * FROM email_log WHERE log_id = ?', $logId
			);
		}

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnLogEntryFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all log entries for journal.
	 * @param $journalId int
	 * @return DAOResultFactory containing matching EmailLogEntry ordered by sequence
	 */
	function &getLogEntriesByJournalId($journalId, $rangeInfo = null) {
		$params = array($journalId);
		if (isset($assocType)) {
			array_push($params, $assocType);
			if (isset($assocId)) {
				array_push($params, $assocId);
			}
		}

		$result =& $this->retrieveRange(
			'SELECT * FROM email_log WHERE journal_id = ?' . (isset($assocType) ? ' AND assoc_type = ?' . (isset($assocId) ? ' AND assoc_id = ?' : '') : '') . ' ORDER BY log_id DESC',
			$params, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnLogEntryFromRow');
		return $returner;
	}

	/**
	 * Internal function to return an EmailLogEntry object from a row.
	 * @param $row array
	 * @return EmailLogEntry
	 */
	function &_returnLogEntryFromRow(&$row) {
		$entry = new EmailLogEntry();
		$entry->setId($row['log_id']);
		$entry->setJournalId($row['journal_id']);
		$entry->setSenderId($row['sender_id']);
		$entry->setDateSent($this->datetimeFromDB($row['date_sent']));
		$entry->setIPAddress($row['ip_address']);
		$entry->setFrom($row['from_address']);
		$entry->setRecipients($row['recipients']);
		$entry->setCcs($row['cc_recipients']);
		$entry->setBccs($row['bcc_recipients']);
		$entry->setSubject($row['subject']);
		$entry->setBody($row['body']);

		HookRegistry::call('EmailLogDAO::_returnLogEntryFromRow', array(&$entry, &$row));

		return $entry;
	}

	/**
	 * Insert a new log entry.
	 * @param $entry EmailLogEntry
	 */
	function insertLogEntry(&$entry) {
		if ($entry->getDateSent() == null) {
			$entry->setDateSent(Core::getCurrentDate());
		}
		if ($entry->getIPAddress() == null) {
			$entry->setIPAddress(Request::getRemoteAddr());
		}
		$this->update(
			sprintf('INSERT INTO email_log
				(journal_id, sender_id, date_sent, ip_address, from_address, recipients, cc_recipients, bcc_recipients, subject, body)
				VALUES
				(?, ?, %s, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($entry->getDateSent())),
			array(
				$entry->getJournalId(),
				$entry->getSenderId(),
				$entry->getIPAddress(),
				$entry->getFrom(),
				$entry->getRecipients(),
				$entry->getCcs(),
				$entry->getBccs(),
				$entry->getSubject(),
				$entry->getBody()
			)
		);

		$entry->setId($this->getInsertLogId());
		return $entry->getId();
	}

	/**
	 * Delete a single log entry for an journal.
	 * @param $logId int
	 * @param $journalId int optional
	 */
	function deleteLogEntry($logId, $journalId = null) {
		if (isset($journalId)) {
			return $this->update(
				'DELETE FROM email_log WHERE log_id = ? AND journal_id = ?',
				array($logId, $journalId)
			);

		} else {
			return $this->update(
				'DELETE FROM email_log WHERE log_id = ?', $logId
			);
		}
	}

	/**
	 * Delete all log entries for a journal.
	 * @param $journalId int
	 */
	function deleteLogEntries($journalId) {
		return $this->update(
			'DELETE FROM email_log WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Transfer all email log entries to another user.
	 * @param $oldUserId int
	 * @param $newUserId int
	 */
	function transferLogEntries($oldUserId, $newUserId) {
		return $this->update(
			'UPDATE email_log SET sender_id = ? WHERE sender_id = ?',
			array($newUserId, $oldUserId)
		);
	}

	/**
	 * Get the ID of the last inserted log entry.
	 * @return int
	 */
	function getInsertLogId() {
		return $this->getInsertId('email_log', 'log_id');
	}
}

?>
