<?php

if ( !defined('ABSPATH') ) return;

function shortcode_voucher_management_page( $atts, $content = '' ) {
	if ( !is_user_logged_in() ) {
		ob_start();
		echo '<h2>You must be logged in to continue</h2>';
		wp_login_form();
		return ob_get_clean();
	}
	
	if ( !current_user_can( 'edit_voucher_applications' ) ) {
		return __('Sorry, you are not allowed to access this page.' );
	}
	
	$args = array(
		'post_type' => 'voucher_application',
		'post_status' => 'any',
	    'nopaging' => true,
	    'orderby' => 'date',
	    'order' => 'DESC',
	);
	
	$vouchers = new WP_Query($args);
	
	ob_start();
	?>
	<h2>Vouchers by date:</h2>
	
	<div class="voucher-list">
		<?php
		if ( $vouchers->have_posts() ) while( $vouchers->have_posts() ): $vouchers->the_post();
			global $post;
			$date = strtotime($post->post_date);
			
			$view_link = 'http://www.spotspayneuter.org/?spot_voucher_id=' . get_the_ID();
			$edit_link = get_edit_post_link( get_the_ID() );
			?>
			<div class="voucher-item">
				
				<div class="voucher-summary">
					<span class="voucher-date"><?php echo date("F j, Y, g:i a", $date); ?></span>
					&ndash;
					<span class="voucher-title"><?php the_title(); ?></span>
					&ndash;
					<span class="voucher-edit"><a href="<?php echo esc_attr($edit_link); ?>" target="_blank">Edit Voucher</a></span>
				</div>
				
				<div class="voucher-details">
					<?php voucher_show_status_and_action_details( get_the_ID() ); ?>
				</div>
				
			</div>
			<?php
		endwhile;
		?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'voucher_management_page', 'shortcode_voucher_management_page' );

/**
 * Adds the shortcode to the management page if it isn't already in there.
 *
 * @param null $content
 *
 * @return null|string
 */
function add_shortcode_to_management_page_if_missing( $content = null ) {
	if ( get_post_type() == 'page' && get_the_ID() == get_field( 'voucher_management_page', 'options', false ) ) {
		if ( !stristr($content, 'voucher_management_page') ) {
			return $content . "\n\n" . '[voucher_management_page]';
		}
	}
	
	return $content;
}
add_filter( 'the_content', 'add_shortcode_to_management_page_if_missing', 4 );