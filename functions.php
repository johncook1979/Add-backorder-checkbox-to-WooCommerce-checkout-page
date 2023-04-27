/**
 * Loop through the contents of the cart to find products with a backordered item
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
 function my_check_cart_has_backorder_product() {
 
   // Loop through each cart item
    foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
      $cart_product =  wc_get_product( $values['data']->get_id() );

      // Check if product is on backorder and return true if it is
      if( $cart_product->is_on_backorder() )
        return true;
    }

    return false;
}



/**
 * Add custom checkout field location: woocommerce_review_order_before_submit
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
add_action( 'woocommerce_review_order_before_submit', 'wcsuccess_custom_checkout_backorder_field' );
function wcsuccess_custom_checkout_backorder_field() {

  // Check if cart contains backordered items
  if(my_check_cart_has_backorder_product()){
  
    // If "true" create a checkbox
    echo '<div id="my_custom_checkout_field">';
    woocommerce_form_field( 'backorder_cart_acknowledge', array(
      'type'      => 'checkbox', // checkbox
      'class'     => array('input-checkbox'), //checkbox CSS class - modify as necessary
      'label'     => __('Acknowledge cart contains backorder items'), // Label
      'required'      => true, // change to false if you do not want it to be a required field
    ),  WC()->checkout->get_value( 'backorder_cart' ) ); // set the name attribute to save to the order
    echo '</div>';
  }
}




/**
 * Process the checkout
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
add_action('woocommerce_checkout_process', 'wcsuccess_custom_checkout_backorder_field_process');

function wcsuccess_custom_checkout_backorder_field_process() {
  if(my_check_cart_has_backorder_product()){
    // Check if set, if its not set add an error.
    if ( ! $_POST['backorder_cart_acknowledge'] )
      wc_add_notice( __( 'Please acknowledge that your cart contains backordered items and shipping will be delayed' ), 'error' );
  }
}





/**
 * Save the custom checkout checkbox field as the order meta 
 *
 * Save an additional field that the cart contains backordered items. This will be used on the order list screen
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
add_action( 'woocommerce_checkout_create_order', 'wcsuccess_custom_checkout_backorder_field_update_order_meta', 10, 2 );
function wcsuccess_custom_checkout_backorder_field_update_order_meta( $order, $data ) {
    // Check cart contains backordered items	
    if(my_check_cart_has_backorder_product()){
      $value = isset($_POST['backorder_cart_acknowledge']) ? 'yes' : 'no'; // Set the correct values

      // Save as custom order meta data that shopper has acknowledged the cart contains backorders
      $order->update_meta_data( 'backorder_cart_acknowledge', $value );
		
      // Save value to the order that it contains backordered items	
      $order->update_meta_data( 'backorder_cart', $value );
    }
}








/**
 * Displaying the terms field value in the order edit page
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'wcsuccess_backorder_field_display_admin_order_meta', 10, 1 );

function wcsuccess_backorder_field_display_admin_order_meta( $order ){

	
  // Get the custom meta field from the order
  $accepted_terms = get_post_meta( $order->get_id(), 'backorder_cart_acknowledge', true );

  if($accepted_terms != 'no' && !empty( $accepted_terms ) ){
    // Check that the field is not empty to display the necessary message
    if( ! empty( $accepted_terms ))
      echo '<p><strong>'.__('Accepted backorder', 'woocommerce').':</strong> ' . ucfirst($accepted_terms) . '</p>';
  }
}




/**
 * Add a custom field (in an order) to the emails
 *
 * Modified from https://woocommerce.com/document/add-a-custom-field-in-an-order-to-the-emails/
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
add_filter( 'woocommerce_email_order_meta_fields', 'wcsuccess_backorder_woocommerce_email_order_meta_fields', 10, 3 );

function wcsuccess_backorder_woocommerce_email_order_meta_fields( $fields, $sent_to_admin, $order ) {
	
  // Get the order meta for the terms
  $accepted_terms = get_post_meta( $order->id, 'backorder_cart_acknowledge', true );
    
  // Only display if the terms field is not empty
  if( !empty( $accepted_terms ) && $accepted_terms != 'no') {
    $fields['meta_key'] = array(
      'label' => __( 'Accepted cart contains backordered items' ),
      'value' => ucfirst($accepted_terms),
    );
    return $fields;
  }
}





/**
 * Add a backorder column to the order list screen
 * 
 * Author: John Cook
 *
 * Link: https://wcsuccessacademy.com/?p=646
 */
add_filter( 'manage_edit-shop_order_columns', 'wcsuccess_add_backorder_column_header', 20 );
function wcsuccess_add_backorder_column_header( $columns ) {

    $new_columns = array();

    foreach ( $columns as $column_name => $column_info ) {

        $new_columns[ $column_name ] = $column_info;

        if ( 'order_total' === $column_name ) {
            
            $new_columns['backorder_cart'] = __( 'Backordered', 'my-textdomain' );
        }
    }

    return $new_columns;
}



/**
 * Add 'Backorder' column content to 'Orders' page.
 *
 * @param string[] $column name of column being displayed
 */
add_action( 'manage_shop_order_posts_custom_column', 'wcsuccess_backorder_column_content' );
function wcsuccess_backorder_column_content( $column ) {
    global $post;

    if ( 'backorder_cart' === $column ) {
  
        $company_name = !empty(get_post_meta($post->ID,'backorder_cart',true)) ? get_post_meta($post->ID,'backorder_cart',true) : 'No';
        
        echo ucfirst($company_name);
    } 
}



// Make custom column sortable
add_filter( "manage_edit-shop_order_sortable_columns", 'wcsuccess_shop_order_column_meta_field_sortable' );
function wcsuccess_shop_order_column_meta_field_sortable( $columns ){
    $meta_key = 'backorder_cart';
    return wp_parse_args( array('backorder_cart' => $meta_key), $columns );
}



