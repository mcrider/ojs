{**
 * emailLog.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show journal email log page.
 *
 *}

{strip}
{assign var="pageTitle" value="submission.emailLog"}
{include file="common/header.tpl"}
{/strip}

<div id="emailLogEntries">
<h3>{translate key="submission.history.submissionEmailLog"}</h3>

<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="5">&nbsp;</td></tr>
	<tr valign="top" class="heading">
		<td width="7%">{translate key="common.date"}</td>
		<td width="25%">{translate key="email.sender"}</td>
		<td width="20%">{translate key="email.recipients"}</td>
		<td>{translate key="common.subject"}</td>
		<td width="60" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="5">&nbsp;</td></tr>
{iterate from=emailLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getDateSent()|date_format:$dateFormatShort}</td>
		<td>{$logEntry->getFrom()|truncate:40:"..."|escape}</td>
		<td>{$logEntry->getRecipients()|truncate:40:"..."|escape}</td>
		<td>{$logEntry->getSubject()|truncate:60:"..."|escape}</td>
		<td align="right"><a href="{url op="mailLog" path=$logEntry->getId()}" class="action">{translate key="common.view"}</a>&nbsp;|&nbsp;<a href="{url op="clearMailLog" path=$logEntry->getJournalId()|to_array:$logEntry->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmDeleteLogEntry"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="5" class="{if $emailLogEntries->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $emailLogEntries->wasEmpty()}
	<tr valign="top">
		<td colspan="5" class="nodata">{translate key="submission.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$emailLogEntries}</td>
		<td colspan="2" align="right">{page_links anchor="emailLogEntries" name="emailLogEntries" iterator=$emailLogEntries}</td>
	</tr>
{/if}
</table>

<a class="action" href="{url op="clearMailLog" path=$journalId}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmClearJournalLog"}')">{translate key="submission.history.clearLog"}</a>

</div>
{include file="common/footer.tpl"}

