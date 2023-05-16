<?php

/**
 * Ask for some love.
 *
 * @package    UserFeedback
 * @author     UserFeedback Team
 * @since      1.0.1
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2018, UserFeedback LLC
 */
class UserFeedback_Review
{
	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.1
	 */
	public function __construct()
	{
		// Admin notice requesting review.
		add_action('admin_notices', array($this, 'review_request'));
		add_action('wp_ajax_userfeedback_review_dismiss', array($this, 'review_dismiss'));
	}

	/**
	 * Add admin notices as needed for reviews.
	 *
	 * @since 1.0.1
	 */
	public function review_request()
	{

		// Only consider showing the review request to admin users.
		if (!is_super_admin()) {
			return;
		}

		// If the user has opted out of product annoucement notifications, don't
		// display the review request.
		if (userfeedback_get_option('hide_am_notices', false) || userfeedback_get_option('network_hide_am_notices', false)) {
			return;
		}
		// Verify that we can do a check for reviews.
		$review = get_option('userfeedback_review');
		$review['time'] = strtotime('-2 days');
		$time   = time();
		$load   = false;
		if (!$review) {
			$review = array(
				'time'      => $time,
				'dismissed' => false,
			);
			update_option('userfeedback_review', $review);
		} else {
			// Check if it has been dismissed or not.
			if ((isset($review['dismissed']) && !$review['dismissed']) && (isset($review['time']) && (($review['time'] + DAY_IN_SECONDS) <= $time))) {
				$load = true;
			}
		}

		// If we cannot load, return early.
		if (!$load) {
			return;
		}

		$this->review();
	}

	/**
	 * Maybe show review request.
	 *
	 * @since 1.0.1
	 */
	public function review()
	{
		$activated = get_option('userfeedback_over_time', array());
		if (!empty($activated['installed_date'])) {
			$days = 15;
			$show_show = (int) $activated['installed_date'] + DAY_IN_SECONDS * $days;
			$time_now = time();
			if ($time_now < $show_show) {
				return;
			}
		} else {
			if (empty($activated)) {
				$data = array(
					'installed_version' => USERFEEDBACK_VERSION,
					'installed_date'    => time(),
					'installed_pro'     => userfeedback_is_pro_version(),
				);
			} else {
				$data = $activated;
			}
			update_option('userfeedback_over_time', $data, false);
		}

		$feedback_url = add_query_arg(array(
			'wpf192157_24' => untrailingslashit(home_url()),
			'wpf192157_26' => userfeedback_get_license_key(),
			'wpf192157_27' => userfeedback_is_pro_version() ? 'pro' : 'lite',
			'wpf192157_28' => USERFEEDBACK_VERSION,
		), 'https://www.userfeedback.com/plugin-feedback/');
		$feedback_url = userfeedback_get_url('review-notice', 'feedback', $feedback_url);
		// We have a candidate! Output a review message.
?>
		<div class="notice notice-info is-dismissible userfeedback-review-notice">
			<div class="userfeedback-review-step userfeedback-review-step-1">
				<p><?php esc_html_e('Are you enjoying UserFeedback?', 'google-analytics-for-wordpress'); ?></p>
				<p>
					<a href="#" class="userfeedback-review-switch-step" data-step="3"><?php esc_html_e('Yes', 'google-analytics-for-wordpress'); ?></a><br />
					<a href="#" class="userfeedback-review-switch-step" data-step="2"><?php esc_html_e('Not Really', 'google-analytics-for-wordpress'); ?></a>
				</p>
			</div>
			<div class="userfeedback-review-step userfeedback-review-step-2" style="display: none">
				<p><?php esc_html_e('We\'re sorry to hear you aren\'t enjoying UserFeedback. We would love a chance to improve. Could you take a minute and let us know what we can do better?', 'google-analytics-for-wordpress'); ?></p>
				<p>
					<a href="<?php echo esc_url($feedback_url); ?>" class="userfeedback-dismiss-review-notice userfeedback-review-out"><?php esc_html_e('Give Feedback', 'google-analytics-for-wordpress'); ?></a><br>
					<a href="#" class="userfeedback-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e('No thanks', 'google-analytics-for-wordpress'); ?></a>
				</p>
			</div>
			<div class="userfeedback-review-step userfeedback-review-step-3" style="display: none">
				<p><?php esc_html_e('That’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'google-analytics-for-wordpress'); ?></p>
				<p>
					<strong><?php echo wp_kses(__('~ Syed Balkhi<br>Co-Founder of UserFeedback', 'google-analytics-for-wordpress'), array('br' => array())); ?></strong>
				</p>
				<p>
					<a href="https://wordpress.org/support/plugin/userfeedback-lite/reviews/?filter=5#new-post" class="userfeedback-dismiss-review-notice userfeedback-review-out" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Ok, you deserve it', 'google-analytics-for-wordpress'); ?></a><br>
					<a href="#" class="userfeedback-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Nope, maybe later', 'google-analytics-for-wordpress'); ?></a><br>
					<a href="#" class="userfeedback-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><?php esc_html_e('I already did', 'google-analytics-for-wordpress'); ?></a>
				</p>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document).on('click', '.userfeedback-dismiss-review-notice, .userfeedback-review-notice button', function(event) {
					if (!$(this).hasClass('userfeedback-review-out')) {
						event.preventDefault();
					}
					$.post(ajaxurl, {
						action: 'userfeedback_review_dismiss'
					});
					$('.userfeedback-review-notice').remove();
				});

				$(document).on('click', '.userfeedback-review-switch-step', function(e) {
					e.preventDefault();
					var target = $(this).attr('data-step');
					if (target) {
						var notice = $(this).closest('.userfeedback-review-notice');
						var review_step = notice.find('.userfeedback-review-step-' + target);
						if (review_step.length > 0) {
							notice.find('.userfeedback-review-step:visible').fadeOut(function() {
								review_step.fadeIn();
							});
						}
					}
				})
			});
		</script>
<?php
	}

	/**
	 * Dismiss the review admin notice
	 *
	 * @since 1.0.1
	 */
	public function review_dismiss()
	{
		$review              = get_option('userfeedback_review', array());
		$review['time']      = time();
		$review['dismissed'] = true;
		update_option('userfeedback_review', $review);

		if (is_super_admin() && is_multisite()) {
			$site_list = get_sites();
			foreach ((array) $site_list as $site) {
				switch_to_blog($site->blog_id);

				update_option('userfeedback_review', $review);

				restore_current_blog();
			}
		}

		die;
	}
}

new UserFeedback_Review();
