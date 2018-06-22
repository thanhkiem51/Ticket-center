<?php
//all ajax functions go here

if (isset($_GET['request'])) { //another method to avoid using 'action' in ajax
	if ($_GET['request']=='get_tickets') {
		$option = $_GET['option']; 
		$second_option = $_GET['second_option'];
		$search_value = $_GET['search_value'];

		$result="<p>No Record Found...Please check your input...</p>";

		if ($option=='All Tickets'){
			$result = '<p>Feature Coming Soon!</p>';
		}
		elseif ($option=='IT Tickets'){
			$result = show_IT_tickets($second_option,$search_value);
		}
		elseif ($option=='New Hire Request'){
			$result = show_new_hire_tickets();
		}
		
		elseif ($option=='Audio Visual Request'){
			$result = '<p>Feature Coming Soon!</p>';
		}
		elseif ($option=='One Ticket'){ //for ticket update page
			$result = show_IT_ticket_detail($search_value);
		}

		elseif ($option=='Export_IT_Excel'){
			// unset($result);
			$result = json_encode(array('it' => Export_IT()));
		}
		elseif ($option=='Export_NH_Excel') {
			$result = json_encode(array('nh' => Export_NH()));
		}
		echo $result;
		wp_die();	
	}

	if ($_GET['request']=='update_ticket') {
		$result =update_ticket();
		echo $result;
		wp_die();
	}
	if ($_GET['request']=='update_ticket_log') {
		$result =update_ticket_log();
		echo $result;
		wp_die();
	}

	
}



function IT_ticket_search($offset,$items_per_page,$category="",$value="") {
	global $wpdb;
    $sql = "SELECT A.ticket_number, A.email, A.location, D.date, D.status
            FROM (SELECT entry_id as ticket_number,
                    MAX(IF(meta_key = 1,meta_value,NULL)) as email,
                    MAX(IF(meta_key = 2,meta_value,NULL)) as location,
                    MAX(IF(meta_key = 3,meta_value,NULL)) as description
                FROM {$wpdb->prefix}gf_entry_meta 
                WHERE form_id=1
                GROUP BY ticket_number ORDER BY ticket_number DESC) as A
            INNER JOIN
                (SELECT ticket_id, date, status
                FROM {$wpdb->prefix}IT_ticket_status B
                WHERE B.date = (SELECT MAX(C.date) FROM {$wpdb->prefix}IT_ticket_status C WHERE B.ticket_id=C.ticket_id)) as D
            ON A.ticket_number=D.ticket_id";
    $suffix=" ORDER BY A.ticket_number DESC"; 
    if($category == 'Ticket Number'){
        $sql = $sql. " WHERE A.ticket_number = $value ";
    }elseif($category == 'Location'){
        $sql = $sql. " WHERE A.location = '$value' ";
    }elseif($category == 'Email'){
        $sql = $sql. " WHERE A.email = '$value' ";
    }elseif($category == 'Ticket Status'){
        $sql = $sql. " WHERE D.status = '$value' ";
    }
    $sql = $sql.$suffix." LIMIT $offset, $items_per_page";  
    $results = $wpdb->get_results($sql);
    return $results;
}

function get_IT_tickets_count($items_per_page, $category="",$value="") {
	global $wpdb;
    $sql = "SELECT A.ticket_number, A.email, A.location, D.date, D.status
            FROM (SELECT entry_id as ticket_number,
                    MAX(IF(meta_key = 1,meta_value,NULL)) as email,
                    MAX(IF(meta_key = 2,meta_value,NULL)) as location,
                    MAX(IF(meta_key = 3,meta_value,NULL)) as description
                FROM {$wpdb->prefix}gf_entry_meta 
                WHERE form_id=1
                GROUP BY ticket_number ORDER BY ticket_number DESC) as A
            INNER JOIN
                (SELECT ticket_id, date, status
                FROM {$wpdb->prefix}IT_ticket_status B
                WHERE B.date = (SELECT MAX(C.date) FROM {$wpdb->prefix}IT_ticket_status C WHERE B.ticket_id=C.ticket_id)) as D
            ON A.ticket_number=D.ticket_id";
    if($category == 'Ticket Number'){
        $sql = $sql. " WHERE A.ticket_number = $value ";
    }elseif($category == 'Location'){
        $sql = $sql. " WHERE A.location = '$value' ";
    }elseif($category == 'Email'){
        $sql = $sql. " WHERE A.email = '$value' ";
    }elseif($category == 'Ticket Status'){
        $sql = $sql. " WHERE D.status = '$value' ";
    }
    $total_query = "SELECT COUNT($items_per_page) FROM ($sql) AS combined_table";
    $total = $wpdb->get_var( $total_query );
    return $total;
}

function show_IT_tickets($second_option,$search_value) {
	$items_per_page = 5;
	$page = isset( $_GET['ITpage'] ) ? abs( (int) $_GET['ITpage'] ) : 1;
	$offset = ( $page * $items_per_page ) - $items_per_page;

	$total = get_IT_tickets_count($items_per_page,$second_option,$search_value);
	$results = IT_ticket_search($offset,$items_per_page,$second_option,$search_value);	
		// echo '<p>Second option is '.$second_option.'. search_value '.$search_value.'. Total is '.$total.'</p>';	

	if ($total==0)
		$html="<p>No record found.</p>";

	else {
		$html= "<table name='IT-ticket-table' class='results'  border='1'>
		      <thead>
		        <tr>
		          <th>Ticket No.</th>
		          <th>Email</th>
		          <th>Location</th>
		          <th>Date</th>
		          <th>Status</th>
		        </tr>
		      </thead>
		      <tbody>";
		    foreach($results as $row) {
		            $html.= '<tr>
		              <td><a href="http://help.delamar.com/it-support-center/ticket-status-update/?ticket_id='.$row->ticket_number.'">'.$row->ticket_number.'</a></td>
		              <td>'.$row->email.'</td>
		              <td>'.$row->location.'</td>
		              <td>'. date('H:i F d, Y', strtotime($row->date)) . '</td>
		              <td>'.$row->status.'</td>
		            </tr>';
		    }
		$html.= "</tbody></table>";
		//pagination div
		$html.= "<div class='IT-tablenav-pages'>";
		$html.= paginate_links( array(
		          'base' => add_query_arg( 'ITpage', '%#%'),
		          'total' => ceil($total / $items_per_page),
		          'current' => $page,
		          'prev_next' => false,
		          'mid_size' => 5,
		          // 'end_size' => 3
		      ));
		$html.= "</div>";
		//excel export button
		$html.="<button type='button' class='it-export'>Export to Excel</button>";
	}
	return $html;
}	

function show_IT_ticket_detail($ticket_id) {
	global $wpdb;
    $sql = $wpdb->prepare("SELECT entry_id as ticket_number,
							MAX(IF(meta_key = 1,meta_value,NULL)) as email,
							MAX(IF(meta_key = 2,meta_value,NULL)) as location,
					        MAX(IF(meta_key = 3,meta_value,NULL)) as description,
							MAX(IF(meta_key = 4,meta_value,NULL)) as attachments
					FROM {$wpdb->prefix}gf_entry_meta
					WHERE form_id=1 AND entry_id=%d
					GROUP BY ticket_number",  	$ticket_id);

   	$results = $wpdb->get_results($sql);

    $html= "<h2>Ticket detail</h2><table name='IT-ticket-detail' class='results' border='1'>";
	foreach($results as $row) {

	   			$html.= '<tr><th>Ticket No.</th><td>'.$row->ticket_number.'</td></tr>
						<tr><th>Email</th><td id="requestor-email">'.$row->email.'</td></tr>
						<tr><th>Location</th><td>'.$row->location.'</td></tr>
                        <tr><th>Description</th><td>'.$row->description.'</td></tr>
                        <tr><th>Attachments</th><td>';
                        $attachments=explode(",",substr($row->attachments,1,-1));
                        for ($no=0;$no<sizeof($attachments);$no++){
                              $file_name = substr($attachments[$no],1,-1);
                              $file_name = explode("/",$file_name);
                              $file = $file_name[sizeof($file_name)-1];
                              $html.= '<a href='.$attachments[$no].' style="margin-right: 15px">'.$file.'</a>';
                        } 
                        $html.= '</td></tr>';
	}
	$html.= "</table>";
	$sql = $wpdb->prepare("SELECT date, status, comment
                            FROM {$wpdb->prefix}IT_ticket_status
                            WHERE ticket_id = %d
                            ORDER BY date ASC", $ticket_id);
	$results = $wpdb->get_results($sql);
	$html.= "<h3>Ticket log</h3><table name='IT-ticket-log' class='results' border='1'>
			   <thead>
		        <tr>
		          <th>Date</th>
		          <th>Status</th>
		          <th>Notes</th>
		        </tr>
		      </thead><tbody id='log-rows'>";
	foreach($results as $row) {
		$html.= '<tr>
					<td>'.date('H:i F d, Y', strtotime($row->date)).'</td>
					<td>'.$row->status.'</td>
					<td>'.$row->comment.'</td>
				</tr>';
	}	      
	$html.= "</tbody></table>";
	return $html;
}




function get_new_hire_tickets($offset,$items_per_page) { //
	global $wpdb;
    $sql= " SELECT * FROM (SELECT 
	        MAX(IF(meta_key = 2,meta_value,NULL)) as date_submitted,
			MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 1.3,meta_value,NULL)) as first_name,
			MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 1.6,meta_value,NULL)) as last_name,
			MAX(IF(meta_key = 4,meta_value,NULL)) as property,
			MAX(IF(meta_key = 5,meta_value,NULL)) as job_title,
			MAX(IF(meta_key = 6,meta_value,NULL)) as department,
			MAX(IF(meta_key = 7,meta_value,NULL)) as start_date,
			MAX(IF(meta_key = 8,meta_value,NULL)) as job_type,
	        CONCAT_WS(',' ,
				MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.1,meta_value,NULL)),
				MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.2,meta_value,NULL)),
				MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.3,meta_value,NULL)),
		        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.4,meta_value,NULL)),
		        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.5,meta_value,NULL))) as access_needed,
			MAX(IF(meta_key = 10,meta_value,NULL)) as hardware_needed,
			MAX(IF(meta_key = 11,meta_value,NULL)) as email
		FROM {$wpdb->prefix}gf_entry_meta WHERE form_id=2 GROUP BY entry_id ORDER BY entry_id DESC) as T";

    $sql = $sql." LIMIT $offset, $items_per_page";  
    // echo $sql;
    $results = $wpdb->get_results($sql);
    return $results;       
}

function show_new_hire_tickets() {
	$items_per_page = 5;
	$page = isset( $_GET['NHpage'] ) ? abs( (int) $_GET['NHpage'] ) : 1;
	$offset = ( $page * $items_per_page ) - $items_per_page;
	$results=get_new_hire_tickets($offset,$items_per_page);

	$total = 10;
	if ($total==0)
		$html="<p>No record found.</p>";
	else {
        $html= "<table id='New-Hire-table' class='results' border='1'>
		      <thead>
		        <tr>
                   <th>Date Submitted</th>
	              <th>New Hire Name</th>
	              <th>Property</th>
	              <th>Job Title</th>
	              <th>Department</th>
	              <th>Start Date</th>
	              <th>Job Type</th>
	              <th>Access Needed</th>
	              <th>Hardware Needed</th>
	              <th>Requestor Email</th>
		        </tr>
		      </thead>
		      <tbody>";
		    foreach($results as $row) {
		            $html.= '<tr>
		              <td>'.date('F d, Y', strtotime($row->date_submitted)).'</td>
		              <td>'.$row->first_name.' '.$row->last_name.'</td>
		              <td>'.$row->property.'</td>
		              <td>'.$row->job_title.'</td>
		              <td>'.$row->department.'</td>
                      <td>'.date('F d, Y', strtotime($row->start_date)).'</td>
                      <td>'.$row->job_type.'</td>
                      <td>'.$row->access_needed.'</td>
                      <td>'.$row->hardware_needed.'</td>
                      <td>'.$row->email.'</td>
		            </tr>';
		    }

		$html.= "</tbody></table>";

		$html.= "<div class='NH-tablenav-pages'>";
		$html.= paginate_links( array(
		          'base' => add_query_arg( 'NHpage', '%#%'),
		          'total' => ceil($total / $items_per_page),
		          'current' => $page,
		          'prev_next' => false,
		          'mid_size' => 5,
		          // 'end_size' => 3
		      ));
		$html.= "</div>";
		$html.="<button type='button' class='nh-export'>Export to Excel</button>";
	}
	return $html;
}


function update_ticket() {
	$ticket_id = $_POST['ticket_id']; 
	$status = $_POST['status'];
	$comment = $_POST['comment'];

	global $wpdb;
    $amNY = new DateTime('America/New_York');
    $date = $amNY->format('Y-m-d H:i:s');
    $table_name = "{$wpdb->prefix}IT_ticket_status";

    $count = $wpdb->insert($table_name,array(
        'ticket_id' => $ticket_id,
        'date'		=> $date,
        'status'	=> $status,
        'comment'	=> $comment));
	if ($count==0) {
		$result = "<h3>Ticket update failed... Possible Database Error</h3>";
	}
	else{
		$result = "<h3>Ticket <span style='color:red;'>#".$ticket_id."</span> is successfully updated!</h3>";
		
		if ($status=="Resolved"){
			$to = $_POST['requestor_email'];
			$subject = "Ticket ". $ticket_id ." Resolved";
			$body = "<html><p>Hello,<br><br> Your ticket ". $ticket_id." has been resolved. If you have any further difficulties regarding this issue, please submit a new IT Support Form and reference this ticket number.<br><br> Thank you,<br><br> IT Support Team</p></html>";
			// $headers = array('Content-Type: text/html; charset=UTF-8;');
			$headers = array('From: IT Support Center <itsupport@thedelamar.com>');
			add_filter('wp_mail_content_type', create_function('', 'return "text/html"; '));
			$sent_message = wp_mail( $to, $subject, $body,$headers);
			remove_filter('wp_mail_content_type', 'set_html_content_type');
			// if ($sent_message)
			// 	$result.="<p>sent</p>";
			// else
			// 	$result.="<p>sent failed</p>";
		}
		
	}
	return $result
}

function update_ticket_log() {
	$ticket_id = $_GET['ticket_id'];
	global $wpdb;
	$sql = $wpdb->prepare("SELECT date, status, comment
                    FROM {$wpdb->prefix}IT_ticket_status
                    WHERE ticket_id = %d
                    ORDER BY date DESC
					LIMIT 0,1",$ticket_id);
	$row = $wpdb->get_row($sql);
	$html= '<tr>
				<td>'.date('H:i F d, Y', strtotime($row->date)).'</td>
				<td>'.$row->status.'</td>
				<td>'.$row->comment.'</td>
			</tr>';
	return $html;
}


function Export_IT() {
	global $wpdb;
    $sql = "SELECT A.ticket_number, A.email, A.location, D.date, D.status
            FROM (SELECT  {$wpdb->prefix}gf_entry_meta.entry_id as ticket_number,
                    MAX(IF({$wpdb->prefix}gf_entry_meta.meta_key = 1,meta_value,NULL)) as email,
                    MAX(IF({$wpdb->prefix}gf_entry_meta.meta_key = 2,meta_value,NULL)) as location,
                    MAX(IF({$wpdb->prefix}gf_entry_meta.meta_key = 3,meta_value,NULL)) as description
                FROM {$wpdb->prefix}gf_entry_meta 
                GROUP BY ticket_number ORDER BY ticket_number DESC) as A
            INNER JOIN
                (SELECT ticket_id, date, status
                FROM {$wpdb->prefix}IT_ticket_status B
                WHERE B.date = (SELECT MAX(C.date) FROM {$wpdb->prefix}IT_ticket_status C WHERE B.ticket_id=C.ticket_id)) as D
            ON A.ticket_number=D.ticket_id
            ORDER BY A.ticket_number DESC";
    // $sql .=" LIMIT 0, 3";         
    $results = $wpdb->get_results($sql);
    $html= "<table>
		      <thead>
		        <tr>
		          <th>Ticket No.</th>
		          <th>Email</th>
		          <th>Location</th>
		          <th>Date</th>
		          <th>Status</th>
		        </tr>
		      </thead>
		      <tbody>";
	foreach($results as $row) {
		            $html.= '<tr>
		              <td>'.$row->ticket_number.'</td>
		              <td>'.$row->email.'</td>
		              <td>'.$row->location.'</td>
		              <td>'. date('H:i F d, Y', strtotime($row->date)) . '</td>
		              <td>'.$row->status.'</td>
		            </tr>';
		    }
	$html.= "</tbody></table>";
	return $html;	      
}

function Export_NH() {
	global $wpdb;
    $sql= " SELECT * FROM (SELECT 
	        MAX(IF(meta_key = 2,meta_value,NULL)) as date_submitted,
			MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 1.3,meta_value,NULL)) as first_name,
			MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 1.6,meta_value,NULL)) as last_name,
			MAX(IF(meta_key = 4,meta_value,NULL)) as property,
			MAX(IF(meta_key = 5,meta_value,NULL)) as job_title,
			MAX(IF(meta_key = 6,meta_value,NULL)) as department,
			MAX(IF(meta_key = 7,meta_value,NULL)) as start_date,
			MAX(IF(meta_key = 8,meta_value,NULL)) as job_type,
	        CONCAT_WS(',' ,
				MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.1,meta_value,NULL)),
				MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.2,meta_value,NULL)),
				MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.3,meta_value,NULL)),
		        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.4,meta_value,NULL)),
		        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 9.5,meta_value,NULL))) as access_needed,
			MAX(IF(meta_key = 10,meta_value,NULL)) as hardware_needed,
			MAX(IF(meta_key = 11,meta_value,NULL)) as email
		FROM {$wpdb->prefix}gf_entry_meta WHERE form_id=2 GROUP BY entry_id ORDER BY entry_id DESC) as T";

    // $sql = $sql." LIMIT $offset, $items_per_page"; 
	$results = $wpdb->get_results($sql);	
	$html= "<table>
		      <thead>
		        <tr>
                   <th>Date Submitted</th>
	              <th>New Hire Name</th>
	              <th>Property</th>
	              <th>Job Title</th>
	              <th>Department</th>
	              <th>Start Date</th>
	              <th>Job Type</th>
	              <th>Access Needed</th>
	              <th>Hardware Needed</th>
	              <th>Requestor Email</th>
		        </tr>
		      </thead>
		      <tbody>";
  	foreach($results as $row) {
        $html.= '<tr>
		              <td>'.date('F d, Y', strtotime($row->date_submitted)).'</td>
		              <td>'.$row->first_name.' '.$row->last_name.'</td>
		              <td>'.$row->property.'</td>
		              <td>'.$row->job_title.'</td>
		              <td>'.$row->department.'</td>
                      <td>'.date('F d, Y', strtotime($row->start_date)).'</td>
                      <td>'.$row->job_type.'</td>
                      <td>'.$row->access_needed.'</td>
                      <td>'.$row->hardware_needed.'</td>
                      <td>'.$row->email.'</td>
		            </tr>';
	}
	$html.= "</tbody></table>";
	return $html;
}

function get_hr_tickets() {
	global $wpdb;
	$sql = "SELECT A.ticket_number, A.position_title, A.property, A.priority, A.experience, A.shift, A.requestor_email, D.date, D.status
            FROM (SELECT entry_id as ticket_number,
                    MAX(IF(meta_key = 1,meta_value,NULL)) as position_title,
                    MAX(IF(meta_key = 3,meta_value,NULL)) as property,
                    MAX(IF(meta_key = 4,meta_value,NULL)) as priority,
                    MAX(IF(meta_key = 5,meta_value,NULL)) as experience,
                    CONCAT_WS(',' ,
                        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 6.1,meta_value,NULL)),
                        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 6.2,meta_value,NULL)),
                        MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 6.3,meta_value,NULL))) as shift,
                    MAX(IF(meta_key = 7,meta_value,NULL)) as requestor_email
                FROM wp_x0vkq095ps_gf_entry_meta 
                WHERE form_id=4
                GROUP BY ticket_number ORDER BY ticket_number DESC) as A
            INNER JOIN
                (SELECT ticket_id, date, status
                FROM wp_x0vkq095ps_IT_ticket_status B
                WHERE B.date = (SELECT MAX(C.date) FROM wp_x0vkq095ps_IT_ticket_status C WHERE B.ticket_id=C.ticket_id)) as D
            ON A.ticket_number=D.ticket_id";
    $results = $wpdb->get_results($sql);
    return $results;
}

function show_hr_tickets() {
	
	$results= get_hr_tickets();
	$html= "<table name='HR-ticket-table' class='results'  border='1'>
		      <thead>
		        <tr>
		          <th>Ticket No.</th>
		          <th>Position Title</th>
		          <th>Property</th>
		          <th>Priority Level</th>
		          <th>Requestor Email</th>
		          <th>Date Submitted</th>
		          <th>Status</th>
		        </tr>
		      </thead>
		      <tbody>";
	foreach($results as $row) {
	            $html.= '<tr>
	              <td><a href="https://help.delamar.com/hr/managers/update-position-request/?ticket_id='.$row->ticket_number.'">'.$row->ticket_number.'</a></td>
	              <td>'.$row->position_title.'</td>
	              <td>'.$row->property.'</td>
	              <td>'.$row->priority.'</td>
	              <td>'.$row->requestor_email.'</td>
	              <td>'. date('H:i F d, Y', strtotime($row->date)) . '</td>
	              <td>'.$row->status.'</td>
	            </tr>';
		    }
	$html.= "</tbody></table>";
	return $html;
}

// function update_hr_ticket() {
// 	$ticket_id = $_POST['ticket_id']; 
// 	$status = $_POST['status'];

// 	global $wpdb;
//     $amNY = new DateTime('America/New_York');
//     $date = $amNY->format('Y-m-d H:i:s');
//     $table_name = "{$wpdb->prefix}IT_ticket_status";

//     $count = $wpdb->insert($table_name,array(
//         'ticket_id' => $ticket_id,
//         'date'		=> $date,
//         'status'	=> $status));
// 	if ($count==0) {
// 		$result = "<h3>Ticket update failed... Possible Database Error</h3>";
// 	}
// 	else{
// 		$result = "<h3>Ticket <span style='color:red;'>#".$ticket_id."</span> is successfully updated!</h3>";		
// 	}
// 	echo $result;
// 	wp_die();
// }

// function update_hr_ticket_log() {
// 	$ticket_id = $_GET['ticket_id'];
// 	global $wpdb;
// 	$sql = $wpdb->prepare("SELECT date, status
//                     FROM {$wpdb->prefix}IT_ticket_status
//                     WHERE ticket_id = %d
//                     ORDER BY date DESC
// 					LIMIT 0,1",$ticket_id);
// 	$row = $wpdb->get_row($sql);
// 	$html= '<tr>
// 				<td>'.date('H:i F d, Y', strtotime($row->date)).'</td>
// 				<td>'.$row->status.'</td>
// 			</tr>';
// 	echo $html;
// 	wp_die();
// }

// function show_hr_ticket_detail($ticket_id) {
// 	global $wpdb;
//     $sql = $wpdb->prepare("SELECT entry_id as ticket_number,
// 							MAX(IF(meta_key = 1,meta_value,NULL)) as position_title,
//                             MAX(IF(meta_key = 3,meta_value,NULL)) as property,
//                             MAX(IF(meta_key = 4,meta_value,NULL)) as priority,
//                             MAX(IF(meta_key = 5,meta_value,NULL)) as experience,
//                             CONCAT_WS(',' ,
//                                 MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 6.1,meta_value,NULL)),
//                                 MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 6.2,meta_value,NULL)),
//                                 MAX(IF(CAST(meta_key as DECIMAL(3,1)) = 6.3,meta_value,NULL))) as shift,
//                             MAX(IF(meta_key = 7,meta_value,NULL)) as requestor_email
// 					FROM {$wpdb->prefix}gf_entry_meta
// 					WHERE form_id=4 AND entry_id=%d
// 					GROUP BY ticket_number",  	$ticket_id);

//    	$results = $wpdb->get_results($sql);

//     $html= "<h2>Ticket detail</h2><table name='hr-ticket-detail' class='results' border='1'>";
// 	foreach($results as $row) {

// 	   			$html.= '<tr><th>Ticket No.</th><td>'.$row->ticket_number.'</td></tr>
//                          <tr><th>Position Title</th><td>'.$row->position_title.'</td></tr>
//                          <tr><th>Property</th><td>'.$row->property.'</td></tr>
//                          <tr><th>Priority Level</th><td>'.$row->priority.'</td></tr>
//                          <tr><th>Experience Level</th><td>'.$row->experience.'</td></tr>
//                          <tr><th>Shift</th><td>'.$row->shift.'</td></tr>
// 						 <tr><th>Requestor Email</th><td id="requestor-email">'.$row->email.'</td></tr>';
// 	}
// 	$html.= "</table>";
// 	$sql = $wpdb->prepare("SELECT date, status
//                             FROM {$wpdb->prefix}IT_ticket_status
//                             WHERE ticket_id = %d
//                             ORDER BY date ASC", $ticket_id);
// 	$results = $wpdb->get_results($sql);
// 	$html.= "<h3>Ticket log</h3><table name='hr-ticket-log' class='results' border='1'>
// 			   <thead>
// 		        <tr>
// 		          <th>Date</th>
// 		          <th>Status</th>
// 		        </tr>
// 		      </thead><tbody id='log-rows'>";
// 	foreach($results as $row) {
// 		$html.= '<tr>
// 					<td>'.date('H:i F d, Y', strtotime($row->date)).'</td>
// 					<td>'.$row->status.'</td>
// 				</tr>';
// 	}	      
// 	$html.= "</tbody></table>";
// 	return $html;
// }
?>