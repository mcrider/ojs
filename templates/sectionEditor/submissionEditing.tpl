{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission editing.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.editing" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.editing"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>{/if}
	<li class="current"><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

{include file="sectionEditor/submission/summary.tpl"}

{if $currentJournal->getId() == 1}
<div class="separator"></div>

{** Coaction customization to send an 'article accepted' email **}
<div id="msAccepted">
	<h3>{translate key="editor.article.acceptEmail"}</h3>
	{url|assign:"url" op="sendAcceptanceEmail" articleId=$submission->getId()}
	{icon name="mail" url=$url}
	{if $msAcceptedLogEntry}{translate key="editor.article.acceptEmailSent"} {$msAcceptedLogEntry->getDateSent()|date_format:$dateFormatShort}
	{else}{translate key="editor.article.acceptEmailDescription"}{/if}
</div>
{/if}

<div class="separator"></div>

{include file="sectionEditor/submission/copyedit.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/scheduling.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/layout.tpl"}

<div class="separator"></div>

{include file="sectionEditor/submission/proofread.tpl"}

{if $currentJournal->getId() == 1}
<div class="separator"></div>

{** Coaction customization to send an 'article published' email **}
<div id="msPublished">
	<h3>{translate key="editor.article.publishedEmail"}</h3>
	{url|assign:"url" op="sendPublishedEmail" articleId=$submission->getId()}
	{icon name="mail" url=$url}
	{if $msPublishedLogEntry}{translate key="editor.article.publishedEmailSent"} {$msPublishedLogEntry->getDateSent()|date_format:$dateFormatShort}
	{else}{translate key="editor.article.publishedEmailDescription"}{/if}
</div>
{/if}

{include file="common/footer.tpl"}

