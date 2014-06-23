<?php


namespace Mailgun_Subscriptions;


class Plugin {
	/** @var Plugin */
	private static $instance = NULL;

	private static $plugin_file = '';

	/** @var Admin_Page */
	private $admin_page = NULL;

	/** @var Submission_Handler */
	private $submission_handler = NULL;

	public function api() {
		return new API(get_option('mailgun_api_key'));
	}

	public function admin() {
		return $this->admin_page;
	}

	public function handler() {
		return $this->submission_handler;
	}

	private function setup( $plugin_file ) {
		self::$plugin_file = $plugin_file;
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
		$this->setup_admin_page();
		$this->setup_widget();
		if ( !empty($_REQUEST['mailgun-action']) ) {
			$this->setup_submission_handler();
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

	private function setup_submission_handler() {
		$this->submission_handler = new Submission_Handler($_POST);
		add_action( 'parse_request', array( $this->submission_handler, 'handle_request' ), 10, 0 );
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