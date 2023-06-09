<?php
if ( ! class_exists( 'AM_Deactivation_Survey' ) ) {
	/**
	 * Awesome Motive Deactivation Survey.
	 *
	 * This prompts the user for more details when they deactivate the plugin.
	 *
	 * @version    1.2.1
	 * @package    AwesomeMotive
	 * @author     Jared Atchison and Chris Christoff
	 * @license    GPL-2.0+
	 * @copyright  Copyright (c) 2018
	 */
	class AM_Deactivation_Survey {

		/**
		 * The API URL we are calling.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $api_url = 'https://api.awesomemotive.com/v1/deactivation-survey/';

		/**
		 * Name for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $name;

		/**
		 * Unique slug for this plugin.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.0.0
		 * @param string $name Plugin name.
		 * @param string $plugin Plugin slug.
		 */
		public function __construct( $name = '', $plugin = '' ) {

			$this->name   = $name;
			$this->plugin = $plugin;

			// Don't run deactivation survey on dev sites.
			if ( $this->is_dev_url() ) {
				// return;
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'load_survey_js' ) );
			add_action( 'admin_print_scripts', array( $this, 'css' ) );
			add_action( 'admin_footer', array( $this, 'modal' ) );
		}

		/**
		 * Checks if current site is a development one.
		 *
		 * @since 1.2.0
		 * @return bool
		 */
		public function is_dev_url() {
			// If it is an AM dev site, return false, so we can see them on our dev sites.
			if ( defined( 'AWESOMEMOTIVE_DEV_MODE' ) && AWESOMEMOTIVE_DEV_MODE ) {
				return false;
			}

			$url          = network_site_url( '/' );
			$is_local_url = false;

			// Trim it up
			$url = strtolower( trim( $url ) );

			// Need to get the host...so let's add the scheme so we can use parse_url
			if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
				$url = 'http://' . $url;
			}
			$url_parts = parse_url( $url );
			$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;
			if ( ! empty( $url ) && ! empty( $host ) ) {
				if ( false !== ip2long( $host ) ) {
					if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						$is_local_url = true;
					}
				} elseif ( 'localhost' === $host ) {
					$is_local_url = true;
				}

				$tlds_to_check = array( '.dev', '.local', ':8888' );
				foreach ( $tlds_to_check as $tld ) {
					if ( false !== strpos( $host, $tld ) ) {
						$is_local_url = true;
						continue;
					}
				}
				if ( substr_count( $host, '.' ) > 1 ) {
					$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
					foreach ( $subdomains_to_check as $subdomain ) {
						$subdomain = str_replace( '.', '(.)', $subdomain );
						$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
						if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
							$is_local_url = true;
							continue;
						}
					}
				}
			}
			return $is_local_url;
		}

		/**
		 * Checks if current admin screen is the plugins page.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public function is_plugin_page() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			if ( empty( $screen ) ) {
				return false;
			}
			return ( ! empty( $screen->id ) && in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) );
		}

		/**
		 * Survey javascript.
		 *
		 * @since 1.0.0
		 */
		public function load_survey_js() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$survey_scripts_path = plugins_url( '/assets/js/am-deactivation-script.js', USERFEEDBACK_PLUGIN_FILE );

			wp_enqueue_script(
				'am-deactivation-survey',
				$survey_scripts_path,
				array( 'jquery' ),
				userfeedback_get_asset_version()
			);

			wp_localize_script(
				'am-deactivation-survey',
				'userfeedback_deactivation',
				array(
					'plugin'   => $this->plugin,
					'name'     => $this->name,
					'home_url' => home_url(),
					'api_url'  => $this->api_url,
				)
			);
		}

		/**
		 * Survey CSS.
		 *
		 * @since 1.0.0
		 */
		public function css() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}
			?>
			<style type="text/css">
			.am-deactivate-survey-modal {
				display: none;
				table-layout: fixed;
				position: fixed;
				z-index: 9999;
				width: 100%;
				height: 100%;
				text-align: center;
				font-size: 14px;
				top: 0;
				left: 0;
				background: rgba(0,0,0,0.8);
			}
			.am-deactivate-survey-wrap {
				display: table-cell;
				vertical-align: middle;
			}
			.am-deactivate-survey {
				background-color: #fff;
				max-width: 550px;
				margin: 0 auto;
				padding: 30px;
				text-align: left;
			}
			.am-deactivate-survey .error {
				display: block;
				color: red;
				margin: 0 0 10px 0;
			}
			.am-deactivate-survey-title {
				display: block;
				font-size: 18px;
				font-weight: 700;
				text-transform: uppercase;
				border-bottom: 1px solid #ddd;
				padding: 0 0 18px 0;
				margin: 0 0 18px 0;
			}
			.am-deactivate-survey-title span {
				color: #999;
				margin-right: 10px;
			}
			.am-deactivate-survey-desc {
				display: block;
				font-weight: 600;
				margin: 0 0 18px 0;
			}
			.am-deactivate-survey-option {
				margin: 0 0 10px 0;
			}
			.am-deactivate-survey-option-input {
				margin-right: 10px !important;
			}
			.am-deactivate-survey-option-details {
				display: none;
				width: 90%;
				margin: 10px 0 0 30px;
			}
			.am-deactivate-survey-footer {
				margin-top: 18px;
			}
			.am-deactivate-survey-deactivate {
				float: right;
				font-size: 13px;
				color: #ccc;
				text-decoration: none;
				padding-top: 7px;
			}
			</style>
			<?php
		}

		/**
		 * Survey modal.
		 *
		 * @since 1.0.0
		 */
		public function modal() {

			if ( ! $this->is_plugin_page() ) {
				return;
			}

			$options = array(
				1 => array(
					'title' => esc_html__( 'I no longer need the plugin', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
				),
				2 => array(
					'title'   => esc_html__( 'I\'m switching to a different plugin', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
					'details' => esc_html__( 'Please share which plugin', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
				),
				3 => array(
					'title' => esc_html__( 'I couldn\'t get the plugin to work', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
				),
				4 => array(
					'title' => esc_html__( 'It\'s a temporary deactivation', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
				),
				5 => array(
					'title'   => esc_html__( 'Other', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
					'details' => esc_html__( 'Please share the reason', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ),
				),
			);
			?>
			<div class="am-deactivate-survey-modal" id="am-deactivate-survey-<?php echo $this->plugin; ?>">
				<div class="am-deactivate-survey-wrap">
					<form class="am-deactivate-survey" method="post">
						<span class="am-deactivate-survey-title"><span class="dashicons dashicons-testimonial"></span><?php echo ' ' . esc_html__( 'Quick Feedback', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ); ?></span>
						<span class="am-deactivate-survey-desc">
							<?php
							// Translators: Placeholder for the plugin name.
							echo sprintf( esc_html__( 'If you have a moment, please share why you are deactivating %s:', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ), $this->name );
							?>
						</span>
						<div class="am-deactivate-survey-options">
							<?php foreach ( $options as $id => $option ) : ?>
							<div class="am-deactivate-survey-option">
								<label for="am-deactivate-survey-option-<?php echo $this->plugin; ?>-<?php echo $id; ?>" class="am-deactivate-survey-option-label">
									<input id="am-deactivate-survey-option-<?php echo $this->plugin; ?>-<?php echo $id; ?>" class="am-deactivate-survey-option-input" type="radio" name="code" value="<?php echo $id; ?>" />
									<span class="am-deactivate-survey-option-reason"><?php echo $option['title']; ?></span>
								</label>
								<?php if ( ! empty( $option['details'] ) ) : ?>
								<input class="am-deactivate-survey-option-details" type="text" placeholder="<?php echo $option['details']; ?>" />
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
						</div>
						<div class="am-deactivate-survey-footer">
							<button type="submit" class="am-deactivate-survey-submit button button-primary button-large">
								<?php
								// Translators: Adds an ampersand.
								echo sprintf( esc_html__( 'Submit %s Deactivate', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ), '&amp;' );
								?>
							</button>
							<a href="#" class="am-deactivate-survey-deactivate">
								<?php
								// Translators: Adds an ampersand.
								echo sprintf( esc_html__( 'Skip %s Deactivate', 'AWESOMEMOTIVE_GENERIC_TEXTDOMAIN' ), '&amp;' );
								?>
							</a>
						</div>
					</form>
				</div>
			</div>
			<?php
		}
	}
} // End if().
