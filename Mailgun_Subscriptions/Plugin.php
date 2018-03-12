<?php


namespace Mailgun_Subscriptions;


class Plugin {
	const VERSION = '1.3.1';

	/** @var Plugin */
	private static $instance = null;

	private static $plugin_file = '';

	/** @var Admin_Page */
	private $admin_page = null;

	/** @var Submission_Handler */
	private $submission_handler = null;

	/** @var Confirmation_Handler */
	private $confirmation_handler = null;

	/** @var Shortcode_Handler */
	private $shortcode_handler = null;

	/** @var Account_Management_Page */
	private $account_management_page = null;

	/** @var Account_Management_Token_Request_Handler */
	private $token_request_handler = null;

	/** @var Change_Email_Request_Handler */
	private $change_email_handler = null;

	/** @var Account_Management_Subscription_Request_Handler */
	private $account_manangement_subscription_handler = null;

	public function api( $public = false ) {
		if ( $public ) {
			return new API( get_option( 'mailgun_api_public_key' ) );
		} else {
			return new API( get_option( 'mailgun_api_key' ) );
		}
	}

	public function admin() {
		return $this->admin_page;
	}

	public function submission_handler() {
		return $this->submission_handler;
	}

	public function confirmation_handler() {
		return $this->confirmation_handler;
	}

	public function shortcode_handler() {
		return $this->shortcode_handler;
	}

	public function account_management_page() {
		return $this->account_management_page;
	}

	public function token_request_handler() {
		return $this->token_request_handler;
	}

	private function setup( $plugin_file ) {
		self::$plugin_file = $plugin_file;
		if ( is_admin() ) {
			$this->setup_admin_page();
		}
		add_action( 'init', array( $this, 'setup_confirmations' ) );
		Cleanup::init();
		$this->setup_frontend_ui();

		if ( ! is_admin() ) {
			if ( ! empty( $_REQUEST[ 'mailgun-action' ] ) ) {
				switch ( $_REQUEST[ 'mailgun-action' ] ) {
					case 'subscribe':
						$this->setup_submission_handler();
						break;
					case 'confirm':
						$this->setup_confirmation_handler();
						break;
					case 'request-token':
						$this->setup_token_request_handler();
						break;
					case 'account-subscribe':
					case 'account-resubscribe':
					case 'account-unsubscribe':
						$this->setup_account_management_handler();
						break;
					case 'change-email':
					case 'confirm-change-email':
						$this->setup_change_email_handler();
						break;
				}
			}
			add_action( 'wp', array( $this, 'setup_confirmation_page' ), 10, 0 );
		}
	}

	private function setup_frontend_ui() {
		add_action( 'mailgun_form_message', array(
			__NAMESPACE__ . '\\Subscription_Form',
			'form_message_callback',
		), 10, 3 );
		add_action( 'mailgun_form_content', array(
			__NAMESPACE__ . '\\Subscription_Form',
			'form_contents_callback',
		), 10, 2 );
		add_action( 'wp_ajax_mailgun_subscribe', array(
			__NAMESPACE__ . '\\Subscription_Form',
			'ajax_request_handler',
		), 10, 0 );
		add_action( 'wp_ajax_nopriv_mailgun_subscribe', array(
			__NAMESPACE__ . '\\Subscription_Form',
			'ajax_request_handler',
		), 10, 0 );
		$this->setup_widget();
		$this->setup_shortcodes();
		$this->setup_account_management_page();
		add_action( 'mailgun_enqueue_assets', array( $this, 'enqueue_assets' ), 10, 0 );
	}

	public function enqueue_assets() {
		$css_path = plugins_url( 'assets/mailgun-subscriptions.css', dirname( __FILE__ ) );
		$css_path = apply_filters( 'mailgun_css_path', $css_path );
		if ( $css_path ) {
			wp_enqueue_style( 'mailgun-subscriptions', $css_path, array(), self::VERSION );
		}
		$js_path = plugins_url( 'assets/mailgun-subscriptions.js', dirname( __FILE__ ) );
		$js_path = apply_filters( 'mailgun_js_path', $js_path );
		if ( $js_path ) {
			wp_enqueue_script( 'mailgun-subscriptions', $js_path, array( 'jquery' ), self::VERSION, true );
		}

		$js_data = apply_filters( 'mailgun_subscriptions_js_config', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );
		wp_localize_script( 'mailgun-subscriptions', 'MailgunSubscriptions', $js_data );
	}

	private function setup_admin_page() {
		$this->admin_page = new Admin_Page();
		add_action( 'admin_menu', array( $this->admin_page, 'register' ), 10, 0 );
		add_action( 'load-settings_page_' . Admin_Page::MENU_SLUG, array( $this->admin_page, 'refresh_caches' ), 10, 0 );
	}

	private function setup_widget() {
		add_action( 'widgets_init', array( __NAMESPACE__ . '\\Widget', 'register' ), 10, 0 );
	}

	public function setup_confirmations() {
		$pt = new Post_Type_Registrar();
		$pt->register();
	}

	public function setup_confirmation_handler() {
		$this->confirmation_handler = new Confirmation_Handler( $_GET );
		add_action( 'parse_request', array( $this->confirmation_handler, 'handle_request' ), 10, 0 );
	}

	private function setup_submission_handler() {
		$this->submission_handler = new Submission_Handler( $_POST );
		add_action( 'parse_request', array( $this->submission_handler, 'handle_request' ), 10, 0 );
	}

	public function setup_confirmation_page() {
		if ( is_page() && get_queried_object_id() == get_option( 'mailgun_confirmation_page', 0 ) ) {

			if ( ! $this->confirmation_handler ) {
				$this->setup_confirmation_handler();
				$this->confirmation_handler->handle_request(); // sets up error messages
			}

			add_filter( 'the_post', array( $this->confirmation_handler, 'setup_page_data' ), 10, 1 );
		}
	}

	public function setup_shortcodes() {
		$this->shortcode_handler = new Shortcode_Handler();
		$this->shortcode_handler->register_shortcodes();
	}

	public function setup_account_management_page() {
		$this->account_management_page = new Account_Management_Page( new Account_Management_Page_Authenticator( $_COOKIE ) );
		add_action( 'init', array( $this->account_management_page, 'init' ) );
	}

	public function setup_token_request_handler() {
		$this->token_request_handler = new Account_Management_Token_Request_Handler( $_POST );
		add_action( 'parse_request', array( $this->token_request_handler, 'handle_request' ), 10, 0 );
	}

	public function setup_account_management_handler() {
		$this->account_manangement_subscription_handler = new Account_Management_Subscription_Request_Handler( $_GET, new Account_Management_Page_Authenticator( $_COOKIE ) );
		add_action( 'parse_request', array( $this->account_manangement_subscription_handler, 'handle_request' ), 10, 0 );
	}

	public function setup_change_email_handler() {
		$this->change_email_handler = new Change_Email_Request_Handler( $_REQUEST, new Account_Management_Page_Authenticator( $_COOKIE ) );
		add_action( 'parse_request', array( $this->change_email_handler, 'handle_request' ), 10, 0 );
	}

	public function get_lists( $orderby = 'address' ) {
		$lists = get_option( 'mailgun_lists', array() );
		switch ( $orderby ) {
			case 'name':
				uasort( $lists, array( $this, 'sort_by_name' ) );
				break;
			case 'address':
			default:
				asort( $lists );
				break;
		}

		return $lists;
	}

	public function sort_by_name( $a, $b ) {
		if ( $a[ 'name' ] == $b[ 'name' ] ) {
			return 0;
		}

		return ( $a[ 'name' ] > $b[ 'name' ] ) ? 1 : - 1;
	}

	public static function init( $file ) {
		self::instance()->setup( $file );
	}

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
} 