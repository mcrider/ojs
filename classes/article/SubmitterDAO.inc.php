<?php

/**
 * @file classes/article/SubmitterDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmitterDAO
 * @ingroup article
 * @see Submitter
 *
 * @brief Operations for retrieving and modifying Submitter objects.
 */


class SubmitterDAO extends DAO {
	var $userDao;
	/**
	 * Constructor
	 */
	function SubmitterDAO() {
		parent::DAO();
		$this->userDao =& DAORegistry::getDAO('UserDAO');
	}

	/**
	 * Retrieve all submitters for a submission.
	 * @param $submissionId int
	 * @return array Users
	 */
	function &getBySubmissionId($submissionId) {
		$submitters = array();

		$result =& $this->retrieve(
			'SELECT u.* FROM users u JOIN submitters s ON (s.user_id = u.user_id) WHERE s.submission_id =  ?',
			(int) $submissionId
		);

		while (!$result->EOF) {
			$user =& $this->userDao->_returnUserFromRowWithData($result->GetRowAssoc(false));
			$submitters[$user->getId()] = $user;
			$result->moveNext();
		}

		$result->Close();
		unset($result);
		
		return $submitters;
	}

	/**
	 * Check if a submitter already exists for a submission
	 * @param $userId int
	 * @param $submissionId int
	 * @return boolean
	 */
	function submitterExists($userId, $submissionId) {
		$submitters = array();

		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM submitters s WHERE s.user_id = ? AND s.submission_id =  ?',
			array((int) $userId, (int) $submissionId)
		);

		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Insert a new Submitter.
	 * @param $userId int
	 * @param $submissionId int
	 */
	function insertSubmitter($userId, $submissionId) {
		$this->update(
			'INSERT INTO submitters
				(user_id, submission_id)
				VALUES
				(?, ?)',
			array(
				(int) $userId,
				(int) $submissionId,
			)
		);
	}

	/**
	 * Delete submitter by submission.
	 * @param $userId int
	 * @param $submissionId int
	 */
	function deleteSubmitterFromSubmission($userId, $submissionId) {
		$this->update('DELETE FROM submitters WHERE user_id = ? and submission_id = ?',
			array((int) $userId, (int) $submissionId));
	}

	/**
	 * Delete all submitters from a submission.
	 * @param $submissionId int
	 */
	function deleteAllSubmittersFromSubmission($userId, $submissionId) {
		$this->update('DELETE FROM submitters WHERE user_id = ? and submission_id = ?',
			array((int) $userId, (int) $submissionId));
	}

	/**
	 * Retrieve a list of all users in the specified role not assigned as editors to the specified article.
	 * @param $journalId int
	 * @param $articleId int
	 * @param $roleId int
	 * @return DAOResultFactory containing matching Users
	 */
	function &getSubmittersNotAssignedToArticle($journalId, $articleId, $roleId, $searchType=null, $search=null, $searchMatch=null, $rangeInfo = null) {
		$users = array();

		$paramArray = array(ASSOC_TYPE_USER, 'interest', $articleId, $articleId, $journalId, $roleId);
		$searchSql = '';

		$searchTypeMap = array(
			USER_FIELD_FIRSTNAME => 'u.first_name',
			USER_FIELD_LASTNAME => 'u.last_name',
			USER_FIELD_USERNAME => 'u.username',
			USER_FIELD_EMAIL => 'u.email',
			USER_FIELD_INTERESTS => 'cves.setting_value'
		);

		if (!empty($search) && isset($searchTypeMap[$searchType])) {
			$fieldName = $searchTypeMap[$searchType];
			switch ($searchMatch) {
				case 'is':
					$searchSql = "AND LOWER($fieldName) = LOWER(?)";
					$paramArray[] = $search;
					break;
				case 'contains':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = '%' . $search . '%';
					break;
				case 'startsWith':
					$searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
					$paramArray[] = $search . '%';
					break;
			}
		} elseif (!empty($search)) switch ($searchType) {
			case USER_FIELD_USERID:
				$searchSql = 'AND u.user_id=?';
				$paramArray[] = $search;
				break;
			case USER_FIELD_INITIAL:
				$searchSql = 'AND (LOWER(u.last_name) LIKE LOWER(?) OR LOWER(u.username) LIKE LOWER(?))';
				$paramArray[] = $search . '%';
				$paramArray[] = $search . '%';
				break;
		}

		$result =& $this->retrieveRange(
			'SELECT DISTINCT
				u.*
			FROM	users u
				LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
				LEFT JOIN roles r ON (r.user_id = u.user_id)
				LEFT JOIN submitters s ON (s.user_id = u.user_id AND s.submission_id = ?)
				LEFT JOIN articles a ON (a.article_id = ?)
			WHERE	r.journal_id = ? AND
				r.role_id = ? AND
				(s.submission_id IS NULL) ' . $searchSql . '
			ORDER BY last_name, first_name',
			$paramArray, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this->userDao, '_returnUserFromRowWithData');
		return $returner;
	}
}

?>
