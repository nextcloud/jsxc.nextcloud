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
		<label>
			<input type="checkbox" name="loginFormEnable" value="true" <?php if($_[ 'loginFormEnable']==='true' || $_[ 'loginFormEnable']===true) echo "checked"; ?> />
			If this option is enabled, the chat will start on login.
		</label>
	</div>
</div>
