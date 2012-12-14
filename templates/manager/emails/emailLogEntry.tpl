{**
 * emailLogEntry.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a single email log entry.
 *
 *}
{strip}
{assign var="pageTitle" value="submission.emailLog"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="submission.history.submissionEmailLog"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.id"}</td>
		<td width="80%" class="value">{$logEntry->getId()}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.date"}</td>
		<td class="value">{$logEntry->getDateSent()|date_format:$datetimeFormatLong}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.sender"}</td>
		<td class="value">
			{if $logEntry->getSenderFullName()}
				{assign var=emailString value=$logEntry->getSenderFullName()|concat:" <":$logEntry->getSenderEmail():">"}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$logEntry->getSubject()}
				{$logEntry->getSenderFullName()|escape} {icon name="mail" url=$url}
			{else}
				{translate key="common.notApplicable"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.from"}</td>
		<td class="value">{$logEntry->getFrom()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.to"}</td>
		<td class="value">{$logEntry->getRecipients()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.cc"}</td>
		<td class="value">{$logEntry->getCcs()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.bcc"}</td>
		<td class="value">{$logEntry->getBccs()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.subject"}</td>
		<td class="value">{$logEntry->getSubject()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="email.body"}</td>
		<td class="value">{$logEntry->getBody()|escape|nl2br}</td>
	</tr>
</table>

<a href="{url op="clearEmailLog" path=$submission->getArticleId()|to_array:$logEntry->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmDeleteLogEntry"}')" class="action">{translate key="submission.email.deleteLogEntry"}</a><br/>

<a href="{url op="mailLog"}" class="action">{translate key="submission.email.backToEmailLog"}</a>

{include file="common/footer.tpl"}

