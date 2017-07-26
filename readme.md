# MailGun Mailing List Subscriptions

Add a Mailgun subscription form to your WordPress site. Your visitors can use the form to subscribe to your lists using the Mailgun API.

## Installation and Setup

Install and activate just as a normal WordPress plugin.

You'll find the "Mailgun Lists" settings page in the Settings admin menu. Here, you can setup your API keys, control which lists you're making available, and create custom descriptions for your lists.

When the plugin is activated, the "Subscription Management" page will be automatically created for you. You can customize the title of this page, or set it to a different page using the option on the settings screen. If you accidentally delete the page, a new one will be created for you.

## Subscription Form Widget

The plugin creates a widget called "Mailgun List Subscription Form". It includes options to set the title, an optional description, whether or not to require the user's name, and the mailing lists that will be available in the widget.

## Shortcode

The plugin creates a shortcode: `[mailgun_subscription_form]`. This displays the same form as the widget. Optional parameters include:

* `lists` - The email addresses of the lists that should be available in the form.
* `description` - The description that will display above the form.
* `name` - Whether or not to require the user to provide a name with their email (true/false value).

### Hooks

`mailgun_form_message` - This action is fired to display notices and errors above the form.

`mailgun_form_content` - This action is fired to display the actual form.

`mailgun_enqueue_assets` - This action enqueues the plugin CSS when the form will be rendered.

`mailgun_css_path` - Filter the path to the plugin CSS file.

`mailgun_subscription_form_description` - Filter the rendering of the form description.

## Confirmation Emails

### Confirmation Email

You can set up templates for three emails the plugin will send.

When a user first submits the subscription form, the "Confirmation Email" is sent. Your template should contain the following shortcodes:

* `[link]` - This becomes a link back to your site with a unique code to confirm the user's subscription request.
* `[email]` - This is the user's email address.
* `[lists]` - This is a list of the lists the user opted to subscribe to.

#### Filters

`mailgun_confirmation_email_subject` - Edit the subject of the confirmation email.

`mailgun_confirmation_email_template` - Edit the confirmation email template.

`mailgun_confirmation_email_lists` - Edit the list of mailing lists in the email template.

### Welcome Email

After the user confirms, the "Welcome Email" is sent. This template can include:

* `[email]` - This is the user's email address.
* `[lists]` - This is a list of the lists the user opted to subscribe to.
* `[link]`  - This is the unique URL to the user's account management page.

#### Filters

`mailgun_welcome_email_subject` - Edit the subject of the welcome email.

`mailgun_welcome_email_template` - Edit the welcome email template.

`mailgun_welcome_email_lists` - Edit the list of mailing lists in the email template.

### Account Management Email

When a user requests access to the account management page, they will receive this email. This template can include:

* `[link]`  - This is the unique URL to the user's account management page.

#### Filters

`mailgun_token_email_subject` - Edit the subject of the welcome email.

`mailgun_token_email_template` - Edit the account management email template.

## Confirmation Page

The confirmation page is a standard WordPress Page. You can create your own, or the plugin will automatically create one for you. On this page, these shortcodes are supported (in addition to all other shortcodes you may have):

* `[mailgun_email]` - This is the user's email address.
* `[mailgun_lists]` - These are the lists the user subscribed to.

If a user visits the confirmation page without a valid confirmation URL, an error message will be displayed instead of the standard page contents.

## Email Address Validation

Email address are validated using WordPress's `is_email()` function. It validates the general form of the email address, but cannot handle some international domain names.

To use the more robust email validation provided by the MailGun API, use the filter `mailgun_subscriptions_validate_email_with_api`. Example:

```
add_filter( 'mailgun_subscriptions_validate_email_with_api', '__return_true' );
```

Or you can use WordPress's `is_email` filter to apply your own validation.