<?php

/**
 * @file plugins/generic/timedView/TimedViewReportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class TimedViewReportPlugin
 * @ingroup plugins_reports_timedView
 *
 * @brief Timed View report plugin
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class TimedViewPlugin extends GenericPlugin {
	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.timedView.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.timedView.description');
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);

		if($success) {
			if($this->getEnabled()) {
				$this->import('TimedViewReportDAO');
				$timedViewReportDAO =& new TimedViewReportDAO();
				DAORegistry::registerDAO('TimedViewReportDAO', $timedViewReportDAO);

				$this->import('TimedViewReportForm');

				$this->addLocaleData();
				HookRegistry::register('ArticleHandler::incrementAbstractViewCount', array(&$this, 'incrementAbstractViewCount'));
				HookRegistry::register('ArticleHandler::incrementGalleyViewCount', array(&$this, 'incrementGalleyViewCount'));
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			}
		}
		return $success;
	}

	/**
	 * Register as a report plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a report plugin.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'reports':
				$this->import('TimedViewReportPlugin');
				$reportPlugin = new TimedViewReportPlugin($this->getName());
				$plugins[$reportPlugin->getSeq()][$reportPlugin->getPluginPath()] =& $reportPlugin;
				break;
		}
		return false;
	}

	function incrementAbstractViewCount($hookName, $args) {
		if (!$this->getEnabled()) return false;
		$article =& $args[0];

		$ip = Request::getRemoteAddr();
		$userAgent = Request::getUserAgent();

		$timedViewReportDAO =& DAORegistry::getDAO('TimedViewReportDAO');
		$timedViewReportDAO->incrementViewCount($article->getJournalId(), $article->getId(), null, $ip, $userAgent);

		// Also increment view count in the regular location
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->incrementViewsByArticleId($article->getId());

		return true;
	}

	function incrementGalleyViewCount($hookName, $args) {
		if (!$this->getEnabled()) return false;
		$galley =& $args[0];

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($galley->getArticleId());

		$ip = Request::getRemoteAddr();
		$userAgent = Request::getUserAgent();

		$timedViewReportDAO =& DAORegistry::getDAO('TimedViewReportDAO');
		$timedViewReportDAO->incrementViewCount($article->getJournalId(), $article->getId(), $galley->getId(), $ip, $userAgent);

		// Also increment view count in the regular location
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galleyDao->incrementViews($galley->getId());

		return true;
	}
}

?>
