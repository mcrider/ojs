<?php

/**
 * @defgroup plugins_generic_articleMetadataImport
 */

/**
 * @file plugins/generic/articleMetadataImport/index.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_articleMetadataImport
 * @brief Wrapper for Article XML Metadata Import plugin.
 *
 */
require_once('ArticleMetadataImportPlugin.inc.php');

return new ArticleMetadataImportPlugin();

?>
