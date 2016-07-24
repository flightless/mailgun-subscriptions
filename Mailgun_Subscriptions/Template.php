<?php


namespace Mailgun_Subscriptions;


abstract class Template {
	public static function confirmation_email() {
		return __("Thank you for subscribing. Please visit [link] to confirm your subscription for [email] to the following lists:\n\n[lists]", 'mailgun-subscriptions');
	}

	public static function welcome_email() {
		return __("Your email address, [email], has been confirmed. You are now subscribed to the following lists:\n\n[lists]\n\nTo manage your subscriptions, visit:\n\n[link]", 'mailgun-subscriptions');
	}

	public static function confirmation_page() {
		return __("<p>Thank you for confirming your subscription. <strong>[mailgun_email]</strong> is now subscribed to:</p>[mailgun_lists]", 'mailgun-subscriptions');
	}

	public static function token_email() {
		return __( "To manage your subscriptions, visit:\n\n[link]", 'mailgun-subscriptions' );
	}
}