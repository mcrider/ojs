<?php

/**
 * @file plugins/generic/articleMedia/ArticleMediaDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMediaDAO
 * @ingroup plugins_generic_articleMedia
 *
 * @brief Operations for retrieving and modifying ArticleMedia objects.
 */

import('lib.pkp.classes.db.DAO');

class ArticleMediaDAO extends DAO {
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ArticleMediaDAO($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Retrieve an ArticleMedia by ID.
	 * @param $articleId int
	 * @param $mediaId int
	 * @return ArticleMedia
	 */
	function &getArticleMedia($articleId, $mediaId) {
		$result =& $this->retrieve(
			'SELECT * FROM article_media WHERE media_id = ? AND article_id = ?', array($mediaId, $articleId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnArticleMediaFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Internal function to return ArticleMedia object from a row.
	 * @param $row array
	 * @return ArticleMedia
	 */
	function &_returnArticleMediaFromRow(&$row) {
		$articleMediaPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$articleMediaPlugin->import('ArticleMedia');

		$articleMedia =& new ArticleMedia();
		$articleMedia->setId($row['media_id']);
		$articleMedia->setArticleId($row['article_id']);
		$articleMedia->setType($row['type']);

		$this->getDataObjectSettings(
			'article_media_settings',
			array('media_id' => $row['media_id'], 'article_id' => $row['article_id']),
			$articleMedia
		);

		return $articleMedia;
	}

	// Override DAO::getDataObjectSettings to accept an array.  Consider porting this to core.
	function getDataObjectSettings($tableName, $idFields, &$dataObject) {

		if ($idFields !== null && is_array($idFields)) {
			$idFieldArray = $params = array();
			foreach ($idFields as $fieldName => $fieldValue) {
				$idFieldArray[] = $fieldName . ' = ?';
				$params[] = $fieldValue;
			}
			$idFieldString = implode(' AND ', $idFieldArray);
			$sql = "SELECT * FROM $tableName WHERE $idFieldString";

		} else {
			$sql = "SELECT * FROM $tableName";
			$params = false;
		}
		$result =& $this->retrieve($sql, $params);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$dataObject->setData(
				$row['setting_name'],
				$this->convertFromDB(
					$row['setting_value'],
					$row['setting_type']
				),
				empty($row['locale'])?null:$row['locale']
			);
			unset($row);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);
	}

	/**
	 * Insert a new external feed.
	 * @param $articleMedia ArticleMedia
	 * @return int
	 */
	function insertArticleMedia(&$articleMedia) {
		$ret = $this->update(
			'INSERT INTO article_media
				(media_id, article_id, type)
			VALUES
				(?, ?, ?)',
			array(
				$articleMedia->getId(),
				$articleMedia->getArticleId(),
				$articleMedia->getType()
			)
		);

		$url = $this->update(
			'INSERT INTO article_media_settings
				(media_id, article_id, setting_name, setting_value, setting_type)
			VALUES
				(?, ?, ?, ?, ?)',
			array(
				$articleMedia->getId(),
				$articleMedia->getArticleId(),
				'url',
				$articleMedia->getUrl(),
				'string'
			)
		);

		$mediaSource = $this->update(
			'INSERT INTO article_media_settings
				(media_id, article_id, setting_name, setting_value, setting_type)
			VALUES
				(?, ?, ?, ?, ?)',
			array(
				$articleMedia->getId(),
				$articleMedia->getArticleId(),
				'mediaSource',
				$articleMedia->getSource(),
				'string'
			)
		);

		$metadata = $this->update(
			'INSERT INTO article_media_settings
				(media_id, article_id, setting_name, setting_value, setting_type)
			VALUES
				(?, ?, ?, ?, ?)',
			array(
				$articleMedia->getId(),
				$articleMedia->getArticleId(),
				'metadata',
				serialize($articleMedia->getMetadata()),
				'string'
			)
		);

		//$this->updateLocaleFields($articleMedia);

		return $articleMedia->getId();
	}

	/**
	 * Get a list of fields for which localized data is supported
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('url', 'mediaSource', 'metadata');
	}

	/**
	 * Update the localized fields for this object.
	 * @param $articleMedia
	 */
	function updateLocaleFields(&$articleMedia) {
		$this->updateDataObjectSettings('article_media_settings', $articleMedia, array(
			'article_id' => $articleMedia->getArticleId(),
			'media_id' => $articleMedia->getId()
		));
	}

	function mediaItemExists($mediaId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM media_items WHERE media_id = ?',
			array($mediaId)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Update an existing external feed.
	 * @param $articleMedia ArticleMedia
	 * @return boolean
	 */
	function updateArticleMedia(&$articleMedia) {
		$this->update(
			'UPDATE article_media
				SET article_id = ?,
					type = ?
				WHERE media_id = ?',
			array(
				$articleMedia->getArticleId(),
				$articleMedia->getType(),
				$articleMedia->getId()
			)
		);

		$this->updateLocaleFields($articleMedia);
	}

	/**
	 * Delete external feed.
	 * @param $articleMedia ArticleMedia
	 * @return boolean
	 */
	function deleteArticleMedia($articleMedia) {
		return $this->deleteArticleMediaById($articleMedia->getId());
	}

	/**
	 * Delete article media by media ID.
	 * @param $mediaId int
	 * @return boolean
	 */
	function deleteArticleMediaById($mediaId) {
		$this->update(
			'DELETE FROM article_media WHERE media_id = ?', $feedId
		);

		$this->update(
			'DELETE FROM article_media_settings WHERE media_id = ?', $feedId
		);
	}

	/**
	 * Delete article media by article ID.
	 * @param $articleId int
	 * @return boolean
	 */
	function deleteArticleMediaByArticleId($articleId) {
		$this->update(
			'DELETE FROM article_media WHERE article_id = ?', $articleId
		);

		$this->update(
			'DELETE FROM article_media_settings WHERE article_id = ?', $articleId
		);
	}

	/**
	 * Retrieve media matching a particular article ID.
	 * @param $articleId int
	 * @return object DAOResultFactory containing matching ArticleMedia objects
	 */
	function &getArticleMediaByArticleId($articleId) {
		$result =& $this->retrieveRange(
			'SELECT * FROM article_media WHERE article_id = ?',
			$articleId
		);

		$returner =& new DAOResultFactory($result, $this, '_returnArticleMediaFromRow');
		return $returner;
	}
}

?>
