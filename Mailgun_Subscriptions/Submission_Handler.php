<?php


namespace Mailgun_Subscriptions;


class Submission_Handler {
	protected $submission = array();
	protected $errors = array();

	public function __construct( $submission ) {
		$this->submission = $submission;
	}

	public function handle_request() {
		if ( $this->is_valid_submission() ) {
			$confirmation_id = $this->save_subscription_request();
			$this->send_confirmation_email( $confirmation_id );
			$this->do_success_redirect();
		} else {
			$this->do_error_redirect();
		}
	}

	protected function is_valid_submission() {
		if ( !$this->get_submitted_lists() ) {
			$this->errors[] = 'no-lists';
		}
		if ( !$this->get_submitted_address() ) {
			$this->errors[] = 'no-email';
		} elseif ( !$this->is_valid_email($this->get_submitted_address()) ) {
			$this->errors[] = 'invalid-email';
		} elseif ( $this->is_unsubscribed( $this->get_submitted_address(), $this->get_submitted_lists() ) ) {
			$this->errors[] = 'unsubscribed';
		} elseif ( $this->is_already_subscribed( $this->get_submitted_address(), $this->get_submitted_lists() ) ) {
			$this->errors[] = 'already-subscribed';
		}
		return empty($this->errors);
	}

	protected function is_valid_email( $address ) {
		return Plugin::instance()->api( TRUE )->validate_email( $address );
	}

	protected function is_already_subscribed( $address, $lists ) {
		$api = Plugin::instance()->api();
		foreach ( $lists as $l ) {
			$member = $api->get( 'lists/'.$l.'/members/'.$address );
			if ( $member['response']['code'] == 200 ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	protected function is_unsubscribed( $address, $lists ) {
		$api = Plugin::instance()->api();
		foreach ( $lists as $l ) {
			$response = $api->get( 'lists/'.$l.'/members/'.$address );
			if ( $response['response']['code'] == 200 && $response['body']->member->subscribed === false ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	protected function save_subscription_request() {
		$confirmation = new Confirmation();
		$confirmation->set_address($this->get_submitted_address());
		$confirmation->set_lists($this->get_submitted_lists());
		$confirmation->save();
		return $confirmation->get_id();
	}

	protected function get_submitted_address() {
		$address = isset($this->submission['mailgun-subscriber-email']) ? $this->submission['mailgun-subscriber-email'] : '';
		return $address;
	}

	protected function get_submitted_lists() {
		$lists = isset($this->submission['mailgun-lists']) ? $this->submission['mailgun-lists'] : array();
		if ( empty($lists) ) {
			return array();
		}
		if ( !is_array($lists) ) {
			$lists = array($lists);
		}
		return $lists;
	}

	protected function send_confirmation_email( $confirmation_id ) {
		$address = $this->get_submitted_address();
		wp_mail( $address, $this->get_confirmation_email_subject(), $this->get_confirmation_email_message($confirmation_id) );
	}

	protected function get_confirmation_email_subject() {
		return apply_filters( 'mailgun_confirmation_email_subject', sprintf( __( '[%s] Confirm Your Subscription', 'mailgun-subscriptions' ), get_bloginfo('name') ) );
	}

	protected function get_confirmation_email_message( $confirmation_id ) {
		$address = $this->get_submitted_address();
		$message = $this->get_confirmation_message_template();
		$message = str_replace( '[email]', $address, $message );
		$message = str_replace( '[lists]', $this->get_formatted_lists(), $message );
		$message = str_replace( '[link]', $this->get_confirmation_url($confirmation_id), $message );
		return $message;
	}

	protected function get_confirmation_message_template() {
		$template = get_option( 'mailgun_confirmation_email_template', '' );
		if ( empty($template) ) {
			$template = Template::confirmation_email();
		}
		return apply_filters( 'mailgun_confirmation_email_template', $template );
	}

	protected function get_confirmation_url( $confirmation_id ) {
		$page = get_option( 'mailgun_confirmation_page', 0 );
		if ( $page ) {
			$url = get_permalink( $page );
		} else {
			$url = home_url();
		}
		$url = add_query_arg(array(
			'mailgun-action' => 'confirm',
			'ref' => $confirmation_id,
		), $url);
		return $url;
	}

	protected function get_formatted_lists() {
		$requested_lists = $this->get_submitted_lists();
		$all_lists = Plugin::instance()->get_lists('name');
		$formatted = array();
		foreach ( $requested_lists as $address ) {
			if ( isset($all_lists[$address]) ) {
				$formatted[] = sprintf( '%s (%s)', $all_lists[$address]['name'], $address );
			}
		}
		return apply_filters( 'mailgun_confirmation_email_lists', implode("\n", $formatted), $requested_lists );
	}

	protected function do_success_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-message' => 'submitted',
		), $url );
		wp_safe_redirect($url);
		exit();
	}

	protected function do_error_redirect() {
		$url = $this->get_redirect_base_url();
		$url = add_query_arg( array(
			'mailgun-message' => $this->errors,
			'mailgun-error' => 1,
		), $url );
		wp_safe_redirect($url);
		exit();
	}

	protected function get_redirect_base_url() {
		$url = $_SERVER['REQUEST_URI'];
		foreach ( array('mailgun-message', 'mailgun-error', 'mailgun-action', 'ref') as $key ) {
			$url = remove_query_arg('key', $url);
		}
		return $url;
	}
} 