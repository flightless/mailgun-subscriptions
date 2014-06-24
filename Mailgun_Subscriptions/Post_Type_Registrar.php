<?php


namespace Mailgun_Subscriptions;

class Post_Type_Registrar {
	public function register() {
		register_post_type( Confirmation::POST_TYPE, $this->confirmation_post_type_args() );
	}

	/**
	 * Build the args array for the mailgun-confirmation post type definition
	 *
	 * @return array
	 */
	protected function confirmation_post_type_args() {
		$args = array(
			'label' => __( 'Mailgun Confirmation', 'mailgun-subscriptions' ),
			'public' => false,
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports' => array('title'),
			'rewrite' => false,
			'query_var' => false,
			'can_export' => true,
			'ep_mask' => EP_NONE,
		);

		$args = apply_filters('mailgun_subscriptions_post_type_args', $args);
		return $args;
	}
}
