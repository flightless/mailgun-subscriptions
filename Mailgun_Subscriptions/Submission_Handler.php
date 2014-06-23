<?php


namespace Mailgun_Subscriptions;


class Submission_Handler {
	protected $submission = array();

	public function __construct( $submission ) {
		$this->submission = $submission;
	}

	public function handle_request() {
		// TODO
		wp_safe_redirect($_SERVER['REQUEST_URI']);
		exit();
	}
} 