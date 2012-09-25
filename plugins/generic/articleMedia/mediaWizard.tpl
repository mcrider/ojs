{**
 * plugins/generic/articleMedia/mediaWizard.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Media Wizard to be embedded into step 3 of the submission wizard
 *}
<script type="text/javascript">
	window.parseXmlUrl = "{$getXmlUrl}";
</script>
<script type="text/javascript" src="{$templatePath}/js/jquery.jplayer.js"></script>
<script type="text/javascript" src="{$templatePath}/js/mediaWizard.js"></script>
<link rel="stylesheet" type="text/css" href="{$templatePath}/styles/jplayer.blue.monday.css" />
<link rel="stylesheet" type="text/css" href="{$templatePath}/styles/articleMedia.css" />

<h3>{translate key='plugins.generic.articleMedia.wizard'}</h3>

<div id="mediaItems">
{if !$articleMedia || $articleMedia->wasEmpty()}
	{include file="../plugins/generic/articleMedia/mediaItemTemplate.tpl" mediaId=1}
{else}
{iterate from=articleMedia item=articleMediaItem}
	{include file="../plugins/generic/articleMedia/mediaItemTemplate.tpl" articleMediaItem=$articleMediaItem}
{/iterate}
{/if}
</div>
<a class="addItem">{translate key='plugins.generic.articleMedia.addNewMediaItem'}</a>
<div style="clear: both;"></div>
<br />

<div class="mediaItemTemplate">
	{include file="../plugins/generic/articleMedia/mediaItemTemplate.tpl" articleMediaItem=false mediaId="--IDNUM--"}
</div>
<div class="metadataItemTemplate">
	{include file="../plugins/generic/articleMedia/metadataItemTemplate.tpl" label='' value='' mediaId="--IDNUM--"}
</div>

<div class="separator"></div>