<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

if (function_exists('script')) {
	script('ojsxc', 'settings/personal');
}
?>

<div class="section">
	<h2>JavaScript Xmpp Client</h2>
	<h3>Settings</h3>

	<div id="ojsxc-settings">
		<p>Launch chat upon login.</p>
		<p>
			<input type="radio" name="loginFormEnable" value="" class="radio" id="jsxc-loginFormEnable-default" <?php if($_[ 'loginForm'] === 'default') echo 'checked="checked"';?> />
			<label for="jsxc-loginFormEnable-default">
				Use global default
			</label>
		</p>
		<p>
			<input type="radio" name="loginFormEnable" value="true" class="radio" id="jsxc-loginFormEnable-enable" <?php if($_[ 'loginForm'] === 'enable') echo 'checked="checked"';?> />
			<label for="jsxc-loginFormEnable-enable">
				Enable
			</label>
		</p>
		<p>
			<input type="radio" name="loginFormEnable" value="false" class="radio" id="jsxc-loginFormEnable-disable" <?php if($_[ 'loginForm'] === 'disable') echo 'checked="checked"';?> />
			<label for="jsxc-loginFormEnable-disable">
				Disable
			</label>
		</p>
	</div>

	<?php if(isset($_['externalConnectable']) && $_['externalConnectable']): ?>
	<h3>Connection parameters</h3>

	<div>
		<p style="margin-bottom:1em;">If you like to use your XMPP account with a <a href="https://xmpp.org/software/clients.html" target="_blank">different client</a>, use <strong><?php p($_['jid']); ?></strong> as your Jabber ID (JID).</p>

		<?php if($_['allowToOverwriteXMPPConfig']): ?>
		<p style="margin-bottom:1em;">You want to use a different XMPP account on this Nextcloud instance? No problem. Just change the fields below.</p>

		<form id="ojsxc">
			<div class="form-group">
				<label>BOSH url</label>
				<div class="form-col">
					<input id="ojsxc-xmpp-bosh" type="text" readonly="readonly" value="<?php p($_['xmppUrl']); ?>" />
				</div>
			</div>

			<div class="form-group">
				<label>Username</label>
				<div class="form-col">
					<input id="ojsxc-xmpp-username" type="text" name="xmpp[username]" value="<?php p($_['xmppUsername']); ?>" pattern="[^&'/:<>@\s]+" />
				</div>
			</div>

			<div class="form-group">
				<label>Domain</label>
				<div class="form-col">
					<input id="ojsxc-xmpp-domain" type="text" name="xmpp[domain]" value="<?php p($_['xmppDomain']); ?>" pattern="[a-z0-9-_.]+" />
				</div>
			</div>

			<div class="form-group">
				<label>Resource</label>
				<div class="form-col">
					<input id="ojsxc-xmpp-resource" type="text" name="xmpp[resource]" value="<?php p($_['xmppResource']); ?>" />
				</div>
			</div>

			<div class="form-col-offset">
				<div class="msg"></div>

				<input type="submit" value="Save XMPP settings" />
			</div>
		</form>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>