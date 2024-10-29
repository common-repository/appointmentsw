<?php
/**
 * appointmentsw.php
 *
 * Copyright (c) 2011,2012 Antonio Blanco http://www.blancoleon.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco
 * @package appointmentsw
 * @since appointmentsw 1.0.0
 *
 * Plugin Name: Appointment SW
 * Plugin URI: https://www.eggemplo.com
 * Description: Simple appointments system
 * Version: 1.3
 * Author: eggemplo
 * Author URI: https://www.eggemplo.com
 * Text Domain: appointmentsw
 * Domain Path: /languages
 * License: GPLv3
 */

define( 'APPOINTMENTSW_PLUGIN_NAME', 'appointmentsw' );

define( 'APPOINTMENTSW_FILE', __FILE__ );

if ( !defined( 'APPOINTMENTSW_CORE_DIR' ) ) {
	define( 'APPOINTMENTSW_CORE_DIR', WP_PLUGIN_DIR . '/appointmentsw/core' );
}

define( 'APPOINTMENTSW_PLUGIN_URL', plugin_dir_url( APPOINTMENTSW_FILE ) );

define( 'APPOINTMENTSW_STATUS_ACCEPTED', 'accepted' );
define( 'APPOINTMENTSW_STATUS_CANCELED', 'canceled' );
define( 'APPOINTMENTSW_STATUS_PAST', 'past' );

define( 'APPOINTMENTSW_NUM_DAYS_DEFAULT', 2 );
define( 'APPOINTMENTSW_MAX_DAYS_OFFSET_DEFAULT', 2 );

define( 'APPOINTMENTSW_DURATION_DEFAULT', 30 );

define( 'APPOINTMENTSW_GUEST_DEFAULT', false );

$appointmentSwAlert = "";

class AppointmentSw_Plugin {

	private static $notices = array();

	public static function init() {

		load_plugin_textdomain( 'appointmentsw', null, APPOINTMENTSW_PLUGIN_NAME . '/languages' );

		register_activation_hook( APPOINTMENTSW_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( APPOINTMENTSW_FILE, array( __CLASS__, 'deactivate' ) );

		register_uninstall_hook( APPOINTMENTSW_FILE, array( __CLASS__, 'uninstall' ) );

		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		add_action('wp_head', array( __CLASS__, 'appointmentsw_enqueue_scripts' ) );

		add_action( 'login_enqueue_scripts', array( __CLASS__, 'appointmentsw_login_enqueue_scripts' ), 999 );

		// AJAX
		add_action( 'wp_ajax_check_changes', array( __CLASS__, 'wp_ajax_check_changes' ) );
	}

	public static function wp_ajax_check_changes () {

		$output = "";
		$output .= get_option( 'appointmentsw_changes', 0 );
		echo $output;
		die();
	}

	public static function wp_init() {
		global $appointmentSwAlert;

		$appointmentSwAlert = "";

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );

		//call register settings function
		add_action( 'admin_init', array( __CLASS__, 'register_appointmentsw_settings' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ) );

		if ( !class_exists( "AppointmentSw" ) ) {
			include_once 'core/class-appointmentsw.php';
			include_once 'core/class-appointmentsw-shortcodes.php';
			include_once 'core/class-appointmentsw-notifications.php';

			include_once 'core/class-calendar-settings.php';
		}

		// Process ADD request
		if (isset ( $_REQUEST ['slot'] ) && isset ( $_REQUEST ['action'] ) && $_REQUEST ['action'] == 'appointmentsw-add' && wp_verify_nonce ( $_REQUEST ['appointmentsw-nonce'], 'appointmentsw' )) {

			$step = isset( $_REQUEST ['step'] ) ? $_REQUEST ['step'] : "0";

			switch ( $step ) {
				case "0": // First step - calendar
				case "1": // Second step - user form & confirmation form
				default:
					// Nothing. In the shortcode we display the second step
					break;
				case "2": // process the appointment
					$result = AppointmentSw::addAppointment( isset( $_REQUEST ['user_id'] ) ? $_REQUEST ['user_id'] : null, intval( trim( $_REQUEST ['slot'] ) ) );

					if ( $result !== null ) {
						$book = AppointmentSw::get_book_by_slot( intval( trim( $_REQUEST ['slot'] ) ) );
						AppointmentSwNotifications::send( $book, APPOINTMENTSW_STATUS_ACCEPTED );
						AppointmentSwNotifications::sendToAdmin( $book, APPOINTMENTSW_STATUS_ACCEPTED );
						$appointmentSwAlert = '<div class="success col-sm-12 center"><h1>RESERVA GUARDADA CORRECTAMENTE</h1></div>';
					} else {
						$appointmentSwAlert = '<div class="warning col-sm-12 center"><h1>Ah ocurrido algún error, inténtelo de nuevo !!</h1></div>';
					}

					if ( get_option( 'appointmentsw_changes' ) !== false ) {
						update_option( 'appointmentsw_changes', 1 );
					} else {
						add_option( 'appointmentsw_changes', 1, null, 'no' );
					}
					break;
			}

			return 1;
		}

		// Process CANCEL request
		if ( isset ( $_REQUEST ['book_id'] ) && isset ( $_REQUEST ['user_id'] ) && isset ( $_REQUEST ['action'] ) && $_REQUEST ['action'] == 'appointmentsw-cancel' && wp_verify_nonce ( $_REQUEST ['appointmentsw-nonce'], 'appointmentsw' )) {

			$book = AppointmentSw::get_book( intval( trim( $_REQUEST ['book_id'] ) ) );

			$result = AppointmentSw::cancelAppointment( intval( trim( $_REQUEST ['book_id'] ) ), intval( trim( $_REQUEST ['user_id'] ) ) );

			if ( $result !== null ) {
				AppointmentSwNotifications::send( $book, APPOINTMENTSW_STATUS_CANCELED );
				AppointmentSwNotifications::sendToAdmin( $book, APPOINTMENTSW_STATUS_CANCELED );
				$appointmentSwAlert = '<div class="success col-sm-12 center"><h1>' . __( "Appointment canceled", 'appointmentsw' ) . '</h1></div>';
			} else {
				$appointmentSwAlert = '<div class="warning col-sm-12 center"><h1>' . __( "An error has occured", 'appointmentsw' ) . '</h1></div>';
			}

			if ( get_option( 'appointmentsw_changes' ) !== false ) {
				update_option( 'appointmentsw_changes', 1 );
			} else {
				add_option( 'appointmentsw_changes', 1, null, 'no' );
			}

			return 1;
		}

		// ADMIN
		// Process ADD request
		if (isset ( $_REQUEST ['slot'] ) && isset ( $_REQUEST ['user_id'] ) && isset ( $_REQUEST ['action'] ) && $_REQUEST ['action'] == 'appointmentsw-add-admin' && wp_verify_nonce ( $_REQUEST ['appointmentsw-nonce'], 'appointmentsw' )) {

			$result = AppointmentSw::addAppointmentAdmin( intval( trim( $_REQUEST ['user_id'] ) ), intval( trim( $_REQUEST ['slot'] ) ) );


			if ( $result !== null ) {
				$book = AppointmentSw::get_book_by_slot( intval( trim( $_REQUEST ['slot'] ) ) );
				AppointmentSwNotifications::send( $book, APPOINTMENTSW_STATUS_ACCEPTED );
				AppointmentSwNotifications::sendToAdmin( $book, APPOINTMENTSW_STATUS_ACCEPTED );
				$appointmentSwAlert = '<div class="success col-sm-12 center"><h1>' . __( "Appointment added", 'appointmentsw' ) . '</h1></div>';
			} else {
				$appointmentSwAlert = '<div class="warning col-sm-12 center"><h1>' . __( "An error has occured", 'appointmentsw' ) . '</h1></div>';
			}

			if ( get_option( 'appointmentsw_changes' ) !== false ) {
				update_option( 'appointmentsw_changes', 0 );
			} else {
				add_option( 'appointmentsw_changes', 0, null, 'no' );
			}

			return 1;
		}

		// Process CANCEL request
		if ( isset ( $_REQUEST ['book_id'] ) && isset ( $_REQUEST ['user_id'] ) && isset ( $_REQUEST ['action'] ) && $_REQUEST ['action'] == 'appointmentsw-cancel-admin' && wp_verify_nonce ( $_REQUEST ['appointmentsw-nonce'], 'appointmentsw' )) {

			$book = AppointmentSw::get_book( intval( trim( $_REQUEST ['book_id'] ) ) );

			$result = AppointmentSw::cancelAppointmentAdmin( intval( trim( $_REQUEST ['book_id'] ) ), intval( trim( $_REQUEST ['user_id'] ) ) );

			if ( $result !== null ) {
				AppointmentSwNotifications::send( $book, APPOINTMENTSW_STATUS_CANCELED );
				AppointmentSwNotifications::sendToAdmin( $book, APPOINTMENTSW_STATUS_CANCELED );
				$appointmentSwAlert = '<div class="success col-sm-12 center"><h1>' . __( "Appointment canceled", 'appointmentsw' ) . '</h1></div>';
			} else {
				$appointmentSwAlert = '<div class="warning col-sm-12 center"><h1>' . __( "An error has occured", 'appointmentsw' ) . '</h1></div>';
			}

			if ( get_option( 'appointmentsw_changes' ) !== false ) {
				update_option( 'appointmentsw_changes', 0 );
			} else {
				add_option( 'appointmentsw_changes', 0, null, 'no' );
			}

			return 1;
		}

		// Process SAVE SETTINGS request
		if ( isset ( $_REQUEST ['date'] ) && isset ( $_REQUEST ['slot'] ) && isset ( $_REQUEST ['action'] ) && $_REQUEST ['action'] == 'appointmentsw-save-settings' && wp_verify_nonce ( $_REQUEST ['appointmentsw-nonce'], 'appointmentsw' ) ) {

			$slots = array();
			if ( sizeof( $_REQUEST ['slot'] ) > 0 ) {
				foreach ($_REQUEST ['slot'] as $key=>$value ) {
					$slots[$value] = intval( trim( $value ) );
				}
			}
			$holidays = AppointmentSw::updateHolidays( stripslashes( wp_filter_nohtml_kses( $_REQUEST ['date'] ) ), $slots );

			return 1;
		}

		// Create new user
		if ( isset ( $_REQUEST ['action'] ) && $_REQUEST ['action'] == 'appointmentsw-new-user-admin' && wp_verify_nonce ( $_REQUEST ['appointmentsw-nonce'], 'appointmentsw' )) {

			$username = isset( $_REQUEST['name'] )?sanitize_title( str_replace('_','-', sanitize_user( stripslashes( wp_filter_nohtml_kses( $_REQUEST['name'] ) ) ) ) ):"";
			if ( $username !== "" ) {
				$password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$email = ( isset( $_REQUEST['email'] ) && $_REQUEST['email']!=="" )?sanitize_email( $_REQUEST['email'] ):$username . "@example.com";

				$user_id = username_exists( $username );
				if ( !$user_id and email_exists($email) == false ) {
					$user_id = wp_create_user( $username, $password, $email );

					if ( ! is_wp_error( $user_id ) ) {
						$appointmentSwAlert = '<div class="success col-sm-12 center"><h1>' . $username . __( "created correctly", 'appointmentsw' ) . '</h1></div>';
					} else {
						$appointmentSwAlert = '<div class="warning col-sm-12 center"><h1>' . __( "An error has occured", 'appointmentsw' ) . '</h1></div>';
					}
				}
			}

			return 1;
		}
	}

	public static function wp_enqueue_scripts ( $page ) {

		// javascript
		wp_register_script('appointmentsw', APPOINTMENTSW_PLUGIN_URL . '/js/scripts.js', array('jquery'),'1.0');
		wp_enqueue_script('appointmentsw');

		// JS
		wp_register_script('asw_bootstrap', APPOINTMENTSW_PLUGIN_URL . '/bootstrap/bootstrap.min.js', array('jquery'));
		wp_enqueue_script('asw_bootstrap');

		// CSS
		wp_register_style('asw_bootstrap', APPOINTMENTSW_PLUGIN_URL . '/bootstrap/bootstrap.min.css');
		wp_enqueue_style('asw_bootstrap');

		wp_localize_script( 'appointmentsw', 'my_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	}


	public static function register_appointmentsw_settings() {
		register_setting( 'appointmentsw', 'appointmentsw' );

	}

	/**
	 * Load scripts.
	 */
	public static function appointmentsw_enqueue_scripts() {
		wp_register_style( 'appointmentsw-style', APPOINTMENTSW_PLUGIN_URL . 'css/style.css' );
		wp_register_style( 'appointmentsw-style-calendar', APPOINTMENTSW_PLUGIN_URL . 'css/style-calendar.css' );
		wp_enqueue_style ('appointmentsw-style');
		wp_enqueue_style ('appointmentsw-style-calendar');
	}

	public static function admin_notices() { 
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}

	/**
	 * Adds the admin sections.
	 */
	public static function admin_menu() {
		$admin_page = add_menu_page(
				__( 'Appointment SW' ),
				__( 'Appointment SW' ),
				'manage_options',
				'appointmentsw',
				array( __CLASS__, 'appointmentsw_settings' )
		);

		// Pages section
		$page = add_submenu_page(
				'appointmentsw',
				__( 'Pages', 'appointmentsw' ),
				__( 'Pages', 'appointmentsw' ),
				'manage_options',
				'appointmentsw-pages',
				array( __CLASS__, 'appointmentsw_submenu_pages' )
		);

		// Admin Appointments section
		$page = add_submenu_page(
				'appointmentsw',
				__( 'Appointments', 'appointmentsw' ),
				__( 'Appointments', 'appointmentsw' ),
				'manage_options',
				'appointmentsw-admin-appointments',
				array( __CLASS__, 'appointmentsw_submenu_admin_appointments' )
		);
		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'wp_enqueue_scripts' ) );
		add_action( 'admin_print_scripts-' . $page, array( __CLASS__, 'wp_enqueue_scripts' ) );

	}

	public static function appointmentsw_settings () {
	?>
		<div class="wrap">
		<h2><?php echo __( 'Appointment SW', 'appointmentsw' ); ?></h2>
		<?php 
		$alert = "";

		if (isset ( $_POST ['submit'] )) {
			$alert = __ ( "Saved", 'appointmentsw' );

			if ( isset( $_POST[ "guest" ] ) ) {
				update_option( "asw-guest", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "guest" ] ) ) ) );
			} else {
				update_option( "asw-guest", APPOINTMENTSW_GUEST_DEFAULT );
			}

			if ( isset( $_POST[ "duration" ] ) ) {
				update_option( "asw-duration", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "duration" ] ) ) ) );
			} else {
				update_option( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT );
			}

			if ( isset( $_POST[ "numdays" ] ) ) {
				update_option( "asw-numdays",stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "numdays" ] ) ) ) );
			} else {
				update_option( "asw-numdays", APPOINTMENTSW_NUM_DAYS_DEFAULT );
			}

			if ( isset( $_POST[ "maxoffset" ] ) ) {
				update_option( "asw-maxoffset", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "maxoffset" ] ) ) ) );
			} else {
				update_option( "asw-maxoffset", APPOINTMENTSW_MAX_DAYS_OFFSET_DEFAULT );
			}

			if ( isset( $_POST[ "start_time_0" ] ) ) {
				update_option( "asw-start_time_0", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "start_time_0" ] ) ) ) );
			} else {
				update_option( "asw-start_time_0", "10:00" );
			}

			if ( isset( $_POST[ "end_time_0" ] ) ) {
				update_option( "asw-end_time_0", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "end_time_0" ] ) ) ) );
			} else {
				update_option( "asw-end_time_0", "14:00" );
			}

			if ( isset( $_POST[ "start_time_1" ] ) ) {
				update_option( "asw-start_time_1", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "start_time_1" ] ) ) ) );
			} else {
				update_option( "asw-start_time_1", "16:00" );
			}

			if ( isset( $_POST[ "end_time_1" ] ) ) {
				update_option( "asw-end_time_1", stripslashes( wp_filter_nohtml_kses( trim( $_POST[ "end_time_1" ] ) ) ) );
			} else {
				update_option( "asw-end_time_1", "20:00" );
			}

			if ($alert != "")
				echo '<div style="background-color: #ffffe0;border: 1px solid #993;padding: 1em;margin-right: 1em;">' . $alert . '</div>';
		}
		?>
		<div class="wrap" style="border: 1px solid #ccc; padding: 10px;">
		<form method="post" action="">
			<table class="form-table">

				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Users can make an appointment as guests:', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$guest = get_option ( "asw-guest", APPOINTMENTSW_GUEST_DEFAULT );
						?>
						<input name="guest" type="checkbox" <?php echo ( $guest ? ' checked="checked" ' : '' ); ?> />
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Appointment duration (min):', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT );
						?>
						<input type="textbox" name="duration" value="<?php echo $duration; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Num. days to display:', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-numdays", APPOINTMENTSW_NUM_DAYS_DEFAULT );
						?>
						<input type="textbox" name="numdays" value="<?php echo $duration; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Appointment max. offset (days):', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-maxoffset", APPOINTMENTSW_MAX_DAYS_OFFSET_DEFAULT );
						?>
						<input type="textbox" name="maxoffset" value="<?php echo $duration; ?>" />
					</td>
				</tr>
				<!-- First time slot -->
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'First time slot - start time:', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-start_time_0", "10:00" );
						?>
						<input type="textbox" name="start_time_0" value="<?php echo $duration; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'First time slot - end time:', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-end_time_0", "14:00" );
						?>
						<input type="textbox" name="end_time_0" value="<?php echo $duration; ?>" />
					</td>
				</tr>
				<!-- Second time slot -->
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Second time slot - start time:', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-start_time_1", "16:00" );
						?>
						<input type="textbox" name="start_time_1" value="<?php echo $duration; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Second time slot - end time:', 'appointmentsw' ); ?></strong></th>
					<td>
						<?php
						$duration = get_option ( "asw-end_time_1", "20:00" );
						?>
						<input type="textbox" name="end_time_1" value="<?php echo $duration; ?>" />
					</td>
				</tr>

			</table>

			<?php submit_button( __( "Save", 'appointmentsw' ) ); ?>
			<?php settings_fields( 'appointmentsw' ); ?>
		</form>
		</div>
		</div>
		<?php
	}

	/**
	 * Dashboard Pages section.
	 */
	public static function appointmentsw_submenu_pages () {
		?>
		<div class="wrap">
		<h2><?php echo __( 'AppointmentSW - Pages', 'appointmentsw' ); ?></h2>

		<div class="wrap" style="border: 1px solid #ccc; padding: 10px;">

			<h3>Help</h3>
			<h4>Shortcodes</h4>
			<p>You can use these shortcodes in your pages.</p>
			<p><i><b>[appointmentsw_form]</b></i> A form where the user can make an appointment.</p>
			<p><i><b>[appointmentsw_myaccount]</b></i> Your appointments, where you can cancel them.</p>
			<p><i><b>[appointmentsw_admin]</b></i> Display a calendar where the admin can manager the appointments from the frontend.</p>
			<p><i><b>[appointmentsw_settings]</b></i> Now you can set your holidays from the frontend.</p>

		</div>
		</div>
		<?php
	}

	/**
	 * Dashboard Admin Appointments section.
	 */
	public static function appointmentsw_submenu_admin_appointments () {
		?>
		<div class="wrap">
			<h2><?php echo __( 'AppointmentSW - Admin Appointments', 'appointmentsw' ); ?></h2>

			<div class="wrap" style="border: 1px solid #ccc; padding: 10px;">
				<?php 
				echo AppointmentSw_Shortcodes::appointmentsw_admin( array() );
				?>
				<div style="clear:both;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Plugin activation work.
	 * 
	 */
	public static function activate() {
		// create the database tables
		self::setup();
	}

	/**
	 * Plugin deactivation.
	 *
	 */
	public static function deactivate() {

	}

	/**
	 * Plugin uninstall. Delete database table.
	 *
	 */
	public static function uninstall() {

	}

	public static function setup() {
		global $wpdb;

		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$queries = array();

		// Book
		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$appointmentsw_table'" ) != $appointmentsw_table ) {
			$queries[] = "CREATE TABLE $appointmentsw_table (
			book_id BIGINT(20) UNSIGNED NOT NULL auto_increment,
			user_id BIGINT(20) UNSIGNED,
			duration INT(10) UNSIGNED,
			datetime         DATETIME NOT NULL UNIQUE,
			status        VARCHAR(100) NOT NULL,
			notified      INT(2) DEFAULT 0,
			PRIMARY KEY   (book_id)
			) $charset_collate;";
		}

		// Bookmeta
		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_bookmeta';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$appointmentsw_table'" ) != $appointmentsw_table ) {
			$queries[] = "CREATE TABLE $appointmentsw_table (
			book_id BIGINT(20) UNSIGNED NOT NULL auto_increment,
			meta_key VARCHAR(128) DEFAULT NULL,
			meta_value LONGTEXT DEFAULT NULL,
			PRIMARY KEY   (book_id, meta_key),
			INDEX  aps_bookmeta (book_id, meta_key)
			) $charset_collate;";
		}

		// Holidays table
		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_holidays';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$appointmentsw_table'" ) != $appointmentsw_table ) {
			$queries[] = "CREATE TABLE $appointmentsw_table (
			holiday_id VARCHAR(100) NOT NULL,
			date DATE NOT NULL,
			timezone        INT NOT NULL,
			PRIMARY KEY   (holiday_id)
			) $charset_collate;";
		}

		if ( !empty( $queries ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $queries );
		}
	}


	// Admin styles
	public static function appointmentsw_login_enqueue_scripts() {
		wp_register_style( 'appointmentsw-style-admin', APPOINTMENTSW_PLUGIN_URL . 'css/style-admin.css', false, '1.0.0' );
		wp_enqueue_style( 'appointmentsw-style-admin' );
	}

}
AppointmentSw_Plugin::init();
