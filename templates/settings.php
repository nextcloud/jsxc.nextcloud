<div class="section">
	<h2>JavaScript Xmpp Client</h2>
	<form id="ojsxc">
		<div class="form-group">
			<input type="radio" name="serverType" id="serverTypeInternal" required="required" value="internal" <?php if($_['serverType'] === 'internal')echo 'checked'; ?> />
			<label for="serverTypeInternal">Internal (Experimental)</label>
			<em>Limited functionality only: No clients besides JSXC in ownCloud, no multi-user chat, no server-to-server federations.</em>
		</div>
		<div class="form-group">
			<input type="radio" name="serverType" id="serverTypeExternal" class="required" required="required" value="external" <?php if($_['serverType'] === 'external')echo 'checked'; ?> />
			<label for="serverTypeExternal">External</label>
			<em>Choose this option to use your own XMPP server.</em>
		</div>
		
		<div class="ojsxc-internal hidden">

		</div>
		
		<div class="ojsxc-external hidden">
			<div class="form-group">
				<label for="xmppDomain">* XMPP domain</label>
				<input type="text" name="xmppDomain" id="xmppDomain" class="required" required="required" value="<?php p($_['xmppDomain']); ?>" />
			</div>
			<div class="form-group">
				<label for="xmppPreferMail">Prefer mail address to loginName@xmppDomain</label>
				<input type="checkbox" name="xmppPreferMail" id="xmppPreferMail" value="true" <?php if($_['xmppPreferMail'] === 'true' || $_['xmppPreferMail'] === true) echo "checked"; ?> />
			</div>
			<div class="form-group">
				<label for="boshUrl">* BOSH url</label>
				<input type="text" name="boshUrl" id="boshUrl" class="required" required="required" value="<?php p($_['boshUrl']); ?>" />
				<div class="boshUrl-msg"></div>
			</div>
			<div class="form-group">
				<label for="xmppResource">XMPP resource</label>
				<input type="text" name="xmppResource" id="xmppResource" value="<?php p($_['xmppResource']); ?>" />
			</div>
			<div class="form-group">
				<label for="xmppOverwrite">Allow user to overwrite XMPP settings</label>
				<input type="checkbox" name="xmppOverwrite" id="xmppOverwrite" value="true" <?php if($_['xmppOverwrite'] === 'true' || $_['xmppOverwrite'] === true) echo "checked"; ?> />
			</div>
		</div>
		
		<div class="form-group">
			<label for="xmppStartMinimized">Hide roster after first login</label>
			<input type="checkbox" name="xmppStartMinimized" id="xmppStartMinimized" value="true" <?php if($_['xmppStartMinimized'] === 'true' || $_['xmppStartMinimized'] === true) echo "checked"; ?> />
		</div>
		<div class="form-group">
			<label for="iceUrl">TURN Url</label>
			<input type="text" name="iceUrl" id="iceUrl" value="<?php p($_['iceUrl']); ?>" />
		</div>
		<div class="form-group">
			<label for="iceUsername">TURN Username</label>
			<input type="text" name="iceUsername" id="iceUrl" value="<?php p($_['iceUsername']); ?>" />
			<em>If no username is set, TURN-REST-API credentials are used.</em>
		</div>
		<div class="form-group">
			<label for="iceCredential">TURN Credential</label>
			<input type="text" name="iceCredential" id="iceCredential" value="<?php p($_['iceCredential']); ?>" />
			<em>If no password is set, TURN-REST-API credentials are used.</em>
		</div>
		<div class="form-group">
			<label for="iceSecret">TURN Secret</label>
			<input type="text" name="iceSecret" id="iceSecret" value="<?php p($_['iceSecret']); ?>" />
			<em>Secret for TURN-REST-API credentials as described <a href="http://tools.ietf.org/html/draft-uberti-behave-turn-rest-00" target="_blank">here</a>.</em>
		</div>
		<div class="form-group">
			<label for="iceTtl">TURN TTL</label>
			<input type="text" name="iceTtl" id="iceTtl" value="<?php p($_['iceTtl']); ?>" />
			<em>Lifetime for TURN-REST-API credentials in seconds.</em>
		</div>
		
		<div class="form-offset-label">
			<div class="msg"></div>
			
			<input type="submit" value="Save settings" />
		</div>
	</form>
</div>
