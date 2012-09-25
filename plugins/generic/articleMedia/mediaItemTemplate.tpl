{**
 * plugins/generic/articleMedia/mediaWizard.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template for a media item
 *}

<script type='text/javascript'>{$additionalJs}</script>
{if $articleMediaItem}
	{assign var="mediaId" value=$articleMediaItem->getId()}
	{if $articleMediaItem->getType()}{assign var="mediaType" value=$articleMediaItem->getType()}{else}{assign var="mediaType" value=1}{/if}
	<table class="mediaItem{if $hideInputs} display{/if}" id="mediaItem_{$mediaId}">
		{if !$hideInputs} {** We hide inputs when using this template to display in a galley **}
		<tr>
			<td>
				<input type="hidden" class='itemId' value="{$mediaId}" />
				<input type="hidden" class='mediaType' value="{$mediaType}" name='mediaItem[{$mediaId}][mediaType]' />

				<div class="mediaSourceContainer mediaInput">
					<label for="mediaSource">{translate key="plugins.generic.articleMedia.source"}</label>
					<select name="mediaItem[{$mediaId}][mediaSource]" class="mediaSource selectMenu">
						<option value="other"{if $articleMediaItem->getSource() == 'other'} selected{/if}>{translate key="plugins.generic.articleMedia.other"}</option>
						<option value="youtube"{if $articleMediaItem->getSource() == 'youtube'} selected{/if}>{translate key="plugins.generic.articleMedia.youtube"}</option>
						<option value="euscreen">{translate key="plugins.generic.articleMedia.euscreen"}</option>
						<option value="openimages"{if $articleMediaItem->getSource() == 'openimages'} selected{/if}>{translate key="plugins.generic.articleMedia.openimages"}</option>
						<option value="soundcloud"{if $articleMediaItem->getSource() == 'soundcloud'} selected{/if}>{translate key="plugins.generic.articleMedia.soundcloud"}</option>
					</select>
				</div>

				<div class="sourceIdContainer mediaInput">
					<label for="sourceId">{translate key="plugins.generic.articleMedia.id"}</label>
					<input name="mediaItem[{$mediaId}][sourceId]" size="80" maxlength="250" class="sourceId" type="text" value="{$articleMediaItem->getUrl()|escape}" />
				</div>

				<div class="mediaInput">
					<input name="testItem" class="testItem" type="button" value="{translate key='plugins.generic.articleMedia.test'}" class="button" />
					<input name="deleteItem" class="deleteItem" type="button" value="{translate key='common.delete'}" class="button" />
				</div>
				<div style="clear: both;"></div>
			</td>
		</tr>
		{/if}
		<tr>
			<td>
				<!-- Display the player -->
				{if $articleMediaItem->getSource() == 'youtube'}
					<iframe class="mediaFrame" title="Youtube Video Player" width="480" height="270" src="http://www.youtube.com/embed/{$articleMediaItem->getUrl()}?fs=1&autoplay=false&loop=0" frameborder="0" allowfullscreen style="border: 1px solid black"></iframe>
				{elseif $articleMediaItem->getSource() == 'openimages'}
					<script type="text/javascript">
						$(function() {ldelim}
							$.ajax({ldelim}
								type: "GET",
								url: "{$getXmlUrl}",
								dataType: "xml",
								data: {ldelim}url: "http://www.openimages.eu/feeds/oai/?verb=GetRecord&identifier=oai:openimages.eu:"+{$articleMediaItem->getUrl()}+"&metadataPrefix=oai_dc"{rdelim},
								success: function(xmlString) {ldelim}
									var formats = [];
									var mediaAttribs = {ldelim}{rdelim};
									var formatAndType = null;
									$.each($(xmlString).find("format"), function(key, value) {ldelim}
										source = $(value).text();
										if(window.mediaGalley) {ldelim}
											formatAndType = window.mediaGalley.determineFormat(source);
										{rdelim}
										if(window.mediaWizard) {ldelim}
											formatAndType = window.mediaWizard.determineFormat(source);
										{rdelim}

										var format = formatAndType.format;
										if(formatAndType && $.inArray(format, formats) < 0) {ldelim}
											formats.push(format);
											mediaAttribs[format] = source;
										{rdelim}
									{rdelim});

									// Load the video
									if(window.mediaGalley) {ldelim}
										window.mediaGalley.initJplayer({$mediaId}, formats.join(', '));
									{rdelim}
									if(window.mediaWizard) {ldelim}
										window.mediaWizard.initJplayer({$mediaId}, formats.join(', '));
									{rdelim}


									$("#mediaItem_"+{$mediaId}).find('.jpContainer').show();
									$("#jquery_jplayer_"+{$mediaId}).jPlayer("setMedia", mediaAttribs).jPlayer('pause');
								{rdelim},
								error: function(xml) {ldelim}
									alert('Could not load data from OpenImages');
								{rdelim}
							{rdelim});
						{rdelim});
					</script>
					{include file="../plugins/generic/articleMedia/jplayerVideo.tpl" mediaId="$mediaId"}
				{elseif $articleMediaItem->getSource() == 'soundcloud'}
					<iframe width="100%" height="166" scrolling="no" frameborder="no" src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F{$articleMediaItem->getUrl()}&show_artwork=true"></iframe>
				{else}

					<script type="text/javascript">
						$(function() {ldelim}
							source = '{$articleMediaItem->getUrl()}';
							if(window.mediaGalley) {ldelim}
								formatAndType = window.mediaGalley.determineFormat(source);
								window.mediaGalley.initJplayer({$mediaId}, formatAndType.format);
							{rdelim}
							if(window.mediaWizard) {ldelim}
								formatAndType = window.mediaWizard.determineFormat(source);
								window.mediaWizard.initJplayer({$mediaId}, formatAndType.format);
							{rdelim}

							var format = formatAndType.format;
							var mediaAttribs = {ldelim}{rdelim};
							mediaAttribs[format] = source;

							$("#jquery_jplayer_"+{$mediaId}).jPlayer("setMedia", mediaAttribs).jPlayer('pause');
							$('.testItem').val('Load').removeAttr('disabled');
						{rdelim});
					</script>
					{if $articleMediaItem->getType() == 1} {** Video **}
						{include file="../plugins/generic/articleMedia/jplayerVideo.tpl" mediaId="$mediaId"}
					{else} {** Audio **}
						{include file="../plugins/generic/articleMedia/jplayerAudio.tpl" mediaId="$mediaId"}
					{/if}
				{/if}
			</td>
		</tr>
		<tr>
			<td>
				{if $hideInputs}
					{if $articleMediaItem->getMetadata()}<div class="hideMetadata close">{translate key="plugins.generic.articleMedia.toggleMetadata"}</div>{/if}
					<div class="metadataDisplayContainer">
						{foreach from=$articleMediaItem->getMetadata() key=metadataLabel item=metadataValue}
							<div class='metadataDisplay'>
								<span class='metadataDisplayLabel'>{$metadataLabel}</span>
								<br />
								<span class='metadataDisplayValue'>{$metadataValue}</span>
							</div>
							<br />
						{/foreach}
					</div>
					<div style="clear:both;"></div>
				{else}
					<div class="metadata" style="display:block;">
						<a class="addMetadataItem">{translate key="plugins.generic.articleMedia.addNewMetadataField"}</a>
						{if $articleMediaItem->getMetadata()}{foreach from=$articleMediaItem->getMetadata() key=metadataLabel item=metadataValue}
							{include file="../plugins/generic/articleMedia/metadataItemTemplate.tpl" label=$metadataLabel value=$metadataValue mediaId=$mediaId}
						{/foreach}{/if}
					</div>
					{translate key='plugins.generic.articleMedia.mediaIdNote' mediaId=$mediaId}
				{/if}
			</td>
		</tr>
	</table>
{elseif $mediaId}
	<table class="mediaItem" id="mediaItem_{$mediaId}">
		<tr><td colspan="2">
			<input type="hidden" class='itemId' value="{$mediaId}" />
			<input type="hidden" class='mediaType' value="1" name='mediaItem[{$mediaId}][mediaType]' />

			<div class="mediaSourceContainer mediaInput">
				<label for="mediaSource">{translate key="plugins.generic.articleMedia.source"}</label>
				<select name="mediaItem[{$mediaId}][mediaSource]" class="mediaSource" name="mediaItem[{$mediaId}][mediaSource]" class="selectMenu">
						<option value="other">{translate key="plugins.generic.articleMedia.other"}</option>
						<option value="youtube">{translate key="plugins.generic.articleMedia.youtube"}</option>
						<option value="euscreen">{translate key="plugins.generic.articleMedia.euscreen"}</option>
						<option value="openimages">{translate key="plugins.generic.articleMedia.openimages"}</option>
						<option value="soundcloud">{translate key="plugins.generic.articleMedia.soundcloud"}</option>
				</select>
			</div>

			<div class="sourceIdContainer mediaInput">
				<label for="sourceId">{translate key="plugins.generic.articleMedia.id"}</label>
				<input name="mediaItem[{$mediaId}][sourceId]" size="80" maxlength="250" class="sourceId" type="text" value="" />
			</div>

			<div class="mediaInput">
				<input name="testItem" class="testItem" type="button" value="{translate key='plugins.generic.articleMedia.test'}" class="button" />
				<input name="deleteItem" class="deleteItem" type="button" value="{translate key='common.delete'}" class="button" />
			</div>
		</td></tr>
		<tr><td class='mediaContainer jpContainer'>
			<div class="videoPlayer">
				{include file="../plugins/generic/articleMedia/jplayerVideo.tpl" mediaId="$mediaId"}
			</div>
			<div class='audioPlayer'>
				{include file="../plugins/generic/articleMedia/jplayerAudio.tpl" mediaId="$mediaId"}
			</div>
		</td>
	</tr>
		<tr><td>
			<div class="metadata">
				<a class="addMetadataItem">{translate key="plugins.generic.articleMedia.addNewMetadataField"}</a>
			</div>
		</td></tr>
	</table>
{/if}