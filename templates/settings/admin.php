<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

if (function_exists('script')) {
	script('ojsxc', 'settings/admin');
}
?>

<div class="section">
	<h2>JavaScript Xmpp Client</h2>
	<form id="ojsxc">
		<h3>Server type</h3>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<input type="radio" name="serverType" required="required" value="internal" <?php if($_['serverType'] === 'internal')echo 'checked'; ?> />
				Internal
			</label>
			<em>Limited functionality only: No clients besides JSXC in Nextcloud, no multi-user chat, no server-to-server federations.</em>
		</div>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<input type="radio" name="serverType" class="required" required="required" value="external" <?php if($_['serverType'] === 'external')echo 'checked'; ?> />
				External
			</label>
			<em>Choose this option to use your own XMPP server.</em>
		</div>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<input type="radio" name="serverType" class="required" required="required" value="managed" <?php if($_['serverType'] === 'managed')echo 'checked'; ?> />
				Managed (Beta service)
			</label>
			<em>Get your own full featured XMPP server directly hosted by the core team of JSXC. For more information visit <a target="_blank" href="https://jsxc.ch/managed">jsxc.ch/managed</a>.</em>
		</div>

		<fieldset>
			<h3>Basic</h3>
			<div class="ojsxc-internal hidden">

			</div>

			<div class="ojsxc-external hidden">
				<div class="form-group">
					<label for="xmppDomain">* XMPP domain</label>
					<div class="form-col">
						<input type="text" name="xmppDomain" id="xmppDomain" class="required" required="required" value="<?php p($_['xmppDomain']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label for="xmppPreferMail">Prefer mail address to loginName@xmppDomain</label>
					<input type="checkbox" name="xmppPreferMail" id="xmppPreferMail" value="true" <?php if($_[ 'xmppPreferMail']==='true' || $_[ 'xmppPreferMail']===true) echo "checked"; ?> />
				</div>
				<div class="form-group">
					<label for="boshUrl">* BOSH url</label>
					<div class="form-col">
						<input type="text" name="boshUrl" id="boshUrl" class="required" required="required" value="<?php p($_['boshUrl']); ?>" />
						<div class="boshUrl-msg"></div>
					</div>
				</div>
				<div class="form-group">
					<label for="xmppResource">XMPP resource</label>
					<div class="form-col">
						<input type="text" name="xmppResource" id="xmppResource" value="<?php p($_['xmppResource']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label for="xmppOverwrite">Allow user to overwrite XMPP settings</label>
					<div class="form-col">
						<input type="checkbox" name="xmppOverwrite" id="xmppOverwrite" value="true" <?php if($_[ 'xmppOverwrite']==='true' || $_[ 'xmppOverwrite']===true) echo "checked"; ?> />
					</div>
				</div>
			</div>

			<div class="form-group">
				<label for="xmppStartMinimized">Hide roster after first login</label>
				<div class="form-col">
					<input type="checkbox" name="xmppStartMinimized" id="xmppStartMinimized" value="true" <?php if($_[ 'xmppStartMinimized']==='true' || $_[ 'xmppStartMinimized']===true) echo "checked"; ?> />
				</div>
			</div>
		</fieldset>

		<fieldset>
			<div class="ojsxc-managed hidden">
				<h3>Registration</h3>
				<?php if($_['managedServer'] === 'registered'): ?>
					<div class="msg jsxc_success">Congratulations! You use our managed server. <a href="#" class="ojsxc-refresh-registration">Redo registration</a>.</div>
				<?php else: ?>
					<div class="msg"></div>
				<?php endif; ?>

				<div class="ojsxc-managed-registration <?php if($_['managedServer'] === 'registered'){echo 'hidden';} ?>">
					<p class="text">In order to create a managed XMPP server for you, we will send the following information to our registration server. The set-up process will take about 20-30 seconds.</p>

					<div class="form-group">
						<label>API URL</label>
						<div class="form-col">
							<input id="ojsxc-managed-api-url" type="text" readonly="readonly" value="<?php p($_['apiUrl']); ?>" />
						</div>
					</div>
					<div class="form-group">
						<label>Secure API token</label>
						<div class="form-col">
							<input id="ojsxc-managed-api-secret" type="text" readonly="readonly" value="<?php p($_['apiSecret']); ?>" />
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
						<input id="ojsxc-register" type="button" value="Register" data-toggle-value="Processing registration" />
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<div class="ojsxc-external hidden">
				<h3>External authentication</h3>
				<p class="text">This information is needed for the ejabberd/Prosody
					<a href="https://github.com/jsxc/ejabberd-cloud-auth/wiki" target="_blank">authentication module</a>
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
						<input type="text" readonly="readonly" value="<?php p($_['apiSecret']); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label for="timeLimitedToken">Activate time-limited tokens (beta)</label>
					<div class="form-col">
						<input type="checkbox" name="timeLimitedToken" id="timeLimitedToken" value="true" <?php if($_[ 'timeLimitedToken']==='true' || $_[ 'timeLimitedToken']===true) echo "checked"; ?> />
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
					<input type="text" name="iceUrl" id="iceUrl" value="<?php p($_['iceUrl']); ?>" placeholder="stun:stun.stunprotocol.org" pattern="^(stun|turn)s?:.+" />
					<em>Multiple servers can be separated by ", ".</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceUsername">TURN Username</label>
				<div class="form-col">
					<input type="text" name="iceUsername" id="iceUsername" value="<?php p($_['iceUsername']); ?>" />
					<em>Leave empty to use the login name of each user.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceCredential">TURN Credential</label>
				<div class="form-col">
					<input type="text" name="iceCredential" id="iceCredential" value="<?php p($_['iceCredential'] || $_['iceSecret']); ?>" />
					<em>Password/secret to use.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceTtl">TURN TTL (seconds)</label>
				<div class="form-col">
					<input type="number" name="iceTtl" id="iceTtl" value="<?php p($_['iceTtl']); ?>" />
					<em>If >0, issue short-term TURN tokens instead of username/credential.</em>
				</div>
			</div>
		</fieldset>
		<fieldset>
			<h3>Screen sharing</h3>
			<div class="form-group">
				<label for="firefoxExtension">Firefox Extension URL</label>
				<div class="form-col">
					<input type="url" name="firefoxExtension" id="firefoxExtension" value="<?php p($_['firefoxExtension']); ?>" />
					<em>Firefox needs an extension in order to support screen sharing. <a href="https://github.com/jsxc/jsxc/wiki/Screen-sharing">More details.</a></em>
				</div>
			</div>
			<div class="form-group">
				<label for="chromeExtension">Chrome Extension URL</label>
				<div class="form-col">
					<input type="url" name="chromeExtension" id="chromeExtension" value="<?php p($_['chromeExtension']); ?>" />
					<em>Chrome needs an extension in order to support screen sharing. <a href="https://github.com/jsxc/jsxc/wiki/Screen-sharing">More details.</a></em>
				</div>
			</div>
		</fieldset>
		<fieldset>
			<h3>CSP <small>Content-Security-Policy</small></h3>
			<div class="form-group">
				<label for="fileUpload">External services</label>
				<div class="form-col">
					<?php foreach($_['externalServices'] as $external): ?>
					<input type="text" name="externalServices[]" value="<?php p($external); ?>" pattern="^(https://)?([\w\d*][\w\d-]*)(\.[\w\d-]+)+(:[\d]+)?$" />
					<?php endforeach;?>
					<button class="add-input">+</button>
					<em>All domains of additional services JSXC should be able to contact, e.g., your XMPP server's http file upload service. <a href="#" id="insert-upload-service">Insert upload services automatically</a>.</em>
				</div>
			</div>
		</fieldset>

		<div class="form-col-offset">
			<div class="msg"></div>

			<input type="submit" value="Save settings" />
		</div>
	</form>
</div>
