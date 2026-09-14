<?php
/**
 * Audit log for compliance-sensitive actions.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Audit {

	const DB_VERSION = '1.0.0';

	/**
	 * Table name.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'animus_audit_log';
	}

	/**
	 * Create/upgrade the audit table.
	 */
	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(64) NOT NULL DEFAULT '',
			object_type VARCHAR(32) NOT NULL DEFAULT '',
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			detail TEXT NULL,
			ip VARCHAR(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY action (action),
			KEY object (object_type, object_id),
			KEY created_at (created_at)
		) {$collate};";

		dbDelta( $sql );
		update_option( 'animus_audit_db_version', self::DB_VERSION );
	}

	/**
	 * Record an audit entry.
	 *
	 * @param string $action      Short action slug.
	 * @param string $object_type Object type (product, batch, order...).
	 * @param int    $object_id   Object ID.
	 * @param array  $detail      Extra context (stored as JSON).
	 */
	public static function log( $action, $object_type = '', $object_id = 0, $detail = array() ) {
		global $wpdb;

		if ( ! get_option( 'animus_audit_db_version' ) ) {
			self::create_table();
		}

		$wpdb->insert(
			self::table(),
			array(
				'created_at'  => current_time( 'mysql', true ),
				'user_id'     => get_current_user_id(),
				'action'      => substr( sanitize_key( $action ), 0, 64 ),
				'object_type' => substr( sanitize_key( $object_type ), 0, 32 ),
				'object_id'   => absint( $object_id ),
				'detail'      => wp_json_encode( $detail ),
				'ip'          => self::client_ip(),
			),
			array( '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Best-effort client IP, truncated to a valid address.
	 */
	public static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Fetch recent entries for the admin screen.
	 *
	 * @param int $limit Row count.
	 * @return array
	 */
	public static function recent( $limit = 200 ) {
		global $wpdb;
		$table = self::table();
		// Table name is internal, not user input; limit is cast to int.
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", absint( $limit ) )
		);
	}
}
