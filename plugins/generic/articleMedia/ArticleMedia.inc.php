<?php

/**
 * @file plugins/generic/articleMedia/ArticleMedia.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleMedia
 * @ingroup plugins_generic_articleMedia
 *
 * @brief Basic class describing an article media object.
 */

define('ARTICLE_MEDIA_TYPE_VIDEO',		1);
define('ARTICLE_MEDIA_TYPE_AUDIO',		2);

class ArticleMedia extends DataObject {

	function ArticleMedia() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the article media object.
	 * @return int
	 */
	function getId() {
		return $this->getData('feedId');
	}

	/**
	 * Set the ID of the article media object.
	 * @param $feedId int
	 */
	function setId($feedId) {
		return $this->setData('feedId', $feedId);
	}

	/**
	 * Get the ID of the associated article.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}

	/**
	 * Set the ID of the associated article.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}

	/**
	 * Get media type.
	 * @return int 
	 */
	function getType() {
		return $this->getData('type');
	}

	/**
	 * Set media type.
	 * @param $type int
	 */
	function setType($type) {
		return $this->setData('type', $type);
	}

	/**
	 * Get media URL.
	 * @return int 
	 */
	function getUrl() {
		return $this->getData('url');
	}

	/**
	 * Set media URL.
	 * @param $url string
	 */
	function setUrl($url) {
		return $this->setData('url', $url);
	}

	/**
	 * Get media source (youtube, other, etc.)
	 * @return string
	 */
	function getSource() {
		return $this->getData('mediaSource');
	}

	/**
	 * Set media source (youtube, other, etc.)
	 * @param $embedCode string
	 */
	function setSource($mediaSource) {
		return $this->setData('mediaSource', $mediaSource);
	}

	/**
	 * Get array of metadata
	 * @return array
	 */
	function getMetadata() {
		return unserialize($this->getData('metadata'));
	}

	/**
	 * Set array of metadata (stored in DB as a serialized array)
	 * @param $metadata array
	 */
	function setMetadata($metadata) {
		return $this->setData('metadata', serialize($metadata));
	}
}

?>
