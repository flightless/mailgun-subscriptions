<?php


namespace Mailgun_Subscriptions;


class Widget extends \WP_Widget {

	public function __construct() {
		$widget_ops = array('classname' => 'mailgun-subscriptions', 'description' => __('A mailgun list subscription form', 'mailgun-subscriptions'));
		$control_ops = array();
		parent::__construct('mailgun-subscriptions', __('Mailgun List Subscription Form', 'mailgun-subscriptions'), $widget_ops, $control_ops);
	}

	public function widget( $args, $instance ) {
		$instance = $this->parse_instance_vars($instance);
		if ( empty($instance['lists']) ) {
			return;
		}
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		echo $args['before_widget'];
		if ( !empty($title) ) {
			echo $args['before_title'];
			echo $title;
			echo $args['after_title'];
		}

		$content = apply_filters( 'widget_description', $instance['content'], $instance, $this->id_base );
		$content = apply_filters( 'the_content', $content );
		if ( $content ) {
			$content = '<div class="mailgun-widget-description">' . $content . '</div>';
		}

		$form = new Subscription_Form();
		$form->display(array(
			'description' => $content,
			'lists' => $instance['lists'],
			'name' => isset($instance['name']),
		));

		if ( apply_filters( 'mailgun_subscriptions_widget_show_account_link', true ) ) {
			$account_management_page = Plugin::instance()->account_management_page();
			$link = $account_management_page->get_page_url();
			if ( $link ) {
				printf( '<p><a href="%s">%s</a></p>', esc_url( $link ), __( 'Manage your subscriptions', 'mailgun-subscriptions' ) );
			}
		}

		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $new_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	protected function parse_instance_vars( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
			'title' => __('Subscribe', 'mailgun-subscriptions'),
			'lists' => array(),
			'content' => ''
		) );
		if ( !is_array($instance['lists']) ) {
			$instance['lists'] = array();
		}
		return $instance;
	}

	public function form( $instance ) {
		$instance = $this->parse_instance_vars($instance);
		$title = strip_tags($instance['title']);
		$content = $instance['content'];
		$lists = $instance['lists'];
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'mailgun-subscriptions'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('content'); ?>"><?php _e('Description:', 'mailgun-subscriptions'); ?></label>
			<textarea class="widefat" id="<?php echo $this->get_field_id('content'); ?>" name="<?php echo $this->get_field_name('content'); ?>"><?php echo esc_textarea($content); ?></textarea></p>

		<p>
		<input class="widefat" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="checkbox" <?php checked(isset($instance['name']) && $instance['name'] === 'on'); ?> />
		<label for="<?php echo $this->get_field_id('name'); ?>"><?php _e('Require name?', 'mailgun-subscriptions'); ?></label>
		</p>

		<p><label for="<?php echo $this->get_field_id('lists'); ?>"><?php _e('List Options:', 'mailgun-subscriptions'); ?></label>
			<ul>
				<?php foreach ( Plugin::instance()->get_lists('name') as $list_address => $list_settings ): ?>
					<li>
						<label><input type="checkbox" value="<?php esc_attr_e($list_address); ?>" name="<?php echo $this->get_field_name('lists'); ?>[]"
								<?php checked(in_array($list_address, $lists)); ?>
								/> <?php esc_html_e($list_settings['name']); ?></label>
					</li>
				<?php endforeach; ?>
			</ul>
		</p>
	<?php
	}

	public static function register() {
		register_widget( __CLASS__ );
	}
} 