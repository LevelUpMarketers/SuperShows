<?php
/**
 * Activation routines for SuperShows Trade Shows Directory.
 *
 * @package SuperShowsTradeShowsDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles setup tasks on plugin activation.
 */
class SuperShows_TradeShows_Activator {

	/**
	 * Creates/updates custom database table and schema version option.
	 *
	 * @return void
	 */
	public static function activate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			address_json longtext NOT NULL,
			address_city varchar(120) NOT NULL DEFAULT '',
			address_state varchar(80) NOT NULL DEFAULT '',
			address_zip varchar(20) NOT NULL DEFAULT '',
			related_url varchar(255) NOT NULL DEFAULT '',
			contact_phone varchar(30) NOT NULL DEFAULT '',
			contact_email varchar(190) NOT NULL DEFAULT '',
			facebook_url varchar(255) NOT NULL DEFAULT '',
			instagram_url varchar(255) NOT NULL DEFAULT '',
			linkedin_url varchar(255) NOT NULL DEFAULT '',
			youtube_url varchar(255) NOT NULL DEFAULT '',
			imagery_json longtext NOT NULL,
			page_id bigint(20) unsigned DEFAULT NULL,
			dates_json longtext NOT NULL,
			start_datetime datetime DEFAULT NULL,
			end_datetime datetime DEFAULT NULL,
			start_month tinyint(2) unsigned DEFAULT NULL,
			start_year smallint(4) unsigned DEFAULT NULL,
			description longtext NOT NULL,
			industries_json longtext NOT NULL,
			industries_search text NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY name (name),
			KEY address_city (address_city),
			KEY address_state (address_state),
			KEY address_zip (address_zip),
			KEY page_id (page_id),
			KEY start_datetime (start_datetime),
			KEY start_month (start_month),
			KEY start_year (start_year)
		) {$charset_collate};";
		// phpcs:enable

		dbDelta( $sql );
		update_option( 'supershows_tradeshows_db_version', SUPERSHOWS_TRADE_SHOWS_DB_VERSION );
	}

	/**
	 * Returns the trade shows table name.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'supershows_tradeshows';
	}
}
