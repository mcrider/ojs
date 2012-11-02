<?php 

/**
 * @defgroup plugins
 */
 
/**
 * @file plugins/paymethod/upay/index.php
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins
 * @brief Wrapper for uPay plugin.
 */
 
require_once('UPayPlugin.inc.php'); 
return new UPayPlugin();
 
?> 
