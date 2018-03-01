<?php


namespace Mailgun_Subscriptions;


class Change_Email_Request_Handler {

	protected $submission    = array();
	private   $action        = '';
	protected $errors        = array();
	protected $error_details = array();
	protected $transient_key = '';
	protected $message_key   = '';

	/** @var Change_Confirmation */
	protected $confirmation = null;

	/** @var Account_Management_Page_Authenticator */
	private $authenticator = null;

	public function __construct( $submission, $authenticator ) {
		$this->submission    = $submission;
		$this->authenticator = $authenticator;
		$this->action        = $this->submission[ 'mailgun-action' ];
	}

	public function handle_request() {
		switch ( $this->action ) {
			case 'change-email':
				if ( $this->is_valid_request_submission() ) {
					$confirmation_id = $this->save_change_request();
					$this->send_confirmation_email( $confirmation_id );
					$this->message_key = 'new-email-submitted';
					if ( empty( $this->errors ) ) {
						$this->do_request_success_redirect();
					}
				}
				break;
			case 'confirm-change-email':
				$this->get_confirmation();
				$this->validate_confirmation();
				if ( empty( $this->errors ) ) {
					$this->do_address_change();
				}
				$this->message_key = 'new-email-confirmed';
				if ( empty( $this->errors ) ) {
					$this->do_confirm_success_redirect( $this->confirmation->get_address() );
				}
				break;
		}

		if ( ! empty( $this->errors ) ) {
			$this->store_errors();
			$this->do_error_redirect();
		}
	}

	public static function get_error_data( $key, $code ) {
		$data = get_transient( 'mg_' . $key );
		if ( empty( $data[ $code ] ) ) {
			return array();
		}

		return $data[ $code ];
	}

	protected function store_errors() {
		if ( empty( $this->error_details ) ) {
			$this->transient_key = 1;

			return;
		}
		$this->transient_key = md5( serialize( $this->error_details ) );
		set_transient( 'mg_' . $this->transient_key, $this->error_details, MINUTE_IN_SECONDS * 3 );
	}

	protected function is_valid_request_submission() {
		if ( ! $this->get_submitted_address() ) {
			$this->errors[] = 'no-email';
		} elseif ( ! $this->is_valid_email( $this->get_submitted_address() ) ) {
			$this->errors[] = 'invalid-email';
		} elseif ( ! isset( $this->submission[ 'nonce' ] ) || ! wp_verify_nonce( $this->submission[ 'nonce' ], $this->action ) ) {
			$this->errors[] = 'invalid-nonce';
		}

		return empty( $this->errors );
	}

	protected function is_valid_email( $address ) {
		if ( apply_filters( 'mailgun_subscriptions_validate_email_with_api', false ) ) {
			$valid = Plugin::instance()->api( true )->validate_email( $address );
		} else {
			$valid = is_email( $address );
		}
		if ( ! $valid ) {
			$this->error_details[ 'invalid-email' ][] = $address;
		}

		return $valid;
	}

	protected function save_change_request() {
		$old_email    = $this->authenticator->get_email();
		$new_email    = $this->get_submitted_address();
		$confirmation = new Change_Confirmation();
		$confirmation->set_address( $new_email );
		$confirmation->set_prior_address( $old_email );
		$confirmation->save();

		return $confirmation->get_id();
	}

	protected function get_submitted_address() {
		$address = isset( $this->submission[ 'mailgun-subscriber-email' ] ) ? $this->submission[ 'mailgun-subscriber-email' ] : '';

		return $address;
	}

	protected function send_confirmation_email( $confirmation_id ) {
		$address = $this->get_submitted_address();
		wp_mail( $address, $this->get_confirmation_email_subject(), $this->get_confirmation_email_message( $confirmation_id ) );
	}

	protected function get_confirmation_email_subject() {
		return apply_filters( 'mailgun_confirmation_email_subject', sprintf( __( '[%s] Confirm Your Address Change', 'mailgun-subscriptions' ), get_bloginfo( 'name' ) ) );
	}

	protected function get_confirmation_email_message( $confirmation_id ) {
		$address = $this->get_submitted_address();
		$message = $this->get_confirmation_message_template();
		$message = str_replace( '[email]', $address, $message );
		$message = str_replace( '[link]', $this->get_confirmation_url( $confirmation_id ), $message );

		return $message;
	}

	protected function get_confirmation_message_template() {
		$template = get_option( 'mailgun_change_email_template', '' );
		if ( empty( $template ) ) {
			$template = Template::change_email();
		}

		return apply_filters( 'mailgun_change_email_template', $template );
	}

	protected function get_confirmation_url( $confirmation_id ) {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-action' => 'confirm-change-email',
			'ref'            => $confirmation_id,
		), $url );

		return $url;
	}


	public function get_confirmation() {
		if ( ! isset( $this->confirmation ) ) {
			$ref = $this->get_confirmation_id();
			if ( $ref ) {
				$this->confirmation = new Change_Confirmation( $ref );
			} else {
				$this->confirmation = new Null_Confirmation();
			}
		}

		return $this->confirmation;
	}

	protected function get_confirmation_id() {
		$id = isset( $this->submission[ 'ref' ] ) ? $this->submission[ 'ref' ] : '';

		return $id;
	}

	protected function validate_confirmation() {
		if ( ! $this->confirmation->get_address() ) {
			$this->errors[] = 'not_found';

			return;
		}
		if ( $this->confirmation->confirmed() ) {
			$this->errors[] = 'already_confirmed';

			return;
		}
		if ( $this->confirmation->expired() ) {
			$this->errors[] = 'expired';

			return;
		}
	}

	protected function do_address_change() {
		$old_address = $this->confirmation->get_prior_address();
		$new_address = $this->confirmation->get_address();

		$lists = $this->get_subscribed_lists( $old_address );

		$api = Plugin::instance()->api();
		foreach ( $lists as $list_address => $list ) {
			if ( empty( $list[ 'member' ] ) ) {
				continue; // not a member
			}
			$api_path = sprintf( 'lists/%s/members/%s', $list_address, $old_address );
			$response = $api->put( $api_path, array(
				'address' => $new_address,
			) );
			if ( ! $response && $response[ 'response' ][ 'code' ] != 200 ) {
				$this->errors[] = 'address_change_failed';
			}
		}
	}

	protected function do_request_success_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-account-message' => $this->message_key,
		), $url );
		wp_safe_redirect( $url );
		exit();
	}

	protected function do_confirm_success_redirect( $new_address ) {
		$url  = $this->get_redirect_base_url();
		$url  = add_query_arg( array(
			'mailgun-account-message' => $this->message_key,
		), $url );
		$auth = new Account_Management_Hash( $new_address );
		$hash = $auth->get_hash();
		$url  = add_query_arg( array(
			Account_Management_Page_Authenticator::EMAIL_ARG => urlencode( $new_address ),
			Account_Management_Page_Authenticator::HASH_ARG  => urlencode( $hash ),
		), $url );
		wp_safe_redirect( $url );
		exit();
	}

	protected function do_error_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-account-message' => $this->errors,
			'mailgun-error'           => $this->transient_key,
		), $url );
		wp_safe_redirect( $url );
		exit();
	}

	protected function get_redirect_base_url() {
		$page_id = $this->get_account_page_id_option();
		$url     = $page_id ? get_permalink( $page_id ) : $_SERVER[ 'REQUEST_URI' ];
		foreach ( array( 'mailgun-message', 'mailgun-error', 'mailgun-action', 'ref', 'list', 'nonce' ) as $key ) {
			$url = remove_query_arg( $key, $url );
		}

		return $url;
	}

	private function get_account_page_id_option() {
		return (int) get_option( Admin_Page::OPTION_ACCOUNT_PAGE, 0 );
	}


	private function get_subscribed_lists( $email_address ) {
		$member = new List_Member( $email_address );

		return $member->get_subscribed_lists( false );
	}
}