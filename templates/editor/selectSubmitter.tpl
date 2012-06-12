{**
 * selectSubmitter.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List authors not assigned to a submission and allow them to be assigned
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value=$roleName|concat:"s"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="editor.article.selectEditor" roleName=$roleName|translate}</h3>

<form name="submit" method="post" action="{url op="assignSubmitter" articleId=$articleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op="assignSubmitter" path=$articleId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="assignSubmitter" articleId=$articleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<div id="submitters">
<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr valign="bottom">
	<td class="heading" width="20%">{translate key="user.username"}</td>
	<td class="heading" width="60%">{translate key="user.name"}</td>
	<td class="heading" width="10%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=submitters item=submitter}
{assign var=submitterId value=$submitter->getId()}
<tr valign="top">
	<td>{$submitter->getUsername()|escape}</a></td>
	<td><a class="action" href="{url op="userProfile" path=$submitterId}">{$submitter->getFullName()|escape}</a></td>
	<td><a class="action" href="{url op="assignSubmitter" articleId=$articleId submitterId=$submitterId}">{translate key="common.assign"}</a></td>
</tr>
<tr><td colspan="5" class="{if $submitters->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $submitters->wasEmpty()}
<tr>
<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
<tr><td colspan="5" class="{if $submitters->eof()}end{/if}separator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$submitters}</td>
		<td colspan="3" align="right">{page_links anchor="submitters" name="submitters" iterator=$submitters searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth articleId=$articleId}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}