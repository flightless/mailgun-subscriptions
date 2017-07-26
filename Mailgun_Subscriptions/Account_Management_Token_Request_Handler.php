<?php


namespace Mailgun_Subscriptions;

class Account_Management_Token_Request_Handler {
	private $submission = array();
	private $error = '';

	public function __construct( $submission ) {
		$this->submission = $submission;
	}
	
	public function handle_request() {
		if ( $this->is_valid_submission() ) {
			$this->send_token_email( $this->submission[ Account_Management_Page::EMAIL_ADDRESS_FIELD ] );
			$this->do_success_redirect();
		} else {
			$this->do_error_redirect();
		}
	}

	protected function send_token_email( $email_address ) {
		$email_builder = new Account_Management_Token_Email( $email_address );
		$email_builder->send();
	}

	protected function is_valid_submission() {
		if ( empty( $this->submission[ Account_Management_Page::EMAIL_ADDRESS_FIELD ] ) ) {
			$this->error = 'no-email';
			return false;
		}
		if ( empty( $this->submission[ '_wpnonce'] ) || ! wp_verify_nonce( $this->submission[ '_wpnonce'], Account_Management_Page::ACTION_REQUEST_TOKEN ) ) {
			$this->error = 'invalid-nonce';
			return false;
		}
		if ( ! $this->is_valid_email( $this->submission[ Account_Management_Page::EMAIL_ADDRESS_FIELD ] ) ) {
			$this->error = 'invalid-email';
			return false;
		}
		return true;
	}

	private function is_valid_email( $email_address ) {
		if ( apply_filters( 'mailgun_subscriptions_validate_email_with_api', false ) ) {
			$valid = Plugin::instance()->api( TRUE )->validate_email( $email_address );
		} else {
			$valid = is_email( $email_address );
		}
		return (bool) $valid;
	}

	protected function do_success_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-account-message' => 'request-submitted',
		), $url );
		wp_safe_redirect($url);
		exit();
	}

	protected function do_error_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-account-message' => $this->error,
		), $url );
		wp_safe_redirect($url);
		exit();
	}

	protected function get_redirect_base_url() {
		$url = Plugin::instance()->account_management_page()->get_page_url();
		foreach ( array('mailgun-account-message', 'mailgun-error', 'mailgun-action', 'ref') as $key ) {
			$url = remove_query_arg( $key, $url);
		}
		return $url;
	}

}