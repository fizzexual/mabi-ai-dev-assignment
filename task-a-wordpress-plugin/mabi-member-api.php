<?php
/**
 * Plugin Name: MABI Member API
 * Plugin URI:  https://mabi.bg/
 * Description: REST API endpoint for retrieving member data on the MABI platform.
 * Version:     1.0.0
 * Author:      MABI Development Team
 * Author URI:  https://mabi.bg/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mabi-member-api
 * Domain Path: /languages
 *
 * @package MABI_Member_API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for MABI Member API.
 *
 * Registers a REST API endpoint that returns membership data for a given user.
 *
 * @since 1.0.0
 */
class MABI_Member_API {

	/**
	 * Cache duration in seconds (5 minutes).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CACHE_DURATION = 300;

	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const API_NAMESPACE = 'mabi/v1';

	/**
	 * Initialize the plugin by hooking into WordPress.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			self::API_NAMESPACE,
			'/member/(?P<user_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_member_data' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && intval( $param ) > 0;
						},
						'sanitize_callback' => 'absint',
						'description'       => __( 'Уникален идентификатор на потребителя.', 'mabi-member-api' ),
					),
				),
			)
		);
	}

	/**
	 * Permission callback for the member endpoint.
	 *
	 * Ensures only logged-in users can access the endpoint.
	 * Regular users can only view their own data; administrators can view any user.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The incoming REST request.
	 * @return true|WP_Error True if permission granted, WP_Error otherwise.
	 */
	public function check_permissions( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'Трябва да сте влезли в профила си, за да достъпите тези данни.', 'mabi-member-api' ),
				array( 'status' => 401 )
			);
		}

		$requested_user_id = absint( $request->get_param( 'user_id' ) );
		$current_user_id   = get_current_user_id();

		if ( $requested_user_id !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Нямате права да преглеждате данните на друг потребител.', 'mabi-member-api' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Retrieve member data for the specified user.
	 *
	 * Returns cached data when available (transient cache with 5-minute TTL).
	 * Falls back to usermeta values with hardcoded defaults for mock data.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The incoming REST request.
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
	 */
	public function get_member_data( $request ) {
		$user_id = absint( $request->get_param( 'user_id' ) );

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'rest_user_not_found',
				/* translators: %d: The user ID that was not found. */
				sprintf( __( 'Потребител с ID %d не е намерен.', 'mabi-member-api' ), $user_id ),
				array( 'status' => 404 )
			);
		}

		// Check transient cache first.
		$cache_key = 'mabi_member_' . $user_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$data = $this->build_member_data( $user );

		// Store in transient cache for 5 minutes.
		set_transient( $cache_key, $data, self::CACHE_DURATION );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Build the member data array for a given user.
	 *
	 * Reads values from usermeta where available, with sensible mock fallbacks.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user The WordPress user object.
	 * @return array Associative array of member data.
	 */
	public function build_member_data( $user ) {
		$user_id = $user->ID;

		// Membership active status from usermeta, default to true.
		$membership_active = get_user_meta( $user_id, 'mabi_membership_active', true );
		if ( '' === $membership_active ) {
			$membership_active = true;
		} else {
			$membership_active = (bool) $membership_active;
		}

		// Membership level from usermeta, default to 'level_1'.
		$membership_level = get_user_meta( $user_id, 'mabi_membership_level', true );
		if ( empty( $membership_level ) ) {
			$membership_level = 'level_1';
		}

		// Membership expiry from usermeta, default to one year from now.
		$membership_expires = get_user_meta( $user_id, 'mabi_membership_expires', true );
		if ( empty( $membership_expires ) ) {
			$membership_expires = gmdate( 'Y-m-d', strtotime( '+1 year' ) );
		}

		// Courses completed from usermeta, default to 0.
		$courses_completed = get_user_meta( $user_id, 'mabi_courses_completed', true );
		$courses_completed = ( '' !== $courses_completed ) ? absint( $courses_completed ) : 0;

		// Total courses from usermeta, default to 8.
		$courses_total = get_user_meta( $user_id, 'mabi_courses_total', true );
		$courses_total = ( '' !== $courses_total ) ? absint( $courses_total ) : 8;

		// Last login from usermeta, default to current time.
		$last_login = get_user_meta( $user_id, 'mabi_last_login', true );
		if ( empty( $last_login ) ) {
			$last_login = gmdate( 'Y-m-d\TH:i:s' );
		}

		// Calculate days as member from registration date stored in usermeta or user_registered.
		$registration_date = get_user_meta( $user_id, 'mabi_registration_date', true );
		if ( empty( $registration_date ) ) {
			$registration_date = $user->user_registered;
		}

		$days_member = 0;
		if ( ! empty( $registration_date ) ) {
			$reg_timestamp     = strtotime( $registration_date );
			$current_timestamp = time();
			$days_member       = max( 0, (int) floor( ( $current_timestamp - $reg_timestamp ) / DAY_IN_SECONDS ) );
		}

		return array(
			'user_id'            => $user_id,
			'display_name'       => $user->display_name,
			'membership_active'  => $membership_active,
			'membership_level'   => $membership_level,
			'membership_expires' => $membership_expires,
			'courses_completed'  => $courses_completed,
			'courses_total'      => $courses_total,
			'last_login'         => $last_login,
			'days_member'        => $days_member,
		);
	}

	/**
	 * Clear the transient cache for a specific user.
	 *
	 * Useful when usermeta is updated and stale cache should be invalidated.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The user ID whose cache should be cleared.
	 * @return bool True if the transient was deleted, false otherwise.
	 */
	public static function clear_cache( $user_id ) {
		return delete_transient( 'mabi_member_' . absint( $user_id ) );
	}
}

// Bootstrap the plugin.
$mabi_member_api = new MABI_Member_API();
$mabi_member_api->init();
