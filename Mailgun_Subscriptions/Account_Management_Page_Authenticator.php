<?php


namespace Mailgun_Subscriptions;

/**
 * Class Account_Management_Page_Authenticator
 *
 * Authenticates access to the account management page
 */
class Account_Management_Page_Authenticator {
	const VALID        = 0;
	const NO_USER      = 1;
	const INVALID_HASH = 2;

	const EMAIL_ARG = 'email';
	const HASH_ARG  = 'hash';
	const COOKIE_NAME = 'mailgun-account';

	private $validation_result;

	private $email_address = '';
	private $hash          = '';

	public function __construct( $submission ) {
		if ( isset( $submission[ self::COOKIE_NAME ] ) ) {
			$submission = json_decode( stripslashes( $submission[ self::COOKIE_NAME ] ), true );
		} else {
			$submission = array();
		}
		if ( !empty( $submission[ self::EMAIL_ARG ] ) ) {
			$this->email_address = $submission[ self::EMAIL_ARG ];
		}
		if ( !empty( $submission[ self::HASH_ARG ] ) ) {
			$this->hash = $submission[ self::HASH_ARG ];
		}
	}

	public function get_email() {
		return $this->email_address;
	}

	public function get_hash() {
		return $this->hash;
	}

	public function validate() {
		if ( isset( $this->validation_result ) ) {
			return $this->validation_result;
		}
		$this->validation_result = $this->do_validation();
		return $this->validation_result;
	}

	private function do_validation() {
		if ( empty( $this->email_address ) ) {
			return self::NO_USER;
		}
		if ( ! $this->validate_hash() ) {
			return self::INVALID_HASH;
		}
		return self::VALID;
	}

	private function validate_hash() {
		$hash = new Account_Management_Hash( $this->email_address );
		return $hash->get_hash() == $this->hash;
	}
}