<?php


namespace Mailgun_Subscriptions;

/**
 * Class Account_Management_Page
 *
 * Sets up and displays the account management page
 */
class Account_Management_Page {
	const SHORTCODE            = 'mailgun_account_management';
	const ACTION_REQUEST_TOKEN = 'request-token';
	const EMAIL_ADDRESS_FIELD  = 'mailgun_account_management_email_address';
	private $page_id = 0;
	/** @var Account_Management_Page_Authenticator */
	private $authenticator = null;

	public function __construct( $authenticator ) {
		$this->authenticator = $authenticator;
	}

	public function init() {
		$this->page_id = $this->get_page_id_option();
		if ( ! $this->page_id && current_user_can( 'edit_pages' ) ) {
			$this->create_default_page();
		}
		add_action( 'template_redirect', array( $this, 'disable_caching' ), 0, 0 );
		add_action( 'template_redirect', array( $this, 'setup_authentication_cookie' ), 10, 0 );

		add_action( 'trashed_post', array( $this, 'listen_for_page_deletion' ) );
		add_action( 'deleted_post', array( $this, 'listen_for_page_deletion' ) );

		add_action( 'the_post', array( $this, 'setup_postdata' ) );
	}

	public function get_page_url() {
		$id  = $this->get_page_id_option();
		$url = get_permalink( $id );

		return $url;
	}

	private function get_page_id_option() {
		return (int) get_option( Admin_Page::OPTION_ACCOUNT_PAGE, 0 );
	}

	/**
	 * Automatically creates the subscription management page
	 *
	 * @return void
	 */
	public function create_default_page() {
		$this->page_id = wp_insert_post( array(
			'post_type'   => 'page',
			'post_title'  => __( 'Subscription Management', 'mailgun-subscriptions' ),
			'post_status' => 'publish',
		) );
		update_option( Admin_Page::OPTION_ACCOUNT_PAGE, $this->page_id );
	}

	public function disable_caching() {
		if ( get_queried_object_id() == $this->get_page_id_option() ) {
			$this->do_not_cache();
		}
	}

	/**
	 * Set headers to try to disable page caching for the current request
	 */
	public function do_not_cache() {
		nocache_headers(); // reverse proxies, browsers
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // W3TC, supercache
		}
		if ( function_exists( 'batcache_cancel' ) ) {
			batcache_cancel(); // batcache
		}
	}

	public function setup_authentication_cookie() {
		if ( get_queried_object_id() != $this->get_page_id_option() ) {
			return;
		}
		if ( isset( $_GET[ Account_Management_Page_Authenticator::EMAIL_ARG ] ) && isset( $_GET[ Account_Management_Page_Authenticator::HASH_ARG ] ) ) {
			$expiration   = time() + apply_filters( 'mailgun_subscriptions_auth_cookie_expiration', 14 * DAY_IN_SECONDS );
			$cookie_value = array(
				Account_Management_Page_Authenticator::EMAIL_ARG => $_GET[ Account_Management_Page_Authenticator::EMAIL_ARG ],
				Account_Management_Page_Authenticator::HASH_ARG  => $_GET[ Account_Management_Page_Authenticator::HASH_ARG ],
			);
			$cookie_value = json_encode( $cookie_value );
			setcookie(
				Account_Management_Page_Authenticator::COOKIE_NAME,
				$cookie_value,
				$expiration,
				COOKIEPATH,
				COOKIE_DOMAIN,
				false,
				true
			);
			wp_safe_redirect( $this->get_page_url() );
			exit();
		}
	}

	public function listen_for_page_deletion( $post_id ) {
		if ( $post_id == $this->page_id ) {
			$this->page_id = 0;
			update_option( Admin_Page::OPTION_ACCOUNT_PAGE, 0 );
		}
	}

	/**
	 * Spoof the global $post to hold just a shortcode for the account management page
	 *
	 * @param \WP_Post $post A reference to the global post object
	 *
	 * @return void
	 */
	public function setup_postdata( $post ) {
		if ( $post->ID != $this->page_id ) {
			return;
		}

		$GLOBALS[ 'pages' ]     = array( sprintf( '[%s]', self::SHORTCODE ) );
		$GLOBALS[ 'numpages' ]  = 1;
		$GLOBALS[ 'multipage' ] = 0;
	}

	public function get_page_contents() {
		switch ( $this->authenticator->validate() ) {
			case Account_Management_Page_Authenticator::VALID:
				$content = $this->get_account_page_content( $this->authenticator->get_email() );
				break;
			case Account_Management_Page_Authenticator::INVALID_HASH:
				$content = $this->get_invalid_hash_content();
				break;
			case Account_Management_Page_Authenticator::NO_USER:
			default:
				$content = $this->get_empty_page_content();
				break;
		}

		return $content;
	}

	private function get_message_codes() {
		$messages = isset( $_GET[ 'mailgun-account-message' ] ) ? $_GET[ 'mailgun-account-message' ] : array();
		if ( isset( $_GET[ 'mailgun-message' ] ) && $_GET[ 'mailgun-message' ] == 'submitted' ) {
			// special case when subscribing to a new list
			$messages[] = 'submitted';
		}

		return $messages;
	}

	private function get_account_page_content( $email_address ) {
		$lists    = $this->get_subscribed_lists( $email_address );
		$base_url = $this->get_page_url();
		ob_start();
		echo '<div class="mailgun-subscription-account-management">';

		$messages = $this->get_message_codes();
		if ( ! empty( $messages ) ) {
			$this->show_form_messages( $messages );
		}

		?>
		<form class="mailgun-change-email" action="<?php echo esc_url( $base_url ); ?>" method="post">
			<?php wp_nonce_field( 'change-email', 'nonce' ); ?>
			<input type="hidden" name="mailgun-action" value="change-email" />
			<p>
				<label for="mailgun-subscriber-email"><?php _e( 'Change email address: ', 'mailgun-subscriptions' ); ?></label>
				<span class="mailgun-change-email-controls">
					<input type="text" name="mailgun-subscriber-email" id="mailgun-subscriber-email" size="30" required value="<?php echo esc_attr( $email_address ); ?>" />
					<input type="submit" value="<?php _e( 'Submit', 'mailgun-subscriptions' ); ?>" />
				</span>
			</p>
		</form>
		<?php
		foreach ( $lists as $list_address => $list ) {
			$subscribe_url   = add_query_arg( array(
				'mailgun-action' => 'account-subscribe',
				'list'           => $list_address,
				'nonce'          => wp_create_nonce( 'account-subscribe' ),
			), $base_url );
			$unsubscribe_url = add_query_arg( array(
				'mailgun-action' => 'account-unsubscribe',
				'list'           => $list_address,
				'nonce'          => wp_create_nonce( 'account-unsubscribe' ),
			), $base_url );
			$resubscribe_url = add_query_arg( array(
				'mailgun-action' => 'account-resubscribe',
				'list'           => $list_address,
				'nonce'          => wp_create_nonce( 'account-resubscribe' ),
			), $base_url );
			echo '<div class="mailgun-subscription-details">';
			echo '<h3>', esc_html( $list[ 'name' ] ), '</h3>';
			if ( $list[ 'member' ] ) {
				if ( ! $list[ 'subscribed' ] ) {
					echo $this->unsubscribed_list( $resubscribe_url );
				} elseif ( $list[ 'suppressions' ][ 'unsubscribes' ] ) {
					echo $this->unsubscribed_domain_list( $resubscribe_url );
				} elseif ( $list[ 'suppressions' ][ 'bounces' ] ) {
					echo $this->bounced_list( $resubscribe_url );
				} elseif ( $list[ 'suppressions' ][ 'complaints' ] ) {
					echo $this->spammed_list( $resubscribe_url );
				} else {
					echo $this->subscribed_list( $unsubscribe_url );
				}
			} else {
				echo $this->not_subscribed_list( $subscribe_url );
			}
			echo '</div>';
		}
		echo '</div>';

		return ob_get_clean();
	}

	private function subscribed_list( $unsubscribe_url ) {
		return sprintf(
			'<p class="subscribe current-status">%s</p><p class="unsubscribe"><a href="%s">%s</a></p>',
			__( 'Subscribed', 'mailgun-subscriptions' ),
			esc_url( $unsubscribe_url ),
			__( 'Unsubscribe »', 'mailgun-subscriptions' )
		);
	}

	private function not_subscribed_list( $subscribe_url ) {
		return sprintf(
			'<p class="subscribe"><a href="%s">%s</a></p><p class="not-subscribed unsubscribe current-status">%s</p>',
			esc_url( $subscribe_url ),
			__( 'Subscribe »', 'mailgun-subscriptions' ),
			__( 'Not subscribed', 'mailgun-subscriptions' )
		);
	}

	private function unsubscribed_list( $resubscribe_url ) {
		return sprintf(
			'<p class="subscribe"><a href="%s">%s</a></p><p class="unsubscribe current-status">%s</p>',
			esc_url( $resubscribe_url ),
			__( 'Subscribe »', 'mailgun-subscriptions' ),
			__( 'Unsubscribed', 'mailgun-subscriptions' )
		);
	}

	private function unsubscribed_domain_list( $resubscribe_url ) {
		return sprintf(
			'<p class="subscribe"><a href="%s">%s</a></p><p class="unsubscribe current-status">%s</p>',
			esc_url( $resubscribe_url ),
			__( 'Subscribe »', 'mailgun-subscriptions' ),
			__( 'Unsubscribed <span class="description">(you have requested to unsubscribe from all emails from this domain)</span>', 'mailgun-subscriptions' )
		);
	}

	private function spammed_list( $resubscribe_url ) {
		return sprintf(
			'<p class="subscribe"><a href="%s">%s</a></p><p class="unsubscribe current-status">%s</p>',
			esc_url( $resubscribe_url ),
			__( 'Subscribe »', 'mailgun-subscriptions' ),
			__( 'Unsubscribed <span class="description">(you reported this list as spam)</span>', 'mailgun-subscriptions' )
		);
	}

	private function bounced_list( $resubscribe_url ) {
		return sprintf(
			'<p class="subscribe"><a href="%s">%s</a></p><p class="unsubscribe current-status">%s</p>',
			esc_url( $resubscribe_url ),
			__( 'Subscribe »', 'mailgun-subscriptions' ),
			__( 'Unsubscribed <span class="description">(your email address bounced)</span>', 'mailgun-subscriptions' )
		);
	}

	private function get_subscribed_lists( $email_address ) {
		$member = new List_Member( $email_address );
		return $member->get_subscribed_lists();
	}

	private function get_invalid_hash_content() {
		$email  = $this->authenticator->get_email();
		$errors = array( 'invalid-hash' );

		return $this->get_request_email_form( $email, $errors );
	}

	private function get_empty_page_content() {
		$messages = $this->get_message_codes();

		return $this->get_request_email_form( '', $messages );
	}

	private function get_request_email_form( $default_address = '', $errors = array() ) {
		ob_start();

		if ( ! empty( $errors ) ) {
			$this->show_form_messages( $errors );
		}
		?>
		<form action="" method="post">
			<?php wp_nonce_field( self::ACTION_REQUEST_TOKEN ); ?>
			<input type="hidden" value="<?php echo self::ACTION_REQUEST_TOKEN; ?>" name="mailgun-action"/>
			<p><?php _e( "Fill in your email address below and we'll send you a link to log in and manage your account.", 'mailgun-subscriptions' ); ?></p>
			<p><input type="text" value="<?php echo esc_attr( $default_address ); ?>"
								name="<?php echo self::EMAIL_ADDRESS_FIELD; ?>"
								placeholder="<?php esc_attr_e( 'e-mail address', 'mailgun-subscriptions' ); ?>"/></p>
			<p><input type="submit" value="<?php esc_attr_e( 'Send e-mail', 'mailgun-subscriptions' ); ?>"/></p>
		</form>
		<?php
		return ob_get_clean();
	}

	protected function show_form_messages( $message ) {
		if ( ! is_array( $message ) ) {
			$message = array( $message );
		}
		foreach ( $message as $code ) {
			echo '<p class="mailgun-message">', $this->get_message_string( $code ), '</p>';
		}
	}

	protected function get_message_string( $code ) {
		switch ( $code ) {
			case 'submitted':
				$message = __( 'Please check your email for a link to confirm your subscription.', 'mailgun-subscriptions' );
				break;
			case 'subscription-updated':
				$message = __( 'Subscription updated.', 'mailgun-subscriptions' );
				break;
			case 'request-submitted':
				$message = __( 'Request submitted. Your email should be arriving shortly.', 'mailgun-subscriptions' );
				break;
			case 'no-email':
				$message = __( 'Please verify that you have entered your email address correctly.', 'mailgun-subscriptions' );
				break;
			case 'invalid-nonce':
				$message = __( 'We were unable to validate your request. Please try submitting the form again.', 'mailgun-subscriptions' );
				break;
			case 'invalid-email':
				$message = __( 'We did not understand your email address. Please try submitting the form again.', 'mailgun-subscriptions' );
				break;
			case 'invalid-hash':
				$message = __( 'Your login URL has expired. Please request a new one.', 'mailgun-subscriptions' );
				break;
			case 'new-email-submitted':
				$message = __( 'Please check your email for a link to confirm your new address.', 'mailgun-subscriptions' );
				break;
			case 'new-email-confirmed':
				$message = __( 'Email address updated.', 'mailgun-subscriptions' );
				break;
			case 'not_found':
				$message = __( 'Your request could not be found. Please try again.', 'mailgun-subscriptions' );
				break;
			case 'already_confirmed':
				$message = __( 'You have already confirmed your request.', 'mailgun-subscriptions' );
				break;
			case 'expired':
				$message = __( 'Your request has expired. Please try again.', 'mailgun-subscriptions' );
				break;
			case 'address_change_failed':
				$message = __( 'We experienced a problem changing one or more subscriptions. Please try again.', 'mailgun-subscriptions' );
				break;
			default:
				$message = '';
				break;
		}
		$message = apply_filters( 'mailgun_message', $message, $code, 'widget' );

		return $message;
	}
}