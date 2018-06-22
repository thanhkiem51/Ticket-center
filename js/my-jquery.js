//option for IT
var IT_option = '<select name="second_option" id="second_option" style="width:30vw;">\
                  <option value="All Tickets">All Tickets</option>\
                  <option value="Ticket Number">Ticket Number</option>\
                  <option value="Ticket Status">Ticket Status</option>\
                  <option value="Location">Location</option>\
                  <option value="Email">Email</option>\
                 </select>';
var status_dropdown = '<select name="search_value" id="search_value" style="width:30vw;">\
                            <option value="New Request">New Request</option>\
                            <option value="In Progress">In Progress</option>\
                            <option value="On Hold">On Hold</option>\
                            <option value="Resolved">Resolved</option>\
                        </select>';

var IT_location_dropdown= '<select name="search_value" id="search_value" style="width:30vw;">\
                            <option value="Delamar Greenwich">Delamar Greenwich</option>\
                            <option value="Delamar West Hartford">Delamar West Hartford</option>\
                            <option value="Delamar Southport">Delamar Southport</option>\
                            <option value="Four Columns">Four Columns</option>\
                            <option value="Seaview">Seaview</option>\
                            <option value="Goodwin">Goodwin</option>\
                            <option value="Limpia">Limpia</option>\
                        </select>';

//option for AV
var AV_option = '<select name="second_option" id="second_option" style="width:30vw;">\
                      <option value="All Request">All Request</option>\
                      <option value="Location">Location</option>\
                      <option value="Date">Date</option>\
                    </select>';


//common field for both IT and AV
var AV_location_dropdown = '<select name="search_value" id="search_value" style="width:30vw;">\
                            <option value="Greenwich">Greenwich</option>\
                            <option value="West Hartford">West Hartford</option>\
                            <option value="Southport">Southport</option>\
                        </select>';

var search_field = '<input id="search_value" style="display:block; width:20vw;" ></input>';


//pre-declare re-usable variable
var option, cur_option, search_value;

// reload the page if user click back to prevent jquery scripts not getting loaded
if(!!window.performance && window.performance.navigation.type === 2) {
    window.location.reload();
}

jQuery(document).ready(function(){
    
    //script for ticket center page
    if (document.location.pathname=="/1-nam-testing/" || document.location.pathname=="/it-support-center/"){ 
        jQuery('#option').on('change',function() {
            jQuery('#placeholder').empty(); //flush all the branching option

            option = jQuery('#option').val();

            if(option=="IT Tickets") { //handling all option branching from IT
                jQuery('#placeholder').append(IT_option);
                
                jQuery('#second_option').on('change',function() {
                    cur_option = jQuery('#second_option').val();
                    //flush the second option 
                    jQuery('#search_value').remove();

                    if (cur_option=="Location") 
                        jQuery('#second_option').after(IT_location_dropdown);
                    
                    else if (cur_option=="Ticket Status") 
                        jQuery('#second_option').after(status_dropdown);
                    
                    else if (cur_option=="Ticket Number" || cur_option=="Email") {
                        jQuery('#second_option').after(search_field);
                        if (cur_option=="Ticket Number")
                            jQuery('#search_value').attr("placeholder","Ticket Number");
                        else
                            jQuery('#search_value').attr("placeholder","Requester's Email");
                    }

                });
            }

            else if (option=="Audio Visual Request") {//handling all option branching from AV Request
                jQuery('#placeholder').append(AV_option);
                
                jQuery('#second_option').on('change',function() {
                    cur_option = jQuery('#second_option').val();
                    //flush the second option
                    jQuery('#search_value').remove();

                    if (cur_option=="Location") 
                        jQuery('#second_option').after(AV_location_dropdown);
                    
                    else if (cur_option=="Date"){
                        jQuery('#second_option').after(search_field);   
                        jQuery('#search_value').attr("placeholder","yyyy-mm-dd");
                    }                
                });
            }

        });

        jQuery('.search').on('click',function() {
            option = jQuery('#option').val();
            cur_option = jQuery('#second_option').val();
            search_value = jQuery('#search_value').val();
            var ITpage =1;
            if (option=="None"){
                jQuery('#results').html('<p>Please choose an option first...</p>');
            }
            else {
                jQuery.ajax({
                    type: "GET",
                    url: ajax_object.ajax_url,
                    data: {'request':"get_tickets", 
                            'option': option, 
                            'second_option':cur_option, 
                            'search_value':search_value },
                    success: function(results){
                        jQuery('#results').html(results);
                    }
                });  
            }
        });

        jQuery('#results').on('click','.IT-tablenav-pages > a', function(e) {
        	e.preventDefault();
            ITpage = jQuery(this).text();
            // if (ITpage=="« Previous" || ITpage=="Next »") {
            // 	var cur_page = jQuery(this).text();
            // }

            jQuery.ajax({
                type:"GET",
                url: ajax_object.ajax_url,
                data: {'request':"get_tickets", 
                        'option': option, 
                        'second_option':cur_option, 
                        'search_value':search_value,
                        'ITpage': ITpage},
                success: function (results) {
                    jQuery('#results').html(results);
                }
            });  
        })

        jQuery('#results').on('click','.NH-tablenav-pages > a', function(e) {
        	e.preventDefault();
            NHpage = jQuery(this).text();

            jQuery.ajax({
                type:"GET",
                url: ajax_object.ajax_url,
                data: {'request':"get_tickets", 
                        'option': option, 
                        'second_option':cur_option, 
                        'search_value':search_value,
                        'NHpage': NHpage},
                success: function (results) {
                    jQuery('#results').html(results);
                }
            });  
        })

        jQuery('#results').on('click','.it-export', function(e) {
            
            // window.open('data:application/vnd.ms-excel,' + jQuery('#it-results').html());
            // e.preventDefault();
            jQuery.ajax({
            	type:"GET",
            	url: ajax_object.ajax_url,
            	dataType: 'json',
            	data: {'request':"get_tickets",
            			'option':'Export_IT_Excel'},
    			success: function (results) {
    				// console.log(results);
    				window.open('data:application/vnd.ms-excel,' + encodeURIComponent(results.it));
    			}
            });
            
        });

        jQuery('#results').on('click','.nh-export', function(e) {
            jQuery.ajax({
                type:"GET",
                url: ajax_object.ajax_url,
                dataType: 'json',
                data: {'request':"get_tickets",
                        'option':'Export_NH_Excel'},
                success: function (results) {
                    // console.log(results);
                    window.open('data:application/vnd.ms-excel,' + encodeURIComponent(results.nh));
                }
            });
            
        });

    }

    //script for ticket update page 
    if (document.location.pathname=="/it-support-center/ticket-status-update/") {   
        // alert("jquery is running here");
        var param = window.location.search.substring(1);
        var ticket_id = param.split("=")[1];

        jQuery.ajax({ //sending request to show ticket detail and ticket log
            type: "GET",
            url: ajax_object.ajax_url,
            data: {'request':"get_tickets",
                    'option':"One Ticket",
                    'second_option':"Ticket Number",
                    'search_value': ticket_id},
            success: function (result) {
                jQuery('#result').html(result);
            }
        });

        jQuery('#search').on('click',function() {
            window.location="?ticket_id="+jQuery('#ticket_number').val();
        });

        jQuery('#update').on('click',function() {
            var status = jQuery('#status').val();
            var comment = jQuery('#notes').val();
            // var requestor_email = jQuery('#requestor-email').text();
            
            jQuery.ajax({
                type: "POST",
                url: ajax_object.ajax_url,
                data: {'request':"update_ticket",
                        'ticket_type':'IT',
                        'ticket_id': ticket_id,
                        'status': status,
                        'comment': comment,
                        'requestor_email': jQuery('#requestor-email').text() },
                success: function (result) {
                    jQuery('#placeholder').html(result);
                    jQuery.ajax({
                        type:"GET",
                        url: ajax_object.ajax_url,
                        data: {'request': "update_ticket_log",
                                'ticket_type':'IT',
                                'ticket_id':ticket_id},
                        success: function(new_row) {
                            jQuery('#log-rows').append(new_row);
                        }
                    });
                }
            });            
        });        
    }

    //script for hr update page 
    // if (document.location.pathname=="/hr/managers/update-position-request/") {   
    //     // alert("jquery is running here");
    //     var param = window.location.search.substring(1);
    //     var ticket_id = param.split("=")[1];

    //     jQuery.ajax({ //sending request to show ticket detail and ticket log
    //         type: "GET",
    //         url: ajax_object.ajax_url,
    //         data: {'action':"get_hr_tickets",
    //                 'option':"HR Ticket",
    //                 'search_value': ticket_id},
    //         success: function (result) {
    //             jQuery('#results').html(result);
    //         }
    //     });

    //     jQuery('#update').on('click',function() {
    //         var status = jQuery('#status').val();
            
    //         jQuery.ajax({
    //             type: "POST",
    //             url: ajax_object.ajax_url,
    //             data: {'action':"update_hr_ticket",
    //                     'ticket_id': ticket_id,
    //                     'status': status },
    //             success: function (result) {
    //                 jQuery('#placeholder').html(result);
    //                 jQuery.ajax({
    //                     type:"GET",
    //                     url: ajax_object.ajax_url,
    //                     data: {'action': "update_hr_ticket_log",
    //                             'ticket_id':ticket_id},
    //                     success: function(new_row) {
    //                         jQuery('#log-rows').append(new_row);
    //                     }
    //                 });
    //             }
    //         });            
    //     });        
    // }
    
    // if (document.location.pathname=="/hr/human-resources-administrators/") {
    //     jQuery('.search').on('click',function() {
    //         option = jQuery('#option').val();
    //         if (option=="None")
    //             jQuery('#results').html('<p>Please pick an option first</p>');
    //         else {
    //             jQuery.ajax({
    //                 type:"GET",
    //                 url: ajax_object.ajax_url,
    //                 data: { 'action':'get_tickets',
    //                         'option':'hr_ticket',
    //                         'second_option': option},
    //                 success: function(results) {
    //                     jQuery('#results').html(results);
    //                 }
    //             })
    //         }
    //     })
    // }
    
});