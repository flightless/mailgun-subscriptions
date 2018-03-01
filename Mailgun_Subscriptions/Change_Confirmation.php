<?php


namespace Mailgun_Subscriptions;


class Change_Confirmation extends Confirmation {
	protected $prior_address = '';


	public function set_prior_address( $address ) {
		$this->prior_address = $address;
	}

	public function get_prior_address() {
		return $this->prior_address;
	}

	public function save() {
		parent::save();
		update_post_meta( $this->post_id, '_mailgun_subscriber_prior_address', $this->prior_address );
	}

	protected function load() {
		parent::load();
		$this->prior_address = get_post_meta( $this->post_id, '_mailgun_subscriber_prior_address', true );
	}


}