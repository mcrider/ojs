<?php

/**
 * @file classes/tasks/ClearCache.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ClearCache
 * @ingroup tasks
 *
 * @brief Class to perform automated cleanup of outdated cache files.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class ClearCache extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ClearCache() {
		$this->ScheduledTask();
	}

	function execute() {
		$path = dirname(INDEX_FILE_LOCATION) . '/cache';

		exec('find ' . escapeshellarg($path) . ' -maxdepth 1 -mmin +60 -type f -name "*.html" -exec rm -f {} \;');
	}
}

?>
