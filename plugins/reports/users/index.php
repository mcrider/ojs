<?php

/**
 * @defgroup plugins_reports_users
 */
 
/**
 * @file plugins/reports/userss/index.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_users
 * @brief Wrapper for users report plugin.
 *
 */

require_once('UsersReportPlugin.inc.php');

return new UsersReportPlugin();

?>
