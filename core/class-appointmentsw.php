<?php
/**
 * class-appointmentsw.php
 *
 * Copyright (c) Antonio Blanco http://www.blancoleon.com
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
 */

/**
 * AppointmentSw class
 */
class AppointmentSw {

	public static function init() {

	}

	public static function get_book_by_slot ( $slot ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';

		$datetime = date('Y-m-d H:i:s', $slot );
		$get_items_sql  = $wpdb->prepare( "SELECT * FROM {$appointmentsw_table} WHERE datetime = %s ", $datetime );
		$line_item     = $wpdb->get_row( $get_items_sql, ARRAY_A );

		return $line_item;
	}

	public static function get_book ( $book_id ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';

		$get_items_sql  = $wpdb->prepare( "SELECT * FROM {$appointmentsw_table} WHERE book_id = %d ", $book_id );
		$line_item     = $wpdb->get_row( $get_items_sql, ARRAY_A );

		return $line_item;
	}

	public static function get_books_by_day ( $day ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';

		$items          = array();
		$get_items_sql  = $wpdb->prepare( "SELECT * FROM {$appointmentsw_table} WHERE date(datetime) = %s ", $day );
		$line_items     = $wpdb->get_results( $get_items_sql, ARRAY_A );

		$assoc = array();
		if ( isset( $line_items ) && ( sizeof( $line_items ) > 0 ) ) {
			foreach ( $line_items as $item ) {
				$assoc[$item['datetime']] = $item;
			}
		}

		return $assoc;
	}

	public static function get_books_by_user_id ( $user_id ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';

		$items          = array();
		$get_items_sql  = $wpdb->prepare( "SELECT * FROM {$appointmentsw_table} WHERE user_id = %d ORDER BY book_id DESC", $user_id );
		$line_items     = $wpdb->get_results( $get_items_sql, ARRAY_A );

		$assoc = array();
		if ( isset( $line_items ) && ( sizeof( $line_items ) > 0 ) ) {
			foreach ( $line_items as $item ) {
				$assoc[$item['datetime']] = $item;
			}
		}

		return $assoc;
	}

	public static function get_book_meta ( $book_id, $meta_key ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_bookmeta';

		$get_items_sql  = $wpdb->prepare( "SELECT * FROM {$appointmentsw_table} WHERE book_id = %d AND meta_key = %d", $book_id, $meta_key );
		$line_item     = $wpdb->get_row( $get_items_sql, ARRAY_A );

		$meta_value = null;
		if ( $line_item ) {
			$meta_value = $line_item['meta_value'];
		}

		return $meta_value;
	}

	public static function update_books_by_user_id ( $user_id ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';

		$books = self::get_books_by_user_id( $user_id );
		if ( isset( $books ) && ( sizeof ( $books ) > 0 ) ) {
			foreach ( $books as $book ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$appointmentsw_table} SET status = %s WHERE datetime < NOW() ", APPOINTMENTSW_STATUS_PAST ) );
			}
		}
	}
	public static function addAppointment ( $user_id = null, $slot = null, $duration = 15, $status = "accepted" ) {
		global $wpdb;

		$result = 0;
		if ( $slot !== null ) {

			if ( ( $user_id === null ) || ( $user_id == "null" ) ) {
				$user_id = get_current_user_id();
			}

			$guest = get_option ( "asw-guest", APPOINTMENTSW_GUEST_DEFAULT );

			if ( ( $guest ) || ( ( !$guest ) && ( $user_id ) ) ) {

				if ( !$user_id ) {
					$user_id = 0;
				}
				$email = $_REQUEST['user_email'];
				$user_login = $_REQUEST['user_login'];

				$datetime = date('Y-m-d H:i:s', $slot );
				$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';
				if ( $wpdb->query( $wpdb->prepare( "INSERT INTO $appointmentsw_table SET user_id = %d, duration = %d, datetime = %s, status = %s", intval( $user_id ), intval( $duration ), $datetime, $status ) ) ) {
					$result = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
				}
				if ( $result ) {
					// bookmeta
					$appointmentsw_table = $wpdb->prefix . 'appointmentsw_bookmeta';
					$wpdb->query( 
							$wpdb->prepare( 
									"INSERT INTO $appointmentsw_table SET book_id = %d ,meta_key = %s, meta_value = %s", 
									intval( $result ), 'user_email', $email
									)
							);
					$wpdb->query(
							$wpdb->prepare(
									"INSERT INTO $appointmentsw_table SET book_id = %d ,meta_key = %s, meta_value = %s",
									intval( $result ), 'user_login', $user_login
									)
							);
				}
			}
		}
		return $result;
	}

	/**
	 * Elimina una cita.
	 * @param unknown $book_id
	 * @param string $user_id
	 * @return Ambigous <number, false>
	 */
	public static function cancelAppointment ( $book_id, $user_id = null ) {
		global $wpdb;

		$result = 0;
		if ( $user_id !== null ) {
			$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';
		//	$result = $wpdb->query( $wpdb->prepare( "UPDATE {$appointmentsw_table} SET status = %s WHERE book_id = %d AND user_id = %s ", APPOINTMENTSW_STATUS_CANCELED, $book_id, $user_id ) );
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$appointmentsw_table} WHERE book_id = %d AND user_id = %s ", $book_id, $user_id ) );
		}
		return $result;
	}

	// Admin
	public static function addAppointmentAdmin ( $user_id = null, $slot = null, $duration = 15, $status = "accepted" ) {
		global $wpdb;

		$result = 0;
		if ( $slot !== null ) {
			if ( $user_id !== null ) {
				$datetime = date('Y-m-d H:i:s', $slot );
				$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';
				if ( $wpdb->query( $wpdb->prepare( "INSERT INTO $appointmentsw_table SET user_id = %d, duration = %d, datetime = %s, status = %s", intval( $user_id ), intval( $duration ), $datetime, $status ) ) ) {
					$result = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
				}
			}
		}
		return $result;
	}

	// Admin
	/**
	 * Elimina una cita. Solicitud enviada por el administrador
	 * @param unknown $book_id
	 * @param string $user_id
	 * @return Ambigous <number, false>
	 */
	public static function cancelAppointmentAdmin ( $book_id, $user_id = null ) {
		global $wpdb;

		$result = 0;
		if ( $user_id !== null ) {
			$appointmentsw_table = $wpdb->prefix . 'appointmentsw_book';
		//	$result = $wpdb->query( $wpdb->prepare( "UPDATE {$appointmentsw_table} SET status = %s WHERE book_id = %d AND user_id = %d ", APPOINTMENTSW_STATUS_CANCELED, $book_id, $user_id ) );
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$appointmentsw_table} WHERE book_id = %d AND user_id = %d ", $book_id, $user_id ) );
		}
		return $result;
	}

	// HOLIDAYS
	public static function getHolidays ( $month = null, $year = null ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_holidays';

		$where = "";
		if ( ( $month !== null ) && ( $year !== null ) ) {
			// @todo
		}
		$items          = array();
		$get_items_sql  = "SELECT * FROM {$appointmentsw_table} {$where}";
		$line_items     = $wpdb->get_results( $get_items_sql, ARRAY_A );

		$assoc = array();
		if ( isset( $line_items ) && ( sizeof( $line_items ) > 0 ) ) {
			foreach ( $line_items as $item ) {
				$assoc[$item['holiday_id']] = $item;
			}
		}

		return $assoc;
	}

	public static function updateHolidays ( $dates, $slots ) {
		global $wpdb;

		if ( ( isset( $dates ) && ( sizeof( $dates ) > 0 ) ) && ( isset( $slots ) && ( sizeof( $slots ) > 0 ) ) ) {
			$holidays = array();
			$dates_values = array();
			$timezone = array();
			$working = array();
			$place_holders = array();
			foreach ( $dates as $date ) {
				if ( isset( $slots[$date . '_0'] ) ) {
					$holidays[] = $date . '_0';
					$dates_values[] = $date;
					$timezone[] = 0;
					$place_holders[] = "('%s', '%s, '%d')";
				} else {
					$working[] = $date . '_0';
				}
				if ( isset( $slots[$date . '_1'] ) ) {
					$holidays[] = $date . '_1';
					$dates_values[] = $date;
					$timezone[] = 1;
					$place_holders[] = "('%s', '%s, '%d')";
				} else {
					$working[] = $date . '_1';
				}
			}

		}
		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_holidays';


		if ( isset( $working ) && ( sizeof ( $working ) > 0 ) ) {
			$working = implode( ',', $working );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$appointmentsw_table} WHERE holiday_id IN (%d)", $working ) );
		}

		if ( isset( $holidays ) && ( sizeof ( $holidays ) > 0 ) ) {
			//$holidays = implode( ',', $holidays );
			//$dates_values = implode( ',', $dates_values );
			//$timezone = implode( ',', $timezone );

			$values = array();
			$cnt = 0;
			foreach ( $holidays as $key => $value ) {
				$values[] = $wpdb->prepare( "(%s,%s,%d)", $holidays[$cnt], $dates_values[$cnt], $timezone[$cnt] );
				$cnt ++;
			}

			$values = implode( ',', $values );
			$wpdb->query( "INSERT IGNORE INTO {$appointmentsw_table} (holiday_id, date, timezone) VALUES " . $values );
		}


	}

	/**
	 * Check if the slot is on holiday
	 * 
	 * @param string $holiday_id similar to 2016-05-02_0 or 2016-05-02_1
	 * @return boolean
	 */
	public static function isSlotOnHoliday ( $holiday_id = null ) {
		global $wpdb;

		$appointmentsw_table = $wpdb->prefix . 'appointmentsw_holidays';

		$where = "";
		if ( $holiday_id !== null ) {
			$where = $wpdb->prepare( " WHERE holiday_id = '%s'", $holiday_id );
		}
		$items          = array();
		$get_items_sql  = "SELECT * FROM {$appointmentsw_table} {$where}";
		$holiday_slot     = $wpdb->get_row( $get_items_sql, ARRAY_A );

		$isOn = false;
		if ( isset( $holiday_slot ) && ( $holiday_slot !== null ) ) {
			$isOn = true;
		}
		return $isOn;
	}
}
AppointmentSw::init();
