<?php
namespace EventEspresso\core\libraries\rest_api\calculations;
use EventEspresso\core\libraries\rest_api\controllers\model\Base;
/**
 *
 * Class Event_Calculations
 *
 * Description here
 *
 * @package         Event Espresso
 * @subpackage
 * @author				Mike Nelson
 * @since		 	   $VID:$
 *
 */
if( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

class Event {
	/**
	 * Calculates the total spaces on the event (not subtracting sales, but taking
	 * sales into account; so this is the optimum sales that CAN still be achieved)
	 * See EE_Event::total_available_spaces( true );
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int
	 */
	public static function optimum_sales_at_start( $wpdb_row, $request, $controller ){
		$event_obj = \EEM_Event::instance()->get_one_by_ID( $wpdb_row[ 'Event_CPT.ID' ] );
		return $event_obj->total_available_spaces( true );
	}

	/**
	 * Calculates the total spaces on the event (ignoring all sales; so this is the optimum
	 * sales that COULD have been achieved)
	 * See EE_Event::total_available_spaces( true );
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int
	 */
	public static function optimum_sales_now( $wpdb_row, $request, $controller ){
		$event_obj = \EEM_Event::instance()->get_one_by_ID( $wpdb_row[ 'Event_CPT.EVT_ID' ] );
		return $event_obj->total_available_spaces( false );
	}

	/**
	 * Like optimum_sales_now, but minus total sales so far.
	 * See EE_Event::spaces_remaining_for_sale( true );
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int
	 */
	public static function spaces_remaining( $wpdb_row, $request, $controller ){
		$event_obj = \EEM_Event::instance()->get_one_by_ID( $wpdb_row[ 'Event_CPT.EVT_ID' ] );
		return $event_obj->spaces_remaining_for_sale();
	}

	/**
	 * Counts the number of approved registrations for this event (regardless
	 * of how many datetimes each registrations' ticket purchase is for)
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int
	 */
	public static function spots_taken( $wpdb_row, $request, $controller ){
		return \EEM_Registration::instance()->count(
			array(
				array(
					'EVT_ID' => $wpdb_row[ 'Event_CPT.ID' ],
					'STS_ID' => \EEM_Registration::status_id_approved
				)
			),
			'REG_ID',
			true
		);
	}

	/**
	 * Counts the number of pending-payment registrations for this event (regardless
	 * of how many datetimes each registrations' ticket purchase is for)
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int
	 */
	public static function spots_taken_pending_payment( $wpdb_row, $request, $controller ){
		if( ! current_user_can( 'ee_read_registrations' ) ) {
			return null;
		}
		return \EEM_Registration::instance()->count(
			array(
				array(
					'EVT_ID' => $wpdb_row[ 'Event_CPT.ID' ],
					'STS_ID' => \EEM_Registration::status_id_pending_payment
				)
			),
			'REG_ID',
			true
		);
	}



	/**
	 * Counts all the registrations who have checked into one of this events' datetimes
	 * See EE_Event::total_available_spaces( false );
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int|null if permission denied
	 */
	public static function registrations_checked_in_count( $wpdb_row, $request, $controller ){
		if( ! current_user_can( 'ee_read_registrations' ) 
			|| ! current_user_can( 'ee_read_checkins' ) ) {
			return null;
		}
		return \EEM_Registration::instance()->count_registrations_checked_into_event( $wpdb_row[ 'Event_CPT.ID' ], true );
	}

	/**
	 * Counts all the registrations who have checked out of one of this events' datetimes
	 * See EE_Event::total_available_spaces( false );
	 *
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return int
	 */
	public static function registrations_checked_out_count( $wpdb_row, $request, $controller ){
		if( ! current_user_can( 'ee_read_registrations' ) 
			|| ! current_user_can( 'ee_read_checkins' ) ) {
			return null;
		}
		return \EEM_Registration::instance()->count_registrations_checked_into_event( $wpdb_row[ 'Event_CPT.ID' ], false );
	}
	
	/**
	 * 
	 * @param array $wpdb_row
	 * @param \WP_REST_Request $request
	 * @param Base $controller
	 * @return array
	 */
	public static function featured_image( $wpdb_row, $request, $controller ) {
		$attachment_post = get_post( get_post_thumbnail_id( $wpdb_row[ 'Event_CPT.ID' ] ) );
		if( ! $attachment_post instanceof \WP_Post ) {
			return null;
		}
		$data = wp_get_attachment_metadata( $attachment_post->ID );
		if ( empty( $data ) ) {
			$data = array();
		} elseif ( ! empty( $data['sizes'] ) ) {

			foreach ( $data['sizes'] as $size => &$size_data ) {

				if ( isset( $size_data['mime-type'] ) ) {
					$size_data['mime_type'] = $size_data['mime-type'];
					unset( $size_data['mime-type'] );
				}

				// Use the same method image_downsize() does
				$image_src = wp_get_attachment_image_src( $attachment_post->ID, $size );
				if ( ! $image_src ) {
					continue;
				}

				$size_data['source_url'] = $image_src[0];
			}

			$full_src = wp_get_attachment_image_src( $attachment_post->ID, 'full' );
			if ( ! empty( $full_src ) ) {
				$data['sizes']['full'] = array(
					'file'          => wp_basename( $full_src[0] ),
					'width'         => $full_src[1],
					'height'        => $full_src[2],
					'mime_type'     => $attachment_post->post_mime_type,
					'source_url'    => $full_src[0],
					);
			}
		} else {
			$data['sizes'] = array();
		}
		return $data;
	}
}
