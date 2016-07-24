<?php


namespace Mailgun_Subscriptions;

/**
 * Class Account_Management_Hash
 *
 * Builds the hash to validate a user's account management request
 */
class Account_Management_Hash {
	private $email_address = '';

	public function __construct( $email_address ) {
		$this->email_address = $email_address;
	}

	public function get_hash() {
		return wp_hash( $this->email_address, 'auth' );
	}
}