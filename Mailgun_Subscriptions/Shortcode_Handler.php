<?php


namespace Mailgun_Subscriptions;


class Shortcode_Handler {
	public function register_shortcodes() {
		add_shortcode( 'mailgun_email', array( $this, 'email_shortcode' ) );
		add_shortcode( 'mailgun_lists', array( $this, 'lists_shortcode' ) );
	}

	public function email_shortcode( $atts, $content, $tag ) {
		$atts = shortcode_atts( array(
			'before' => '',
			'after' => '',
			'empty' => '',
		), $atts );
		$confirmation = Plugin::instance()->confirmation_handler()->get_confirmation();
		$address = $confirmation->get_address();
		if ( empty($address) ) {
			$address = $atts['empty'];
		}
		if ( empty($address) ) {
			return '';
		}
		return $atts['before'].esc_html($confirmation->get_address()).$atts['after'];
	}

	public function lists_shortcode( $atts, $content, $tag ) {
		$atts = shortcode_atts( array(
			'before' => '<ul>',
			'after' => '</ul>',
			'before_item' => '<li>',
			'after_item' => '</li>',
			'separator' => '',
		), $atts );
		$confirmation = Plugin::instance()->confirmation_handler()->get_confirmation();
		$subscribed_lists = $confirmation->get_lists();
		$all_lists = Plugin::instance()->get_lists('name');
		$items = array();
		foreach ( $all_lists as $list_address => $list_data ) {
			if ( in_array($list_address, $subscribed_lists) ) {
				$items[] = $atts['before_item'] . sprintf( __('%1$s (%2$s)', 'mailgun-subscriptions'), $list_data['name'], $list_address ) . $atts['after_item'];
			}
		}
		if ( !empty($items) ) {
			return $atts['before'].implode($atts['separator'], $items).$atts['after'];
		}
		return '';
	}
} 