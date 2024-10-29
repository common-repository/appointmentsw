<?php
/**
 * class-appointmentsw-notifications.php
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
class AppointmentSwNotifications {

	public static function init() {
		
	}

	public static function send ( $book, $status = APPOINTMENTSW_STATUS_ACCEPTED ) {
		$user = get_user_by( 'ID', $book['user_id'] );
		
		if ( $user ) {
			$email = $user->user_email;
		} else {
			$email = AppointmentSw::get_book_meta( $book['book_id'], 'user_email' );
		}
		
		$datetime = $book['datetime'];
		
		$from = get_bloginfo('admin_email');
		
		$headers[] = 'From: ' . get_bloginfo('name') . " <" . $from . ">";
		$headers[] = 'Content-type: text/html';
		
		$to = array();
		$to[] = $email;
		
		$subject = trim( get_option( 'appointmentsw-subject', get_bloginfo('name') . " - Reservas de citas" ) );
		
		$email_content = "";
		$dt = new DateTime( $datetime );
		$dia = $dt->format('d-m-Y');
		$hora = $dt->format('H:i');

		if ( $status == APPOINTMENTSW_STATUS_ACCEPTED ) {
			$email_content = 'Gracias por reservar en <b>' . get_bloginfo( 'name' ) . '</b>.<br>Cita reservada para el ' . $dia . ' a las ' . $hora . '.';
		} else {
			$email_content = "Su cita para el " . $dia . ' a las ' . $hora . " ha sido cancelada.";
		}
		
		/*
		// tags content
		$tags = array();
		$tags['product_name'] = get_the_title($product_id);
		$tags['product_id'] = $product_id;
		
		$tags = apply_filters( 'woo_notify_updated_product_tags', $tags );
			
		foreach ( $tags as $key => $value ) {
			$email_content = str_replace( "[" . $key . "]", $value, $email_content );
		}
		*/
		
		wp_mail( $to, $subject, $email_content, $headers );
	}
		

	public static function sendToAdmin ( $book, $status = APPOINTMENTSW_STATUS_ACCEPTED ) {
		$user = get_user_by( 'ID', $book['user_id'] );
		if ( $user ) {
			$name = $user->display_name;
		} else {
			$name = AppointmentSw::get_book_meta( $book['book_id'], 'user_login' );
		}
		
		$datetime = $book['datetime'];
		
		$from = get_bloginfo('admin_email');
	
		$headers[] = 'From: ' . get_bloginfo('name') . " <" . $from . ">";
		$headers[] = 'Content-type: text/html';
	
		$to = array();
		$to[] = get_option('admin_email');
	
		$subject = trim( get_option( 'appointmentsw-subject', get_bloginfo('name') . " - Sistema de reservas" ) );

		$dt = new DateTime( $datetime );
		$dia = $dt->format('d-m-Y');
		$hora = $dt->format('H:i');

		$email_content = "";
		if ( $status == APPOINTMENTSW_STATUS_ACCEPTED ) {
			$email_content = "El usuario <b>" . $name . "</b> ha reservado una cita para el " . $dia . ' a las ' . $hora . '.';
		} else {
			$email_content = "Se ha anulado la reserva de <b>" . $name . "</b> para el " . $dia . ' a las ' . $hora . ".";
		}
	
		/*
			// tags content
			$tags = array();
			$tags['product_name'] = get_the_title($product_id);
			$tags['product_id'] = $product_id;
	
			$tags = apply_filters( 'woo_notify_updated_product_tags', $tags );
				
			foreach ( $tags as $key => $value ) {
			$email_content = str_replace( "[" . $key . "]", $value, $email_content );
			}
			*/

		wp_mail( $to, $subject, $email_content, $headers );
	}

}
AppointmentSwNotifications::init();
