<?php
/**
 * Unit tests for the MABI Member API plugin.
 *
 * @package MABI_Member_API
 * @subpackage Tests
 */

/**
 * Test case for MABI_Member_API.
 *
 * @since 1.0.0
 */
class Test_MABI_Member_API extends WP_UnitTestCase {

	/**
	 * Instance of the plugin class.
	 *
	 * @var MABI_Member_API
	 */
	private $plugin;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $test_user_id;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Set up test fixtures.
	 *
	 * @since 1.0.0
	 */
	public function set_up() {
		parent::set_up();

		$this->plugin = new MABI_Member_API();

		// Create a regular subscriber user.
		$this->test_user_id = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Тестов Потребител',
				'user_login'   => 'testuser_mabi',
			)
		);

		// Create an admin user.
		$this->admin_user_id = $this->factory->user->create(
			array(
				'role'         => 'administrator',
				'display_name' => 'Админ МАБИ',
				'user_login'   => 'admin_mabi',
			)
		);

		// Set up mock usermeta for the test user.
		update_user_meta( $this->test_user_id, 'mabi_membership_active', true );
		update_user_meta( $this->test_user_id, 'mabi_membership_level', 'level_2' );
		update_user_meta( $this->test_user_id, 'mabi_membership_expires', '2026-12-31' );
		update_user_meta( $this->test_user_id, 'mabi_courses_completed', 4 );
		update_user_meta( $this->test_user_id, 'mabi_courses_total', 8 );
		update_user_meta( $this->test_user_id, 'mabi_last_login', '2026-05-28T14:32:00' );
		update_user_meta( $this->test_user_id, 'mabi_registration_date', '2025-12-01 00:00:00' );
	}

	/**
	 * Tear down test fixtures.
	 *
	 * @since 1.0.0
	 */
	public function tear_down() {
		// Clear transient caches.
		MABI_Member_API::clear_cache( $this->test_user_id );
		MABI_Member_API::clear_cache( $this->admin_user_id );

		parent::tear_down();
	}

	/**
	 * Test that REST routes are registered correctly.
	 *
	 * @since 1.0.0
	 */
	public function test_routes_registered() {
		$this->plugin->register_routes();

		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/mabi/v1/member/(?P<user_id>\\d+)', $routes );
	}

	/**
	 * Test that unauthenticated requests receive a 401 response.
	 *
	 * @since 1.0.0
	 */
	public function test_unauthenticated_request_returns_401() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/' . $this->test_user_id );
		$request->set_param( 'user_id', $this->test_user_id );

		$result = $this->plugin->check_permissions( $request );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );

		$error_data = $result->get_error_data();
		$this->assertSame( 401, $error_data['status'] );
	}

	/**
	 * Test that a regular user accessing another user's data receives a 403 response.
	 *
	 * @since 1.0.0
	 */
	public function test_regular_user_accessing_other_user_returns_403() {
		wp_set_current_user( $this->test_user_id );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/' . $this->admin_user_id );
		$request->set_param( 'user_id', $this->admin_user_id );

		$result = $this->plugin->check_permissions( $request );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );

		$error_data = $result->get_error_data();
		$this->assertSame( 403, $error_data['status'] );
	}

	/**
	 * Test that a regular user can access their own data.
	 *
	 * @since 1.0.0
	 */
	public function test_regular_user_can_access_own_data() {
		wp_set_current_user( $this->test_user_id );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/' . $this->test_user_id );
		$request->set_param( 'user_id', $this->test_user_id );

		$result = $this->plugin->check_permissions( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test that an admin can access any user's data.
	 *
	 * @since 1.0.0
	 */
	public function test_admin_can_access_any_user_data() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/' . $this->test_user_id );
		$request->set_param( 'user_id', $this->test_user_id );

		$result = $this->plugin->check_permissions( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test that requesting a non-existent user returns a 404 response.
	 *
	 * @since 1.0.0
	 */
	public function test_nonexistent_user_returns_404() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/99999' );
		$request->set_param( 'user_id', 99999 );

		$result = $this->plugin->get_member_data( $request );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'rest_user_not_found', $result->get_error_code() );

		$error_data = $result->get_error_data();
		$this->assertSame( 404, $error_data['status'] );
	}

	/**
	 * Test that valid member data is returned with correct structure.
	 *
	 * @since 1.0.0
	 */
	public function test_valid_member_data_returned() {
		wp_set_current_user( $this->test_user_id );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/' . $this->test_user_id );
		$request->set_param( 'user_id', $this->test_user_id );

		$response = $this->plugin->get_member_data( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( $this->test_user_id, $data['user_id'] );
		$this->assertSame( 'Тестов Потребител', $data['display_name'] );
		$this->assertTrue( $data['membership_active'] );
		$this->assertSame( 'level_2', $data['membership_level'] );
		$this->assertSame( '2026-12-31', $data['membership_expires'] );
		$this->assertSame( 4, $data['courses_completed'] );
		$this->assertSame( 8, $data['courses_total'] );
		$this->assertSame( '2026-05-28T14:32:00', $data['last_login'] );
		$this->assertIsInt( $data['days_member'] );
		$this->assertGreaterThanOrEqual( 0, $data['days_member'] );
	}

	/**
	 * Test that response data is cached via transients.
	 *
	 * @since 1.0.0
	 */
	public function test_response_is_cached() {
		wp_set_current_user( $this->test_user_id );

		$request = new WP_REST_Request( 'GET', '/mabi/v1/member/' . $this->test_user_id );
		$request->set_param( 'user_id', $this->test_user_id );

		// First request — should set the transient.
		$this->plugin->get_member_data( $request );

		$cache_key = 'mabi_member_' . $this->test_user_id;
		$cached    = get_transient( $cache_key );

		$this->assertNotFalse( $cached );
		$this->assertIsArray( $cached );
		$this->assertSame( $this->test_user_id, $cached['user_id'] );
	}

	/**
	 * Test that cache can be cleared.
	 *
	 * @since 1.0.0
	 */
	public function test_cache_clear() {
		$cache_key = 'mabi_member_' . $this->test_user_id;

		set_transient( $cache_key, array( 'test' => true ), 300 );
		$this->assertNotFalse( get_transient( $cache_key ) );

		MABI_Member_API::clear_cache( $this->test_user_id );
		$this->assertFalse( get_transient( $cache_key ) );
	}

	/**
	 * Test that default values are used when usermeta is empty.
	 *
	 * @since 1.0.0
	 */
	public function test_default_values_when_no_usermeta() {
		// Create a user with no MABI-specific usermeta.
		$bare_user_id = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Нов Потребител',
			)
		);

		wp_set_current_user( $bare_user_id );

		$user = get_userdata( $bare_user_id );
		$data = $this->plugin->build_member_data( $user );

		$this->assertSame( $bare_user_id, $data['user_id'] );
		$this->assertSame( 'Нов Потребител', $data['display_name'] );
		$this->assertTrue( $data['membership_active'] );
		$this->assertSame( 'level_1', $data['membership_level'] );
		$this->assertSame( 0, $data['courses_completed'] );
		$this->assertSame( 8, $data['courses_total'] );
		$this->assertIsInt( $data['days_member'] );

		// Clean up.
		MABI_Member_API::clear_cache( $bare_user_id );
	}
}
