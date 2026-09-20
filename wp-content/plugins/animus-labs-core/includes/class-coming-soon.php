<?php
/**
 * Pre-launch coming-soon wall. Logged-out visitors see a branded holding page
 * while staff keep full access to the site and admin.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Coming_Soon {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 0 );
	}

	/**
	 * Whether the wall should apply to the current request.
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( 'yes' !== animus_core_setting( 'coming_soon_enabled', 'no' ) ) {
			return false;
		}
		if ( is_user_logged_in() ) {
			return false;
		}
		if ( self::has_preview_access() ) {
			return false;
		}
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return false;
		}

		global $pagenow;
		if ( in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Preview-key bypass: ?animus_preview=<key> sets a cookie that unlocks
	 * the site for that browser until it expires. Key is set in admin.
	 */
	private static function has_preview_access() {
		$key = animus_core_setting( 'coming_soon_key', '' );
		if ( '' === $key ) {
			return false;
		}
		if ( isset( $_COOKIE['animus_preview'] ) && hash_equals( $key, sanitize_text_field( wp_unslash( $_COOKIE['animus_preview'] ) ) ) ) {
			return true;
		}
		if ( isset( $_GET['animus_preview'] ) && hash_equals( $key, sanitize_text_field( wp_unslash( $_GET['animus_preview'] ) ) ) ) {
			setcookie( 'animus_preview', $key, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			return true;
		}
		return false;
	}

	/**
	 * Serve the holding page in place of the requested document.
	 */
	public static function maybe_render() {
		if ( ! self::is_active() ) {
			return;
		}

		nocache_headers();
		status_header( 503 );
		header( 'Retry-After: 86400' );
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		self::render();
		exit;
	}

	/**
	 * Holding page markup. Self-contained so it renders without the theme.
	 */
	private static function render() {
		$title = animus_core_setting( 'coming_soon_title' );
		$body  = animus_core_setting( 'coming_soon_body' );
		$css   = get_template_directory_uri() . '/assets/css/main.css';
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $title . ' — ' . get_bloginfo( 'name' ) ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( $css ); ?>">
	<style>
		body.animus-soon {
			min-height: 100vh;
			display: grid;
			place-items: center;
			padding: 8vh 6vw;
			text-align: center;
		}
		.animus-soon__inner { max-width: 44rem; }
		.animus-soon__brand {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: .6rem;
			letter-spacing: .28em;
			text-transform: uppercase;
			font-size: .78rem;
			margin-bottom: 2.5rem;
		}
		.animus-soon__title { font-size: clamp(2rem, 6vw, 3.4rem); line-height: 1.08; margin: 0 0 1.4rem; }
		.animus-soon__body { opacity: .74; font-size: 1.05rem; line-height: 1.7; margin: 0 auto 2.6rem; max-width: 34rem; }
		.animus-soon__rule { width: 3.5rem; height: 1px; background: var(--al-accent, #c62828); margin: 0 auto 2.2rem; }
		.animus-soon__foot { margin-top: 3.5rem; font-size: .72rem; letter-spacing: .18em; text-transform: uppercase; opacity: .45; }
	</style>
</head>
<body class="animus-soon">
	<main class="animus-soon__inner">
		<p class="animus-soon__brand">
			<span class="animus-brand__mark" aria-hidden="true">&#x039B;</span>
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
		</p>
		<div class="animus-soon__rule"></div>
		<h1 class="animus-soon__title"><?php echo esc_html( $title ); ?></h1>
		<p class="animus-soon__body"><?php echo wp_kses_post( $body ); ?></p>
		<p class="animus-soon__foot"><?php echo esc_html( animus_core_setting( 'topbar_notice' ) ); ?></p>
	</main>
</body>
</html>
		<?php
	}
}
