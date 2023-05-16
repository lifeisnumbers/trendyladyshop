<?php

namespace cnb\admin\settings;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\admin\api\CnbAdminCloud;
use cnb\admin\api\CnbAppRemote;
use cnb\admin\api\CnbMigration;
use cnb\admin\deactivation\Activation;
use cnb\admin\domain\CnbDomainController;
use cnb\admin\models\CnbActivation;
use cnb\notices\CnbAdminNotices;
use cnb\notices\CnbNotice;
use cnb\utils\CnbUtils;
use WP_Error;

class CnbSettingsController {

    /**
     * Returns the default values for a (legacy) Button
     *
     * This is also part of "register_setting" in the CallNowButton class
     *
     * @return array
     */
    public function get_defaults() {
        $defaults = array(
            'active'                      => 0,
            'number'                      => '',
            'text'                        => '',
            'color'                       => '#00bb00',
            'iconcolor'                   => '#ffffff',
            'appearance'                  => 'right',
            'hideIcon'                    => 0,
            'limit'                       => 'include',
            'frontpage'                   => 0,
            'conversions'                 => 0,
            'zoom'                        => 1,
            'z-index'                     => 10,
            'tracking'                    => 0,
            'show'                        => '',
            'version'                     => CNB_VERSION,
            'changelog_version'           => CNB_VERSION,
            'cloud_enabled'               => 0,
            'advanced_view'               => 0,
            'show_all_buttons_for_domain' => 0,
            'footer_show_traces'          => 0,
            'api_caching'                 => 0,
            'reporting_enabled'           => 0,
	        'displaymode'                 => 'MOBILE_ONLY',
        );

        return $this->post_option_cnb( $defaults );
    }

    /**
     * This gets the current Call Now Button settings and fixes some entries to be properly cast to their type.
     * For example, active will be an int (1 or 2), regardless of the DB has it as an int or string.
     * There are a few global used throughout the plugin.
     *
     * All are named `cnb_*` so not to collide with others.
     *
     * This is set up early via the `plugins_loaded` hook
     *
     * @return array
     */
    public function post_option_cnb( $cnb_options ) {
        $cnb_options['active']                      = isset( $cnb_options['active'] ) && $cnb_options['active'] == 1 ? 1 : 0;
        $cnb_options['hideIcon']                    = isset( $cnb_options['hideIcon'] ) && $cnb_options['hideIcon'] == 1 ? 1 : 0;
        $cnb_options['frontpage']                   = isset( $cnb_options['frontpage'] ) && $cnb_options['frontpage'] == 1 ? 1 : 0;
        $cnb_options['advanced_view']               = isset( $cnb_options['advanced_view'] ) && $cnb_options['advanced_view'] == 1 ? 1 : 0;
        $cnb_options['show_all_buttons_for_domain'] = isset( $cnb_options['show_all_buttons_for_domain'] ) && $cnb_options['show_all_buttons_for_domain'] == 1 ? 1 : 0;
        $cnb_options['footer_show_traces']          = isset( $cnb_options['footer_show_traces'] ) && $cnb_options['footer_show_traces'] == 1 ? 1 : 0;
        $cnb_options['api_caching']                 = isset( $cnb_options['api_caching'] ) && $cnb_options['api_caching'] == 1 ? 1 : 0;
        $cnb_options['conversions']                 = isset( $cnb_options['conversions'] ) && ( $cnb_options['conversions'] == 1 || $cnb_options['conversions'] == 2 ) ? (int) $cnb_options['conversions'] : 0;
        $cnb_options['cloud_enabled']               = ( new CnbUtils() )->isCloudActive( $cnb_options );

        return $cnb_options;
    }


    /**
     * After the upgrade (changelog) dialog is dismissed, the "cnb_update_<version>" action is fired.
     *
     * This action updates the internal version number, so we're ready for the next update.
     *
     * @return void
     */
    public function update_version() {
        $this->set_changelog_version();
    }

    private function set_changelog_version() {
        $cnb_options     = get_option( 'cnb' );
        $updated_options = array_merge(
            $cnb_options,
            array(
                'changelog_version' => CNB_VERSION
            )
        );
        update_option( 'cnb', $updated_options );
    }

    /**
     * @param $cnb_options array
     *
     * @return string cloud, enabled or legacy
     */
    public static function getStatus( $cnb_options ) {
        return $cnb_options['cloud_enabled'] ? 'cloud' : ( $cnb_options['active'] ? 'enabled' : 'disabled' );
    }

	/**
	 * "Advanced view" shows all available options, validation rules and more, intended for power users
	 * and Call Now Button administrators.
	 *
	 * @return bool
	 */
	public static function is_advanced_view() {
		$cnb_options = get_option( 'cnb' );
		return isset( $cnb_options['advanced_view'] ) && $cnb_options['advanced_view'] == 1;
	}

    /**
     *
     * @param $input array The options for <code>cnb</code>
     *
     * @return array The adjusted options array for Call Now Button
     */
    public function validate_options( $input ) {
        $original_settings = get_option( 'cnb' );

        $messages = array();

        // Cloud Domain settings can be set here as well
        // phpcs:ignore WordPress.Security
        if ( array_key_exists( 'domain', $_POST ) &&
             array_key_exists( 'cloud_enabled', $input ) &&
             $input['cloud_enabled'] == 1 ) {
            $message = ( new CnbDomainController() )->updateWithoutRedirect();

            // Only add the message to the results if something went wrong
            if ( is_array( $message ) && sizeof( $message ) === 1 &&
                 $message[0] instanceof CnbNotice && $message[0]->type != 'success' ) {
                $messages = array_merge( $messages, $message );
            }

            // Remove from settings
            unset( $input['domain'] );
        }

        // If api_key is empty, assume unchanged and unset it (so it uses the old value)
        if ( isset( $input['api_key'] ) && empty( $input['api_key'] ) ) {
            unset( $input['api_key'] );
        }

        // If api_key is "delete_me", this is the special value to trigger "forget this key"
        if ( ( isset( $input['api_key'] ) && $input['api_key'] === 'delete_me' ) ||
             ( isset( $original_settings['api_key'] ) && $original_settings['api_key'] === 'delete_me' ) ) {
            $input['api_key']                  = '';
            $original_settings['api_key']      = '';
            $input['cloud_use_id']             = '';
            $original_settings['cloud_use_id'] = '';

            $messages[] = new CnbNotice( 'success', '<p>Your API key has been removed - you can now activate Call Now Button with another API key.</p>' );
        }

        $updated_options = array_merge( $original_settings, $input );

        // If the cloud is enabled, this is a fail-safe to ensure the user ID is set, even if it isn't
        // explicitly set by the user YET. Since the whole "cnb[cloud_use_id]" input field doesn't exist yet...
        $adminCloud = new CnbAdminCloud();
        if ( isset( $updated_options['cloud_enabled'] ) && $updated_options['cloud_enabled'] == 1 ) {
            $cloud_use_id = $adminCloud->getCloudUseId( $updated_options );
            if ( $cloud_use_id !== null ) {
                $updated_options['cloud_use_id'] = $cloud_use_id;
            }
        }

        // This is triggered if the passed in API key is different from the stored API key.
        if ( ! empty( $original_settings['api_key'] ) && ! empty( $input['api_key'] ) && $original_settings['api_key'] !== $input['api_key'] ) {
            unset( $updated_options['cloud_use_id'] );
            $cloud_use_id = $adminCloud->getCloudUseId( $updated_options );
            if ( $cloud_use_id !== null ) {
                $updated_options['cloud_use_id'] = $cloud_use_id;
            }
        }

        $show_updated_notice = true;
        if($this->is_version_upgrade($original_settings, $updated_options)) {
            $show_updated_notice = false;
        }
        if (array_key_exists('activation_time', $input)) {
            $show_updated_notice = false;
        }

        // Check for legacy button
        $check = $this->disallow_active_without_phone_number( $updated_options );

        if ( is_wp_error( $check ) ) {
            if ( $check->get_error_code() === 'CNB_PHONE_NUMBER_MISSING' ) {
                $messages[] = new CnbNotice( 'warning', '<p>Your settings have been updated, but your button could <strong>not</strong> be enabled. Please enter a <i>Phone number</i>.</p>' );
                // Reset enabled/active to false
                $updated_options['active'] = 0;
            } else {
                // This part is VERY generic and should not be reached, since
                // self::disallow_active_without_phone_number() returns a single WP_Error.
                // But just in case, this is here for other unseen errors..
                $messages[] = CnbAdminCloud::cnb_admin_get_error_message( 'save', 'settings', $check );
            }
        } else {
            if ($show_updated_notice) {
                $messages[] = new CnbNotice( 'success', '<p>Your settings have been updated!</p>' );
            }
        }

        $this->update_user_email_opt_in($original_settings, $input);
        $transient_id = 'cnb-options';
        $messages = apply_filters( 'cnb_after_save', $messages );
        set_transient( $transient_id, $messages, HOUR_IN_SECONDS );

        // We do not actually store this value in the DB!
        unset( $updated_options['status'] );
        unset( $updated_options['user_marketing_email_opt_in'] );

        do_action( 'cnb_after_button_changed' );

        return $updated_options;
    }

    private function is_version_upgrade(&$original_settings, &$updated_options) {
        if (!key_exists('changelog_version', $original_settings)) {
            $original_settings['changelog_version'] = CNB_VERSION;
        }
        if (!key_exists('changelog_version', $updated_options)) {
            $updated_options['changelog_version'] = CNB_VERSION;
        }
        return $original_settings['version'] != $updated_options['version'] || $original_settings['changelog_version'] != $updated_options['changelog_version'];

    }

    private function update_user_email_opt_in($current, $new) {
        if (!key_exists('user_marketing_email_opt_in', $new)) {
            return;
        }
        $cnb_remote = new CnbAppRemote();

        // Test if properties are there (if not in current or current != new, update CnbUser
        if (!key_exists('user_marketing_email_opt_in', $current) || $current['user_marketing_email_opt_in'] != $new['user_marketing_email_opt_in']) {
            // phpcs:ignore PHPCompatibility.FunctionUse
            if (boolval($new['user_marketing_email_opt_in'])) {
                $cnb_remote->enable_email_opt_in();
            } else {
                $cnb_remote->disable_email_opt_in();
            }
        }
    }

    /**
     * For the Legacy button, disallow setting it to active with a missing phone number
     *
     * @param array $input
     *
     * @return WP_Error
     */
    private function disallow_active_without_phone_number( $input ) {
        $active        = trim( $input['active'] );
        $number        = trim( $input['number'] );
        $cloud_enabled = array_key_exists( 'cloud_enabled', $input ) ? $input['cloud_enabled'] : 0;

        if ( $active == 1 && $cloud_enabled == 0 && empty( $number ) ) {
            return new WP_Error( 'CNB_PHONE_NUMBER_MISSING', 'Please enter a phone number before enabling your button.' );
        }

        return null;
    }

    /**
     * @param $result CnbActivation
     *
     * @return mixed|string|null
     */
    private function getApiKey( $result ) {
        // Parse special header(s)
        $api_key        = null;
        $api_key_direct = filter_input( INPUT_GET, 'api_key', @FILTER_SANITIZE_STRING );
        $api_key_ott    = filter_input( INPUT_GET, 'api_key_ott', @FILTER_SANITIZE_STRING );

        if ( ! empty( $api_key_direct ) ) {
            $api_key                    = $api_key_direct;
            $result->activation_attempt = true;
        }

        if ( ! empty( $api_key_ott ) ) {
            $api_key                    = $this->get_api_key_from_ott( $api_key_ott );
            $result->activation_attempt = true;
        }

        return $api_key;
    }

    /**
     * @return CnbActivation
     */
    public function parseApiAndOttHeader() {
        $cnb_options = get_option( 'cnb' );
        $adminCloud  = new CnbAdminCloud();
        $result      = new CnbActivation();
        $cnb_remote  = new CnbAppRemote();

        $api_key = $this->getApiKey( $result );

        // In case:
        // - there already is an API key (so no need to update)
        // - a token is provided anyway (api_key[_ott])
        // - the cloud is disabled (for some reason)
        // Then this re-enables it (and shows a warning that we did that)
        if ( ! empty( $cnb_options['api_key'] ) && $api_key && $cnb_options['cloud_enabled'] != 1 ) {
            CnbAdminNotices::get_instance()->warning( '<p>You have followed a link, but an API key is already present or the token has expired.</p><p>We have enabled <strong>Premium</strong>, but did not change the already present API key.</p>' );
            $options                  = array();
            $options['cloud_enabled'] = 1;
            update_option( 'cnb', $options );

            return $result;
        }

        $api_key_valid = false;
        if ( $api_key ) {
            $api_key_valid = $adminCloud->is_api_key_valid( $api_key );
        }

        // This is really the first time a user tries to activate a key, so:
        // - Check the key for validity
        // - If valid, enable cloud, set the API key, update domain/button
        if ( empty( $cnb_options['api_key'] ) && $api_key ) {
            if ( ! $api_key_valid ) {
                CnbAdminNotices::get_instance()->error( '<p>This API key is invalid.</p>' );
                $result->activation_attempt = true;
                $result->success            = false;

                return $result;
            }

            // This also enabled the cloud
            $options                  = array();
            $options['cloud_enabled'] = 1;
            $options['api_key']       = $api_key;
            update_option( 'cnb', $options );

            // set "migration done"
            // We should really only do this once, so we need to save something in the settings to stop continuous migration.
            add_option( 'cnb_cloud_migration_done', true );
            CnbAdminNotices::get_instance()->success( '<p>Successfully connected to your NowButtons account.</p>' );
        }

        // If an API key was passed (no matter the status of activation)
        if ( $api_key && $api_key_valid ) {
            $migration = new CnbMigration();
            $migration->createOrUpdateDomainAndButton( $result );
            // Reset the wp info (since the API key is now set/available)
            $cnb_remote->get_wp_info();
        }

        return $result;
    }

    /**
     * @param $api_key_ott string API key OTT (OneTimeToken)
     *
     * @return string|null The API key if found
     */
    function get_api_key_from_ott( $api_key_ott ) {
        $cnb_remote  = new CnbAppRemote();
        $api_key = $cnb_remote->get_apikey_via_ott( $api_key_ott );

        if ( $api_key === null ) {
            return null;
        }

        // Special case for expired OTTs
        if ( is_wp_error( $api_key ) ) {
            $code = 'CNB_ERROR';
            if ( $api_key->get_error_code() === $code ) {
                $messages = $api_key->get_error_messages( $code );
                if ( is_array( $messages ) && count( $messages ) && $api_key->get_error_messages( $code )[0] === 'Bad Request' ) {
                    // This is most likely an expired key
                    $message = '<p>A <em>one-time token</em> was provided, but that token is invalid or has expired.</p>';
                    CnbAdminNotices::get_instance()->error( $message );

                    return null;
                }
            }
            $error_details = CnbAdminCloud::cnb_admin_get_error_message_details( $api_key );
            $message       = '<p>We could not enable <strong>Premium</strong> with thsis <em>one-time token</em>.';
            $message       .= ' <code>' . esc_html( $api_key_ott ) . '</code> :-(.' . $error_details . '</p>';
            $notice        = new CnbNotice( 'error', $message );
            CnbAdminNotices::get_instance()->notice( $notice );

            return null;
        }

        return $api_key->key;
    }

    /**
     * is called via admin_post
     *
     * @return void
     */
    public function delete_all_settings() {
        $nonce = filter_input( INPUT_POST, '_wpnonce', @FILTER_SANITIZE_STRING );
        $success = 0;
        if ( $nonce && wp_verify_nonce( $nonce, 'cnb_delete_all_settings' ) ) {

            // Delete known options
            $option_names = array( 'cnb', 'cnb_cloud_migration_done' );

            foreach ( $option_names as $option_name ) {
                // Delete the standard options
                delete_option( $option_name );

                // Delete site options in Multisite
                delete_site_option( $option_name );
            }
            $this->set_default_settings();

            // Delete notice dismissals
            $options_slug = 'call-now-button';

            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $wpdb->options
                           WHERE option_name
                           LIKE %s",
                    $options_slug . '_dismissed_%' )
            );
            $success = 1;
        }
        // Always redirect back, even when not successful
        $this->redirect_to_delete_all_settings($success);
    }

    /**
     * is called via admin_post
     *
     * @return void
     */
    public function set_default_settings() {
        $nonce   = filter_input( INPUT_POST, '_wpnonce', @FILTER_SANITIZE_STRING );
        $success = 0;
        if ( $nonce && wp_verify_nonce( $nonce, 'cnb_set_default_settings' ) ) {
            Activation::onActivation( null, false );
            $success = 2;
        }
        // Always redirect back, even when not successful
        $this->redirect_to_delete_all_settings($success);
    }

    /**
     * is called via admin_post
     *
     * @return void
     */
    public function override_changelog_version() {
        $nonce   = filter_input( INPUT_POST, '_wpnonce', @FILTER_SANITIZE_STRING );
        $success = 0;
        if ( $nonce && wp_verify_nonce( $nonce, 'cnb_set_default_settings' ) ) {
            $changelog_version   = filter_input( INPUT_POST, 'changelog_version', @FILTER_SANITIZE_STRING );
            $options = ['changelog_version' => $changelog_version];
            update_option('cnb', $options);
            $success = 3;
        }
        // Always redirect back, even when not successful
        $this->redirect_to_delete_all_settings($success);
    }

    private function redirect_to_delete_all_settings($success = 0) {
        $url           = admin_url( 'admin.php' );
        $redirect_link =
            add_query_arg(
                array(
                    'page' => CNB_SLUG . '-settings',
                    'action' => 'delete_all_settings',
                    'success' => $success,
                ),
                $url );
        $redirect_url  = esc_url_raw( $redirect_link );
        do_action( 'cnb_finish' );
        wp_safe_redirect( $redirect_url );

    }
}
