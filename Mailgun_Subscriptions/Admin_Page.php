<?php

namespace Mailgun_Subscriptions;

/**
 * Class Admin_Page
 */
class Admin_Page {
	const MENU_SLUG = 'mailgun_subscriptions';
	const OPTION_ACCOUNT_PAGE = 'mailgun_account_management_page';

	public function refresh_caches() {
		$lists = $this->get_mailing_lists_from_api();
		if ( $lists ) {
			$this->cache_lists($lists);
			$this->clear_invalid_lists();
		}
	}

	public function register() {
		add_options_page(
			__('Mailgun Mailing Lists', 'mailgun-subscriptions'),
			__('Mailgun Lists', 'mailgun-subscriptions'),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'display' )
		);
		add_settings_section(
			'credentials',
			__('API Credentials', 'mailgun-subscriptions'),
			'__return_false',
			self::MENU_SLUG
		);
		add_settings_field(
			'mailgun_api_key',
			__('API Key', 'mailgun-subscriptions'),
			array( $this, 'display_text_field' ),
			self::MENU_SLUG,
			'credentials',
			array(
				'option' => 'mailgun_api_key',
			)
		);
		register_setting(
			self::MENU_SLUG,
			'mailgun_api_key'
		);

		add_settings_field(
			'mailgun_api_public_key',
			__('API Public Key', 'mailgun-subscriptions'),
			array( $this, 'display_text_field' ),
			self::MENU_SLUG,
			'credentials',
			array(
				'option' => 'mailgun_api_public_key',
			)
		);
		register_setting(
			self::MENU_SLUG,
			'mailgun_api_public_key'
		);

		if ( !get_option( 'mailgun_api_key', '' ) ) {
			return; // don't display any more settings if there's no key
		}

		add_settings_section(
			'lists',
			__('Available Lists', 'mailgun-subscriptions'),
			array( $this, 'display_available_lists' ),
			self::MENU_SLUG
		);

		add_settings_section(
			'confirmation',
			__('Subscription Confirmation', 'mailgun-subscriptions'),
			'__return_false',
			self::MENU_SLUG
		);

		add_settings_field(
			'mailgun_confirmation_page',
			__('Confirmation Page', 'mailgun-subscriptions'),
			array( $this, 'display_confirmation_page_field' ),
			self::MENU_SLUG,
			'confirmation',
			array(
				'option' => 'mailgun_confirmation_page',
			)
		);

		register_setting(
			self::MENU_SLUG,
			'mailgun_confirmation_page',
			array( $this, 'save_confirmation_page_field' )
		);

		add_settings_field(
			'mailgun_confirmation_expiration',
			__('Expiration Period', 'mailgun-subscriptions'),
			array( $this, 'display_text_field' ),
			self::MENU_SLUG,
			'confirmation',
			array(
				'option' => 'mailgun_confirmation_expiration',
				'description' => __('Subscription requests will become invalid after this many days', 'mailgun-subscriptions'),
				'default' => 7,
			)
		);

		register_setting(
			self::MENU_SLUG,
			'mailgun_confirmation_expiration',
			'intval'
		);

		add_settings_field(
			'mailgun_confirmation_email_template',
			__('Confirmation Email', 'mailgun-subscriptions'),
			array( $this, 'display_textarea_field' ),
			self::MENU_SLUG,
			'confirmation',
			array(
				'option' => 'mailgun_confirmation_email_template',
				'description' => $this->get_confirmation_email_field_description(),
				'default' => Template::confirmation_email(),
			)
		);
		register_setting(
			self::MENU_SLUG,
			'mailgun_confirmation_email_template'
		);

		add_settings_field(
			'mailgun_welcome_email_template',
			__('Welcome Email', 'mailgun-subscriptions'),
			array( $this, 'display_textarea_field' ),
			self::MENU_SLUG,
			'confirmation',
			array(
				'option' => 'mailgun_welcome_email_template',
				'description' => $this->get_welcome_email_field_description(),
				'default' => Template::welcome_email(),
			)
		);

		register_setting(
			self::MENU_SLUG,
			'mailgun_welcome_email_template'
		);

		add_settings_field(
			'mailgun_token_email_template',
			__('Account Management Email', 'mailgun-subscriptions'),
			array( $this, 'display_textarea_field' ),
			self::MENU_SLUG,
			'confirmation',
			array(
				'option' => 'mailgun_token_email_template',
				'description' => $this->get_token_email_field_description(),
				'default' => Template::token_email(),
			)
		);

		register_setting(
			self::MENU_SLUG,
			'mailgun_token_email_template'
		);

		register_setting(
			self::MENU_SLUG,
			'mailgun_lists'
		);

		register_setting(
			self::MENU_SLUG,
			'mailgun_new_list',
			array( $this, 'save_new_list' )
		);

		add_settings_section(
			'account_management',
			__('Account Management', 'mailgun-subscriptions'),
			'__return_false',
			self::MENU_SLUG
		);

		add_settings_field(
			self::OPTION_ACCOUNT_PAGE,
			__('Account Management Page', 'mailgun-subscriptions'),
			array( $this, 'display_page_select_field' ),
			self::MENU_SLUG,
			'account_management',
			array(
				'option' => self::OPTION_ACCOUNT_PAGE,
				'description' => $this->get_account_management_page_description(),
				'default' => 0,
			)
		);

		register_setting(
			self::MENU_SLUG,
			self::OPTION_ACCOUNT_PAGE,
			'intval'
		);
	}

	public function display() {
		$title = __('Mailgun Mailing Lists', 'mailgun-subscriptions');
		$nonce = wp_nonce_field('mailgun-settings', 'mailgun-settings-nonce', true, false);
		$button = get_submit_button(__('Save Settings', 'mailgun-subscriptions'));
		$action = admin_url('options.php');

		ob_start();
		settings_fields(self::MENU_SLUG);
		do_settings_sections(self::MENU_SLUG);
		$fields = ob_get_clean();

		$form = sprintf('<form method="post" action="%s" enctype="multipart/form-data">%s%s%s</form>', $action, $nonce, $fields, $button);

		$content = $form;

		printf( '<div class="wrap"><h2>%s</h2>%s</div>', $title, $content );
	}

	public function display_text_field( $args ) {
		if ( !isset($args['option']) ) {
			return;
		}
		$args = wp_parse_args( $args, array('default' => '', 'description' => '') );
		$value = get_option( $args['option'], $args['default'] );
		printf( '<input type="text" value="%s" name="%s" class="widefat" />', esc_attr($value), esc_attr($args['option']) );
		if ( !empty($args['description']) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}
	}

	public function display_textarea_field( $args ) {
		if ( !isset($args['option']) ) {
			return;
		}
		$args = wp_parse_args( $args, array('default' => '', 'description' => '', 'rows' => 5, 'cols' => 40) );
		$value = get_option( $args['option'], $args['default'] );
		printf( '<textarea rows="%s" cols="%s" name="%s" class="widefat">%s</textarea>', intval($args['rows']), intval($args['cols']), esc_attr($args['option']), esc_textarea($value) );
		if ( !empty($args['description']) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}
	}

	public function display_page_select_field( $args ) {
		if ( !isset($args['option']) ) {
			return;
		}
		$args = wp_parse_args( $args, array('default' => 0, 'description' => '' ) );
		$value = get_option( $args['option'], $args['default'] );
		wp_dropdown_pages( array(
			'selected' => $value,
			'name' => $args[ 'option' ],
			'show_option_none' => false,
		));
		if ( !empty($args['description']) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}
	}

	public function display_available_lists() {
		$lists = $this->get_mailing_lists_from_cache();
		?>
		<table class="form-table">
			<thead>
				<tr>
					<th scope="col"><?php _e('Address', 'mailgun-subscriptions'); ?></th>
					<th scope="col"><?php _e('Name', 'mailgun-subscriptions'); ?></th>
					<th scope="col" style="width: auto;"><?php _e('Description', 'mailgun-subscriptions'); ?></th>
					<th scope="col" style="width:80px;"><?php _e('Hidden', 'mailgun-subscriptions'); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php
		foreach ( $lists as $item ) {
			echo '<tr>';
			printf( '<th scope="row">%s</th>', esc_html($item->address) );
			printf( '<td class="mailgun-name">%s%s</td>', esc_html($item->name), $this->get_name_field($item->address, $item->name) );
			printf( '<td class="mailgun-description">%s</td>', $this->get_description_field($item->address, $item->description) );
			printf( '<td class="mailgun-hidden">%s</td>', $this->get_hidden_list_checkbox($item->address) );
			echo '</tr>';
		}

		// new list fields
		?>
		<tr>
			<td colspan="4"><strong><?php _e('Create a new list', 'mailgun-subscriptions'); ?></strong></td>
		</tr>
		<tr>
			<td><input type="text" value="" class="widefat" name="mailgun_new_list[address]" /></td>
			<td class="mailgun-name"><input type="text" class="widefat" value="" name="mailgun_new_list[name]" /></td>
			<td class="mailgun-description"><textarea class="widefat" rows="2" cols="40" name="mailgun_new_list[description]"></textarea></textarea></td>
			<td class="mailgun-hidden"></td>
		</tr>
		<?php

		echo '</tbody></table>';
	}

	private function get_name_field( $address, $name ) {
		return sprintf( '<input type="hidden" name="mailgun_lists[%s][name]" value="%s" />', esc_attr($address), esc_attr($name) );
	}

	private function get_hidden_list_checkbox( $address ) {
		$list = new Mailing_List($address);
		return sprintf( '<input type="checkbox" name="mailgun_lists[%s][hidden]" value="1" %s />', esc_attr($address), checked($list->is_hidden(), TRUE, FALSE) );
	}

	private function get_description_field( $address, $default = '' ) {
		$list = new Mailing_List($address);
		return sprintf( '<textarea name="mailgun_lists[%s][description]" class="widefat">%s</textarea>', esc_attr($address), esc_textarea( $list->exists() ? $list->get_description() : $default) );
	}

	private function cache_lists( $lists ) {
		set_transient( 'mailgun_mailing_lists', $lists );
	}

	private function get_mailing_lists_from_cache() {
		$lists = get_transient('mailgun_mailing_lists');
		if ( empty($lists) ) {
			$lists = $this->get_mailing_lists_from_api();
			$this->cache_lists($lists);
		}
		return $lists;
	}

	private function get_mailing_lists_from_api() {
		$api = Plugin::instance()->api();
		$response = $api->get('lists');
		if ( !$response || $response['response']['code'] != 200 ) {
			return array();
		}
		return $response['body']->items;
	}

	private function clear_invalid_lists() {
		$lists = $this->get_mailing_lists_from_cache();
		$addresses = wp_list_pluck( $lists, 'address' );
		$saved = (array) get_option('mailgun_lists');
		$gone = array_diff( array_keys($saved), $addresses );
		if ( !empty($gone) ) {
			foreach ( $gone as $address ) {
				unset($saved[$address]);
			}
			update_option( 'mailgun_lists', $saved );
		}
	}

	public function save_new_list( $submitted ) {
		if ( !empty($submitted['address']) && isset($submitted['name']) && isset($submitted['description']) ) {
			$address = $submitted['address'];
			$name = $submitted['name'] ? $submitted['name'] : $submitted['address'];
			$description = $submitted['description'];
			$api = Plugin::instance()->api();
			$response = $api->post('lists', array(
				'address' => $address,
				'name' => $name,
				'description' => $description,
			));
			if ( $response && $response['response']['code'] == 200 ) {
				$saved_lists = get_option('mailgun_lists');
				$saved_lists[$address] = array(
					'name' => $name,
					'description' => $description,
					'hidden' => 0
				);
				update_option( 'mailgun_lists', $saved_lists );
			}
		}
		return false;
	}

	public function display_confirmation_page_field( $args ) {
		if ( empty($args['option']) ) {
			return;
		}
		$current = get_option( $args['option'], 0 );
		wp_dropdown_pages(array(
			'selected' => $current,
			'name' => $args['option'],
			'show_option_none' => __('-- New Page --', 'mailgun-subscriptions'),
			'option_none_value' => 0,
		));
	}

	public function save_confirmation_page_field( $value ) {
		if ( empty($value) ) {
			$value = $this->create_new_confirmation_page();
		}
		return $value;
	}

	public function create_new_confirmation_page() {
		$title = __('Subscription Confirmed', 'mailgun-subscriptions');
		$content = Template::confirmation_page();
		$new_post = array(
			'post_title' => apply_filters( 'mailgun_confirmation_page_default_title', $title ),
			'post_content' => apply_filters( 'mailgun_confirmation_page_default_content', $content ),
			'post_type' => 'page',
			'post_status' => 'publish'
		);
		return wp_insert_post( $new_post );
	}

	public function get_confirmation_email_field_description() {
		$description = __("This email will contain a link for users to confirm their subscriptions. Your template should contain the following shortcodes:<br />
			<code>[link]</code> &ndash; This becomes a link back to your site with a unique code to confirm the user's subscription request.<br />
			<code>[email]</code> &ndash; This is the user's email address.<br />
			<code>[lists]</code> &ndash; This is a list of the lists the user opted to subscribe to.", 'mailgun-subscriptions' );
		return $description;
	}

	public function get_welcome_email_field_description() {
		$description = __("This email will be sent to users after they confirm their subscription. Leave blank to disable this email. Your template can contain the following shortcodes:<br />
			<code>[email]</code> &ndash; This is the user's email address.<br />
			<code>[lists]</code> &ndash; This is a list of the lists the user opted to subscribe to.<br />
			<code>[link]</code> &ndash; This is the URL to the user's account management page.", 'mailgun-subscriptions' );
		return $description;
	}

	public function get_token_email_field_description() {
		$description = __("This email will be sent to users when they request a link to their account management page. Your template can contain the following shortcodes:<br />
			<code>[link]</code> &ndash; This is the URL to the user's account management page.", 'mailgun-subscriptions' );
		return $description;
	}

	public function get_account_management_page_description() {
		return __( 'Select which Page of your site will be used to display the Account Management page', 'mailgun-subscriptions' );
	}
}
 