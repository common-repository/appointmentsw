<?php
/**
 * class-appointmentsw-shortcodes.php
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
class AppointmentSw_Shortcodes {

	public static function init() {
		// Booking form
		add_shortcode( 'appointmentsw_form' , array( __CLASS__, 'appointmentsw_form') );

		// My books
		add_shortcode( 'appointmentsw_myaccount' , array( __CLASS__, 'appointmentsw_myaccount') );

		// Admin Calendar
		add_shortcode( 'appointmentsw_admin' , array( __CLASS__, 'appointmentsw_admin') );

		// Vacations

		// @deprecated shortcode. Uses [appointmentsw_vacations]
		add_shortcode( 'appointmentsw_settings' , array( __CLASS__, 'appointmentsw_vacations') );

		add_shortcode( 'appointmentsw_vacations' , array( __CLASS__, 'appointmentsw_vacations') );

	}

	public static function appointmentsw_form ( $atts ) {
		global $appointmentSwAlert;

		$output = '';

		$output .= $appointmentSwAlert;

		$guest = get_option ( "asw-guest", APPOINTMENTSW_GUEST_DEFAULT );
		$step = isset( $_REQUEST ['step'] ) ? $_REQUEST ['step'] : "0";

		switch ( $step ) {
			case "0":
			default:
				// Calendar
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
					$output .= self::display_calendar( $user_id );
				} else {
					if ( $guest ) {
						$output .= self::display_calendar( null );
					} else {
						$output .= '<p>' . 'You need to be registered for reservations.' . '</p>';
					}
				}
				break;
			case "1":
				// User data & confirmation
				$user_id = get_current_user_id();
				$output .= self::display_calendar_confirmation( $user_id );
				break;
		}

		return $output;
	}

	/**
	 * Display the reservation calendar.
	 * 
	 * @param int $user_id If user_id is null, then an extra form is displayed.
	 * 
	 * @return string
	 */
	public static function display_calendar ( $user_id = null ) {
		global $wp;

		$output = "";

		$current_url = home_url(add_query_arg(array(),$wp->request));

		$day = 0;
		if ( isset( $_REQUEST ['offset'] ) ) {
			$day = intval( trim( $_REQUEST ['offset'] ) );
		}
		if ( $day > get_option ( "asw-maxoffset", APPOINTMENTSW_MAX_DAYS_OFFSET_DEFAULT ) ) {
			$day = get_option ( "asw-maxoffset", APPOINTMENTSW_MAX_DAYS_OFFSET_DEFAULT );
		}

		$num_days = get_option ( "asw-numdays", APPOINTMENTSW_NUM_DAYS_DEFAULT );

		// Pagination
		$output .= '<div class="pagination" style="clear:both;margin: 30px 0px 0px 0px;height:30px;width:100%;">';
		if ( $day > 0 ) {
			$output .= '<span style="float:left;text-size:120%;"><a href="' . $current_url . '?offset=' . intval($day-$num_days)  . '" class="btn btn-info">&lt;&lt; Anterior</a></span>';
		}
		if ( $day < get_option ( "asw-maxoffset", APPOINTMENTSW_MAX_DAYS_OFFSET_DEFAULT ) ) {
			$output .= '<span style="float:right;text-size:120%;"><a href="' . $current_url . '?offset=' . intval($day+$num_days)  . '" class="btn btn-info">Siguiente &gt;&gt;</a></span>';
		}
		$output .= '</div>';

		$output .= '<div class="row">';

		for ( $cnt=0; $cnt < $num_days; $cnt++ ) {

			$date = strtotime("+" . intval($day+$cnt) . " day", current_time( 'timestamp' ));
			$today = date('Y-m-d', $date);
			$today_txt = __( date( 'l', $date ) ) . " " . date('d', $date);
			$books = AppointmentSw::get_books_by_day ( $today );

			$output .= '<div class="col-sm-6" style="margin-bottom:30px;">';
			$output .= '<table style="width:100%;" class="sb-table">
				<thead>
				<tr>
					<th colspan="4" style="text-align:center;background-color:#333;color:#fff;font-size:120%;">' . $today_txt  . '</th>
				</tr>
				</thead>
				<tbody>';

			$week_day = date( 'N', $date );
			/*
			switch ( $week_day ) {
				case 1:
					$start_time_1 = $today . "10:00";
					$end_time_1 = $today . "10:00";
					$start_time_2 = $today . "16:05";
					$end_time_2 = $today . "21:30";
					break;
				case 2:
				case 3:
				case 4:
				case 5:
					$start_time_1 = $today . "09:35";
					$end_time_1 = $today . "13:45";
					$start_time_2 = $today . "16:05";
					$end_time_2 = $today . "21:30";
					break;
				case 6:
					$start_time_1 = $today . "9:00";
					$end_time_1 = $today . "14:00";
					$start_time_2 = $today . "16:00";
					$end_time_2 = $today . "16:00";
					break;
				default:
					$start_time_1 = $today . "8:00";
					$end_time_1 = $today . "8:00";
					$start_time_2 = $today . "16:00";
					$end_time_2 = $today . "16:00";
			}
			*/

			$start_time_1 = $today . get_option ( "asw-start_time_0", "10:00" );
			$end_time_1 = $today . get_option ( "asw-end_time_0", "14:00" );
			$start_time_2 = $today . get_option ( "asw-start_time_1", "16:00" );
			$end_time_2 = $today . get_option ( "asw-end_time_1", "20:00" );

			if ( AppointmentSw::isSlotOnHoliday( $today . "_0" ) && AppointmentSw::isSlotOnHoliday( $today . "_1" ) ) {
				$output .= '<tr><td colspan="4" style="background-color:#e4223c;color:#fff;vertical-align: middle;">' . __( "Closed all day", 'appointmentsw' ) . '</td></tr>';
			} else {
				// Primer tramo
				if ( !AppointmentSw::isSlotOnHoliday( $today . "_0" ) ) {
					$dteStart = new DateTime($start_time_1);
					$dteEnd = new DateTime($end_time_1);
					$dteDiff  = $dteStart->diff($dteEnd);
					$horas = $dteDiff->format('%H');
					$minutos = $dteDiff->format('%I');

					$num_slots = ( $horas * 60 ) + $minutos;
					$num_slots = intval( $num_slots / get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) );

					for ( $cnt2=0; $cnt2 < $num_slots; $cnt2++ ) {
						if ( ( $cnt2 % 4 ) == 0 ) {
							$output .= '<tr>';
						}
						$slot_time_ = strtotime( "+" . $cnt2 * get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) . " minute", mktime( $dteStart->format("H"), $dteStart->format("i"), 0, $dteStart->format("m"), $dteStart->format("d"), $dteStart->format("Y") ) );
						$slot_time = date('Y-m-d H:i:s', $slot_time_);
						$now = current_time( 'timestamp' );

						$datetime = new DateTime( $slot_time );
						if ( $slot_time_ <  $now ) {
							$output .= '<td>' . $datetime->format("H:i") . '</td>';
						} else {
							if ( isset( $books[$slot_time] ) ) {
								$output .= '<td style="background-color:#e4223c;color:#fff;vertical-align: middle;">' . $datetime->format("H:i") . '</td>';
							} else {
								$output .= '<form action="" method="post">';
								$output .= '<input type="hidden" name="action" value="appointmentsw-add" />';
								$output .= '<input type="hidden" name="step" value="1" />';
								$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
								$output .= '<input type="hidden" name="slot" value="' . $slot_time_ . '" />';
								$output .= '<td style=""><input type="submit" value="' . $datetime->format("H:i") . '" style="background-color:#0c61b4;color:white;width:100%;"/></td>';
								$output .= '</form>';
							}
						}
						if ( ( ( $cnt2+1 ) % 4 ) == 0 ) {
							$output .= '</tr>';
						}
					}
				} else {
					$output .= '<tr><td colspan="4" style="background-color:#e4223c;color:#fff;vertical-align: middle;">' . __( "Morning closed", 'appointmentsw' ) . '</td></tr>';
				}

				// Almuerzo
				$output .= '<tr><td colspan="4" style="background-color: #ddd;">' . __( "Lunchtime", 'appointmentsw' ) . '</td></tr>';

				// Segundo tramo
				if ( !AppointmentSw::isSlotOnHoliday( $today . "_1" ) ) {

					$dteStart = new DateTime($start_time_2);
					$dteEnd = new DateTime($end_time_2);
					$dteDiff  = $dteStart->diff($dteEnd);
					$horas = $dteDiff->format("%H");

					$horas = $dteDiff->format('%H');
					$minutos = $dteDiff->format('%I');

					$num_slots = ( $horas * 60 ) + $minutos;

					$num_slots = intval( $num_slots / get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) );

					for ( $cnt2=0; $cnt2 < $num_slots; $cnt2++ ) {
						if ( ( $cnt2 % 4 ) == 0 ) {
							$output .= '<tr>';
						}
						$slot_time_ = strtotime( "+" . $cnt2 * get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) . " minute", mktime( $dteStart->format("H"), $dteStart->format("i"), 0, $dteStart->format("m"), $dteStart->format("d"), $dteStart->format("Y") ) );
						$slot_time = date('Y-m-d H:i:s', $slot_time_);
						$now = current_time( 'timestamp' );

						$datetime = new DateTime( $slot_time );
						if ( $slot_time_ <  $now ) {
							$output .= '<td>' . $datetime->format("H:i") . '</td>';
						} else {
							if ( isset( $books[$slot_time] ) ) {
								$output .= '<td style="background-color:#e4223c;color:#fff;vertical-align: middle;">' . $datetime->format("H:i") . '</td>';
							} else {
								$output .= '<form action="" method="post">';
								$output .= '<input type="hidden" name="action" value="appointmentsw-add" />';
								$output .= '<input type="hidden" name="step" value="1" />';
								$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
								$output .= '<input type="hidden" name="slot" value="' . $slot_time_ . '" />';
								$output .= '<td style=""><input type="submit" value="' . $datetime->format("H:i") . '" style="background-color:#0c61b4;color:white;width:100%;"/></td>';
								$output .= '</form>';
							}
						}
						if ( ( ( $cnt2+1 ) % 4 ) == 0 ) {
							$output .= '</tr>';
						}
					}
				} else {
					$output .= '<tr><td colspan="4" style="background-color:#e4223c;color:#fff;vertical-align: middle;">' . __( "closed afternoon", 'appointmentsw' ) . '</td></tr>';
				}
			}
			$output .= '</tbody>
					</table>
				</div>';

		}
		$output .= '</div>';
		return $output;
	}

	public static function display_calendar_confirmation ( $user_id = null ) {
		$output = "";

		$slot = isset ( $_REQUEST ['slot'] ) ? $_REQUEST ['slot'] : null;
		$slot_time = date( 'Y-m-d H:i:s', $slot );
		$datetime = new DateTime( $slot_time );
		$slot_txt = $datetime->format( 'H:i d-m-Y' );

		$user_id = null;
		$name = "";
		$email = "";
		$phone = "";

		$name_disabled = '';
		$email_disabled = '';

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
			$name = $user->user_login;
			$email = $user->user_email;

			$name_disabled = ' disabled ';
			$email_disabled = ' disabled ';
		}

		$output .= '<p>' . sprintf( __( "You'll make an appointment on %s", 'appointmentsw' ), $slot_txt ) . '</p>';

		$output .= '<form action="" method="post">';
		$output .= '<input type="text" name="user_login" value="' . $name . '" ' . $name_disabled . '/>';
		$output .= '<input type="text" name="user_email" value="' . $email . '" ' . $email_disabled . '/>';

		if ( $user_id ) {
			$output .= '<input type="hidden" name="user_id" value="' . $user_id . '" />';
		}

		$output .= '<input type="hidden" name="action" value="appointmentsw-add" />';
		$output .= '<input type="hidden" name="step" value="2" />';
		$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
		$output .= '<input type="hidden" name="slot" value="' . $slot . '" />';
		$output .= '<td style=""><input type="submit" value="' . __( "Confirm", 'appointmentsw' ) . '" style="background-color:#0c61b4;color:white;width:100%;"/></td>';
		$output .= '</form>';

		return $output;
	}

	public static function appointmentsw_myaccount ( $atts ) {
		global $appointmentSwAlert;

		$output = '';
		if ( is_user_logged_in() ) {

			$atts = shortcode_atts(
					array(
							'form_id'    => null
					),
					$atts
			);

			$user_id = get_current_user_id();
			AppointmentSw::update_books_by_user_id( $user_id );

			$books = AppointmentSw::get_books_by_user_id( $user_id );

			// limitamos a 5 entradas
			array_splice($books, 5);

			$output .= $appointmentSwAlert;
			$output .= '<div class="row">';

			if ( isset( $books ) && ( sizeof( $books ) > 0 ) ) {
				$output .= '<div class="col-sm-12" style="margin-bottom:30px;">';
				$output .= '<table style="width:100%"  class="sb-table">
				<thead>
				<tr>
					<th colspan="2" style="text-align:center;background-color:#333;color:#fff;font-size:120%;">' . __( "My appointments", 'appointmentsw' ) . '</th>
				</tr>
				</thead>
				<tbody>';

				foreach ( $books as $book ) {
					$output .= '<tr>';
					$output .= '<td>' . $book['datetime'] . '</td>';
					if ( $book['status'] == APPOINTMENTSW_STATUS_ACCEPTED ) {
						$output .= '<form action="" method="post">';
						$output .= '<input type="hidden" name="action" value="appointmentsw-cancel" />';
						$output .= '<input type="hidden" name="user_id" value="' . $user_id . '" />';
						$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
						$output .= '<input type="hidden" name="book_id" value="' . $book['book_id'] . '" />';
						$output .= '<td style=""><input type="submit" value="' . __( 'Cancel', 'appointmentsw' ) . '"  style="background-color:#e4223c;color:white;width:100%;"/></td>';
						$output .= '</form>';
					} else if ( $book['status'] == APPOINTMENTSW_STATUS_PAST ) {
						$output .= '<td>' . __( 'Past', 'appointmentsw' ) . '</td>';
					} else {
						$output .= '<td>' . ucwords( $book['status'] ) . '</td>';
					}
					$output .= '</tr>';
				}

				$output .= '</tbody>
					</table>
				</div>';
			}
			$output .= '</div>';
		}

		return $output;
	}


	// ADMIN

	public static function appointmentsw_admin ( $atts ) {
		global $appointmentSwAlert;

		$output = '';

		if ( is_user_logged_in() ) {

			$atts = shortcode_atts(
					array(
							'form_id'    => null
					),
					$atts
			);

			if ( get_option( 'appointmentsw_changes' ) !== false ) {
				update_option( 'appointmentsw_changes', 0 );
			} else {
				add_option( 'appointmentsw_changes', 0, null, 'no' );
			}

			$user_id = get_current_user_id();

			$output .= $appointmentSwAlert;

			$output .= '<ul class="nav nav-pills" id="mytabs" style="margin-bottom:30px;border-bottom:2px solid #337ab7;">
			<li class="active"><a data-toggle="tab" href="#calendario">' . __( "Appointments", 'appointmentsw' ) . '</a></li>
			<li><a data-toggle="tab" href="#usuarios">' . __( "Clients", 'appointmentsw' ) . '</a></li>
			<li><a data-toggle="tab" href="#nuevousuario">' . __( "New client", 'appointmentsw' ) . '</a></li>
			</ul>';

			$output .= '<div class="tab-content">';

			$output .= '<div id="calendario" class="tab-pane fade in active">';
			$output .= self::display_calendar_admin( $user_id );
			$output .= '</div>';

			$output .= '<div id="usuarios" class="tab-pane fade">';
			$output .= self::display_add_form_admin();
			$output .= '</div>';


			$output .= '<div id="nuevousuario" class="tab-pane fade">';
			$output .= self::display_new_user_form_admin( $user_id );
			$output .= '</div>';

			$output .= '</div>';
			return $output;
		}
	}

	public static function display_calendar_admin ( $user_id ) {
		global $wp;

		$current_url = home_url(add_query_arg(array(),$wp->request));

		$output = "";

		$day = 0;
		if ( isset( $_REQUEST ['offset'] ) ) {
			$day = intval( trim( $_REQUEST ['offset'] ) );
		}

		$num_days = get_option ( "asw-numdays", APPOINTMENTSW_NUM_DAYS_DEFAULT );

		$output .= '<div class="col-sm-12" style="margin-bottom:30px;">';

		$output .= '<div class="appointmentsw-admin-user-selected">';

		$output .= __( 'User selected: ', 'appointmentsw' ) . '<span id="user-selected">' . __( "Select a client", 'appointmentsw' ) . '</span>';

		$output .= '</div>';

		// Pagination
		$output .= '<div class="row pagination" style="clear:both;margin: 30px 0px 0px 0px;height:30px;width:100%;">';
		$output .= '<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4 left">';
		$output .= '<span style="float:left;text-size:120%;"><a href="' . $current_url . '?offset=' . intval($day-$num_days)  . '" class="btn btn-info">' . __( '&lt;&lt; Previous', 'appointmentsw' ) . '</a></span>';
		$output .= '</div>';
		$output .= '<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4 center">';
		$output .= '<input type="button" class="btn btn-success" value="' . __( 'Reload', 'appointmentsw' ) . '" onClick="window.location.reload()" id="button_changes">';
		$output .= '</div>';
		$output .= '<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4 right">';
		$output .= '<span style="float:right;text-size:120%;"><a href="' . $current_url . '?offset=' . intval($day+$num_days)  . '" class="btn btn-info">' . __( 'Siguiente &gt;&gt;', 'appointmentsw' ) . '</a></span>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="row">';

		for ( $cnt=0; $cnt < $num_days; $cnt++ ) {

			$date = strtotime("+" . intval($day+$cnt) . " day", current_time( 'timestamp' ));
			$today = date('Y-m-d', $date);
			$today_txt = __( date( 'l', $date ) ) . " " . date('d', $date);

			$books = AppointmentSw::get_books_by_day ( $today );

			$output .= '<div class="col-sm-6" style="margin-bottom:30px;">';
			$output .= '<table style="width:100%;" class="sb-table">
				<thead>
				<tr>
					<th colspan="4" style="text-align:center;background-color:#333;color:#fff;font-size:120%;">' . $today_txt  . '</th>
				</tr>
				</thead>
				<tbody>';

			$week_day = date( 'N', $date );
			/*
			switch ( $week_day ) {
				case 1:
					$start_time_1 = $today . "10:00";
					$end_time_1 = $today . "10:00";
					$start_time_2 = $today . "16:05";
					$end_time_2 = $today . "21:30";
					break;
				case 2:
				case 3:
				case 4:
				case 5:
					$start_time_1 = $today . "09:35";
					$end_time_1 = $today . "13:45";
					$start_time_2 = $today . "16:05";
					$end_time_2 = $today . "21:30";
					break;
				case 6:
					$start_time_1 = $today . "9:00";
					$end_time_1 = $today . "14:00";
					$start_time_2 = $today . "16:00";
					$end_time_2 = $today . "16:00";
					break;
				default:
					$start_time_1 = $today . "8:00";
					$end_time_1 = $today . "8:00";
					$start_time_2 = $today . "16:00";
					$end_time_2 = $today . "16:00";
			}
			*/
			$start_time_1 = $today . get_option ( "asw-start_time_0", "10:00" );
			$end_time_1 = $today . get_option ( "asw-end_time_0", "14:00" );
			$start_time_2 = $today . get_option ( "asw-start_time_1", "16:00" );
			$end_time_2 = $today . get_option ( "asw-end_time_1", "20:00" );

			// Primer tramo
			$dteStart = new DateTime($start_time_1);
			$dteEnd = new DateTime($end_time_1);
			$dteDiff  = $dteStart->diff($dteEnd);
			$horas = $dteDiff->format('%H');
			$minutos = $dteDiff->format('%I');

			$num_slots = ( $horas * 60 ) + $minutos;
			$num_slots = intval( $num_slots / get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) );

			for ( $cnt2=0; $cnt2 < $num_slots; $cnt2++ ) {
				if ( ( $cnt2 % 4 ) == 0 ) {
					$output .= '<tr>';
				}
				$slot_time_ = strtotime( "+" . $cnt2 * get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) . " minute", mktime( $dteStart->format("H"), $dteStart->format("i"), 0, $dteStart->format("m"), $dteStart->format("d"), $dteStart->format("Y") ) );
				$slot_time = date('Y-m-d H:i:s', $slot_time_);
				$now = current_time( 'timestamp' );

				$datetime = new DateTime( $slot_time );
				if ( $slot_time_ <  $now ) {
					$output .= '<td>' . $datetime->format("H:i") . '</td>';
				} else {
					if ( isset( $books[$slot_time] ) ) {
						$user = get_user_by ( 'id', $books[$slot_time]['user_id'] );
						$output .= '<form action="" method="post">';
						$output .= '<input type="hidden" name="action" value="appointmentsw-cancel-admin" />';
						$output .= '<input type="hidden" name="user_id" value="' . $books[$slot_time]['user_id'] . '" />';
						$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
						$output .= '<input type="hidden" name="book_id" value="' . $books[$slot_time]['book_id'] . '" />';
						$output .= '<td style="color:#e4223c;">' . $datetime->format("H:i");
						$output .= '<p>' . $user->user_login . '</p>';
						$att = array();
						$att['extra_attr'] = " data-user_id='" . $books[$slot_time]['user_id'] . "' data-user_name='" . $user->user_login . "'";
						$att['class'] = "selectUser";
						if ( $userAvatar = get_user_meta($books[$slot_time]['user_id'], 'thechamp_large_avatar', true) ) {
							$att['url'] = $userAvatar;
						}
						$output .= get_avatar( $books[$slot_time]['user_id'], 96, "", "", $att );
						$output .= '<br> <input type="submit" value="Cancelar"  style="background-color:#e4223c;color:white;width:100%;"/>';
						$output .= '</td>';
						$output .= '</form>';
					} else {
						$output .= '<form action="" method="post">';
						$output .= '<input type="hidden" name="action" value="appointmentsw-add-admin" />';
						$output .= '<input type="hidden" name="user_id" value="" />';
						$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
						$output .= '<input type="hidden" name="slot" value="' . $slot_time_ . '" />';
						$output .= '<td style="color:#0c61b4;">' . $datetime->format("H:i") . '<br> <input type="submit" value="' . __( "Submit", 'appointmentsw' ) . '" class="appointmentsw-submit"  style="background-color:#0c61b4;color:white;width:100%;"/></td>';
						$output .= '</form>';
					}
				}
				if ( ( ( $cnt2+1 ) % 4 ) == 0 ) {
					$output .= '</tr>';
				}
			}

			// Almuerzo
			$output .= '<tr><td colspan="4" style="background-color: #ddd;">' . __( "Lunchtime", 'appointmentsw' ) . '</td></tr>';

			// Segundo tramo
			$dteStart = new DateTime($start_time_2);
			$dteEnd = new DateTime($end_time_2);
			$dteDiff  = $dteStart->diff($dteEnd);
			$horas = $dteDiff->format('%H');
			$minutos = $dteDiff->format('%I');

			$num_slots = ( $horas * 60 ) + $minutos;

			$num_slots = intval( $num_slots / get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) );

			for ( $cnt2=0; $cnt2 < $num_slots; $cnt2++ ) {
				if ( ( $cnt2 % 4 ) == 0 ) {
					$output .= '<tr>';
				}
				$slot_time_ = strtotime( "+" . $cnt2 * get_option ( "asw-duration", APPOINTMENTSW_DURATION_DEFAULT ) . " minute", mktime( $dteStart->format("H"), $dteStart->format("i"), 0, $dteStart->format("m"), $dteStart->format("d"), $dteStart->format("Y") ) );
				$slot_time = date('Y-m-d H:i:s', $slot_time_);
				$now = current_time( 'timestamp' );

				$datetime = new DateTime( $slot_time );
				if ( $slot_time_ <  $now ) {
					$output .= '<td>' . $datetime->format("H:i") . '</td>';
				} else {
					if ( isset( $books[$slot_time] ) && ( $books[$slot_time]['status'] == APPOINTMENTSW_STATUS_ACCEPTED ) ) {
						$user = get_user_by ( 'id', $books[$slot_time]['user_id'] );
						$output .= '<form action="" method="post">';
						$output .= '<input type="hidden" name="action" value="appointmentsw-cancel-admin" />';
						$output .= '<input type="hidden" name="user_id" value="' . $books[$slot_time]['user_id'] . '" />';
						$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
						$output .= '<input type="hidden" name="book_id" value="' . $books[$slot_time]['book_id'] . '" />';
						$output .= '<td style="color:#e4223c;">' . $datetime->format("H:i");
						$output .= '<p>' . $user->user_login . '</p>';
						$att = array();
						$att['extra_attr'] = " data-user_id='" . $books[$slot_time]['user_id'] . "' data-user_name='" . $user->user_login . "'";
						$att['class'] = "selectUser";
						if ( $userAvatar = get_user_meta($books[$slot_time]['user_id'], 'thechamp_large_avatar', true) ) {
							$att['url'] = $userAvatar;
						}
						$output .= get_avatar( $books[$slot_time]['user_id'], 96, "", "", $att );
						$output .= '<br> <input type="submit" value="Cancelar" style="background-color:#e4223c;color:white;width:100%;" />';
						$output .= '</td>';
						$output .= '</form>';
					} else {
						$output .= '<form action="" method="post">';
						$output .= '<input type="hidden" name="action" value="appointmentsw-add-admin" />';
						$output .= '<input type="hidden" name="user_id" value="" />';
						$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );
						$output .= '<input type="hidden" name="slot" value="' . $slot_time_ . '" />';
						$output .= '<td style="color:#0c61b4;">' . $datetime->format("H:i") . '<br> <input type="submit" value="' . __( "Submit", 'appointmentsw' ) . '" class="appointmentsw-submit"  style="background-color:#0c61b4;color:white;width:100%;"/></td>';
						$output .= '</form>';
					}
				}
				if ( ( ( $cnt2+1 ) % 4 ) == 0 ) {
					$output .= '</tr>';
				}
			}

			$output .= '</tbody>
				</table>
			</div>';

		}
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	public static function display_add_form_admin () {

		$output = "";

		$all_users = get_users();
		if ( sizeof( $all_users ) > 0 ) {
			$output .= '<div class="row" style="margin:10px 0px;">';
			foreach ( $all_users as $user ) {
				$output .= '<div class="col-xs-4 col-sm-3 col-md-3 col-lg-2 center">';
				$output .= '<div class="sb-user-item">';
				$output .= '<p>' . $user->user_login . '</p>';
				$att = array();
				$att['extra_attr'] = " data-user_id='" . $user->ID . "' data-user_name='" . $user->user_login . "'";
				$att['class'] = "selectUser";
				if ( $userAvatar = get_user_meta($user->ID, 'thechamp_large_avatar', true) ) {
					$att['url'] = $userAvatar;
				}
				$output .= get_avatar( $user->ID, 96, "", "", $att );
				$output .= '</div>';
				$output .= '</div>';
			}
			$output .= '</div>';
		}

		return $output;
	}

	public static function display_new_user_form_admin () {

		$output = "";

		if ( current_user_can( 'manage_options' ) ) {
			$output .= '<div class="row" style="margin:10px 0px;">';

			$output .= '<form action="" method="post">';
			$output .= '<input type="hidden" name="action" value="appointmentsw-new-user-admin" />';
			$output .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );

			$output .= '<div class="form-group">';
			$output .= '<label for="pwd">Nombre:</label>';
			$output .= '<input type="text" name="name" class="form-control" />';
			$output .= '</div>';
			$output .= '<div class="form-group">';
			$output .= '<label for="pwd">Email:</label>';
			$output .= '<input type="text" name="email" class="form-control" />';
			$output .= '</div>';
			$output .= '<input type="submit" value="' . __( "Add client", 'appointmentsw' ) . '" />';

			$output .= '</form>';
			$output .= '</div>';
		}

		return $output;
	}


	// Vacations
	public static function appointmentsw_vacations ( $atts ) {
		global $appointmentSwAlert;

		$output = '';

		if ( is_user_logged_in() ) {

			$atts = shortcode_atts(
					array(
							'form_id'    => null
					),
					$atts
			);

			$output .= $appointmentSwAlert;

			$user_id = get_current_user_id();

			$date = strtotime( current_time( 'timestamp' ) );
			$this_month = date('m', $date);
			$this_day = date('d', $date);
			$this_year = date('Y', $date);

			$calendar = new CalendarSettings();

			$output .= $calendar->show();

			return $output;
		}
	}
}
AppointmentSw_Shortcodes::init();
