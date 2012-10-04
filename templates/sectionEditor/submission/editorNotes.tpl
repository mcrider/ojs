{**
 * templates/sectionEditor/submission/editorNotes.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining shared notes for editors.
 *}
<div id="editorNotesContainer">
<h3>{translate key="editor.article.notes"}</h3>
<br />
<form method="post" action="{url op='saveEditorNotes'}">
<input type="hidden" name="articleId" value="{$submission->getId()|escape}" />
<input type="hidden" name="redirectTo" value="{$redirectTo|escape}" />
<table width="100%" class="data">
	<tr valign="top">
		<td class="value">
			<textarea name="editorNotes[{$formLocale|escape}]" class="textArea" id="editorNotes" rows="12" cols="100">{$submission->getEditorNotes($formLocale)|escape}</textarea><br/>
			<span class="instruct">{translate key="editor.article.notes.description"}</span>
			<input type="submit" id="saveEditorNotes" class="button defaultButton" value="{translate key='common.save'}" />
		</td>
	</tr>
</table>
</form>
</div>

