<?php


namespace Mailgun_Subscriptions;


class Subscription_Form {
	public function display( $args ) {
		$args = wp_parse_args( $args, array(
			'description' => '',
			'lists' => array(),
		));

		do_action( 'mailgun_enqueue_assets' );

		if ( !empty($_GET['mailgun-message']) ) {
			do_action( 'mailgun_form_message', $_GET['mailgun-message'], !empty($_GET['mailgun-error']), $this );
		}

		if ( empty($_GET['mailgun-message']) || !empty($_GET['mailgun-error']) ) {
			do_action( 'mailgun_form_content', $args, $this );
		}
	}

	/**
	 * @param string $message_code
	 * @param bool $error
	 * @param self $form
	 *
	 * @return void
	 */
	public static function form_message_callback( $message_code, $error, $form ) {
		$form->show_form_message( $message_code, $error );
	}

	/**
	 * @param string $message
	 * @param bool $error
	 *
	 * @return void
	 */
	protected function show_form_message( $message, $error = FALSE ) {
		if ( !is_array($message) ) {
			$message = array($message);
		}
		$error_class = $error ? ' error' : '';
		foreach ( $message as $code ) {
			echo '<p class="mailgun-message'.$error_class.'">', $this->get_message_string($code), '</p>';
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
			case 'unsubscribed':
				$message = __('You have previously unsubscribed. Please contact us to reactivate your account.', 'mailgun-subscriptions');
				break;
			case 'already-subscribed':
				$message = __('You are already subscribed. Please contact us if you have trouble receiving messages.', 'mailgun-subscriptions');
				break;
			default:
				$message = '';
				break;
		}
		$message = apply_filters( 'mailgun_message', $message, $code, 'widget' );
		return $message;
	}

	/**
	 * @param array $instance
	 * @param self $form
	 *
	 * @return void
	 */
	public static function form_contents_callback( $instance, $form ) {
		$form->do_form_contents($instance);
	}

	/**
	 * @param array $instance
	 *
	 * @return void
	 */
	protected function do_form_contents( $instance ) {
		static $instance_counter = 0;
		$instance_counter++;

		$description = apply_filters( 'mailgun_subscription_form_description', $instance['description'] );
		if ( $description ) {
			echo '<div class="mailgun-form-description">'.$description.'</div>';
		}

		printf('<form class="mailgun-subscription-form" method="post" action="%s">', $this->get_form_action());
		echo '<input type="hidden" name="mailgun-action" value="subscribe" />';
		if ( count($instance['lists']) > 1 ) {
			echo '<ul class="mailgun-subscription-form-lists">';
			foreach ( $instance['lists'] as $address ) {
				$list = new Mailing_List($address);
				$this->print_list_option($list);
			}
			echo '</ul>';
		} else {
			echo '<p class="mailgun-subscription-form-lists single-list">';
			$list = new Mailing_List(reset($instance['lists']));
			$this->print_solitary_list($list);
			echo '</p>';
		}
		if ( !empty($instance['name']) ) {
			echo '<p class="full-name">';
			printf( '<label for="mailgun-full-name-%d">%s</label> ', $instance_counter, __('Full Name', 'mailgun-subscriptions') );
			printf( '<input type="text" name="mailgun-subscriber-name" size="20" id="mailgun-full-name-%d" required placeholder="%s" />', $instance_counter, __('Full Name', 'mailgun-subscriptions') );
			echo '</p>';
		}

		echo '<p class="email-address">';
		printf( '<label for="mailgun-email-address-%d">%s</label> ', $instance_counter, __('Email Address', 'mailgun-subscriptions') );
		$default_email = '';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$default_email = $user->user_email;
		}
		printf( '<input type="text" value="%s" name="mailgun-subscriber-email" size="20" id="mailgun-email-address-%d" required placeholder="%s" />', $default_email, $instance_counter, __('Email', 'mailgun-subscriptions') );
		echo '</p>';
		printf( '<p class="submit"><input type="submit" value="%s" /></p>', apply_filters( 'mailgun_subscription_form_button_label', __('Subscribe', 'mailgun-subscriptions') ) );
		echo '</form>';
	}

	/**
	 * @param Mailing_List $list
	 *
	 * @return void
	 */
	protected function print_list_option( $list ) {
		if ( !$list->exists() || $list->is_hidden() ) {
			return;
		}
		echo '<li>';
		printf( '<label class="mailgun-list-name"><input type="checkbox" value="%s" name="mailgun-lists[]" %s /> %s</label>', esc_attr($list->get_address()), checked(apply_filters('mailgun_is_checked', FALSE, $list->get_address()), TRUE, FALSE), esc_html($list->get_name()) );
		if ( $description = $list->get_description() ) {
			printf( '<span class="sep"> &ndash; </span><span class="mailgun-list-description">%s</span>', $description );
		}
		echo '</li>';
	}


	/**
	 * @param Mailing_List $list
	 *
	 * @return void
	 */
	protected function print_solitary_list( $list ) {
		if ( $list->exists() && !$list->is_hidden() ) {
			printf( '<label class="mailgun-list-name"><input type="hidden" value="%s" name="mailgun-lists[]" />%s</label>', esc_attr($list->get_address()), esc_html($list->get_name()) );
			if ( $description = $list->get_description() ) {
				printf( '<span class="sep"> &ndash; </span><span class="mailgun-list-description">%s</span>', $description );
			}
		}
	}

	protected function get_form_action() {
		$url = $_SERVER['REQUEST_URI'];
		foreach ( array('mailgun-message', 'mailgun-error', 'mailgun-action', 'ref') as $key ) {
			$url = remove_query_arg($key, $url);
		}
		return $url;
	}
}
