<?php

/**
 * @file plugins/generic/articleMedia/ArticleMediaPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GoogleViewerPlugin
 *
 * @brief This plugin creates a mini-wizard in the submission wizard to enter links to media (images, audio, and video) and their accompanying metadata, and produces tags for easily entering the media into HTML galleys.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ArticleMediaPlugin extends GenericPlugin {
	/* @var articleId int */
	var $articleId;

	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				$url = Request::getUserVar('url');
				if(isset($url)) {
					$this->getXmlData($url);
					return true;
				}

				$this->import('ArticleMediaDAO');
				$articleMediaDAO = new ArticleMediaDAO($this->getName());
				DAORegistry::registerDAO('ArticleMediaDAO', $articleMediaDAO);

				// Insert the wizard into the submit process and in meta edit forms
				HookRegistry::register('Templates::Author::Submit::AdditionalMetadata', array($this, 'insertWizard'));
				HookRegistry::register('Templates::Submission::MetadataEdit::AdditionalMetadata', array($this, 'insertWizard'));

				// Hook for initData in two forms -- init the new field
				HookRegistry::register('metadataform::initdata', array($this, 'metadataInitData'));
				HookRegistry::register('authorsubmitstep3form::initdata', array($this, 'metadataInitData'));

				// Hook for readUserVars in two forms -- consider the new field entry
				HookRegistry::register('metadataform::readuservars', array($this, 'metadataReadUserVars'));
				HookRegistry::register('authorsubmitstep3form::readuservars', array($this, 'metadataReadUserVars'));

				// Save the data
				HookRegistry::register('authorsubmitstep3form::execute', array($this, 'metadataExecute'));
				HookRegistry::register('metadataform::execute', array($this, 'metadataExecute'));

				// Insert media into HTML galleys
				HookRegistry::register('Article::ArticleHTMLGalley::getHTMLContents', array($this, 'insertMedia'));
			}

			return true;
		}
		return false;
	}

	function getDisplayName() {
		return __('plugins.generic.articleMedia.name');
	}

	function getDescription() {
		return __('plugins.generic.articleMedia.description');
	}

	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args) {
		$templateManager =& TemplateManager::getManager();
		$templateManager->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		if (!$this->getEnabled() && $verb != 'enable') return false;
		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				return false;
			case 'disable':
				$this->setEnabled(false);
				return false;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Insert the media wizard into metadata edit
	 */
	function insertWizard($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$this->import('ArticleMedia');

		$request =& PKPApplication::getRequest();
		$smarty->assign('templatePath', $request->getBaseUrl() . '/' . $this->getPluginPath());
		$smarty->assign('getXmlUrl', $this->smartyPluginUrl(array('path' => 'getXmlData'), $smarty));

		$output .= $smarty->fetch($this->getTemplatePath() . 'mediaWizard.tpl');
		return false;
	}

	/**
	 * Init article projectID
	 */
	function metadataInitData($hookName, $params) {
		$form =& $params[0];
		$article =& $form->article;

		$this->import('ArticleMedia');
		$articleMediaDao = new ArticleMediaDAO($this->getName());

		$articleMedia =& $articleMediaDao->getArticleMediaByArticleId($article->getId());

		$form->setData('articleMedia', $articleMedia);
		return false;
	}


	/**
	 * Include sourceId field in the form
	 */
	function metadataReadUserVars($hookName, $params) {
		$userVars =& $params[1];
		$userVars[] = 'mediaItem';

		return false;
	}

	/**
	 * Hook into the submit form and save a media item
	 */
	function metadataExecute($hookName, $params) {
		$form =& $params[0];
		$article =& $form->article;
		$mediaItems = $form->getData('mediaItem');

		$this->import('ArticleMedia');
		$articleMediaDao = new ArticleMediaDAO($this->getName());

		$articleMediaDao->deleteArticleMediaByArticleId($article->getId());
		foreach($mediaItems as $key => $mediaItem) {
			if(!isset($mediaItem['sourceId']) || $key == '--IDNUM--') continue;
			$articleMedia =& new ArticleMedia();
			$articleMedia->setArticleId($article->getId());

			$metadataLabels = isset($mediaItem['metadataLabel']) ? $mediaItem['metadataLabel'] : null;
			$metadataValues = isset($mediaItem['metadataValue']) ? $mediaItem['metadataValue'] : null;
			$metadata = array();
			for ($i=0; $i < count($metadataLabels); $i++) {
				$metadata[$metadataLabels[$i]] = $metadataValues[$i];
			}
			$articleMedia->setMetadata($metadata);

			$articleMedia->setType((int) $mediaItem['mediaType']);
			$articleMedia->setSource($mediaItem['mediaSource']);
			$articleMedia->setUrl($mediaItem['sourceId']);
			$articleMedia->setId($key);
			$articleMediaDao->insertArticleMedia($articleMedia);
			unset($articleMedia);
		}

		return false;
	}

	/**
	 * Insert media into HTML galleys
	 */
	function insertMedia($hookName, $params) {
		$articleHTMLGalley =& $params[0];
		$contents =& $params[1];

		$this->articleId = $articleHTMLGalley->getArticleId();

		// Perform replacement for <articleMedia> tags
		$contents = preg_replace_callback(
			'/<[Aa][Rr][Tt][Ii][Cc][Ll][Ee][Mm][Ee][Dd][Ii][Aa]\ [Ii][Dd]="[0-9][0-9]*"(\ )*\/>/',
			array(&$this, '_handleMediaElement'),
			$contents
		);

		// Insert necessary JS and CSS files into the head
		$additionalScripts = '<script type="text/javascript" src="'.Request::getBaseUrl().'/plugins/generic/articleMedia/js/mediaGalley.js"></script>
			<script type="text/javascript" src="'.Request::getBaseUrl().'/plugins/generic/articleMedia/js/jquery.jplayer.js"></script>
			<link rel="stylesheet" type="text/css" href="'.Request::getBaseUrl().'/plugins/generic/articleMedia/styles/jplayer.blue.monday.css" />
			<link rel="stylesheet" type="text/css" href="'.Request::getBaseUrl().'/plugins/generic/articleMedia/styles/articleMedia.css" />';

		$contents = $additionalScripts . $contents;
	}


	/**
	 * Transform mediaElement tags to a media player
	 */
	function _handleMediaElement($match) {
		preg_match_all('!\d+!', $match[0], $id);
		$mediaId = (int) $id[0][0];
		if(empty($mediaId)) return false;

		$articleMediaDao =& DAORegistry::getDAO('ArticleMediaDAO');
		$articleMedia = $articleMediaDao->getArticleMedia($this->articleId, $mediaId);
		if(!isset($articleMedia)) return false;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleMediaItem', $articleMedia);
		$templateMgr->assign('hideInputs', true);
		$templateMgr->assign('getXmlUrl', $this->smartyPluginUrl(array('path' => 'getXmlData'), $templateMgr));

		$url = $articleMedia->getUrl();
		$type = $articleMedia->getType();
		//$templateMgr->assign('additionalJs', "$(function() { window.mediaGalley.initJplayer($mediaId, $type); window.mediaGalley.loadMediaItem($mediaId, '$url'); });");
		return $templateMgr->fetch($this->getTemplatePath() . 'mediaItemTemplate.tpl');
	}

	/**
	 * Retrieve XML from a remote source (not possible in JS-land due to cross-site exclusion)
	 */
	function getXmlData($url) {
		if(empty($url)) return false;

		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $retValue = curl_exec($ch);
        curl_close($ch);

        header('Content-type: application/xml');
		echo $retValue;
		exit();
	}
}

?>
