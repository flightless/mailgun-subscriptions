<?php


namespace Mailgun_Subscriptions;


class Suppressions {
	const BOUNCES      = 'bounces';
	const UNSUBSCRIBES = 'unsubscribes';
	const COMPLAINTS   = 'complaints';

	/**
	 * @var self[] For efficiency, instances are stored and reusable
	 */
	private static $instances = array();

	private $email_address = '';
	private $api           = null;
	private $suppressions  = array(
		'bounces'      => array(),
		'unsubscribes' => array(),
		'complaints'   => array(),
	);

	public function __construct( $email_address ) {
		$this->email_address = $email_address;
		$this->api = Plugin::instance()->api();
	}

	public function has_bounces( $list ) {
		return $this->has_suppressions( self::BOUNCES, $list );
	}

	public function has_unsubscribes( $list ) {
		return $this->has_suppressions( self::UNSUBSCRIBES, $list );
	}

	public function has_complaints( $list ) {
		return $this->has_suppressions( self::COMPLAINTS, $list );
	}

	public function clear_all( $list ) {
		$this->clear_bounces( $list );
		$this->clear_complaints( $list );
		$this->clear_unsubscribes( $list );
	}

	public function clear_bounces( $list ) {
		$this->clear_suppressions( self::BOUNCES, $list );
	}

	public function clear_unsubscribes( $list ) {
		$this->clear_suppressions( self::UNSUBSCRIBES, $list );
	}

	public function clear_complaints( $list ) {
		$this->clear_suppressions( self::COMPLAINTS, $list );
	}

	private function extract_domain( $list ) {
		$parts = explode( '@', $list );
		return end( $parts );
	}

	private function has_suppressions( $type, $list ) {
		$suppressions = $this->get_suppressions( $type, $list );
		return !empty( $suppressions );
	}

	private function clear_suppressions( $type, $list ) {
		$domain = $this->extract_domain( $list );
		$endpoint = $this->get_endpoint( $type, $domain );
		$response = $this->api->delete( $endpoint );

		if ( !$response || $response[ 'response' ][ 'code' ] != 200 ) {
			return false;
		}
		unset( $this->suppressions[ $type ][ $domain ] );
		return true;
	}

	private function get_suppressions( $type, $list ) {
		$domain = $this->extract_domain( $list );
		if ( !isset( $this->suppressions[ $type ][ $domain ] ) ) {
			$this->suppressions[ $type ][ $domain ] = $this->fetch_from_api( $type, $domain );
		}
		return $this->suppressions[ $type ][ $domain ];
	}

	private function fetch_from_api( $type, $domain ) {
		$endpoint = $this->get_endpoint( $type, $domain );
		$response = $this->api->get( $endpoint );

		if ( !$response || $response[ 'response' ][ 'code' ] != 200 ) {
			return false;
		}

		return $response[ 'body' ];
	}

	private function get_endpoint( $type, $domain ) {
		return sprintf( '%s/%s/%s', $domain, $type, $this->email_address );
	}

	/**
	 * Get an instance of this class for the given email address.
	 * Works just like the constructor, but returns previously
	 * used instances when possible to avoid extra API calls.
	 * 
	 * @param string $email_address
	 * @return self
	 */
	public static function instance( $email_address ) {
		if ( !isset( self::$instances[ $email_address ] ) ) {
			self::$instances[ $email_address ] = new self( $email_address );
		}
		return self::$instances[ $email_address ];
	}
}