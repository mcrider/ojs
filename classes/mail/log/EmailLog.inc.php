<?php

/**
 * @defgroup article_log
 */

/**
 * @file classes/mail/log/ArticleLog.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MailLog
 * @ingroup mail_log
 *
 * @brief Static class for adding / accessing mail log entries.
 */

class EmailLog {

	/**
	 * Add an email log entry to this article.
	 * @param $articleId int
	 * @param $entry ArticleEmailLogEntry
	 */
	function logEmailEntry($journalId, &$entry) {
		if (!$journalId) return false;

		// Add the entry
		$entry->setJournalId($journalId);

		if ($entry->getSenderId() == null) {
			$user =& Request::getUser();
			$entry->setSenderId($user == null ? 0 : $user->getId());
		}

		$logDao =& DAORegistry::getDAO('EmailLogDAO');
		return $logDao->insertLogEntry($entry);
	}
}

?>
