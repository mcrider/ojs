{**
 * templates/manager/sections/sectionCateogries.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of sections categories in journal management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.sectionCategories"}
{include file="common/header.tpl"}
{/strip}

<br/>

<div id="sections">
<table width="100%" class="listing" id="dragTable">
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="60%">{translate key="section.title"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
{foreach from=$sectionCategories item=sectionCategory key=categoryId}
	<tr valign="top" id="category-{$categoryId}" class="data">
		<td class="drag">{$sectionCategory.name|escape}</td>
		<td align="right" class="nowrap">
			<a href="{url op="editSectionCategory" path=$categoryId}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSectionCategory" path=$categoryId}" onclick="return confirm('{translate|escape:"jsparam" key="manager.sections.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
		</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.sections.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>

</table>
<a class="action" href="{url op="createSectionCategory"}">{translate key="manager.sectionCategories.create"}</a>
</div>

{include file="common/footer.tpl"}

