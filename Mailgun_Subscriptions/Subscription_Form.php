<?php


namespace Mailgun_Subscriptions;


class Subscription_Form {
	public function display( $args ) {
		$args = wp_parse_args( $args, array(
			'description' => '',
			'lists' => array(),
		));

		if ( !empty($_GET['mailgun-message']) ) {
			$this->show_form_message( $_GET['mailgun-message'], !empty($_GET['mailgun-error']) );
		}

		if ( empty($_GET['mailgun-message']) || !empty($_GET['mailgun-error']) ) {
			$this->do_form_contents( $args );
		}
	}



	protected function show_form_message( $message, $error = FALSE ) {
		if ( !is_array($message) ) {
			$message = array($message);
		}
		$error_class = $error ? ' error' : '';
		foreach ( $message as $code ) {
			echo '<p class="mailgun-message'.$error_class.'">', esc_html($this->get_message_string($code)), '</p>';
		}
	}

	protected function get_message_string( $code ) {
		switch ( $code ) {
			case 'submitted':
				$message = __('Please check your email for a link to confirm your subscription.', 'mailgun-subscriptions');
				break;
			case 'no-lists':
				$message = __('Please select a mailing list.', 'mailgun-subscriptions');
				break;
			case 'no-email':
				$message = __('Please enter your email address.', 'mailgun-subscriptions');
				break;
			case 'invalid-email':
				$message = __('Please verify your email address.', 'mailgun-subscriptions');
				break;
			default:
				$message = $code;
				break;
		}
		$message = apply_filters( 'mailgun_message', $message, $code, 'widget' );
		return $message;
	}


	protected function do_form_contents( $instance ) {
		static $instance_counter = 0;
		$instance_counter++;

		echo $instance['description'];

		printf('<form class="mailgun-subscription-form" method="post" action="%s">', $this->get_form_action());
		echo '<input type="hidden" name="mailgun-action" value="subscribe" />';
		echo '<ul class="mailgun-widget-lists">';
		foreach ( $instance['lists'] as $address ) {
			$list = new Mailing_List($address);
			if ( $list->is_hidden() ) {
				continue;
			}
			echo '<li>';
			printf( '<label><input type="checkbox" value="%s" name="mailgun-lists[]" /> %s</label>', esc_attr($list->get_address()), esc_html($list->get_name()) );
			if ( $description = $list->get_description() ) {
				printf( '<p class="description">%s</p>', $description );
			}
			echo '</li>';
		}
		echo '</ul>';
		echo '<p class="email-address">';
		printf( '<label for="mailgun-email-address-%d">%s</label>', $instance_counter, __('Email Address', 'mailgun-subscriptions') );
		$default_email = '';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$default_email = $user->user_email;
		}
		printf( '<input type="text" value="%s" name="mailgun-subscriber-email" size="20" id="mailgun-email-address-%d" />', $default_email, $instance_counter );
		echo '</p>';
		printf( '<p class="submit"><input type="submit" value="%s" /></p>', __('Subscribe', 'mailgun-subscriptions') );
		echo '</form>';
	}

	public function get_form_action() {
		$url = $_SERVER['REQUEST_URI'];
		foreach ( array('mailgun-message', 'mailgun-error', 'mailgun-action', 'ref') as $key ) {
			$url = remove_query_arg($key, $url);
		}
		return $url;
	}
} 