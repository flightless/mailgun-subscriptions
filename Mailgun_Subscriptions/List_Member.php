<?php


namespace Mailgun_Subscriptions;


class List_Member {
	private $email_address = '';

	public function __construct( $email_address ) {
		$this->email_address = $email_address;
	}


	public function get_subscribed_lists( $with_suppressions = true ) {
		$api   = Plugin::instance()->api();
		$lists = Plugin::instance()->get_lists( 'name' );
		$lists = wp_list_filter( $lists, array( 'hidden' => true ), 'NOT' );
		foreach ( $lists as $list_address => &$list ) {
			$list[ 'member' ]       = false;
			$list[ 'suppressions' ] = array();
			$path                   = sprintf( 'lists/%s/members/%s', $list_address, $this->email_address );
			$response               = $api->get( $path );
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$body                   = wp_remote_retrieve_body( $response );
				$list[ 'member' ]       = true;
				$list[ 'subscribed' ]   = ! empty( $body->member->subscribed );
				$list[ 'suppressions' ] = $with_suppressions ? $this->get_suppressions( $list_address ) : array();
			}
		}

		return $lists;
	}

	private function get_suppressions( $list_address ) {
		$suppressions = Suppressions::instance( $this->email_address );

		return array(
			Suppressions::BOUNCES      => $suppressions->has_bounces( $list_address ),
			Suppressions::COMPLAINTS   => $suppressions->has_complaints( $list_address ),
			Suppressions::UNSUBSCRIBES => $suppressions->has_unsubscribes( $list_address ),
		);
	}
}