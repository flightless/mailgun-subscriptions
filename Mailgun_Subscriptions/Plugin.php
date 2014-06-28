<?php


namespace Mailgun_Subscriptions;


class Plugin {
	const VERSION = '1.0';

	/** @var Plugin */
	private static $instance = NULL;

	private static $plugin_file = '';

	/** @var Admin_Page */
	private $admin_page = NULL;

	/** @var Submission_Handler */
	private $submission_handler = NULL;

	/** @var Confirmation_Handler */
	private $confirmation_handler = NULL;

	/** @var Shortcode_Handler */
	private $shortcode_handler = NULL;

	public function api( $public = FALSE ) {
		if ( $public ) {
			return new API(get_option('mailgun_api_public_key'));
		} else {
			return new API(get_option('mailgun_api_key'));
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

	private function setup( $plugin_file ) {
		self::$plugin_file = $plugin_file;
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
		if ( is_admin() ) {
			$this->setup_admin_page();
		}
		add_action( 'init', array( $this, 'setup_confirmations' ) );
		Cleanup::init();
		$this->setup_frontend_ui();

		if ( !is_admin() ) {
			if ( !empty($_POST['mailgun-action']) && $_POST['mailgun-action'] == 'subscribe' ) {
				$this->setup_submission_handler();
			}
			if ( !empty($_GET['mailgun-action']) && $_GET['mailgun-action'] == 'confirm' ) {
				$this->setup_confirmation_handler();
			}

			add_action( 'wp', array( $this, 'setup_confirmation_page' ), 10, 0 );
		}
	}

	private function setup_frontend_ui() {
		add_action( 'mailgun_form_message', array( __NAMESPACE__.'\\Subscription_Form', 'form_message_callback' ), 10, 3 );
		add_action( 'mailgun_form_content', array( __NAMESPACE__.'\\Subscription_Form', 'form_contents_callback' ), 10, 2 );
		$this->setup_widget();
		$this->setup_shortcodes();
		add_action( 'mailgun_enqueue_assets', array( $this, 'enqueue_assets' ), 10, 0 );
	}

	public function enqueue_assets() {
		$css_path = plugins_url( 'assets/mailgun-subscriptions.css', dirname(__FILE__) );
		$css_path = apply_filters( 'mailgun_css_path', $css_path );
		if ( $css_path ) {
			wp_enqueue_style( 'mailgun-subscriptions', $css_path, array(), self::VERSION );
		}
	}

	private function setup_admin_page() {
		$this->admin_page = new Admin_Page();
		add_action( 'admin_menu', array( $this->admin_page, 'register' ), 10, 0 );
		add_action( 'load-settings_page_'.Admin_Page::MENU_SLUG, array( $this->admin_page, 'refresh_caches' ), 10, 0 );
	}

	private function setup_widget() {
		add_action( 'widgets_init', array( __NAMESPACE__.'\\Widget', 'register' ), 10, 0 );
	}

	public function setup_confirmations() {
		$pt = new Post_Type_Registrar();
		$pt->register();
	}

	public function setup_confirmation_handler() {
		$this->confirmation_handler = new Confirmation_Handler($_GET);
		add_action( 'parse_request', array( $this->confirmation_handler, 'handle_request' ), 10, 0 );
	}

	private function setup_submission_handler() {
		$this->submission_handler = new Submission_Handler($_POST);
		add_action( 'parse_request', array( $this->submission_handler, 'handle_request' ), 10, 0 );
	}

	public function setup_confirmation_page() {
		if ( is_page() && get_queried_object_id() == get_option('mailgun_confirmation_page', 0) ) {

			if ( !$this->confirmation_handler ) {
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

	public function get_lists( $orderby = 'address' ) {
		$lists = get_option( 'mailgun_lists', array() );
		switch ( $orderby ) {
			case 'name':
				uasort($lists, array( $this, 'sort_by_name' ) );
				break;
			case 'address':
			default:
				asort($lists);
				break;
		}
		return $lists;
	}

	public function sort_by_name( $a, $b ) {
		if ( $a['name'] == $b['name'] ) {
			return 0;
		}

		return ( $a['name'] > $b['name'] ) ? 1 : -1;
	}

	public static function init( $file ) {
		self::instance()->setup( $file );
	}

	public static function instance() {
		if ( !isset(self::$instance) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function autoload( $class ) {
		if (substr($class, 0, strlen(__NAMESPACE__)) != __NAMESPACE__) {
			//Only autoload libraries from this package
			return;
		}
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
		$path = dirname(self::$plugin_file) . DIRECTORY_SEPARATOR . $path . '.php';
		if (file_exists($path)) {
			require $path;
		}
	}
} 