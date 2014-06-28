<?php


namespace Mailgun_Subscriptions;


class Cleanup {
	const WP_CRON_HOOK = 'mailgun-subscriptions-cleanup';

	public static function init() {
		if ( !wp_next_scheduled(self::WP_CRON_HOOK) ) {
			$schedule = apply_filters( 'mailgun-subscriptions-cleanup-schedule', 'daily' );
			wp_schedule_event( time(), $schedule, self::WP_CRON_HOOK );
		}

		add_action( self::WP_CRON_HOOK, array( __CLASS__, 'run' ) );
	}

	public static function run() {
		$cleaner = new self();
		$cleaner->delete_old_requests();
	}

	/**
	 * @return void
	 */
	protected function delete_old_requests() {
		$post_ids = $this->get_post_ids_to_delete();
		foreach ( $post_ids as $id ) {
			wp_delete_post( $id, TRUE );
		}
	}

	protected function get_post_ids_to_delete() {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_date < %s";
		$sql = $wpdb->prepare( $sql, Confirmation::POST_TYPE, $this->get_cutoff_date() );
		return $wpdb->get_col( $sql );
	}

	protected function get_cutoff_date() {
		$now = current_time('timestamp');
		$then = $now - $this->get_cutoff_duration();
		return date('Y-m-d H:i:s', $then);
	}

	protected function get_cutoff_duration() {
		$days = get_option( 'mailgun_confirmation_expiration', 7 );
		return $days * 24 * 60 * 60;
	}
} 