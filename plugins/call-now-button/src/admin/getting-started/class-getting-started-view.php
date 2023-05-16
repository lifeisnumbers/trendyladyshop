<?php

namespace cnb\admin\gettingstarted;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

use cnb\CnbHeaderNotices;

class GettingStartedView {
    public function render() {

        wp_enqueue_style( CNB_SLUG . '-styling' );
        wp_enqueue_script( CNB_SLUG . '-premium-activation' );

        // Create link to the regular legacy page
        $url      = admin_url( 'admin.php' );
        $link =
            add_query_arg(
                array(
                    'page'   => 'call-now-button'
                ),
                $url );
        ?>
        <div class="cnb-welcome-page">
          <div class="cnb-welcome-blocks cnb-extra-top">

            <img class="cnb-logo" src="<?php echo esc_url(WP_PLUGIN_URL . '/' . CNB_BASEFOLDER . '/resources/images/icon-256x256.png');?>" width="128" height="128" alt="Call Now Button icon" />
            <h1>Welcome to Call Now Button</h1>
            <h3>Thank you for choosing Call Now Button - The web's most popular click-to-call button</h3>
            <div class="cnb-block cnb-signup-box">
              <br>
            <?php echo CnbHeaderNotices::cnb_settings_email_activation_input(); // phpcs:ignore WordPress.Security ?>
            </div>
            <div class="cnb-divider"></div>
            <p>Or click <a href="<?php echo esc_url( $link ) ?>">here</a> to continue without an account.</p>
            <div class="cnb-divider"></div>
            <br>
            <h2>👋 Connect with NowButtons.com to enable the following actions:</h2>
              <div class="cnb-block">

                  <h3 style="line-height:1.9">
                    <span>WhatsApp</span> ✨ <span>Messenger</span> ✨ <span>Telegram</span> ✨ <span>Signal</span><br> 
                    <span>SMS/Text</span> ✨ <span>Email</span><br>
                    <span>Location</span> ✨ <span>URLs</span> ✨ <span>Scroll to Point</span><br>
                    <span>Skype</span> ✨ <span>Viber</span> ✨ <span>Line</span> ✨ <span>Zalo</span> ✨ <span>WeChat</span><br>
                  </h3>

                  <br>
                  <h2>...and enable more features!</h2>
                  <br>


                  <h3>🆕 4 extra buttons</h3>
                  <p>Get 5 buttons instead of 1</p>
                  <h3>🖥️ All devices</h3>
                  <p>Desktop/laptop and mobile support</p>
                  <h3>🎯 Display rules</h3>
                  <p>Create smarter rules for your buttons to appear</p>

              </div>
              <div class="cnb-block cnb-signup-box">
                <br>
                <h2>Sign up now to enable all of this for free</h2>
                <?php echo CnbHeaderNotices::cnb_settings_email_activation_input(); // phpcs:ignore WordPress.Security ?>
              </div>
            </div>
            <div class="cnb-welcome-blocks cnb-welcome-blocks-plain">
              <div class="cnb-block">
                <p><i>Only need a Call button? <a href="<?php echo esc_url( $link ) ?>">Continue without an account</a>.</i></p>
              </div>
          </div>
          <div class="cnb-welcome-blocks">
            <div class="cnb-block">
              <h1>Why do I need an account?</h1>
              <h3>With an account you enable the cloud features from nowbuttons.com.</h3>
              <p>Once you've signed up you directly have access to the features described above. <strong>Completely FREE!</strong></p>
              <div class="cnb-block cnb-signup-box">
              <?php echo CnbHeaderNotices::cnb_settings_email_activation_input(); // phpcs:ignore WordPress.Security ?>
              </div>
            </div>
          </div>
          <div class="cnb-welcome-blocks">
            <div class="cnb-block">
                <h1>Upgrade to PRO to get even more!</h1>


                <br>
                <h2>🎁 Icon selection with each action 🎁</h2>
                  <img class="cnb-width-80 cnb-extra-space" src="<?php echo esc_url(WP_PLUGIN_URL . '/' . CNB_BASEFOLDER . '/resources/images/cnb-icons-actions.png');?>" alt="WhatsApp modal">

                <div class="cnb-divider"></div>

                <h2>💬 Add WhatsApp Chat to your website 💬</h2>
                <img src="<?php echo esc_url(WP_PLUGIN_URL . '/' . CNB_BASEFOLDER . '/resources/images/whatsapp-modal.png');?>" alt="WhatsApp modal">
                <p>Start the WhatsApp conversation on your website.</p>

                <div class="cnb-divider"></div>

                <h2>💎 Multibutton 💎</h2>
                <img class="cnb-width-80" src="<?php echo esc_url(WP_PLUGIN_URL . '/' . CNB_BASEFOLDER . '/resources/images/multibutton.png');?>" alt="Multibutton">
                <p>Takes up little space but reveals a treasure of options.</p>

                <div class="cnb-divider"></div>

                <h2>✨ Buttonbar ✨</h2>
                <img class="cnb-width-80" src="<?php echo esc_url(WP_PLUGIN_URL . '/' . CNB_BASEFOLDER . '/resources/images/buttonbar.png');?>" alt="Buttonbar">
                <p>Create a web app experience on your website.</p>

                <div class="cnb-divider"></div>

                <h2>🕘 The scheduler 🕔</h2>
                <img src="<?php echo esc_url(WP_PLUGIN_URL . '/' . CNB_BASEFOLDER . '/resources/images/button-scheduler.png');?>" alt="The scheduler">
                <p>Control exactly when your buttons are displayed. Maybe a call button during business hours and a mail buttons when you're closed.</p>

                <br>
                <h2>Plus...</h2>
                <div class="cnb-center">
                  <h3>🌼 More button types</h3>
                  <h3>📄 Slide-in content windows</h3>
                  <h3>📷 Use custom images on buttons</h3>
                  <h3>🌍 Include and exclude countries</h3>
                  <h3>↕️ Set scroll height for buttons to appear</h3>
                  <h3>🔌 Intercom Chat integration</h3>
                </div>
                <h2>...and much more!</h2>
              </div>
          </div>

        </div>
        <div class="cnb-welcome-blocks">
          <div class="cnb-block cnb-signup-box">
            <h2>Create your free account and supercharge your Call Now Button.</h2>
            <?php echo CnbHeaderNotices::cnb_settings_email_activation_input(); // phpcs:ignore WordPress.Security ?>
          </div>
        </div>

        <div class="cnb-welcome-blocks cnb-welcome-blocks-plain">
          <div class="cnb-block cnb-signup-box">
          <p><i>Only need a Call button? <a href="<?php echo esc_url( $link ) ?>">Continue without an account</a>.</i></p>
        </div>
      </div>

  <?php  }
}
