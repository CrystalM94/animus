<?php
/**
 * Launch-day signup capture: name + email, stored in a custom table,
 * with admin listing, CSV export, and signup notifications
 * (email, Twilio SMS over HTTPS, or email-to-SMS gateway).
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Launch_Signup {

	const TABLE_VERSION = '1';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_create_table' ) );
		add_shortcode( 'animus_launch_signup', array( __CLASS__, 'form' ) );
		add_action( 'admin_post_animus_launch_signup', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_animus_launch_signup', array( __CLASS__, 'handle' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_export' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'animus_launch_signups';
	}

	public static function maybe_create_table() {
		if ( self::TABLE_VERSION === get_option( 'animus_signups_table_v' ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = 'CREATE TABLE ' . self::table_name() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(190) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL,
			created_at datetime NOT NULL,
			ip varchar(45) NOT NULL DEFAULT '',
			UNIQUE KEY email (email),
			PRIMARY KEY (id)
		) " . $wpdb->get_charset_collate() . ';';
		dbDelta( $sql );
		update_option( 'animus_signups_table_v', self::TABLE_VERSION );
	}

	/**
	 * Signup form markup. Result messaging comes back via redirect flag.
	 */
	public static function form() {
		$status = isset( $_GET['animus_signup'] ) ? sanitize_key( wp_unslash( $_GET['animus_signup'] ) ) : '';
		$ref    = esc_url( remove_query_arg( 'animus_signup' ) );

		ob_start();
		?>
		<form class="animus-signup" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="animus_launch_signup">
			<input type="hidden" name="redirect" value="<?php echo esc_attr( $ref ); ?>">
			<?php wp_nonce_field( 'animus_launch_signup', 'animus_signup_nonce' ); ?>
			<span class="animus-signup__hp" aria-hidden="true"><label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></span>
			<input type="text" name="name" class="animus-signup__input" placeholder="<?php esc_attr_e( 'Name', 'animus-labs-core' ); ?>" required maxlength="120">
			<input type="email" name="email" class="animus-signup__input" placeholder="<?php esc_attr_e( 'Email address', 'animus-labs-core' ); ?>" required maxlength="190">
			<button type="submit" class="animus-btn animus-btn--solid"><?php esc_html_e( 'Notify me at launch', 'animus-labs-core' ); ?></button>
		</form>
		<?php if ( 'ok' === $status ) : ?>
			<p class="animus-signup__msg animus-signup__msg--ok"><?php esc_html_e( 'You are on the launch list — see you Black Friday.', 'animus-labs-core' ); ?></p>
		<?php elseif ( 'exists' === $status ) : ?>
			<p class="animus-signup__msg"><?php esc_html_e( 'That email is already on the launch list.', 'animus-labs-core' ); ?></p>
		<?php elseif ( 'invalid' === $status ) : ?>
			<p class="animus-signup__msg animus-signup__msg--err"><?php esc_html_e( 'Please enter a valid name and email address.', 'animus-labs-core' ); ?></p>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	public static function handle() {
		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
		$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

		$finish = function ( $status ) use ( $redirect ) {
			wp_safe_redirect( add_query_arg( 'animus_signup', $status, $redirect ) );
			exit;
		};

		// Honeypot: bots fill hidden fields, humans never see it.
		if ( ! empty( $_POST['website'] ) ) {
			$finish( 'ok' );
		}
		if ( ! isset( $_POST['animus_signup_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['animus_signup_nonce'] ) ), 'animus_launch_signup' ) ) {
			$finish( 'invalid' );
		}

		// Rate limit: 5 submissions per IP per hour.
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'animus_signup_rl_' . md5( $ip );
		$cnt = (int) get_transient( $key );
		if ( $cnt >= 5 ) {
			$finish( 'invalid' );
		}
		set_transient( $key, $cnt + 1, HOUR_IN_SECONDS );

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( '' === $name || ! is_email( $email ) ) {
			$finish( 'invalid' );
		}

		global $wpdb;
		$inserted = $wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO ' . self::table_name() . ' (name, email, created_at, ip) VALUES (%s, %s, %s, %s)',
				$name,
				$email,
				current_time( 'mysql' ),
				$ip
			)
		);
		if ( false === $inserted ) {
			$finish( 'invalid' );
		}
		if ( 0 === (int) $inserted ) {
			$finish( 'exists' );
		}

		self::notify( $name, $email );
		$finish( 'ok' );
	}

	/**
	 * Alert Crystal: Twilio SMS over HTTPS (primary), email-to-SMS gateway
	 * or admin email via wp_mail as configured.
	 */
	private static function notify( $name, $email ) {
		$msg = sprintf( '%s signed up for launch day (%s)', $name, $email );

		$topic = animus_core_setting( 'ntfy_topic', '' );
		if ( $topic ) {
			wp_remote_post(
				'https://ntfy.sh/' . rawurlencode( $topic ),
				array(
					'body'    => $msg,
					'headers' => array( 'Title' => 'Animus Labs signup', 'Priority' => 'high' ),
					'timeout' => 10,
				)
			);
		}

		$sid   = animus_core_setting( 'twilio_sid', '' );
		$token = animus_core_setting( 'twilio_token', '' );
		$from  = animus_core_setting( 'twilio_from', '' );
		$to    = animus_core_setting( 'sms_to', '' );
		if ( $sid && $token && $from && $to ) {
			wp_remote_post(
				'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $sid ) . '/Messages.json',
				array(
					'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ) ),
					'body'    => array( 'To' => $to, 'From' => $from, 'Body' => $msg ),
					'timeout' => 10,
				)
			);
		} else {
			$sms_gw = animus_core_setting( 'signup_sms_to', '' );
			if ( $sms_gw ) {
				wp_mail( $sms_gw, '', $msg );
			}
		}

		$email_to = animus_core_setting( 'signup_notify_email', '' );
		if ( ! $email_to ) {
			$email_to = get_option( 'admin_email' );
		}
		if ( $email_to ) {
			wp_mail( $email_to, __( 'New launch signup', 'animus-labs-core' ), $msg );
		}
	}

	public static function menu() {
		add_submenu_page(
			'animus-labs',
			__( 'Launch Signups', 'animus-labs-core' ),
			__( 'Launch Signups', 'animus-labs-core' ),
			'manage_woocommerce',
			'animus-launch-signups',
			array( __CLASS__, 'render_admin' )
		);
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'animus-labs-core' ) );
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT name, email, created_at, ip FROM ' . self::table_name() . ' ORDER BY created_at DESC LIMIT 500' );
		echo '<div class="wrap"><h1>' . esc_html__( 'Launch Signups', 'animus-labs-core' ) . '</h1>';
		echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=animus-launch-signups&export=csv&_wpnonce=' . wp_create_nonce( 'animus_signups_csv' ) ) ) . '">' . esc_html__( 'Export CSV', 'animus-labs-core' ) . '</a></p>';
		echo '<table class="widefat striped"><thead><tr><th>Name</th><th>Email</th><th>Signed up</th><th>IP</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			echo '<tr><td>' . esc_html( $r->name ) . '</td><td>' . esc_html( $r->email ) . '</td><td>' . esc_html( $r->created_at ) . '</td><td>' . esc_html( $r->ip ) . '</td></tr>';
		}
		if ( empty( $rows ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No signups yet.', 'animus-labs-core' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function maybe_export() {
		if ( ! isset( $_GET['page'], $_GET['export'] ) || 'animus-launch-signups' !== $_GET['page'] || 'csv' !== $_GET['export'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'animus_signups_csv' ) ) {
			wp_die( 'Forbidden' );
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT name, email, created_at FROM ' . self::table_name() . ' ORDER BY created_at', ARRAY_A );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=animus-launch-signups.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'name', 'email', 'created_at' ) );
		foreach ( $rows as $r ) {
			fputcsv( $out, $r );
		}
		fclose( $out );
		exit;
	}
}
