{**
 * plugins/generic/articleMedia/metadataItemTemplate.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template for a metadata item for the media wizard
 *}
<div class="metadataItem">
	<div class="mediaInput">
		<div class="metadataLabelContainer">
			<span>{translate key="plugins.generic.articleMedia.label"}</span><br />
			<input name="mediaItem[{$mediaId}][metadataLabel][]" size="80" maxlength="250" class="metadataLabel" type="text" value="{$label|escape}" />
		</div>
		<div class="metadataValueContainer">
			<span for="metadataValue">{translate key="plugins.generic.articleMedia.value"}</span><br />
			<textarea rows="2" cols="50" name="mediaItem[{$mediaId}][metadataValue][]" size="80" maxlength="250" class="metadataValue" type="text">{$value|strip_unsafe_html}</textarea>
		</div>
		<input class="deleteMetadata" type="button" value="{translate key='common.delete'}" class="button" />
	</div>
</div>