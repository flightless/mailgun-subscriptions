<?php


namespace Mailgun_Subscriptions;

class Account_Management_Token_Email {
	private $email_address = '';
	
	public function __construct( $email_address ) {
		$this->email_address = $email_address;
	}

	public function send() {
		return wp_mail( $this->email_address, $this->get_subject(), $this->get_body() );
	}

	private function get_token_link() {
		$hasher = new Account_Management_Hash( $this->email_address );
		$hash = $hasher->get_hash();
		$base_url = Plugin::instance()->account_management_page()->get_page_url();
		$url = add_query_arg( array(
			Account_Management_Page_Authenticator::EMAIL_ARG => $this->email_address,
			Account_Management_Page_Authenticator::HASH_ARG => $hash,
		), $base_url );
		return $url;
	}

	private function get_subject() {
		return 'Account Management'; // TODO: customizable
	}

	private function get_body() {
		$link = $this->get_token_link();
		$link = sprintf( '<a href="%s">%s</a>', esc_url_raw( $link ), esc_url( $link ) );
		return $link; // TODO: customizable
	}
}