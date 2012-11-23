{**
 * articleMetadataImport.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Interface for the JATS XML importer to be displayed on submission editing page
 *
 *}

<h3>{translate key="plugins.generic.articleMetadataImport.displayName"}</h3>

<form method="post" action="{$pluginUrl}" enctype="multipart/form-data">
	<input type="hidden" name="submissionId" value={$submissionId} />
	<input type="file" name="xmlFile" class="uploadField" /><input type="submit" value="{translate key='common.saveAndContinue'}" />
</form>
<br />
<div class="separator"></div>