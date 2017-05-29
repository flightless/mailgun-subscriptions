<?php


namespace Mailgun_Subscriptions;


class Confirmation {
	const POST_TYPE = 'mailgun-confirmation';

	protected $id = '';
	protected $post_id = '';
	protected $address = '';
	protected $name = '';
	protected $confirmed = FALSE;
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

	/**
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	public function save() {
		if ( !$this->post_id ) {
			$this->id = empty($this->id) ? $this->generate_id() : $this->id;
			$this->post_id = wp_insert_post(array(
				'post_type' => self::POST_TYPE,
				'post_status' => 'publish',
				'post_author' => 0,
				'post_title' => sprintf( '%s (%s)', get_post_type_object( static::POST_TYPE )->labels->singular_name, $this->id ),
				'post_name' => $this->id,
			));
		}
		delete_post_meta( $this->post_id, '_mailgun_subscriber_lists' );
		update_post_meta( $this->post_id, '_mailgun_subscriber_address', $this->address );
		update_post_meta( $this->post_id, '_mailgun_subscriber_name', $this->name );
		update_post_meta( $this->post_id, '_mailgun_subscription_confirmed', $this->confirmed );
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
				'name' => $this->id,
				'posts_per_page' => 1,
				'fields' => 'ids',
			));
			if ( !$results ) {
				return;
			}
		}
		$this->post_id = reset($results);
		$this->address = get_post_meta($this->post_id, '_mailgun_subscriber_address', true);
		$this->name = get_post_meta($this->post_id, '_mailgun_subscriber_name', true);
		$this->lists = get_post_meta($this->post_id, '_mailgun_subscriber_lists', false);
		$this->confirmed = get_post_meta($this->post_id, '_mailgun_subscription_confirmed', true);
	}

	public function get_id() {
		return $this->id;
	}

	public function confirmed() {
		return $this->confirmed;
	}

	public function mark_confirmed() {
		$this->confirmed = TRUE;
		if ( $this->post_id ) {
			update_post_meta( $this->post_id, '_mailgun_subscription_confirmed', TRUE );
		}
	}

	public function expired() {
		if ( $this->post_id ) {
			$created = get_post_time('U', TRUE, $this->post_id);
			$age = time() - $created;

			$days = get_option( 'mailgun_confirmation_expiration', 7 );
			$threshold = $days * 24 * 60 * 60;
			return $age > $threshold;
		} else {
			return FALSE;
		}
	}
} 