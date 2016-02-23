<?php
/**
 * CC User Subscriptions
 *
 * @package   CC User Subscriptions
 * @author    David Cavins
 * @license   GPLv3
 * @copyright 2016 Community Commons
 */

/**
 * @package CC User Subscriptions
 * @author  David Cavins
 */
class CC_User_Subscriptions {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cc-user-subscriptions';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Renders subscription fields on "my profile" edit form in wp-admin.
		add_action( 'show_user_profile', array( $this, 'meta_form_markup' ) );
		// Renders subscription fields on user edit form in wp-admin.
		add_action( 'edit_user_profile', array( $this, 'meta_form_markup' ) );

		// Catch the saving of the new meta input from "my profile".
		add_action( 'personal_options_update', array( $this, 'meta_form_save') );
		// Catch the saving of the new meta input from edit user screen.
		add_action( 'edit_user_profile_update', array( $this, 'meta_form_save') );

		// Add our new column to the WP Users list table.
		add_filter('manage_users_columns', array( $this, 'add_subscription_column') );
		// Populate our new column in the WP Users list table.
		add_action('manage_users_custom_column', array( $this, 'subscription_column_content' ), 10, 3);
		// Tell WP that our new column is sortable.
		add_filter( 'manage_users_sortable_columns', array( $this, 'subscription_column_declare_sortable' ) );
		// Produce the SQL that sorts our column when necessary.
		add_action( 'pre_user_query', array( $this, 'sort_by_subscription_date' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Renders subscription fields on user edit form in wp-admin.
	 *
	 * @since  1.0.0
	 *
	 * @param  obj $user The user object.
	 *
	 * @return void
	 */
	public function meta_form_markup( $user ) {
		// Only allow site admins to edit this field.
		if ( ! current_user_can( 'delete_others_pages' ) ) {
			return;
		}
		$expires = get_user_meta( $user->ID, 'cc_subscription', true );
		?>
		<h3>Community Commons Individual Subscription</h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="cc_subscription">Subscription End Date</label></th>
					<td>
					<input type="text" name="cc_subscription" id="cc-subscription" value="<?php
					if ( ! empty( $expires ) ) {
						echo $expires;
						}
					?>">
					</td>
				</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			jQuery( document ).ready( function(){
				jQuery( "#cc-subscription" ).datepicker( {
					dateFormat: "yy-mm-dd",
				} );
			});
		</script>
		<?php
	}

	/**
	 * Saves subscription fields on user edit form in wp-admin.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $user_id The user's ID.
	 *
	 * @return void
	 */
	public function meta_form_save( $user_id ) {
		// Only allow site admins to edit this field.
		if ( ! current_user_can( 'delete_others_pages' ) ) {
			return;
		}

		$meta = array( 'cc_subscription' );

		foreach ( $meta as $meta_key ) {
			$new_value = ! empty( $_POST[$meta_key] ) ? $_POST[$meta_key] : '';
			$old_value = get_user_meta( $user_id, $fieldname, true );

			// If there is no new meta value but an old value exists, delete it.
			if ( empty( $new_value ) && $old_value ) {
				delete_user_meta( $user_id, $meta_key, $new_value );

			// If a new meta value was added and there was no previous value, add it.
			// If the new meta value does not match the old value, update it.
			} elseif ( $new_value && ( '' == $old_value || $new_value != $old_value ) ) {
				update_user_meta( $user_id, $meta_key, $new_value );
			}
			// If there's no value to save or the old and new values match, we do nothing.
		}

	}

	/**
	 * Add our new column to the WP Users list table.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $columns The array of columns to include.
	 *
	 * @return array $columns
	 */
	public function add_subscription_column( $columns ) {
		$columns['cc_subscription'] = 'CC Subscription';
		return $columns;
	}

	/**
	 * Populate our new column in the WP Users list table.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $value       Custom column output. Default empty.
	 * @param  string $column_name Column name.
	 * @param  int $user_id        ID of the currently-listed user.
	 *
	 * @return array $columns
	 */
	public function subscription_column_content( $value, $column_name, $user_id ) {
		if ( 'cc_subscription' == $column_name ) {
			return get_user_meta( $user_id, 'cc_subscription', true );
		}
		return $value;
	}

	/**
	 * Tell WP that our new column is sortable.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $columns The array of columns to make sortable.
	 *
	 * @return array $columns
	 */
	public function subscription_column_declare_sortable( $columns ) {
		$columns['cc_subscription'] = 'cc_subscription';
		return $columns;
	}

	/**
	 * Produce the SQL that sorts our column when necessary.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $query The current WP_User_Query instance, passed by reference.
	 */
	public function sort_by_subscription_date( &$query ) {
		global $wpdb, $current_screen;

		if ( 'users' != $current_screen->id  ) {
			return;
		}

		// This doesn't work like pre_get_posts, and you have to produce the SQL.
		$orderby = $query->get( 'orderby');
		if ( 'cc_subscription' == $orderby ) {
			$order = $query->get( 'order');

			/**
			 * These aren't used directly, but it seems like being a good citizen to set them,
			 * since other plugins may be looking at these values later on down the line.
			 */
			$query->set( 'meta_key','cc_subscription' );
			$query->set( 'meta_compare','EXISTS' );
			$query->set( 'orderby','meta_value' );

			/**
			 * These are used directly, which seems weird.
			 */
			$query->query_from    = $query->query_from . " INNER JOIN $wpdb->usermeta ON ( $wpdb->users.ID = $wpdb->usermeta.user_id )";
			$query->query_where   = $query->query_where . " AND $wpdb->usermeta.meta_key = 'cc_subscription'";
			$query->query_orderby = "ORDER BY $wpdb->usermeta.meta_value $order";
		}
	}

}
