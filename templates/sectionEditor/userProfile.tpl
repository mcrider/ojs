{**
 * userProfile.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display user profile.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="user.profile"}: {$user->getFullName()|escape}</h3>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{translate key="user.salutation"}:</td>
	<td width="80%" class="value">{$user->getSalutation()|escape}</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="user.username"}:</td>
	<td width="80%" class="value">{$user->getUsername()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.firstName"}:</td>
	<td class="value">{$user->getFirstName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.middleName"}:</td>
	<td class="value">{$user->getMiddleName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.lastName"}:</td>
	<td class="value">{$user->getLastName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.gender"}</td>
	<td class="value">
		{if $user->getGender() == "M"}{translate key="user.masculine"}
		{elseif $user->getGender() == "F"}{translate key="user.feminine"}
		{elseif $user->getGender() == "O"}{translate key="user.other"}
		{else}&mdash;
		{/if}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.affiliation"}:</td>
	<td class="value">{$user->getLocalizedAffiliation()|escape|nl2br}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.signature"}:</td>
	<td class="value">{$user->getLocalizedSignature()|escape|nl2br}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.email"}:</td>
	<td class="value">
		{$user->getEmail()|escape}
		{assign var=emailString value=$user->getFullName()|concat:" <":$user->getEmail():">"}
		{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
		{icon name="mail" url=$url}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.url"}:</td>
	<td class="value"><a href="{$user->getUrl()|escape:"quotes"}">{$user->getUrl()|escape}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.phone"}:</td>
	<td class="value">{$user->getPhone()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.fax"}:</td>
	<td class="value">{$user->getFax()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.interests"}:</td>
	<td class="value">{$userInterests|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.gossip"}:</td>
	<td class="value">{$user->getLocalizedGossip()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.mailingAddress"}:</td>
	<td class="value">{$user->getMailingAddress()|strip_unsafe_html|nl2br}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.biography"}:</td>
	<td class="value">{$user->getLocalizedBiography()|strip_unsafe_html|nl2br}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.workingLanguages"}:</td>
	<td class="value">{foreach name=workingLanguages from=$user->getLocales() item=localeKey}{$localeNames.$localeKey|escape}{if !$smarty.foreach.workingLanguages.last}; {/if}{/foreach}</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.dateRegistered"}:</td>
	<td class="value">{$user->getDateRegistered()|date_format:$datetimeFormatLong}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.dateLastLogin"}:</td>
	<td class="value">{$user->getDateLastLogin()|date_format:$datetimeFormatLong}</td>
</tr>
</table>

<br /><br />
<h4>Review History</h4>
<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td>&nbsp;</td>
		<td>Article</td>
		<td>Assigned</td>
		<td>Confirmed</td>
		<td>Completed</td>
		<td>Recommendation</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
{foreach name=reviewAssignments from=$reviewAssignments key=reviewAssignmentNum item=reviewAssignment}
	<tr>
		<td>{$reviewAssignmentNum+1}.</td>
		<td><a href="{url op='submissionReview' path=$reviewAssignment->getSubmissionId()}" class="action">{$reviewAssignment->getLocalizedSubmissionTitle()|strip_tags|truncate:40:"..."}</a></td>
		<td>{$reviewAssignment->getDateAssigned()}</td>
		<td>{if $reviewAssignment->getCancelled()}Cancelled
			{elseif $reviewAssignment->getDeclined()}Declined
			{elseif $reviewAssignment->getDateConfirmed()}{$reviewAssignment->getDateConfirmed()}
			{else}&mdash;{/if}</td>
		<td>{if $reviewAssignment->getDateCompleted()}{$reviewAssignment->getDateCompleted()}
			{else}&mdash;{/if}</td>
		<td>{if $reviewAssignment->getRecommendation()}
				{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
				{translate key=$reviewerRecommendationOptions.$recommendation}
				<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$reviewAssignment->getSubmissionId()|to_array:$reviewAssignment->getId()}');" class="icon">{icon name="comment"}</a>
			{else}&mdash;{/if}
	</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}

