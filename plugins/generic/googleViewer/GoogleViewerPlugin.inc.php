<?php

/**
 * @file plugins/generic/googleViewer/GoogleViewerPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GoogleViewerPlugin
 *
 * @brief This plugin enables embedding of the google document viewer for PDF display
 */

import('classes.plugins.GenericPlugin');

class GoogleViewerPlugin extends GenericPlugin {
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// Add custom locale data for all locale files registered after this plugin
				HookRegistry::register('Article::viewPDF', array(&$this, '_callback'));
			}

			return true;
		}
		return false;
	}

	function getDisplayName() {
		return __('plugins.generic.googleViewer.name');
	}

	function getDescription() {
		return __('plugins.generic.googleViewer.description');
	}

	function _callback($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty =& $params[1];

			$submission =& $smarty->get_template_vars('article'); /* @var $submission Article */

			$output =& $params[2];
			$output .= $smarty->fetch($this->getTemplatePath() . 'index.tpl');
		}
		return true;
	}
}

?>
