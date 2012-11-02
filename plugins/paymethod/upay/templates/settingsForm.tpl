{**
 * plugins/paymethod/upay/templates/settingsForm.tpl
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for uPay settings.
 *}
	<tr>
		<td colspan="2"><h4>{translate key="plugins.paymethod.upay.settings"}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="upayurl" required="true" key="plugins.paymethod.upay.settings.upayurl"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="upayurl" id="upayurl" size="50" value="{$upayurl|escape}" /><br/>
			{translate key="plugins.paymethod.upay.settings.upayurl.description"}<br/>
			&nbsp;
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="siteId" required="true" key="plugins.paymethod.upay.settings.siteId"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="siteId" id="siteId" value="{$siteId|escape}" /><br/>
			{**translate key="plugins.paymethod.upay.settings.selleraccount.description"**}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="transId" required="true" key="plugins.paymethod.upay.settings.transId"}</td>
		<td class="value" width="80%">
			<input type="text" class="textField" name="transId" id="transId" value="{$transId|escape}" /><br/>
			{**translate key="plugins.paymethod.upay.settings.selleraccount.description"**}
		</td>
	</tr>
	{if !$isCurlInstalled}
		<tr>
			<td colspan="2">
				<span class="instruct">{translate key="plugins.paymethod.upay.settings.curlNotInstalled"}</span>
			</td>
		</tr>
	{/if}
