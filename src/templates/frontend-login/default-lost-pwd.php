<?php
namespace uncanny_learndash_toolkit;
?>
<h2><?php echo $innerText['Password-Recovery-Title']; ?></h2>
<form id="lostpasswordform" name="lostpasswordform" action="<?php echo site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ?>" method="post">
	<p>
		<label for="user_login"><?php echo $innerText['Password-Recovery-Label']; ?></label>
		<input size="20" type="text" name="user_login" id="user_login" value="">
	</p>

	<input type="hidden" name="redirect_to" value="<?php echo $login_page_url ?>action=forgot&success=1">
	<?php if ( '' !== trim( $recaptcha_key ) ) { ?>
		<div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_key; ?>" data-callback="correctCaptcha" data-expired-callback="expiredCaptcha"></div>
	<?php } ?>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" value="<?php echo $innerText['Get-New-Password']; ?>"/></p>
</form>