<?php

namespace cnb\admin\apikey;

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

/**
 * Somewhat simple router, here to ensure we stay in line with the rest.
 *
 * Since APIKey creation is handled via admin-post, the only thing we do here is render the overview.
 */
class CnbApiKeyRouter {
    public function render() {
        do_action( 'cnb_init', __METHOD__ );
        ( new CnbApiKeyView() )->render();
        do_action( 'cnb_finish' );
    }
}
