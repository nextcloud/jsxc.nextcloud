<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

if (function_exists(script)) {
	script('ojsxc', 'settings/admin');
}
?>

<div class="section">
	<h2>JavaScript Xmpp Client</h2>
	<form id="ojsxc">
		<div class="form-group">
			<label class="text-left form-no-padding">
				<input type="radio" name="serverType" required="required" value="internal" <?php if($_['serverType'] === 'internal')echo 'checked'; ?> />
				Internal (Experimental)
			</label>
			<em>Limited functionality only: No clients besides JSXC in ownCloud, no multi-user chat, no server-to-server federations.</em>
		</div>
		<div class="form-group">
			<label class="text-left form-no-padding">
				<input type="radio" name="serverType" class="required" required="required" value="external" <?php if($_['serverType'] === 'external')echo 'checked'; ?> />
				External
			</label>
			<em>Choose this option to use your own XMPP server.</em>
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
			<div class="ojsxc-external hidden">
				<h3>External authentication</h3>
				<p class="text">This information is needed for the ejabberd/prosody
					<a href="https://github.com/jsxc/ejabberd-cloud-auth" target="_blank">authentication module</a>
					and can not be changed.</p>
				<div class="form-group">
					<label>API URL</label>
					<div class="form-col">
						<input id="jsxc-api-url" type="text" readonly="readonly" />
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
							from <a href="https://github.com/jsxc/xmpp-cloud-auth" target="_blank">xmpp-cloud-auth</a>.</em>
					</div>
				</div>
			</div>
		</fieldset>

		<fieldset>
			<h3>ICE server <small>(WebRTC)</small></h3>
			<div class="form-group">
				<label for="iceUrl">Url</label>
				<div class="form-col">
					<input type="text" name="iceUrl" id="iceUrl" value="<?php p($_['iceUrl']); ?>" placeholder="stun:stun.stunprotocol.org" pattern="^(stun|turn):.+" />
				</div>
			</div>
			<div class="form-group">
				<label for="iceUsername">TURN Username</label>
				<div class="form-col">
					<input type="text" name="iceUsername" id="iceUsername" value="<?php p($_['iceUsername']); ?>" />
					<em>If no username is set, TURN-REST-API credentials are used.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceCredential">TURN Credential</label>
				<div class="form-col">
					<input type="text" name="iceCredential" id="iceCredential" value="<?php p($_['iceCredential']); ?>" />
					<em>If no password is set, TURN-REST-API credentials are used.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceSecret">TURN Secret</label>
				<div class="form-col">
					<input type="text" name="iceSecret" id="iceSecret" value="<?php p($_['iceSecret']); ?>" />
					<em>Secret for TURN-REST-API credentials as described <a href="http://tools.ietf.org/html/draft-uberti-behave-turn-rest-00" target="_blank">here</a>.</em>
				</div>
			</div>
			<div class="form-group">
				<label for="iceTtl">TURN TTL</label>
				<div class="form-col">
					<input type="number" name="iceTtl" id="iceTtl" value="<?php p($_['iceTtl']); ?>" />
					<em>Lifetime for TURN-REST-API credentials in seconds.</em>
				</div>
			</div>
		</fieldset>
		<fieldset>
			<h3>Screen sharing</h3>
			<div class="form-group">
				<label for="firefoxExtension">Firefox Extension Url</label>
				<div class="form-col">
					<input type="url" name="firefoxExtension" id="firefoxExtension" value="<?php p($_['firefoxExtension']); ?>" />
					<em>Firefox needs an extension in order to support screen sharing. <a href="https://github.com/jsxc/jsxc/wiki/Screen-sharing">More details.</a></em>
				</div>
			</div>
			<div class="form-group">
				<label for="chromeExtension">Chrome Extension Url</label>
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
					<em>All domains of external services which JSXC should reach. E.g. http file upload service. <a href="#" id="insert-upload-service">Insert upload services automatically</a>.</em>
				</div>
			</div>
		</fieldset>

		<div class="form-col-offset">
			<div class="msg"></div>

			<input type="submit" value="Save settings" />
		</div>
	</form>
</div>
