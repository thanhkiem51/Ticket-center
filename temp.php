<?php
//connect to db and insert a support ticket record
add_action( 'gform_after_submission_5', 'insert_new_ticket');
function insert_new_ticket( $entry, $form) {
	$ticket_id = $entry['id'];
	$amNY = new DateTime('America/New_York');
  $date = $amNY->format('Y-m-d H:i:s');
	global $wpdb;
  $table_name = "{$wpdb->prefix}ticket_status";
  $wpdb->insert($table_name,array(
        'ticket_id'	=> $ticket_id,
        'date'		=> $date,
        'status' 	=> "New Request"));
}

//add_filter( 'gform_submit_button_8', '__return_false' );

//remove form entry after submission to prevent obsolete data
add_action('gform_after_submission_8', 'remove_form_entry', 10, 2); 
add_action('gform_after_submission_9', 'remove_form_entry', 10, 2); //
add_action('gform_after_submission_14', 'remove_form_entry', 10, 2); //
function remove_form_entry($entry, $form){
    GFAPI::delete_entry( $entry['id'] ); 
}

add_action( 'gform_after_submission_9', 'update_ticket_status');
function update_ticket_status($entry, $form) {
    $ticket_id = $entry["6"];
    $status = $entry["2"];
    $comment = $entry["4"];
    global $wpdb;
    $amNY = new DateTime('America/New_York');
    $date = $amNY->format('Y-m-d H:i:s');
    $table_name = "{$wpdb->prefix}ticket_status";

    $wpdb->insert($table_name,array(
        'ticket_id' => $ticket_id,
        'date'		=> $date,
        'status'	=> $status,
        'comment'	=> $comment));
}


function get_audio_request_count($items_per_page, $location="") {
    global $wpdb;
    $sql = "SELECT * FROM (SELECT lead_id as ticket_no, MAX(IF(field_number = 5,value,NULL)) as location
			FROM wp_rg_lead_detail WHERE form_id=13 GROUP BY lead_id) as T";
    if (count(func_get_args()) > 1) {
        $sql = $sql." WHERE location='$location'";
    }
    $total_query = "SELECT COUNT($items_per_page) FROM ($sql) AS combined_table";
    $total = $wpdb->get_var( $total_query );
    return $total;
}


function get_audio_request_list($offset,$items_per_page,$location="") {
    global $wpdb;
    $sql = "SELECT * FROM (SELECT lead_id as ticket_no,
        MAX(IF(field_number = 1,value,NULL)) as company_name,
		MAX(IF(field_number = 2,value,NULL)) as contact_name,
		MAX(IF(field_number = 3,value,NULL)) as phone,
		MAX(IF(field_number = 4,value,NULL)) as email,
		MAX(IF(field_number = 5,value,NULL)) as location,
		MAX(IF(field_number = 6,value,NULL)) as event_name,
		MAX(IF(field_number = 7,value,NULL)) as event_date,
		MAX(IF(field_number = 9,value,NULL)) as multi_day,
        MAX(IF(field_number = 10,value,NULL)) as end_date,
		MAX(IF(field_number = 11,value,NULL)) as start_time,
		MAX(IF(field_number = 12,value,NULL)) as end_time,
		MAX(IF(field_number = 15,value,NULL)) as guest_count,
		MAX(IF(field_number = 13,value,NULL)) as book_inquiry,
		MAX(IF(field_number = 14,value,NULL)) as organizer_email,
		MAX(IF(field_number = 16,value,NULL)) as note
	FROM wp_rg_lead_detail WHERE form_id=13 GROUP BY lead_id ORDER BY lead_id DESC) as T";
    if ($location!="") {
        $sql= $sql." WHERE location = '$location'";
    }
    $sql = $sql." LIMIT $offset, $items_per_page";
    $results = $wpdb->get_results($sql);
    return $results;   
}

function get_detail_audio_request($id) {
    global $wpdb;
    $sql = "SELECT * FROM (SELECT lead_id as ticket_no,
        MAX(IF(field_number = 1,value,NULL)) as company_name,
		MAX(IF(field_number = 2,value,NULL)) as contact_name,
		MAX(IF(field_number = 3,value,NULL)) as phone,
		MAX(IF(field_number = 4,value,NULL)) as email,
		MAX(IF(field_number = 5,value,NULL)) as location,
		MAX(IF(field_number = 6,value,NULL)) as event_name,
		MAX(IF(field_number = 7,value,NULL)) as event_date,
		MAX(IF(field_number = 9,value,NULL)) as multi_day,
        MAX(IF(field_number = 10,value,NULL)) as end_date,
		MAX(IF(field_number = 11,value,NULL)) as start_time,
		MAX(IF(field_number = 12,value,NULL)) as end_time,
		MAX(IF(field_number = 15,value,NULL)) as guest_count,
		MAX(IF(field_number = 13,value,NULL)) as book_inquiry,
		MAX(IF(field_number = 14,value,NULL)) as organizer_email,
		MAX(IF(field_number = 16,value,NULL)) as note
	FROM wp_rg_lead_detail WHERE form_id=13 AND lead_id = $id GROUP BY lead_id ORDER BY lead_id DESC) as T";
    $results = $wpdb->get_results($sql);
    return $results;   
}

function get_new_hire($offset,$items_per_page) { //
    global $wpdb;
    $sql= "SELECT * FROM (SELECT 
        MAX(IF(field_number = 17,value,NULL)) as date_submitted,
		MAX(IF(CAST(field_number as DECIMAL(3,1)) = 12.3,value,NULL)) as first_name,
		MAX(IF(CAST(field_number as DECIMAL(3,1)) = 12.6,value,NULL)) as last_name,
		MAX(IF(field_number = 7,value,NULL)) as property,
		MAX(IF(field_number = 15,value,NULL)) as job_title,
		MAX(IF(field_number = 16,value,NULL)) as department,
		MAX(IF(field_number = 8,value,NULL)) as start_date,
		MAX(IF(field_number = 3,value,NULL)) as job_type,
        CONCAT_WS(',' ,
		MAX(IF(CAST(field_number as DECIMAL(3,1)) = 10.1,value,NULL)),
		MAX(IF(CAST(field_number as DECIMAL(3,1)) = 10.2,value,NULL)),
		MAX(IF(CAST(field_number as DECIMAL(3,1)) = 10.3,value,NULL))) as access_needed,
		MAX(IF(field_number = 11,value,NULL)) as hardware_needed,
		MAX(IF(field_number = 14,value,NULL)) as email
FROM wp_rg_lead_detail WHERE form_id=12 GROUP BY lead_id ORDER BY lead_id DESC) as T";

    $sql = $sql." LIMIT $offset, $items_per_page";  
    $results = $wpdb->get_results($sql);
    return $results;       
}

function search_by_condition($offset,$items_per_page,$category="all",$value="") {
    global $wpdb;
    $sql = "SELECT A.ticket_number, A.email, A.location,A.description, D.date, D.status
            FROM (SELECT  wp_rg_lead_detail.lead_id as ticket_number,
                    MAX(IF(wp_rg_lead_detail.field_number = 5,value,NULL)) as email,
                    MAX(IF(wp_rg_lead_detail.field_number = 6,value,NULL)) as location,
                    MAX(IF(wp_rg_lead_detail.field_number = 4,value,NULL)) as description
                FROM wp_rg_lead_detail 
                GROUP BY ticket_number ORDER BY ticket_number DESC) as A
            INNER JOIN
                (SELECT ticket_id, date, status
                FROM wp_ticket_status B
                WHERE B.date = (SELECT MAX(C.date) FROM wp_ticket_status C WHERE B.ticket_id=C.ticket_id)) as D
            ON A.ticket_number=D.ticket_id";
    $suffix=" ORDER BY A.ticket_number DESC"; 
    if($category == 'ticket_id'){
        $sql = $sql. " WHERE A.ticket_number = $value ";
    }elseif($category == 'location'){
        $sql = $sql. " WHERE A.location = '$value' ";
    }elseif($category == 'email'){
        $sql = $sql. " WHERE A.email = '$value' ";
    }elseif($category == 'status'){
        $sql = $sql. " WHERE D.status = '$value' ";
    }
    $sql = $sql.$suffix." LIMIT $offset, $items_per_page";    
    $results = $wpdb->get_results($sql);
    return $results;
}

function get_count($items_per_page, $category="all",$value="") {
    global $wpdb;
    $sql = "SELECT A.ticket_number, A.email, A.location,A.description, D.date, D.status
            FROM (SELECT  wp_rg_lead_detail.lead_id as ticket_number,
                    MAX(IF(wp_rg_lead_detail.field_number = 5,value,NULL)) as email,
                    MAX(IF(wp_rg_lead_detail.field_number = 6,value,NULL)) as location,
                    MAX(IF(wp_rg_lead_detail.field_number = 4,value,NULL)) as description
                FROM wp_rg_lead_detail 
                GROUP BY ticket_number ORDER BY ticket_number DESC) as A
            INNER JOIN
                (SELECT ticket_id, date, status
                FROM wp_ticket_status B
                WHERE B.date = (SELECT MAX(C.date) FROM wp_ticket_status C WHERE B.ticket_id=C.ticket_id)) as D
            ON A.ticket_number=D.ticket_id";
    if($category == 'ticket_id'){
        $sql = $sql. " WHERE A.ticket_number = $value ";
    }elseif($category == 'location'){
        $sql = $sql. " WHERE A.location = '$value' ";
    }elseif($category == 'email'){
        $sql = $sql. " WHERE A.email = '$value' ";
    }elseif($category == 'status'){
        $sql = $sql. " WHERE D.status = '$value' ";
    }
    $total_query = "SELECT COUNT($items_per_page) FROM ($sql) AS combined_table";
    $total = $wpdb->get_var( $total_query );
    return $total;
}

function get_ticket_log($ticket_id) {
    global $wpdb;
	$sql = $wpdb->prepare("SELECT date, status, comment
                            FROM wp_ticket_status
                            WHERE ticket_id = %d
                            ORDER BY date ASC"   ,  $ticket_id);
    $results = $wpdb->get_results($sql);
    return $results;
}

function get_ticket_detail($ticket_id) {
    global $wpdb;
	$sql = $wpdb->prepare("SELECT  lead_id as ticket_number,
		MAX(IF(field_number = 5,value,NULL)) as email,
		MAX(IF(field_number = 6,value,NULL)) as location,
        MAX(IF(field_number = 4,value,NULL)) as description,
		MAX(IF(field_number = 7,value,NULL)) as attachments,
        MAX(IF(field_number = 9,value,NULL)) as contact
FROM wp_rg_lead_detail
WHERE form_id=5 AND lead_id = %d
GROUP BY ticket_number"   ,  $ticket_id);
    $results = $wpdb->get_row($sql);
    return $results;
}


?>