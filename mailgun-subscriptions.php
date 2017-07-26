<?php
/*
Plugin Name: Mailgun Mailing List Subscriptions
Plugin URI: https://github.com/flightless/mailgun-subscriptions
Description: A widget for visitors to subscribe to Mailgun mailing lists
Author: Flightless
Author URI: http://flightless.us/
Version: 1.2.0
Text Domain: mailgun-subscriptions
Domain Path: /languages
*/

if ( !function_exists('mailgun_subscriptions_load') ) {

	function mailgun_subscriptions_load() {
		require_once( __DIR__ . '/vendor/autoload.php' );
		add_action( 'init', 'mailgun_load_textdomain', 10, 0 );
		if ( mailgun_subscriptions_version_check() ) {
			\Mailgun_Subscriptions\Plugin::init(__FILE__);
		} else {
			add_action( 'admin_notices', 'mailgun_subscriptions_version_notice' );
		}
	}

	function mailgun_load_textdomain() {
		$domain = 'mailgun-subscriptions';
		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR.'/mailgun-subscriptions/'.$domain.'-'.$locale.'.mo');
		load_plugin_textdomain($domain, FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
	}

	function mailgun_subscriptions_version_check() {
		if ( version_compare(PHP_VERSION, '5.3.2', '>=') ) {
			return TRUE;
		}
		return FALSE;
	}

	function mailgun_subscriptions_version_notice() {
		$message = sprintf(__('MailGun Mailing List Subscriptions requires PHP version %s or higher. You are using version %s.', 'mailgun-subscriptions'), '5.3.2', PHP_VERSION);
		printf( '<div class="error"><p>%s</p></div>', $message );
	}

	add_action( 'plugins_loaded', 'mailgun_subscriptions_load' );
}