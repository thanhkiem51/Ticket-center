var welcome = "<h1>Help Center FAQ Page</h1>\
    Please look through the documentation below for answers to frequently asked questions regarding the plugin.\
    <div class='frame-right'>\
			<div id='docpreview'>\
				<iframe id='docframe' src='http://help.delamar.com/wp-content/uploads/2018/05/Help-Center-FAQ.pdf' width='800' height='380'></iframe>\
			</div>\
    </div>";

jQuery(document).ready(function($){
	var link;
	$('.iframe-update').on('click',function(element) {
		link = $(this).attr("href");
		// alert(link);
		$("#docframe").attr("src",link);
	})
});