<?php
/**
 * Editable compliance copy + store options.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Settings {

	const OPTION = 'animus_labs_settings';
	const GROUP  = 'animus_labs_settings_group';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Seed defaults on activation so admins see editable copy immediately.
	 */
	public static function seed_defaults() {
		$existing = get_option( self::OPTION, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}
		update_option( self::OPTION, array_merge( animus_core_default_settings(), $existing ) );
	}

	public static function menu() {
		add_submenu_page(
			'animus-labs',
			__( 'Compliance Settings', 'animus-labs-core' ),
			__( 'Compliance Settings', 'animus-labs-core' ),
			'manage_woocommerce',
			'animus-labs-settings',
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => animus_core_default_settings(),
			)
		);
	}

	/**
	 * Sanitize every field; long-form copy allows basic inline HTML only.
	 *
	 * @param mixed $input Raw option value.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$out      = array();
		$defaults = animus_core_default_settings();
		$allowed  = array(
			'a'      => array( 'href' => array(), 'title' => array(), 'target' => array(), 'rel' => array() ),
			'strong' => array(),
			'em'     => array(),
			'br'     => array(),
		);

		foreach ( $defaults as $key => $default ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : $default;
			if ( 'policy_version' === $key ) {
				$out[ $key ] = sanitize_text_field( $value );
				continue;
			}
			$out[ $key ] = wp_kses( (string) $value, $allowed );
		}

		// Non-copy toggles.
		$out['age_gate_enabled'] = ! empty( $input['age_gate_enabled'] ) ? 'yes' : 'no';
		$out['age_gate_min']     = isset( $input['age_gate_min'] ) ? absint( $input['age_gate_min'] ) : 21;
		$out['age_gate_title']   = isset( $input['age_gate_title'] ) ? sanitize_text_field( $input['age_gate_title'] ) : __( 'Restricted access', 'animus-labs-core' );
		$out['age_gate_body']    = isset( $input['age_gate_body'] ) ? wp_kses( $input['age_gate_body'], $allowed ) : '';

		Animus_Audit::log( 'settings_updated', 'settings', 0, array( 'keys' => array_keys( $out ) ) );

		return $out;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'animus-labs-core' ) );
		}

		$opts = wp_parse_args( get_option( self::OPTION, array() ), animus_core_default_settings() );

		$fields = array(
			'coming_soon_title'  => array( __( 'Coming-soon page heading', 'animus-labs-core' ), 'text' ),
			'coming_soon_body'   => array( __( 'Coming-soon page copy', 'animus-labs-core' ), 'textarea' ),
			'coming_soon_key'    => array( __( 'Coming-soon preview key (?animus_preview=KEY bypasses the wall)', 'animus-labs-core' ), 'text' ),
			'topbar_notice'      => array( __( 'Header notice bar', 'animus-labs-core' ), 'text' ),
			'ruo_notice'         => array( __( 'Research-use-only notice', 'animus-labs-core' ), 'textarea' ),
			'checkout_ack'       => array( __( 'Checkout acknowledgement text', 'animus-labs-core' ), 'textarea' ),
			'checkout_ack_sub'   => array( __( 'Checkout acknowledgement sub-text', 'animus-labs-core' ), 'textarea' ),
			'policy_version'     => array( __( 'Policy version (recorded on each order)', 'animus-labs-core' ), 'text' ),
			'verification_intro' => array( __( 'Lot verification intro', 'animus-labs-core' ), 'textarea' ),
			'order_lot_note'     => array( __( 'Order lot traceability note', 'animus-labs-core' ), 'textarea' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Animus Labs — Compliance Settings', 'animus-labs-core' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'All customer-facing compliance language is editable here. Changing the policy version means future orders record the new version; historical orders keep the version they were placed under.', 'animus-labs-core' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( $fields as $key => $field ) : ?>
						<tr>
							<th scope="row"><label for="animus-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $field[1] ) : ?>
									<textarea id="animus-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $opts[ $key ] ); ?></textarea>
								<?php else : ?>
									<input type="text" id="animus-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $opts[ $key ] ); ?>" class="regular-text">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>

					<tr>
						<th scope="row"><?php esc_html_e( 'Coming soon', 'animus-labs-core' ); ?></th>
						<td>
							<label for="animus-coming-soon">
								<input type="checkbox" id="animus-coming-soon" name="<?php echo esc_attr( self::OPTION ); ?>[coming_soon_enabled]" value="yes" <?php checked( 'yes', isset( $opts['coming_soon_enabled'] ) ? $opts['coming_soon_enabled'] : 'no' ); ?>>
								<?php esc_html_e( 'Show a coming-soon page to all logged-out visitors', 'animus-labs-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Staff can still log in at /wp-login.php and use the full site. Heading and copy are editable above.', 'animus-labs-core' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Age / researcher gate', 'animus-labs-core' ); ?></th>
						<td>
							<label for="animus-age-gate">
								<input type="checkbox" id="animus-age-gate" name="<?php echo esc_attr( self::OPTION ); ?>[age_gate_enabled]" value="yes" <?php checked( 'yes', isset( $opts['age_gate_enabled'] ) ? $opts['age_gate_enabled'] : 'yes' ); ?>>
								<?php esc_html_e( 'Show the restricted-access gate to first-time visitors', 'animus-labs-core' ); ?>
							</label>
							<p style="margin-top:10px">
								<label for="animus-age-min" style="display:inline"><?php esc_html_e( 'Minimum age', 'animus-labs-core' ); ?></label>
								<input type="number" min="0" max="99" id="animus-age-min" name="<?php echo esc_attr( self::OPTION ); ?>[age_gate_min]" value="<?php echo esc_attr( isset( $opts['age_gate_min'] ) ? $opts['age_gate_min'] : 21 ); ?>" class="small-text">
							</p>
							<p>
								<label for="animus-age-title" style="display:inline"><?php esc_html_e( 'Gate heading', 'animus-labs-core' ); ?></label><br>
								<input type="text" id="animus-age-title" name="<?php echo esc_attr( self::OPTION ); ?>[age_gate_title]" value="<?php echo esc_attr( isset( $opts['age_gate_title'] ) ? $opts['age_gate_title'] : __( 'Restricted access', 'animus-labs-core' ) ); ?>" class="regular-text">
							</p>
							<p>
								<label for="animus-age-body" style="display:inline"><?php esc_html_e( 'Gate body copy', 'animus-labs-core' ); ?></label><br>
								<textarea id="animus-age-body" name="<?php echo esc_attr( self::OPTION ); ?>[age_gate_body]" rows="3" class="large-text"><?php echo esc_textarea( isset( $opts['age_gate_body'] ) ? $opts['age_gate_body'] : Animus_Compliance::default_gate_body() ); ?></textarea>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
