<?php

namespace Mailgun_Subscriptions;

/**
 * Class Admin_Page
 */
class Admin_Page {
	const MENU_SLUG = 'mailgun_subscriptions';

	public function register() {
		add_options_page(
			__('Mailgun Mailing Lists', 'mailgun-subscriptions'),
			__('Mailgun Lists', 'mailgun-subscriptions'),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'display' )
		);
	}

	public function display() {
		$title = __('Mailgun Mailing Lists', 'mailgun-subscriptions');
		$nonce = wp_nonce_field('mailgun-settings', 'mailgun-settings-nonce', true, false);
		$button = get_submit_button(__('Save Settings', 'mailgun-subscriptions'));
		$action = admin_url('options.php');
		$form = sprintf('<form method="post" action="%s" enctype="multipart/form-data">%s%s</form>', $action, $nonce, $button);

		$content = $form;

		ob_start();
		settings_errors();
		$messages = ob_get_clean();

		printf( '<div class="wrap"><h2>%s</h2>%s%s</div>', $title, $messages, $content );
	}
}
 