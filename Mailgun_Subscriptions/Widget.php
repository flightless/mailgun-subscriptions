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

		if ( !empty($_GET['mailgun-message']) ) {
			$this->show_widget_message( $_GET['mailgun-message'] );
		}

		if ( empty($_GET['mailgun-message']) || !empty($_GET['mailgun-error']) ) {
			$this->do_widget_contents( $instance );
		}

		echo $args['after_widget'];
	}

	protected function show_widget_message( $message ) {
		echo '<p class="mailgun-message">', esc_html($message), '</p>';
	}

	protected function do_widget_contents( $instance ) {
		static $widget_counter = 0;
		$widget_counter++;

		$content = apply_filters( 'widget_description', $instance['content'], $instance, $this->id_base );
		$content = apply_filters( 'the_content', $content );
		if ( $content ) {
			echo '<div class="mailgun-widget-description">', $content, '</div>';
		}
		echo '<form class="mailgun-subscription-form" method="post" action="">';
		echo '<input type="hidden" name="mailgun-action" value="subscribe" />';
		echo '<ul class="mailgun-widget-lists">';
		foreach ( $instance['lists'] as $address ) {
			$list = new Mailing_List($address);
			if ( $list->is_hidden() ) {
				continue;
			}
			echo '<li>';
			printf( '<label><input type="checkbox" value="%s" name="mailgun-lists[]" /> %s</label>', esc_attr($list->get_address()), esc_html($list->get_name()) );
			if ( $description = $list->get_description() ) {
				printf( '<p class="description">%s</p>', $description );
			}
			echo '</li>';
		}
		echo '</ul>';
		echo '<p class="email-address">';
		printf( '<label for="mailgun-email-address-%d">%s</label>', $widget_counter, __('Email Address', 'mailgun-subscriptions') );
		$default_email = '';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$default_email = $user->user_email;
		}
		printf( '<input type="text" value="%s" name="mailgun-subscriber-email" size="20" />', $default_email );
		echo '</p>';
		printf( '<p class="submit"><input type="submit" value="%s" /></p>', __('Subscribe', 'mailgun-subscriptions') );
		echo '</form>';
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['content'] = $new_instance['content'];
		$instance['lists'] = $new_instance['lists'];
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

		<p><label for="<?php echo $this->get_field_id('lists'); ?>"><?php _e('Options:', 'mailgun-subscriptions'); ?></label>
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