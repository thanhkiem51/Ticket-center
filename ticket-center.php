<?php
/*
Plugin Name: Ticket Center
Author: Nam Nguyen, Mia Knowles
Version: 1.0
Description: A custom plug-in for ticket center of Delamar Hospitality
*/

// load other php files here
require_once('ajax.php');
//Put actual plug-ins codes here

function enqueuing_stuffs() {      
	if (is_page(395) || is_page(524) || is_page(384) || is_page(648) || is_page(644)) { //only load these scripts on these pages
		wp_enqueue_script( 'ajax-script', plugins_url( '/js/my-jquery.js', __FILE__ ), array('jquery') );
		wp_localize_script('ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'))); //defining ajaxurl for jQuery
		wp_enqueue_style('table-style', plugins_url('/css/style.css', __FILE__));

		//used for ticket update email sending
	}
}

add_action( 'wp_enqueue_scripts', 'enqueuing_stuffs' );


//insert a record into a log table whenever an user update an IT ticket
add_action( 'gform_after_submission_1', 'insert_new_ticket');
function insert_new_ticket( $entry, $form) {
	$ticket_id = $entry['id'];
	$amNY = new DateTime('America/New_York');
  	$date = $amNY->format('Y-m-d H:i:s');
	global $wpdb;
  	$table_name = "{$wpdb->prefix}IT_ticket_status";
  	$wpdb->insert($table_name,array(
        'ticket_id'	=> $ticket_id,
        'date'		=> $date,
        'status' 	=> "New Request"));
}

add_action( 'gform_after_submission_4', 'insert_hr_ticket');
function insert_hr_ticket( $entry, $form) {
    $ticket_id = $entry['id'];
    $amNY = new DateTime('America/New_York');
    $date = $amNY->format('Y-m-d H:i:s');
    global $wpdb;
    $table_name = "{$wpdb->prefix}IT_ticket_status";
    $wpdb->insert($table_name,array(
        'ticket_id' => $ticket_id,
        'date'      => $date,
        'status'    => "Requested"));
}
// Admin Page

function help_plugin_setup_menu(){
	global $plugin_front_page;
    $plugin_front_page = add_menu_page( 'Help Center Page', 'Help Center', 'manage_options', 'help-plugin', 'test_init' );
}
add_action('admin_menu', 'help_plugin_setup_menu');

// **use this template function to separate js and php when coding the admin panel if desire
function help_plugin_load_script($hook) {
	global $plugin_front_page;

    if ($hook != $plugin_front_page ) {
        return;
    }
    wp_enqueue_script('plugin-script', plugins_url( '/js/admin-panel.js', __FILE__ ), array('jquery') );
}

add_action('admin_enqueue_scripts', 'help_plugin_load_script');

function test_init(){
    echo "<h1>Help Center FAQ Page</h1>
    Please look through the documentation below for answers to frequently asked questions regarding the plugin.";
    echo "<ul>
    		<li><a class='iframe-update' href='#'>General How to Guide</a></li>
    		<li><a class='iframe-update' href='#'>Announcements How to Guide</a></li>
    		<li><a class='iframe-update' href='#'>Specials and Discounts How to Guide</a></li>
    	</ul>";
    echo "<div class='frame-right'>
			<div id='docpreview'>
				<iframe id='docframe' src='http://help.delamar.com/wp-content/uploads/2018/05/Help-Center-FAQ.pdf' width='800' height='380'></iframe>
			</div>
    </div>";
}

?>