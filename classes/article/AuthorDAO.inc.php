<?php

/**
 * @file classes/article/AuthorDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDAO
 * @ingroup article
 * @see Author
 *
 * @brief Operations for retrieving and modifying Author objects.
 */

import('classes.article.Author');
import('classes.article.Article');
import('lib.pkp.classes.submission.PKPAuthorDAO');

class AuthorDAO extends PKPAuthorDAO {
	/**
	 * Constructor
	 */
	function AuthorDAO() {
		parent::PKPAuthorDAO();
	}

	/**
	 * Retrieve all published submissions associated with authors with
	 * the given first name, middle name, last name, affiliation, and country.
	 * @param $journalId int (null if no restriction desired)
	 * @param $firstName string
	 * @param $middleName string
	 * @param $lastName string
	 * @param $affiliation string
	 * @param $country string
	 */
	function &getPublishedArticlesForAuthor($journalId, $firstName, $middleName, $lastName, $affiliation, $country) {
		$publishedArticles = array();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$params = array(
			'affiliation',
			$firstName, $middleName, $lastName,
			$firstName, $middleName, $lastName, $affiliation, $country
		);
		if ($journalId !== null) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN articles a ON (aa.submission_id = a.article_id)
				LEFT JOIN author_settings asl ON (asl.author_id = aa.author_id AND asl.setting_name = ?)
				LEFT JOIN author_settings aslfn ON (aa.author_id = aslfn.author_id AND aslfn.setting_name = ?)
				LEFT JOIN author_settings aslmn ON (aa.author_id = aslmn.author_id AND aslmn.setting_name = ?)
				LEFT JOIN author_settings aslln ON (aa.author_id = aslln.author_id AND aslln.setting_name = ?)
			WHERE a.status = ' . STATUS_PUBLISHED . '
				AND aslfn.setting_value = ?
				AND (aslmn.setting_value = ?' . (empty($middleName)?' OR aslmn.setting_value IS NULL':'') . ')
				AND aslln.setting_value = ?
				AND (asl.setting_value = ?' . (empty($affiliation)?' OR asl.setting_value IS NULL':'') . ')
				AND (aa.country = ?' . (empty($country)?' OR aa.country IS NULL':'') . ') ' .
				($journalId!==null?(' AND a.journal_id = ?'):''),
			$params
		);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($row['submission_id']);
			if ($publishedArticle) {
				$publishedArticles[] =& $publishedArticle;
			}
			$result->moveNext();
			unset($publishedArticle);
		}

		$result->Close();
		unset($result);

		return $publishedArticles;
	}

	/**
	 * Retrieve all published authors for a journal in an associative array by
	 * the first letter of the last name, for example:
	 * $returnedArray['S'] gives array($misterSmithObject, $misterSmytheObject, ...)
	 * Keys will appear in sorted order. Note that if journalId is null,
	 * alphabetized authors for all journals are returned.
	 * @param $journalId int
	 * @param $initial An initial the last names must begin with
	 * @param $rangeInfo Range information
	 * @param $includeEmail Whether or not to include the email in the select distinct
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsAlphabetizedByJournal($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
		$authors = array();
		$params = array(
			'affiliation', AppLocale::getPrimaryLocale(),
			'affiliation', AppLocale::getLocale(),
			'firstName', AppLocale::getPrimaryLocale(),
			'firstName', AppLocale::getLocale(),
			'middleName', AppLocale::getPrimaryLocale(),
			'middleName', AppLocale::getLocale(),
			'lastName', AppLocale::getPrimaryLocale(),
			'lastName', AppLocale::getLocale()
		);

		if (isset($journalId)) $params[] = $journalId;
		if (isset($initial)) {
			$params[] = String::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(aslln.setting_value) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}

		$result =& $this->retrieveRange(
			'SELECT DISTINCT
				CAST(\'\' AS CHAR) AS url,
				0 AS author_id,
				0 AS submission_id,
				' . ($includeEmail?'aa.email AS email,':'CAST(\'\' AS CHAR) AS email,') . '
				0 AS primary_contact,
				0 AS seq,
				COALESCE(aslfn.setting_value, asplfn.setting_value) as first_name_l,
				asplfn.setting_value as first_name_pl,
				COALESCE(aslmn.setting_value, asplmn.setting_value) as middle_name_l,
				asplmn.setting_value as middle_name_pl,
				COALESCE(aslln.setting_value, asplln.setting_value) as last_name_l,
				asplln.setting_value as last_name_pl,
				SUBSTRING(asl.setting_value FROM 1 FOR 255) AS affiliation_l,
				asl.locale,
				SUBSTRING(aspl.setting_value FROM 1 FOR 255) AS affiliation_pl,
				aspl.locale AS primary_locale,
				aa.country
			FROM	authors aa
				LEFT JOIN author_settings aspl ON (aa.author_id = aspl.author_id AND aspl.setting_name = ? AND aspl.locale = ?)
				LEFT JOIN author_settings asl ON (aa.author_id = asl.author_id AND asl.setting_name = ? AND asl.locale = ?)
				LEFT JOIN author_settings asplfn ON (aa.author_id = asplfn.author_id AND asplfn.setting_name = ? AND asplfn.locale = ?)
				LEFT JOIN author_settings aslfn ON (aa.author_id = aslfn.author_id AND aslfn.setting_name = ? AND aslfn.locale = ?)
				LEFT JOIN author_settings asplmn ON (aa.author_id = asplmn.author_id AND asplmn.setting_name = ? AND asplmn.locale = ?)
				LEFT JOIN author_settings aslmn ON (aa.author_id = aslmn.author_id AND aslmn.setting_name = ? AND aslmn.locale = ?)
				LEFT JOIN author_settings asplln ON (aa.author_id = asplln.author_id AND asplln.setting_name = ? AND asplln.locale = ?)
				LEFT JOIN author_settings aslln ON (aa.author_id = aslln.author_id AND aslln.setting_name = ? AND aslln.locale = ?)
				LEFT JOIN articles a ON (a.article_id = aa.submission_id)
				LEFT JOIN published_articles pa ON (pa.article_id = a.article_id)
				LEFT JOIN issues i ON (pa.issue_id = i.issue_id)
			WHERE	i.published = 1 AND
				aa.submission_id = a.article_id AND ' .
				(isset($journalId)?'a.journal_id = ? AND ':'') . '
				pa.article_id = a.article_id AND
				a.status = ' . STATUS_PUBLISHED . ' AND
				(asplln.setting_value IS NOT NULL AND asplln.setting_value <> \'\')' .
				$initialSql . '
			ORDER BY last_name_l, first_name_l',
			$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSimpleAuthorFromRow');
		return $returner;
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new Author();
	}

	/**
	 * Insert a new Author.
	 * @param $author Author
	 */
	function insertAuthor(&$author) {
		$this->update(
			'INSERT INTO authors
				(submission_id, country, email, url, primary_contact, seq)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$author->getSubmissionId(),
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence()
			)
		);

		$author->setId($this->getInsertAuthorId());
		$this->updateLocaleFields($author);

		return $author->getId();
	}

	/**
	 * Update an existing Author.
	 * @param $author Author
	 */
	function updateAuthor(&$author) {
		$returner = $this->update(
			'UPDATE authors
			SET	country = ?,
				email = ?,
				url = ?,
				primary_contact = ?,
				seq = ?
			WHERE	author_id = ?',
			array(
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence(),
				(int) $author->getId()
			)
		);
		$this->updateLocaleFields($author);
		return $returner;
	}

	/**
	 * Delete authors by submission.
	 * @param $submissionId int
	 */
	function deleteAuthorsByArticle($submissionId) {
		$authors =& $this->getAuthorsBySubmissionId($submissionId);
		foreach ($authors as $author) {
			$this->deleteAuthor($author);
		}
	}
}

?>
