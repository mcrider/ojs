<?php

/**
 * @defgroup plugins_reports_journalStats
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for journal stats report plugin.
 *
 * @ingroup plugins_reports_journalStats
 */

require_once('JournalStatsPlugin.inc.php');

return new JournalStatsPlugin();

?>
