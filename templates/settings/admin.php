<?php
global $params;
$params = $_;

function printTextInput($key, $required=true, $type='text') {
	global $params;

	$name = $params[$key]['name'];
	$value = $params[$key]['value'];
	$requiredAttrs = $required ? 'class="required" required="required"' : '';

	echo "<input type=\"$type\" name=\"$name\" id=\"$key\" value=\"$value\" $requiredAttrs />";
}

function printCheckboxInput($key, $value='1') {
	global $params;

	$name = $params[$key]['name'];
	$storedValue = $params[$key]['value'];
	$checked = $value === $storedValue ? 'checked="checked"' : '';

	echo "<input type=\"checkbox\" name=\"$name\" id=\"$key\" value=\"$value\" $checked />";
}

function printRadioInput($key, $value, $required=true) {
	global $params;

	$name = $params[$key]['name'];
	$storedValue = $params[$key]['value'];
	$requiredAttrs = $required ? 'class="required" required="required"' : '';
	$checked = $value === $storedValue ? 'checked="checked"' : '';

	echo "<input type=\"radio\" name=\"$name\" value=\"$value\" $checked $requiredAttrs />";
}
?>
<div class="section">
	<h2>JavaScript Xmpp Client</h2>
	<form id="ojsxc-admin" class="ojsxc">
		<h3>Server type</h3>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<?php printRadioInput('serverType', 'internal'); ?>
				Internal
			</label>
			<em>Limited functionality only: No clients besides JSXC in Nextcloud, no multi-user chat,
				no server-to-server federations.</em>
		</div>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<?php printRadioInput('serverType', 'external'); ?>
				External
			</label>
			<em>Choose this option to use your own XMPP server.</em>
		</div>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<?php printRadioInput('serverType', 'managed'); ?>
				Managed (Beta service)
			</label>
			<em>Get your own full featured XMPP server directly hosted by the core team of JSXC.
				For more information visit <a target="_blank" href="https://jsxc.ch/managed">jsxc.ch/managed</a>.</em>
		</div>

		<fieldset>
			<h3>Basic</h3>
			<div class="ojsxc-internal hidden">

			</div>

			<div class="ojsxc-external hidden">
				<div class="form-group">
					<label for="xmppDomain">* XMPP domain</label>
					<div class="form-col">
						<?php printTextInput('xmppDomain'); ?>
					</div>
				</div>
				<div class="form-group">
					<label for="xmppPreferMail">Prefer mail address to loginName@xmppDomain</label>
					<?php printCheckboxInput('xmppPreferMail'); ?>
				</div>
				<div class="form-group">
					<label for="boshUrl">* BOSH url</label>
					<div class="form-col">
						<?php printTextInput('boshUrl'); ?>
						<div class="boshUrl-msg"></div>
					</div>
				</div>
				<div class="form-group">
					<label for="xmppResource">XMPP resource</label>
					<div class="form-col">
						<?php printTextInput('xmppResource', false); ?>
					</div>
				</div>
				<div class="form-group">
					<label for="xmppOverwrite">Allow user to overwrite XMPP settings</label>
					<div class="form-col">
						<?php printCheckboxInput('xmppOverwrite'); ?>
					</div>
				</div>
			</div>

			<div class="form-group">
				<label for="xmppStartMinimized">Hide roster after first login</label>
				<div class="form-col">
					<?php printCheckboxInput('xmppStartMinimized'); ?>
				</div>
			</div>

			<div class="form-group">
				<label for="loginFormEnable">Enable chat on log in</label>
				<div class="form-col">
					<?php printCheckboxInput('loginFormEnable'); ?>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<div class="ojsxc-managed hidden">
				<h3>Registration</h3>
				<?php if($_['managedServer'] === 'registered'): ?>
					<div class="msg jsxc_success">
						<p>Congratulations! You use our managed server. If you like to connect with a different client,
						use <strong><?php p($_['xmppDomain']); ?></strong> as your XMPP domain.
						You can also <a href="#" class="ojsxc-refresh-registration">redo the registration</a> if you like.</p>
					</div>
				<?php else: ?>
					<div class="msg"></div>
				<?php endif; ?>

				<div class="ojsxc-managed-registration <?php if($_['managedServer'] === 'registered'){echo 'hidden';} ?>">
					<p class="text">In order to create a managed XMPP server for you, we will send the following information to
						our registration server. The set-up process will take about 20-30 seconds.</p>

					<div class="form-group">
						<label>API URL</label>
						<div class="form-col">
							<input id="ojsxc-managed-api-url" type="text" readonly="readonly" value="<?php p($_['apiUrl']); ?>" />
						</div>
					</div>
					<div class="form-group">
						<label>Secure API token</label>
						<div class="form-col">
							<input id="ojsxc-managed-api-secret" type="text" readonly="readonly" value="<?php p($_['apiSecret']['value']); ?>" />
						</div>
					</div>
					<div class="form-group">
						<label>Your user id</label>
						<div class="form-col">
							<input id="ojsxc-managed-user-id" type="text" readonly="readonly" value="<?php p($_['userId']); ?>" />
						</div>
					</div>
					<div class="form-group">
						<label for="ojsxc-managed-promotion-code">Promotion code (if any)</label>
						<div class="form-col">
							<input id="ojsxc-managed-promotion-code" type="text" pattern="[a-zA-Z0-9]+" />
						</div>
					</div>
					<div class="form-col-offset">
						<label><input id="ojsxc-legal" type="checkbox" /> I acknowledge that this is a beta service offered
						by the <a target="_blank" href="https://jsxc.ch/federated-communication-association">Federated Communication
						Association</a> without any warranty whatsoever.</label>
					</div>
					<div class="form-col-offset">
						<label><input id="ojsxc-dp" type="checkbox" /> I have read and accept
						the <a target="_blank" href="https://jsxc.ch/privacy-policy">privacy policy</a>.</label>
					</div>
					<div class="form-col-offset">
						<input id="ojsxc-register" type="button" value="Register" data-toggle-value="Processing registration" disabled="disabled" />
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<div class="ojsxc-external hidden">
				<h3>External authentication</h3>
				<p class="text">This information is needed for the ejabberd/Prosody
					<a href="https://github.com/jsxc/xmpp-cloud-auth/wiki" target="_blank">authentication module</a>
					and can not be changed.</p>
				<div class="form-group">
					<label>API URL</label>
					<div class="form-col">
						<input id="jsxc-api-url" type="text" readonly="readonly" value="<?php p($_['apiUrl']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label>Secure API token</label>
					<div class="form-col">
						<input type="text" readonly="readonly" value="<?php p($_['apiSecret']['value']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label for="timeLimitedToken">Activate time-limited tokens (beta)</label>
					<div class="form-col">
						<?php printCheckboxInput('timeLimitedToken'); ?>
						<em>Activate this checkbox if the XMPP server supports time-limited tokens
							through <a href="https://github.com/jsxc/xmpp-cloud-auth" target="_blank">xmpp-cloud-auth</a>.</em>
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<h3>ICE server <small>(WebRTC)</small></h3>
			<div class="form-group">
				<label for="iceUrl">URLs</label>
				<div class="form-col">
					<input type="text" name="<?php p($_['iceUrl']['name']); ?>" id="iceUrl" value="<?php p($_['iceUrl']['value']); ?>" placeholder="stun:stun.stunprotocol.org" pattern="^(stun|turn):.+" />
					<em>Multiple servers can be separated by ", ".</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceUsername">TURN Username</label>
				<div class="form-col">
					<?php printTextInput('iceUsername', false); ?>
					<em>Leave empty to use the UID of each user.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceCredential">TURN Credential</label>
				<div class="form-col">
					<?php printTextInput('iceCredential', false); ?>
					<em>If no password is set, TURN-REST-API credentials are used.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceSecret">TURN Secret</label>
				<div class="form-col">
					<?php printTextInput('iceSecret', false); ?>
					<em>Secret for TURN-REST-API credentials as described <a href="http://tools.ietf.org/html/draft-uberti-behave-turn-rest-00" target="_blank">here</a>.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceTtl">TURN TTL</label>
				<div class="form-col">
					<?php printTextInput('iceTtl', false, 'number'); ?>
					<em>Lifetime for TURN-REST-API credentials in seconds.</em>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<h3>CSP <small>Content-Security-Policy</small></h3>
			<div class="form-group">
				<label for="fileUpload">External services</label>
				<div class="form-col">
					<?php foreach($_['externalServices']['value'] as $external): ?>
					<input type="text" name="<?php p($_['externalServices']['name']); ?>" value="<?php p($external); ?>" pattern="^(https://)?([\w\d*][\w\d-]*)(\.[\w\d-]+)+(:[\d]+)?$" />
					<?php endforeach;?>
					<button class="add-input">+</button>
					<em>All domains of additional services JSXC should be able to contact, e.g., your XMPP server's http file upload service.</em>
				</div>
			</div>
		</fieldset>

		<div class="form-col-offset">
			<div class="msg"></div>

			<input type="submit" value="Save settings" />
		</div>
	</form>
</div>
