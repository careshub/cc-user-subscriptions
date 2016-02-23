<?php
/*
Plugin Name: CC User Subscriptions
Description: Manually set user subscription status for use in maproom.
Version: 1.0.0
Author: David Cavins
Licence: GPLv3
*/


/**
 * Creates instance of CC_User_Subscriptions
 * This is where most of the running gears are.
 *
 * @package CC User Subscriptions
 * @since 1.0.0
 */

function cc_user_subscriptions_class_init(){
	// Get the class fired up
	require( dirname( __FILE__ ) . '/class-cc-user-subscriptions.php' );
	add_action( 'admin_init', array( 'CC_User_Subscriptions', 'get_instance' ), 11 );
}
add_action( 'admin_init', 'cc_user_subscriptions_class_init' );