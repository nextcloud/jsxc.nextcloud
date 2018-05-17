<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

if (function_exists('script')) {
	script('ojsxc', 'settings/personal');
}
?>
<div class="section">
	<h2>JavaScript Xmpp Client</h2>

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
</div>
