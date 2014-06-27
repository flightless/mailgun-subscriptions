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

	public function has_errors() {
		return !empty($this->errors);
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
		$template = get_option( 'mailgun_welcome_email_template', Template::welcome_email() );
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

	public function setup_page_data( $post ) {
		if ( $post->ID != get_option('mailgun_confirmation_page', 0) ) {
			return; // not concerned with this post
		}
		if ( empty($this->errors) ) {
			return; // no need to override the confirmation page
		}
		$messages = array();
		if ( !isset($_GET['mailgun-message']) ) { // otherwise we got here from the subscription form
			foreach ( $this->errors as $error_code ) {
				$messages[] = '<p class="error">'.$this->get_message($error_code).'</p>';
			}
		}
		$page_content = implode($messages).$this->get_subscription_form();
		$post->post_content = $page_content;
		$GLOBALS['pages'] = array( $post->post_content );
		$post->post_title = apply_filters('mailgun_error_page_title', __('Error Confirming Your Subscription', 'mailgun-subscriptions'));
	}

	protected function get_message( $code ) {
		switch ( $code ) {
			case 'not_found':
				$message = __('Your request could not be found. Please try again.', 'mailgun-subscriptions');
				break;
			case 'no_lists':
				$message = __('There are no mailing lists associated with your request. Please try again.', 'mailgun-subscriptions');
				break;
			case 'already_confirmed':
				$message = __('You have already confirmed your request.', 'mailgun-subscriptions');
				break;
			case 'expired':
				$message = __('Your request has expired. Please try again.', 'mailgun-subscriptions');
				break;
			case 'subscription_failed':
				$message = __('We experienced a problem setting up your subscription. Please try again.', 'mailgun-subscriptions');
				break;
			default:
				$message = $code;
				break;
		}
		return apply_filters( 'mailgun_message', $message, $code, 'confirmation' );
	}

	protected function get_subscription_form() {
		$shortcodes = Plugin::instance()->shortcode_handler();
		return $shortcodes->form_shortcode(array());
	}
} 