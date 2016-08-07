<?php


namespace Mailgun_Subscriptions;

/**
 * Class Account_Management_Token_Email
 *
 * Sends an email to the user with a link to manage their account.
 */
class Account_Management_Token_Email {
	private $email_address = '';
	
	public function __construct( $email_address ) {
		$this->email_address = $email_address;
	}

	public function send() {
		return wp_mail( $this->email_address, $this->get_subject(), $this->get_body() );
	}

	public function get_token_link() {
		$hasher = new Account_Management_Hash( $this->email_address );
		$hash = $hasher->get_hash();
		$base_url = Plugin::instance()->account_management_page()->get_page_url();
		$url = add_query_arg( array(
			Account_Management_Page_Authenticator::EMAIL_ARG => urlencode( $this->email_address ),
			Account_Management_Page_Authenticator::HASH_ARG => urlencode( $hash ),
		), $base_url );
		return $url;
	}

	private function get_subject() {
		return apply_filters( 'mailgun_token_email_subject', sprintf( __( '[%s] Manage Your Subscriptions', 'mailgun-subscriptions' ), get_bloginfo('name') ) );
	}

	private function get_body() {
		$message = $this->get_token_message_template();
		$message = str_replace( '[link]', esc_url_raw( $this->get_token_link() ), $message );
		return $message;
	}

	protected function get_token_message_template() {
		$template = get_option( 'mailgun_token_email_template', Template::token_email() );
		return apply_filters( 'mailgun_token_email_template', $template );
	}
}