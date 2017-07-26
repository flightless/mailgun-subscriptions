<?php


namespace Mailgun_Subscriptions;


class Shortcode_Handler {
	public function register_shortcodes() {
		add_shortcode( 'mailgun_email', array( $this, 'email_shortcode' ) );
		add_shortcode( 'mailgun_lists', array( $this, 'lists_shortcode' ) );
		add_shortcode( 'mailgun_subscription_form', array( $this, 'form_shortcode' ) );
		add_shortcode( Account_Management_Page::SHORTCODE, array( $this, 'account_management_shortcode' ) );
	}

	public function email_shortcode( $atts, $content = '', $tag = '' ) {
		$atts = shortcode_atts( array(
			'before' => '',
			'after' => '',
			'empty' => '',
		), $atts );
		$handler = Plugin::instance()->confirmation_handler();
		if ( !$handler ) {
			return '';
		}
		$confirmation = $handler->get_confirmation();
		$address = $confirmation->get_address();
		if ( empty($address) ) {
			$address = $atts['empty'];
		}
		if ( empty($address) ) {
			return '';
		}
		return $atts['before'].esc_html($confirmation->get_address()).$atts['after'];
	}

	public function lists_shortcode( $atts, $content = '', $tag = '' ) {
		$atts = shortcode_atts( array(
			'before' => '<ul>',
			'after' => '</ul>',
			'before_item' => '<li>',
			'after_item' => '</li>',
			'separator' => '',
		), $atts );
		$handler = Plugin::instance()->confirmation_handler();
		if ( !$handler ) {
			return '';
		}
		$confirmation = $handler->get_confirmation();
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

	public function form_shortcode( $atts, $content = '', $tag = '' ) {
		$atts = shortcode_atts(array(
			'description' => '',
			'lists' => '',
			'name' => false,
		), $atts);
		if ( empty($atts['lists']) ) {
			$lists = $this->get_visible_list_addresses();
		} else {
			$lists = array_filter(preg_split('/( |,)+/', $atts['lists']));
		}

		$form = new Subscription_Form();
		ob_start();
		$form->display(array(
			'description' => $atts['description'],
			'lists' => $lists,
			'name' => wp_validate_boolean($atts['name']),
		));
		return ob_get_clean();
	}

	protected function get_visible_list_addresses() {
		$lists = Plugin::instance()->get_lists('name');
		$lists = wp_list_filter( $lists, array( 'hidden' => true ), 'NOT' );
		return array_keys($lists);
	}

	public function account_management_shortcode( $atts, $content = '', $tag = '' ) {
		$page = Plugin::instance()->account_management_page();
		return $page->get_page_contents();
	}
} 