<?php

/**
 * @file plugins/generic/alm/AlmPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AlmPlugin
 * @ingroup plugins_generic_alm
 *
 * @brief Alm plugin class
 */

define('ALM_URL', 'http://url.com/api.php');

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.webservice.XmlWebService');

class AlmPlugin extends GenericPlugin {
	var $caches;

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		$this->addLocaleData();
		if ($success) {
			// Insert Alm page tag to article footer
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'AlmPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.alm.displayName');
	}

	function getDescription() {
		return __('plugins.generic.alm.description');
	}

	/**
	 * Insert Alm page tag to footer
	 */
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = &$params[1];
			$output = &$params[2];
			
			$article = $smarty->get_template_vars('article');
			
			$cache =& $this->_getCache('alm');
			$resultXml = $cache->get($article->getId());

			$smarty->assign('resultXml', $resultXml);
			$smarty->display($this->getTemplatePath() . 'output.tpl');
		}
		return false;
	}

	function _cacheMiss(&$cache, $id) {
		// Construct the parameters to send to the web service
		$searchParams = array(
			'query1' => 'term1',
			'query2' => 'term2'
		);

		// Call the web service (URL defined at top of this file)
		$resultXml =& $this->callWebService(ALM_URL, $searchParams);

		$cache->setCache($id, $resultXml);
		return $resultXml;
	}

	function &_getCache($cacheId) {
		if (!isset($this->caches)) $this->caches = array();
		if (!isset($this->caches[$cacheId])) {
			$cacheManager =& CacheManager::getManager();
			$this->caches[$cacheId] =& $cacheManager->getObjectCache('alm', $cacheId, array(&$this, '_cacheMiss'));
		}
		return $this->caches[$cacheId];
	}

	/**
	 * Call web service with the given parameters
	 * @param $params array GET or POST parameters
	 * @return DOMDocument or null in case of error
	 */
	function &callWebService($url, &$params, $returnType = XSL_TRANSFORMER_DOCTYPE_DOM, $method = 'GET') {
		// Create a request
		$webServiceRequest = new WebServiceRequest($url, $params, $method);

		// Configure and call the web service
		$xmlWebService = new XmlWebService();
		$xmlWebService->setReturnType($returnType);
		$result =& $xmlWebService->call($webServiceRequest);

		return $result;
	}
}
?>
