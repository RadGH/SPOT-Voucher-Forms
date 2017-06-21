<?php

if ( !defined('ABSPATH') ) return;

/**
 * Register the voucher_application post type
 */
function register_voucher_application_post_type() {
	$labels = array(
		'name'                  => 'Voucher Applications',
		'singular_name'         => 'Voucher Application',
		'menu_name'             => 'Vouchers',
		'name_admin_bar'        => 'Voucher Application',
		'archives'              => 'Voucher Application Archives',
		'attributes'            => 'Voucher Application Attributes',
		'parent_item_colon'     => 'Parent Voucher Application:',
		'all_items'             => 'Voucher Applications',
		'add_new_item'          => 'New Voucher Application',
		'add_new'               => 'Add New',
		'new_item'              => 'New Voucher Application',
		'edit_item'             => 'Edit Voucher Application',
		'update_item'           => 'Update Voucher Application',
		'view_item'             => 'View Voucher Application',
		'view_items'            => 'View Voucher Applications',
		'search_items'          => 'Search Voucher Application',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into voucher Application',
		'uploaded_to_this_item' => 'Uploaded to this voucher Application',
		'items_list'            => 'Voucher Application list',
		'items_list_navigation' => 'Voucher Application list navigation',
		'filter_items_list'     => 'Filter voucher Applications list',
	);
	$args = array(
		'label'                 => 'Voucher Application',
		'labels'                => $labels,
		'supports'              => array( 'title', 'revisions' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'rewrite'               => false,
	    'menu_icon'             => 'dashicons-media-text',
	    
	    // Custom permissions:
		'capability_type'     => array('edit_voucher_application','edit_voucher_applications'),
		'map_meta_cap'        => true,
	);
	register_post_type( 'voucher_application', $args );
	
	acf_add_options_page(array(
		'parent_slug' => 'edit.php?post_type=voucher_application',
		'page_title' => 'Settings',
		'menu_title' => 'Settings',
		'capability' => 'manage_options',
		'autoload'   => false
	));
}
add_action( 'init', 'register_voucher_application_post_type', 0 );

// Disable yoast for vouchers
function remove_yoast_for_voucher_application() {
	remove_meta_box('wpseo_meta', 'voucher_application', 'normal');
}
add_action( 'add_meta_boxes', 'remove_yoast_for_voucher_application', 11 );

function voucher_application_fields_from_gf( $entry, $form ) {
	if ( empty($entry['form_id']) ) return;
	if ( $entry['form_id'] != get_field( 'voucher_application_form_id', 'options' ) ) return;
	
	// These map to field IDs in the form. Decimals are for subfields. Assistance can have multiple values and is special.
	$field_keys = array(
		'income' => 1,
		'employment' => 2,
		'assistance' => 3, // can have multiple values eg: 3.1, 3.2, 3.3
		'other_assistance' => 4,
		'homeless' => 5,
		
		'first_name' => "8.3",
		'last_name' => "8.6",
		'home_phone' => 9,
		'cell_phone' => 10,
		'email_address' => 11,
		'city' => 30,
		'zip' => 31,
		
		'dog_name' => 20,
		'dog_breed' => 21,
		'dog_gender' => 23,
		'dog_weight' => 22,
		'dog_age' => 24,
		'dog_health' => 25,
		
		'referral' => 26,
	);
	
	$args = array(
		'post_title' => $entry[$field_keys['first_name']] . ' ' . $entry[$field_keys['last_name']] . ', ' . $entry[$field_keys['dog_name']],
		'post_type' => 'voucher_application',
	    'post_status' => 'publish',
	);
	
	$voucher_id = wp_insert_post( $args );
	
	if ( !$voucher_id ) {
		wp_die($voucher_id);
		exit;
	}
	
	foreach( $field_keys as $field_key => $form_index ) {
		$value = !empty($entry[$form_index]) ? $entry[$form_index] : null;
		
		// Try using a string for the key if we were trying an integer before
		if ( $value === null && is_int($form_index) ) $value = !empty($entry[(string) $form_index]) ? $entry[(string) $form_index] : null;
		
		// Get the list of fields for assistance, eg, 3.7 = Other
		if ( $field_key == 'assistance' ) {
			$arr = array();
			for( $i = 0; $i < 20; $i++ ) {
				$form_index_sub = $form_index . '.' . $i;
				if ( !empty($entry[$form_index_sub]) ) {
					
					// If it says other, just use the word "Other" and not the parenthesis text
					if ( stristr($entry[$form_index_sub], 'Other' ) !== false ) {
						$arr[] = 'Other';
					}else{
						$arr[] = $entry[$form_index_sub];
					}
				}
			}
			$value = implode(', ', $arr );
		}
		
		update_field( $field_key, $value, $voucher_id );
	}
	
	update_field( 'gf_entry_id', $entry['id'], $voucher_id );
	update_field( 'voucher_completed', '', $voucher_id );
	update_field( 'voucher_completed_date', '', $voucher_id );
}
add_action( 'gform_entry_created', 'voucher_application_fields_from_gf', 10, 2 );


function voucher_print_metabox_register() {
	add_meta_box( 'voucher-print-box', __( 'Voucher Settings', 'textdomain' ), 'voucher_print_metabox_display', 'voucher_application', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'voucher_print_metabox_register' );

function voucher_print_metabox_display( $post ) {
	$voucher_id = get_the_ID();
	voucher_show_status_and_action_details($voucher_id);
}

function voucher_show_status_and_action_details($voucher_id) {
	// Links also work with gravity form entry id eg: http://www.spotspayneuter.org/?spot_voucher_gf_entry_id=9
	
	if ( is_voucher_completed($voucher_id) ) {
		$date = get_field( 'voucher_completed_date', $voucher_id );
		$status_title = 'This voucher has been completed on ' . date('F j, Y, g:i a', strtotime($date));
		$status_text = 'Revert voucher to pending status';
		$class = '';
		$status_url = get_voucher_mark_as_pending_link( $voucher_id );
	}else{
		$status_title = '<strong>This voucher is awaiting review.</strong> Please print and process the voucher, and then mark this entry as completed.';
		$status_text = 'Mark as complete';
		$class = 'button button-primary';
		$status_url = get_voucher_mark_as_complete_link( $voucher_id );
	}
	
	?>
	<p><?php echo $status_title; ?></p>
	<p><a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_attr($status_url); ?>"><?php echo $status_text; ?></a></p>
	<p><a class="button button-secondary" href="http://www.spotspayneuter.org/?spot_voucher_id=<?php echo $voucher_id; ?>" target="_blank">Print voucher</a></p>
	<?php
}

// ADMIN BAR: Add  link to manage vouchers
function manage_vouchers_admin_bar_link() {
	global $wp_admin_bar;
	
	if ( !current_user_can( 'edit_voucher_applications' ) ) return;
	
	if ( !function_exists('get_field') ) return;
	
	$manage_page_id = get_field( 'voucher_management_page', 'options' );
	if ( !$manage_page_id ) return;
	
	$args = array(
		'post_type' => 'voucher_application',
		'post_status' => 'any',
		'posts_per_page' => 1,
	    'meta_query' => array(
		    array(
	            'key' => 'voucher_completed',
	            'value' => '1',
	            'compare' => '!=',
	            'type' => 'NUMERIC'
		    )
	    ),
	);
	
	$q = new WP_Query($args);
	$voucher_count = $q->found_posts;
	
	$title = 'Vouchers';
	if ( $voucher_count > 0 ) {
		$title .= ' <span class="pending-vouchers-count">('. $voucher_count .' Pending)</span>';
	}else{
		$title .= ' <span class="no-pending-vouchers">(None Pending)</span>';
	}
	
	$wp_admin_bar->add_node( array(
		'id' => 'spot-vouchers',
		'title' => $title,
		'href' => get_permalink( $manage_page_id ),
		'meta' => array(
		
		),
	));
	
	/* add_node args:
	 *     @type string $id     ID of the item.
	 *     @type string $title  Title of the node.
	 *     @type string $parent Optional. ID of the parent node.
	 *     @type string $href   Optional. Link for the item.
	 *     @type bool   $group  Optional. Whether or not the node is a group. Default false.
	 *     @type array  $meta   Meta data including the following keys: 'html', 'class', 'rel', 'lang', 'dir',
	 *                          'onclick', 'target', 'title', 'tabindex'. Default empty.
	 */
}

add_action( 'wp_before_admin_bar_render', 'manage_vouchers_admin_bar_link' );

/**
 * Returns a URL that will mark the given voucher as PENDING. Redirect URL can be provided, defaults to current URL.
 *
 * @param $voucher_id
 * @param null $redirect_url
 *
 * @return string
 */
function get_voucher_mark_as_pending_link( $voucher_id, $redirect_url = null ) {
	$_nonce = wp_create_nonce('voucher-pending');
	return get_voucher_mark_as_complete_link( $voucher_id, $redirect_url, $_nonce );
}

/**
 * Returns a URL that will mark the given voucher as COMPLETE. Redirect URL can be provided, defaults to current URL.
 *
 * @param $voucher_id
 * @param null $redirect_url
 * @param null $_nonce
 *
 * @return string
 */
function get_voucher_mark_as_complete_link( $voucher_id, $redirect_url = null, $_nonce = null ) {
	if ( $_nonce === null ) $_nonce = wp_create_nonce('voucher-complete');
	if ( $redirect_url === null ) $redirect_url = $_SERVER['REQUEST_URI'];
	return add_query_arg( array('set_voucher_status' => $_nonce, 'completed_voucher_id' => $voucher_id), $redirect_url );
}

/**
 * Marks a voucher as complete or pending.
 * Triggered by URL behavior from get_voucher_mark_as_pending_link() and get_voucher_mark_as_complete_link()
 */
function voucher_change_status_from_link() {
	$nonce = isset($_REQUEST['set_voucher_status']) ? stripslashes($_REQUEST['set_voucher_status']) : false;
	if ( !$nonce ) return;
	
	$voucher_id = (int) $_REQUEST['completed_voucher_id'];
	
	if ( $voucher_id && get_post_type( $voucher_id ) == 'voucher_application' ) {
		
		if ( !current_user_can( 'edit_voucher_applications' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) );
			exit;
		}
		
		if ( wp_verify_nonce($nonce, 'voucher-complete') ) {
			// Complete a voucher
			$completed_date = date('Y-m-d H:i:s', current_time('timestamp'));
			update_field( 'voucher_completed', 1, $voucher_id );
			update_field( 'voucher_completed_date', $completed_date, $voucher_id );
		}else if ( wp_verify_nonce($nonce, 'voucher-pending') ) {
			// Mark as pending
			update_field( 'voucher_completed', '', $voucher_id );
			update_field( 'voucher_completed_date', '', $voucher_id );
		}
		
		wp_redirect( remove_query_arg( array('set_voucher_status', 'completed_voucher_id') ) );
		exit;
	}else{
		wp_die('Invalid voucher id');
		exit;
	}
}
add_action( 'init', 'voucher_change_status_from_link' );

/**
 * Returns true if a voucher has been completed.
 *
 * @param $voucher_id
 *
 * @return bool
 */
function is_voucher_completed( $voucher_id ) {
	return get_field( 'voucher_completed', $voucher_id, false );
}

function link_display_printable_voucher() {
	// By voucher id
	if ( isset($_REQUEST['spot_voucher_id']) ) {
		$voucher_id = (int) $_REQUEST['spot_voucher_id'];
	}
	
	// By gf entry id
	if ( isset($_REQUEST['spot_voucher_gf_entry_id']) ) {
		$args = array(
			'post_type' => 'voucher_application',
		    'post_status' => 'any',
		    'meta_query' => array(
		    	array(
		    		'key' => 'gf_entry_id',
			        'value' => (int) $_REQUEST['spot_voucher_gf_entry_id'],
			        'type' => 'NUMERIC',
			    )
		    ),
		);
		
		$p = new WP_Query( $args );
		
		if ( $p->have_posts() ) $voucher_id = $p->posts[0]->ID;
		else die( 'No such entry id exists' );
	}
	
	if ( isset($voucher_id) ) {
		if ( !is_user_logged_in() ) auth_redirect(); // Require login, else go to login page
		
		generate_printable_voucher( $voucher_id );
		exit;
	}
}
add_action( 'template_redirect', 'link_display_printable_voucher' );


function generate_printable_voucher( $voucher_id ) {
	$fields = array(
		'income' => null,
		'employment' => null,
		'assistance' => null,
		'other_assistance' => null,
		'homeless' => null,
		
		'first_name' => null,
		'last_name' => null,
		'home_phone' => null,
		'cell_phone' => null,
		'email_address' => null,
		'city' => null,
		'zip' => null,
		
		'dog_name' => null,
		'dog_breed' => null,
		'dog_gender' => null,
		'dog_weight' => null,
		'dog_age' => null,
		'dog_health' => null,
		
		'referral' => null,
	);
	
	$meta = get_post_meta( $voucher_id );
	
	foreach( $fields as $key => $value ) {
		if ( isset($meta[$key]) ) $fields[$key] = $meta[$key][0];
	}
	
	if ( empty($fields['income']) && $fields['income'] !== '0' && $fields['income'] !== 0 ) {
		$income_text = 'My monthly household income is <span class="user-input"><em>(Not Provided)</em></span>.';
	}else{
		$income_text = 'My monthly household income is <span class="user-input">'. esc_attr($fields['income']) .'</span>.';
	}
	
	// Format assistance formatting with "Other" option as a separate field, add it in parenthesis
	if ( $fields['assistance'] && $fields['other_assistance'] ) {
		$assistance_text = 'I receive the following assistance: <span class="user-input">'. esc_html($fields['assistance']) .'</span> (Other: <span class="user-input">'. esc_html($fields['other_assistance']) . '</span>)';
	}else if ( $fields['other_assistance'] ) {
		$assistance_text = 'I receive the following assistance: Other - <span class="user-input">'. esc_html($fields['other_assistance']) .'</span>.';
	}else if ( $fields['assistance'] ) {
		$assistance_text = 'I receive the following assistance: <span class="user-input">'. esc_html($fields['assistance']) .'</span>.';
	}else{
		$assistance_text = 'I do not receive any special assistance.';
	}
	
	?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width">
	<title><?php echo esc_html( get_the_title( $voucher_id ) ); ?></title>
	<style type="text/css">
		html {
			margin: 0;
		}
		
		body {
			margin: 0;
			font-family: Calibri, Helvetica, Arial, sans-serif;
			font-size: 16px;
			line-height: 22px;
		}
		
		span.user-input {
			font-weight: bold;
			display: inline-block;
		}
		
		.return-to-spot {
			display: none;
		}
		
		.table-h td {
			padding-bottom: 3px;
			border-bottom: 1px solid #ccc;
		}
		
		.table-h + tr td {
			padding-top: 3px;
		}
		
		.underline {
			color: #fff;
			color: transparent;
			border-bottom: 1px solid #888;
		}
		
		@media screen {
			.page {
				max-width: 700px;
				margin: 10px auto;
			}
			
			.return-to-spot {
				display: block;
				margin: 20px 0;
			}
			
			.return-to-spot a {
				font-size: 24px;
				color: #000;
				font-weight: bold;
			}
		}
	</style>
</head>
<body>
<div class="page">
	
	<div class="return-to-spot">
		<a href="<?php echo site_url(); ?>">&larr; Return to SPOT Website</a>
	</div>
	
	<p>Application Date: <span class="underline">_________________________</span></p>
	
	<p><?php echo $income_text; ?><br>
	   I am currently <span class="user-input"><?php echo esc_attr($fields['employment']); ?></span>. <br>
	   <?php echo $assistance_text; ?><br>
	   I am <span class="user-input"><?php echo (strtolower($fields['homeless']) == 'yes') ? 'currently homeless' : 'NOT homeless'; ?></span>.</p>
	
	<table>
		<tbody>
		<tr class="table-h">
			<td style="vertical-align: top; padding-right: 20px;"><strong>Owner's Information</strong></td>
			<td style="vertical-align: top;"><strong>Pet Information</strong></td>
		</tr>
		<tr>
			<td style="vertical-align: top; padding-right: 20px;">First Name: <span class="user-input"><?php echo esc_attr($fields['first_name']); ?></span><br>
				Last Name: <span class="user-input"><?php echo esc_attr($fields['last_name']); ?></span><br>
				Home Phone: <span class="user-input"><?php echo esc_attr($fields['home_phone']); ?></span><br>
				Cell Phone: <span class="user-input"><?php echo esc_attr($fields['cell_phone']); ?></span><br>
				Email: <span class="user-input"><?php echo esc_attr($fields['email_address']); ?></span><br>
				City / Zip: <span class="user-input"><?php echo esc_attr($fields['city']); ?> / <?php echo esc_attr($fields['zip']); ?></span></td>
			<td style="vertical-align: top;">Dog's Name: <span class="user-input"><?php echo esc_attr($fields['dog_name']); ?></span><br>
				Breed: <span class="user-input"><?php echo esc_attr($fields['dog_breed']); ?></span><br>
				Gender: <span class="user-input"><?php echo esc_attr($fields['dog_gender']); ?></span><br>
				Estimated Weight: <span class="user-input"><?php echo esc_attr($fields['dog_weight']); ?></span><br>
				Age (Years or Months): <span class="user-input"><?php echo esc_attr($fields['dog_age']); ?></span><br>
				Health Issues: <span class="user-input"><?php echo esc_attr($fields['dog_health']); ?></span></td>
		</tr>
		</tbody>
	</table>
	
	<p>How did you hear about S.P.O.T? <span class="user-input"><?php echo esc_attr($fields['referral']); ?></span></p>
	
	<br>
	<hr />
	
	<p class="spot-only"><em>For S.P.O.T use only, please do not fill out info below.</em></p>
	
	
	<table>
		<tbody>
		<tr>
			<td style="vertical-align: top; padding-top: 10px;">Client co-pay:</td>
			<td style="vertical-align: top; padding-top: 10px; padding-right: 20px;"><span class="underline">_________________________</span></td>
			<td style="vertical-align: top; padding-top: 10px;"></td>
			<td style="vertical-align: top; padding-top: 10px;"></td>
		</tr>
		<tr>
			<td style="vertical-align: top; padding-top: 20px;">S.P.O.T. co-pay:</td>
			<td style="vertical-align: top; padding-top: 20px; padding-right: 20px;"><span class="underline">_________________________</span></td>
			<td style="vertical-align: top; padding-top: 20px;">Extra Charges:</td>
			<td style="vertical-align: top; padding-top: 20px;"><span class="underline">___</span> IV $7 <span class="underline">___</span>Sr $25-30 <span class="underline">___</span> Crypt $13<br><br>
			
			                                 <span class="underline">___</span>PG $27-30 <span class="underline">___</span>OW $ 1 or $2 lb over 90<br><br>
			
			                                 <span class="underline">___</span> Other $</td>
		</tr>
		</tbody>
	</table>
	<table>
		<tbody>
		<tr>
			<td style="vertical-align: top; padding-right: 20px;">Appointment Date:</td>
			<td style="vertical-align: top;">Voucher Number:</td>
		</tr>
		<tr>
			<td style="vertical-align: top; padding-right: 20px;"><span class="underline">_________________________</span></td>
			<td style="vertical-align: top;"><span class="underline">_________________________</span></td>
		</tr>
		</tbody>
	</table>
</div>
	
	<script type="text/javascript">window.print();</script>
</body>
</html>
	<?php
}