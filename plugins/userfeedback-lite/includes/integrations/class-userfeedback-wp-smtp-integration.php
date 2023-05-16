<?php

class UserFeedback_Wp_Smtp_Integration extends UserFeedback_Plugin_Integration {

	/**
	 * @inheritdoc
	 */
	protected static $slug = 'wp-mail-smtp/wp_mail_smtp.php';

	/**
	 * @inheritdoc
	 */
	protected $name = 'wp-mail-smtp';

	/**
	 * @inheritdoc
	 */
	public function get_basename() {
		return 'wp-mail-smtp/wp_mail_smtp.php';
	}
}

new UserFeedback_Wp_Smtp_Integration();
