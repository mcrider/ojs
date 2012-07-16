{**
 * templates/manager/sections/sectionCategoryForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a journal section category.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.sectionCategories"}
{assign var="pageCrumbTitle" value="manager.sectionCategories"}
{include file="common/header.tpl"}
{/strip}

<form id="sectionCategory" method="post" action="{url op="updateSectionCategory" path=$sectionCategoryId}">

{include file="common/formErrors.tpl"}

<div id="sectionForm">
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="categoryName" required="true" key="manager.sectionCategories.name"}</td>
	<td width="80%" class="value"><input type="text" name="categoryName" value="{$categoryName}" id="categoryName" size="80" maxlength="200" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="sectionCategories" escape=false}'" /></p>

</form>

{include file="common/footer.tpl"}

