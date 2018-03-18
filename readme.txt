=== Mailgun Subscriptions ===
Contributors: jbrinley
Tags: mailing lists, subscriptions, widget, email
Requires at least: 3.9
Tested up to: 4.8
Stable tag: 1.3.2
License: GPL-2.0
License URI: https://opensource.org/licenses/GPL-2.0

Add a Mailgun subscription form to your WordPress site. Your visitors can use the form to subscribe to your lists using the Mailgun API.

== Description ==

Add a Mailgun subscription form to your WordPress site. Your visitors can use the form to subscribe to your lists using the Mailgun API.

== Installation ==

1. Install and activate just as a normal WordPress plugin.
1. You'll find the "Mailgun Lists" settings page in the Settings admin menu. Here, you can setup your API keys, control which lists you're making available, and create custom descriptions for your lists.

== Changelog ==

= 1.3.2 =

* Fix PHP warning when subscribing to a new list from the account management page

= 1.3.1 =

* Remove current/change email toggle from account management page
* Merge account management page dynamic content with static page content
* Enable admin to manually place dynamic account management page content using the [mailgun_account_management] shortcode
* Fix bug with ajax submission handling for logged out users

= 1.3.0 =

* Ajax handling of the subscription widget
* Form to change email address on the account management page

= 1.2.0 =

* Optional name field for the subscription widget (credit to Paul Ryley)
* Fix PHP notice when first loading admin page
* Default to WordPress email validation, with filter to use MailGun API

= 1.1.2 =

* Fix fatal error during uninstall
* Fix error saving account management page ID

= 1.1.1 =

* Validate email address when requesting token to manage account
* Disable caching of subscription management page

= 1.1 =

* New feature: subscription management

= 1.0 =
* Initial version
