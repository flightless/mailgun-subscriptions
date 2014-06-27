<?php


namespace Mailgun_Subscriptions;


class Mailing_List {
	private $address = '';
	public function __construct( $address ) {
		$this->address = trim($address);
	}

	public function exists() {
		$settings = $this->get_settings();
		return $settings !== FALSE;
	}

	public function get_description() {
		$settings = $this->get_settings();
		return $this->exists() ? $settings['description'] : '';
	}

	public function get_name() {
		$settings = $this->get_settings();
		return $this->exists() ? $settings['name'] : '';
	}

	public function get_address() {
		return $this->address;
	}

	public function is_hidden() {
		$settings = $this->get_settings();
		return $this->exists() ? (bool)$settings['hidden'] : FALSE;
	}

	protected function get_settings() {
		$settings = get_option('mailgun_lists');
		if ( isset($settings[$this->address]) ) {
			return wp_parse_args($settings[$this->address], array(
				'hidden' => FALSE,
				'name' => '',
				'description' => '',
			));
		} else {
			return FALSE;
		}
	}
} 