<?php


namespace Mailgun_Subscriptions;


class Confirmation_Handler {
	protected $submission = array();
	protected $errors = array();

	/** @var Confirmation */
	protected $confirmation = NULL;

	public function __construct( $submission ) {
		$this->submission = $submission;
	}

	public function handle_request() {
		$this->get_confirmation();
		$this->validate_confirmation();
		if ( empty($this->errors) ) {
			$this->do_subscription();
		}
		if ( empty($this->errors) ) {
			$this->send_welcome_email();
			$this->confirmation->mark_confirmed();
		}
	}

	public function get_confirmation() {
		if ( !isset($this->confirmation) ) {
			$ref = $this->get_confirmation_id();
			if ( $ref ) {
				$this->confirmation = new Confirmation( $ref );
			} else {
				$this->confirmation = new Null_Confirmation();
			}
		}
		return $this->confirmation;
	}

	protected function get_confirmation_id() {
		$id = isset( $this->submission['ref'] ) ? $this->submission['ref'] : '';
		return $id;
	}

	protected function validate_confirmation() {
		if ( !$this->confirmation->get_address() ) {
			$this->errors[] = 'not_found';
			return;
		}
		if ( !$this->confirmation->get_lists() ) {
			$this->errors[] = 'no_lists';
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

	protected function do_subscription() {
		$address = $this->confirmation->get_address();
		$lists = $this->confirmation->get_lists();
		$api = Plugin::instance()->api();
		foreach ( $lists as $list_address ) {
			$response = $api->post("lists/$list_address/members", array(
				'address' => $address,
				'upsert' => 'yes',
			));
			if ( !$response && $response['response']['code'] != 200 ) {
				$this->errors[] = 'subscription_failed';
			}
		}
	}

	protected function send_welcome_email() {
		$address = $this->confirmation->get_address();
		wp_mail( $address, $this->get_welcome_email_subject(), $this->get_welcome_email_message() );
	}

	protected function get_welcome_email_subject() {
		return apply_filters( 'mailgun_welcome_email_subject', sprintf( __( '[%s] Your Subscription Is Confirmed', 'mailgun-subscriptions' ), get_bloginfo('name') ) );
	}

	protected function get_welcome_email_message() {
		$message = $this->get_welcome_message_template();
		$message = str_replace( '[email]', $this->confirmation->get_address(), $message );
		$message = str_replace( '[lists]', $this->get_formatted_lists(), $message );
		return $message;
	}

	protected function get_welcome_message_template() {
		$template = get_option( 'mailgun_welcome_email_template', Template::WELCOME_EMAIL );
		return apply_filters( 'mailgun_welcome_email_template', $template );
	}

	protected function get_formatted_lists() {
		$requested_lists = $this->confirmation->get_lists();
		$all_lists = Plugin::instance()->get_lists('name');
		$formatted = array();
		foreach ( $requested_lists as $address ) {
			if ( isset($all_lists[$address]) ) {
				$formatted[] = sprintf( '%s (%s)', $all_lists[$address]['name'], $address );
			}
		}
		return apply_filters( 'mailgun_welcome_email_lists', implode("\n", $formatted), $requested_lists );
	}
} 