<?php


namespace Mailgun_Subscriptions;


class API {
	private $key = '';
	private $url = '';
	public function __construct( $apiKey, $apiEndpoint = "api.mailgun.net", $apiVersion = "v2", $ssl = true ) {
		$this->key = $apiKey;
		$this->url = $this->build_base_url( $apiEndpoint, $apiVersion, $ssl );
	}

	public function get( $endpoint, $args = array() ) {
		$url = $this->url . $endpoint;
		$response = wp_remote_get(
			$url,
			array(
				'body' => $args,
				'headers' => $this->get_request_headers(),
			)
		);
		if ( is_wp_error($response) ) {
			return FALSE;
		}
		$response['body'] = json_decode($response['body']);
		return $response;
	}

	public function post( $endpoint, $args = array() ) {
		$url = $this->url . $endpoint;
		$response = wp_remote_post(
			$url,
			array(
				'body' => $args,
				'headers' => $this->get_request_headers(),
			)
		);
		if ( is_wp_error($response) ) {
			return FALSE;
		}
		$response['body'] = json_decode($response['body']);
		return $response;
	}

	public function validate_email( $address ) {
		$response = $this->get( 'address/validate', array(
			'address' => $address,
		));
		if ( !$response || $response['response']['code'] != 200 ) {
			return FALSE;
		}
		return $response['body']->is_valid;
	}

	private function build_base_url( $apiEndpoint = "api.mailgun.net", $apiVersion = "v2", $ssl = TRUE ) {
		return 'http' . ( $ssl ? 's' : '' ) . '://' . $apiEndpoint . '/' . $apiVersion . '/';
	}

	private function get_request_headers() {
		return array(
			'Authorization' => $this->get_auth_header_value(),
		);
	}

	private function get_auth_header_value() {
		return 'Basic '.base64_encode( 'api:' . $this->key );
	}
} 