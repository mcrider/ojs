{**
 * templates/manager/plugins/reportFilter.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a form to select which columns to display in a report
 *}

{strip}
{assign var="pageTitle" value="manager.plugins.reports.title"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="manager.plugins.reports.filterDescription"}</p>

<form action="" method="post">
<input type="hidden" name="generateReport" value="true" />
{foreach from=$columns item=column key=columnKey}
	<input type="checkbox" name="{$columnKey|escape}" checked="checked" value="{$thisJournalId|escape}" /> 
	<label for="{$columnKey|escape}">{$column|escape}</label><br/>
{/foreach}

<p><input type="submit" value="{translate key='manager.plugins.reports.generate'}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page='manager' op='statistics'}'" /></p>

</form>

{include file="common/footer.tpl"}

