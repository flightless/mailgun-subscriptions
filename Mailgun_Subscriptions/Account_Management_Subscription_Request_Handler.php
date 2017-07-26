<?php


namespace Mailgun_Subscriptions;

class Account_Management_Subscription_Request_Handler {
	private $submission = array();
	/** @var Account_Management_Page_Authenticator */
	private $authenticator = null;
	private $error = '';
	private $action = '';

	public function __construct( $submission, $authenticator ) {
		$this->submission = $submission;
		$this->authenticator = $authenticator;
		if ( !isset( $this->submission[ 'mailgun-action' ] ) ) {
			throw new \InvalidArgumentException( __( 'No action provided', 'mailgun-subscriptions' ) );
		}
		$this->action = $this->submission[ 'mailgun-action' ];
	}
	
	public function handle_request() {
		if ( $this->is_valid_submission() ) {
			switch ( $this->action ) {
				case 'account-subscribe':
					$this->handle_subscribe_request( $this->authenticator->get_email(), $this->submission[ 'list' ], $this->submission[ 'name' ] );
					break;
				case 'account-unsubscribe':
					$this->handle_unsubscribe_request( $this->authenticator->get_email(), $this->submission[ 'list' ] );
					break;
				case 'account-resubscribe':
					$this->handle_resubscribe_request( $this->authenticator->get_email(), $this->submission[ 'list' ] );
					break;
			}
			$this->do_success_redirect();
		} else {
			$this->do_error_redirect();
		}
	}

	private function handle_subscribe_request( $email_address, $list_address, $name = '' ) {
		$submission_handler = new Submission_Handler( array(
			'mailgun-lists' => array( $list_address ),
			'mailgun-subscriber-email' => $email_address,
			'mailgun-subscriber-name' => $name,
		));
		$submission_handler->handle_request();
	}

	private function handle_unsubscribe_request( $email_address, $list_address ) {
		$api = Plugin::instance()->api();
		$path = sprintf( 'lists/%s/members/%s', $list_address, $email_address );
		$api->put( $path, array( 'subscribed' => 'no' ) );
	}

	private function handle_resubscribe_request( $email_address, $list_address ) {
		$suppressions = Suppressions::instance( $email_address );
		$suppressions->clear_all( $list_address );

		$api = Plugin::instance()->api();
		$path = sprintf( 'lists/%s/members/%s', $list_address, $email_address );
		$api->put( $path, array( 'subscribed' => 'yes' ) );
	}

	protected function is_valid_submission() {
		if ( !isset( $this->submission[ 'nonce' ] ) || !wp_verify_nonce( $this->submission[ 'nonce' ], $this->action ) ) {
			$this->error = 'invalid-nonce';
		} elseif( $this->authenticator->validate() !== Account_Management_Page_Authenticator::VALID || !isset( $this->submission[ 'list' ] ) ) {
			$this->error = 'invalid-request';
		}
		return empty($this->error);
	}

	protected function do_success_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-account-message' => 'subscription-updated',
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
		foreach ( array('mailgun-action', 'list', 'nonce') as $key ) {
			$url = remove_query_arg( $key, $url);
		}
		return $url;
	}

}