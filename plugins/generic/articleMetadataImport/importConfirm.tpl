{**
 * articleMetadataImport.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Interface for the JATS XML importer to be displayed on submission editing page
 *
 *}
{assign var="pageTitle" value="plugins.generic.articleMetadataImport.confirmTitle"}
{include file="common/header.tpl"}


<p>{translate key="plugins.generic.articleMetadataImport.importConfirmMessage"}</p>
<form name="saveMetadata" method="post" action="{$pluginUrl}">
	<table width="100%" class="data">
	{foreach from=$metadata item=field name=metadata key=fieldName}
		{if $fieldName == 'articleTitle'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="common.title"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="articleTitle" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='transTitle'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="plugins.generic.articleMetadataImport.titleTranslated"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="transTitle" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='abstract'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="article.abstract"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="abstract" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='transAbstract'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="plugins.generic.articleMetadataImport.titleTranslated"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="transAbstract" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='doi'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="metadata.property.displayName.doi"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="doi" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='keywords'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="common.keywords"}</td>
				<td width="80%" class="value">
					{foreach from=$field item=keywords key=locale}
						<input type="hidden" name="keywords[{$locale}]" value="{$keywords|escape}" />
						<span>{$locale|escape|default:'en_US'}: {$keywords|escape|default:'–'}</span>
						<br />
					{/foreach}
				</td>
			</tr>
		{elseif $fieldName=='language'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="article.language"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="language" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='agencies'}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.supportingAgencies"}</td>
				<td width="80%" class="value">
					<input type="hidden" name="agencies" value="{$field|escape}" />
					<span>{$field|escape|default:'–'}</span>
				</td>
			</tr>
		{elseif $fieldName=='authors'}
			{** Display authors.  If author is found show 'Matched to author in OJS' else throw a warning message' **}
			{foreach from=$field item=author name=authorLoop}
			<tr valign="top">
				<td width="20%" class="label">Author {$smarty.foreach.authorLoop.iteration}</td>
				<td width="80%" class="value">
					{assign var='authorId' value=$author.authorId}

					{if !$authorId}<span style="font-weight: bold; font-size: .8em;">{translate key="plugins.generic.articleMetadataImport.authorWarning"}</span>{/if}

					<input type="hidden" name="authors[{$authorId}][firstname]" value="{$author.firstname|escape}" />
					<input type="hidden" name="authors[{$authorId}][lastname]" value="{$author.lastname|escape}" />
					<input type="hidden" name="authors[{$authorId}][affiliation]" value="{$author.affiliation|escape}" />
					<input type="hidden" name="authors[{$authorId}][primary]" value="{$author.primary|escape}" />

					<div>{translate key="user.name"}: {$author.firstname|escape} {$author.lastname|escape}</div>
					<div>{translate key="user.affiliation"}: {$author.affiliation|escape|default:'–'}</div>
					<div>{translate key="plugins.generic.articleMetadataImport.isPrimary"}: {if $author.primary}Yes{else}No{/if}</div>
				</td>
			</tr>
			{/foreach}
		{/if}

	{/foreach}
	</table>

	<input type="hidden" name="submissionId" value={$submissionId} />
	<input type="hidden" name="saving" value=1 />
	<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

</form>



{include file="common/footer.tpl"}
