<?php
/*
Plugin Name: Mailgun Mailing List Subscriptions
Plugin URI: https://github.com/flightless/mailgun-subscriptions
Description: A widget for visitors to subscribe to Mailgun mailing lists
Author: Flightless
Author URI: http://flightless.us/
Version: 1.0
*/

if ( !function_exists('mailgun_subscriptions_load') ) {

	function mailgun_subscriptions_load() {
		if ( mailgun_subscriptions_version_check() ) {
			require_once('Mailgun_Subscriptions/Plugin.php');
			\Mailgun_Subscriptions\Plugin::init(__FILE__);
		} else {
			add_action( 'admin_notices', 'mailgun_subscriptions_version_notice' );
		}
	}

	function mailgun_subscriptions_version_check() {
		if ( version_compare(PHP_VERSION, '5.3.2', '>=') ) {
			return TRUE;
		}
		return FALSE;
	}

	function mailgun_subscriptions_version_notice() {
		$message = sprintf(__('MailGun Mailing List Subscriptions requires PHP version %s or higher. You are using version %s.'), '5.3.2', PHP_VERSION);
		// TODO: display it
	}

	add_action( 'plugins_loaded', 'mailgun_subscriptions_load' );
}