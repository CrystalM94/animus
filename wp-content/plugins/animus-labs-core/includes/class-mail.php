<?php
/**
 * Outbound mail via the Brevo transactional REST API (HTTPS/443).
 * The host blocks outbound SMTP ports, so wp_mail is intercepted here
 * and every site email (signup alerts, order mail) goes through Brevo.
 * Inactive until a brevo_api_key is configured in settings.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Mail {

	public static function init() {
		add_filter( 'pre_wp_mail', array( __CLASS__, 'send' ), 10, 2 );
	}

	/**
	 * Short-circuit wp_mail and deliver through Brevo's REST API.
	 *
	 * @param null|bool $return Filter return.
	 * @param array     $atts   wp_mail args: to, subject, message, headers, attachments.
	 * @return bool|null
	 */
	public static function send( $return, $atts ) {
		$key = animus_core_setting( 'brevo_api_key', '' );
		if ( '' === $key ) {
			return $return;
		}

		$to = array();
		foreach ( (array) $atts['to'] as $addr ) {
			$to[] = array( 'email' => trim( $addr ) );
		}
		if ( empty( $to ) ) {
			return false;
		}

		$headers = self::parse_headers( $atts['headers'] );
		$from    = $headers['from'];
		$subject = is_string( $atts['subject'] ) ? $atts['subject'] : '';
		$message = is_string( $atts['message'] ) ? $atts['message'] : '';

		$payload = array(
			'sender' => array(
				'email' => $from['email'],
				'name'  => $from['name'],
			),
			'to'     => $to,
		);
		if ( '' !== $subject ) {
			$payload['subject'] = $subject;
		}
		if ( false !== stripos( $headers['content_type'], 'text/html' ) ) {
			$payload['htmlContent'] = $message;
		} else {
			$payload['textContent'] = $message;
		}

		$res = wp_remote_post(
			'https://api.brevo.com/v3/smtp/email',
			array(
				'headers' => array(
					'api-key'      => $key,
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $res ) ) {
			Animus_Audit::log( 'mail_failed', 'mail', 0, array( 'error' => $res->get_error_message(), 'to' => wp_json_encode( $to ) ) );
			return false;
		}
		$code = wp_remote_retrieve_response_code( $res );
		if ( $code >= 300 ) {
			Animus_Audit::log( 'mail_failed', 'mail', 0, array( 'code' => $code, 'to' => wp_json_encode( $to ) ) );
			return false;
		}
		return true;
	}

	/**
	 * Pull From / Reply-To / Content-Type out of wp_mail headers.
	 *
	 * @param string|array $headers Raw headers.
	 * @return array
	 */
	private static function parse_headers( $headers ) {
		$from_name  = get_bloginfo( 'name' );
		$from_email = animus_core_setting( 'brevo_from_email', get_option( 'admin_email' ) );
		$ctype      = 'text/plain';
		$lines      = is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );

		foreach ( $lines as $line ) {
			if ( preg_match( '/^from:\s*(.*)$/i', trim( $line ), $m ) ) {
				$v = trim( $m[1] );
				if ( preg_match( '/^(.*)<(.+)>$/', $v, $mm ) ) {
					$from_name  = trim( $mm[1], ' "' ) ?: $from_name;
					$from_email = trim( $mm[2] );
				} elseif ( is_email( $v ) ) {
					$from_email = $v;
				}
			}
			if ( preg_match( '/^content-type:\s*(.*)$/i', trim( $line ), $m ) ) {
				$ctype = trim( $m[1] );
			}
		}
		return array(
			'from'         => array( 'name' => $from_name, 'email' => $from_email ),
			'content_type' => $ctype,
		);
	}
}
