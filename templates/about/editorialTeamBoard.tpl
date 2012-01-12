{**
 * editorialTeam.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id: editorialTeamBoard.tpl,v 1.12 2008/06/11 18:55:18 asmecher Exp $
 *}
{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}

<!--
{foreach from=$groups item=group}
<h4>{$group->getGroupTitle()}</h4>
{assign var=groupId value=$group->getGroupId()}
{assign var=members value=$teamInfo[$groupId]}

{foreach from=$members item=member}
	{assign var=user value=$member->getUser()}
	<a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$user->getUserId()}')">{$user->getFullName()|escape}</a>{if $user->getAffiliation()}, {$user->getAffiliation()|escape}{/if}{if $user->getCountry()}{assign var=countryCode value=$user->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}
	<br />
{/foreach}
{/foreach}
-->

<h4>Senior Editors</h4>
 Bechara Saab, <em>Editor-in-Chief</em><br />
 Maya Boudiffa, <em>Features Editor</em><br /> 
 Dunja Damjanovic, <em>Managing Section Editor</em><br />
 Shu Ito, <em>Chief Technology Officer</em><br />
 Angela Kelsey, <em>Chief of Knowledge Translation</em><br />
 Ashlee Jollymore, <em>Chief of Marketing</em><br />
 Iacovos Michael, <em>Chief Business Officer</em><br />
 Jennifer Moore, <em>Copyeditor</em><br />
 Fiona Robinson, <em>Feature editor</em><br />
 Jeff Sharom, <em>Policy Editor</em><br />
 Laura Southcott, <em>Art Editor</em><br />
 Vanessa Tran, <em>Finance Editor</em><br />

 <h4>Associate Editors</h4>
 Laura Feldcamp<br />
 Ryan Fobel<br />
 Debbie Gordon<br />
 Takuro Ishikawa<br />
 Amber Juilfs<br />
 Martin Kahn<br />
 Oksana Kaidanovich Beilin<br />
 Lynn Kimlicka<br />
 Lukas Kus<br />
 Alexandra Laungerotte<br />
 Ellen Lu<br />
 Alexander McGirr<br />
 Safa Mohanna<br />
 Rosemary Oh-McGinnis<br />
 Ben Paylor<br />
 Lindsay Petley-Ragan<br />
 Justine Southby<br />
 Judit Takacs<br />
 Marcel van 't Hoff<br />
 Ryan Whitehead<br />
 Alec Witty<br />
 Andrea Wong<br />
 Joanna Yu<br />


{include file="common/footer.tpl"}
