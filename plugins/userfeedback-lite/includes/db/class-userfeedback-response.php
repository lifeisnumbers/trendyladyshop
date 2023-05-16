<?php

/**
 * Survey Response class.
 *
 * @see UserFeedback_DB
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage DB
 * @author  David Paternina
 */
class UserFeedback_Response extends UserFeedback_DB {

	/**
	 * @inheritdoc
	 */
	protected $table_name = 'userfeedback_survey_responses';

	/**
	 * @inheritdoc
	 */
	function get_columns() {
		return array( 'id', 'survey_id', 'answers', 'page_submitted', 'user_ip', 'user_browser', 'user_os', 'user_device', 'submitted_at' );
	}

	/**
	 * @inheritdoc
	 */
	protected $casts = array(
		'answers'        => 'array',
		'page_submitted' => 'object',
	);

	/**
	 * @inheritdoc
	 */
	protected $timestamp_column = 'submitted_at';

	/**
	 * @inheritdoc
	 */
	public function cast_entity_attributes( $item ) {
		$response = parent::cast_entity_attributes( $item );

		if ( isset( $response->page_submitted ) ) {
			$response->page_submitted->url =
				'publish' === get_post_status( $response->page_submitted->id )
					? get_permalink( $response->page_submitted->id )
					: null;
		}
		return $response;
	}

	/**
	 * @inheritdoc
	 */
	function create_table() {
		global $wpdb;

		if ( self::table_exists() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = self::get_table();

		$surveys_db_instance = new UserFeedback_Survey();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            survey_id bigint(20) NOT NULL,
            answers longtext,
            page_submitted text,
            user_ip varchar(256),
            user_browser varchar(128),
            user_os varchar(128),
            user_device varchar(64),
            submitted_at timestamp NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (survey_id) 
                REFERENCES {$surveys_db_instance->get_table()}({$surveys_db_instance->primary_key}) ON DELETE CASCADE
        ) $charset_collate;";

		dbDelta( $sql );
	}

	function get_relationship_config( $name ) {
		switch ( $name ) {
			case 'survey':
				return array(
					'type'  => 'one',
					'class' => UserFeedback_Survey::class,
					'key'   => 'survey_id',
				);
			default:
				return null;
		}
	}
}
