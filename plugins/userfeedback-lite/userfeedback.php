<?php

/**
 * Plugin Name: UserFeedback Lite
 * Plugin URI: https://www.userfeedback.com/lite?utm_source=liteplugin&utm_medium=pluginlist
 * Description: See what your analytics software isn’t telling you with powerful UserFeedback surveys.
 * Author: UserFeedback Team
 * Version: 1.0.3
 * Requires PHP: 5.6
 * Requires at least: 5.9.0
 * Author URI: https://userfeedback.com/lite
 * Text Domain: userfeedback
 * Domain Path: /languages
 *
 * @category            Plugin
 * @copyright           Copyright © 2022 UserFeedback
 * @author              David Paternina
 * @package             UserFeedback
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'userfeedback-base.php';

/**
 * Main plugin class
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author David Paternina
 * @access public
 */
final class UserFeedback_Lite extends UserFeedback_Base {


	/**
	 * @inheritdoc
	 */
	public $plugin_name = 'UserFeedback Lite';

	/**
	 * @inheritdoc
	 */
	public $plugin_slug = 'userfeedback-lite';

	/**
	 *
	 */
	public function __construct() {
		$this->file = __FILE__;
		add_filter( 'userfeedback_vue_notices', array( $this, 'add_vue_notices' ) );
		add_action( 'wp_logout', array( $this, 'handle_user_logout' ) );
	}

	/**
	 * Run tasks before user is logged out.
	 *
	 * @param $notices
	 * @return mixed
	 */
	public function handle_user_logout( $user_id ) {
		$upgrade_notice_id = 'lite-upgrade-prompt';
		delete_user_meta( $user_id, userfeedback_get_notice_hide_opt_prefix() . $upgrade_notice_id );
	}

	/**
	 * Add Vue notices for Lite plugin
	 *
	 * @param $notices
	 * @return mixed
	 */
	public function add_vue_notices( $notices ) {

		// Lite Upgrade prompt
		$upgrade_notice_id   = 'lite-upgrade-prompt';
		$user_upgrade_prompt = get_user_meta( get_current_user_id(), userfeedback_get_notice_hide_opt_prefix() . $upgrade_notice_id, true );
		// $show_upgrade_prompt = get_option( userfeedback_get_notice_hide_opt_prefix() . $upgrade_notice_id, 0 );
		$show_upgrade_prompt = ( $user_upgrade_prompt == '' ) ? 0 : $user_upgrade_prompt;

		if ( time() - $show_upgrade_prompt > 14 * DAY_IN_SECONDS ) {
			$show_upgrade_prompt = true;
		} else {
			$show_upgrade_prompt = false;
		}

		if ( $show_upgrade_prompt ) {
			$upgrade_link = userfeedback_get_upgrade_link( 'floatbar', 'upgrade' );
			$notices[]    = array(
				'id'      => $upgrade_notice_id,
				'content' =>
				sprintf(
					__( "%1\$sYou're using UserFeedback Lite%2\$s. To unlock more features, consider %3\$supgrading to Pro%4\$s.", 'userfeedback' ),
					'<b>',
					'</b>',
					'<a target="_blank" rel="noopener" href="' . $upgrade_link . '">',
					'</a>'
				),
				'icon'    => 'exclamation-circle',
			);
		}

		return $notices;
	}

	/**
	 * @inheritdoc
	 */
	public function define_globals() {
		parent::define_globals();

		if ( ! defined( 'USERFEEDBACK_LITE_VERSION' ) ) {
			define( 'USERFEEDBACK_LITE_VERSION', USERFEEDBACK_VERSION );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function check_for_dual_installation() {
		// Detect Pro version and return early
		if ( class_exists( 'UserFeedback_Pro' ) ) {
			add_action( 'admin_notices', array( self::$instance, 'userfeedback_pro_notice' ) );
			return true;
		}
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function load_instance_properties() {

		if ( is_admin() ) {
			require_once USERFEEDBACK_PLUGIN_DIR . 'lite/includes/admin/class-userfeedback-connect.php';
		}
	}

	/**
	 * @inheritdoc
	 */
	public function define_instance_globals() {
	}

	/**
	 * Output a nag notice if the user has both Lite and Pro activated
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return  void
	 */
	public function userfeedback_pro_notice() {
		$url = admin_url( 'plugins.php' );
		// Check for MS dashboard
		if ( is_network_admin() ) {
			$url = network_admin_url( 'plugins.php' );
		}
		?>
		<div class="error">
			<p><?php echo sprintf( esc_html__( 'Please %1$suninstall%2$s the UserFeedback Lite Plugin. Your Pro version of UserFeedback may not work as expected until the Lite version is uninstalled.', 'userfeedback' ), '<a href="' . esc_url_raw($url) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}
}

/**
 * Fired when the plugin is activated.
 *
 * @access public
 * @since 1.0.0
 *
 * @global int $wp_version      The version of WordPress for this install.
 * @global object $wpdb         The WordPress database object.
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 *
 * @return void
 */
function userfeedback_lite_activation_hook( $network_wide ) {
	$url = admin_url( 'plugins.php' );
	// Check for MS dashboard
	if ( is_network_admin() ) {
		$url = network_admin_url( 'plugins.php' );
	}

	if ( class_exists( 'UserFeedback' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( sprintf( esc_html__( 'Please uninstall and remove UserFeedback Pro before activating UserFeedback Lite. The Lite version has not been activated. %1$sClick here to return to the Dashboard%2$s.', 'userfeedback' ), '<a href="' . esc_url_raw($url) . '">', '</a>' ) );
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-userfeedback-compatibility-check.php';
	$compatibility = UserFeedback_Compatibility_Check::get_instance();
	$compatibility->maybe_deactivate_plugin( plugin_basename( __FILE__ ) );

	// Add transient to trigger redirect.
	set_transient( '_userfeedback_activation_redirect', 1, 30 );
}
register_activation_hook( __FILE__, 'userfeedback_lite_activation_hook' );

/**
 * Fired when the plugin is uninstalled.
 *
 * @access public
 * @since 1.0.0
 *
 * @return  void
 */
function userfeedback_uninstall_hook() {
	wp_cache_flush();
	$instance = UserFeedback();

	// Don't delete any data if the PRO version is already active.
	if ( userfeedback_is_pro_version() ) {
		return;
	}

	// Remove email summaries cron jobs.
	wp_clear_scheduled_hook( 'userfeedback_email_summaries_cron' );

	// Delete the notifications data.
	$instance->notifications->delete_notifications_data();

	// Delete other options.
	userfeedback_uninstall_remove_options();
}
register_uninstall_hook( __FILE__, 'userfeedback_uninstall_hook' );

/**
 * The main function responsible for returning the one true UserFeedback_Lite
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $userfeedback = UserFeedback_Lite(); ?>
 *
 * @since 1.0.0
 *
 * @uses UserFeedback_Lite::get_instance() Retrieve UserFeedback_Lite instance.
 *
 * @return UserFeedback_Lite The singleton UserFeedback_Lite instance.
 */
function UserFeedback_Lite() {
	return UserFeedback_Lite::get_instance();
}
