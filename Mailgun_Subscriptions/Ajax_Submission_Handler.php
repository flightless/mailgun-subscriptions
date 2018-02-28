<?php


namespace Mailgun_Subscriptions;


class Ajax_Submission_Handler extends Submission_Handler {

	protected function do_success_redirect() {
		$_GET[ 'mailgun-message' ] = 'submitted';
	}

	protected function do_error_redirect() {
		$_GET = array(
			'mailgun-message' => $this->errors,
			'mailgun-error'   => $this->transient_key,
		);
	}
} 