<?php


namespace Mailgun_Subscriptions;


class Confirmation {
	const POST_TYPE = 'mailgun-confirmation';

	protected $id = '';
	protected $post_id = '';
	protected $address = '';
	protected $lists = array();

	public function __construct( $confirmation_id = '' ) {
		$this->id = $confirmation_id;
		if ( $this->id ) {
			$this->load();
		}
	}

	public function set_address( $address ) {
		$this->address = $address;
	}

	public function get_address() {
		return $this->address;
	}

	/**
	 * @param array $lists
	 */
	public function set_lists( array $lists ) {
		$this->lists = $lists;
	}

	/**
	 * @return array
	 */
	public function get_lists() {
		return $this->lists;
	}


	public function save() {
		if ( !$this->post_id ) {
			$this->id = empty($this->id) ? $this->generate_id() : $this->id;
			$this->post_id = wp_insert_post(array(
				'post_type' => self::POST_TYPE,
				'post_status' => 'publish',
				'post_author' => 0,
				'post_title' => $this->id,
			));
		}
		delete_post_meta( $this->post_id, '_mailgun_subscriber_lists' );
		update_post_meta( $this->post_id, '_mailgun_subscriber_address', $this->address );
		foreach ( $this->lists as $list ) {
			add_post_meta( $this->post_id, '_mailgun_subscriber_lists', $list );
		}
	}

	protected function generate_id() {
		return wp_generate_password(32, false, false);
	}

	protected function load() {
		if ( empty($this->post_id) ) {
			$results = get_posts(array(
				'post_type' => self::POST_TYPE,
				'post_status' => 'publish',
				'title' => $this->id,
				'posts_per_page' => 1,
				'fields' => 'ids',
			));
			if ( !$results ) {
				return;
			}
		}
		$this->post_id = reset($results);
		$this->address = get_post_meta($this->post_id, '_mailgun_subscriber_address', true);
		$this->lists = get_post_meta($this->post_id, '_mailgun_subscriber_lists', false);
	}

	public function get_id() {
		return $this->id;
	}
} 