jQuery(document).ready(function($) {
	
	$("button.rsvpvolunteer").click(function() {
	//PostRSVPVolunteer(this.getAttribute("user_id"), this.getAttribute("event_id"), this.getAttribute("role"), this.getAttribute("key") );
	PostRSVPVolunteer(this.getAttribute("user_id"), this.getAttribute("event_id"), this.getAttribute("key"), this.getAttribute("hours") );
  })  	

	$("select.rsvpvolunteer_edit").change(function() {
	PostRSVPVolunteer($(this).val(), this.getAttribute("event_id"), this.getAttribute("name"), this.getAttribute("hours") );
  })

	$("button.rsvpvolunteer_withdraw").click(function() {
	PostRSVPVolunteer_Withdraw(this.getAttribute("user_id"), this.getAttribute("event_id"), this.getAttribute("role"), this.getAttribute("key") );
  })

function PostRSVPVolunteer(user_id, event_id, key, hours ) {
//alert('user: ' + user_id + ' event id: ' + event_id + ' key: ' + key + ' ajax: ' + volunteer_roles_data.ajax_url); //

$.post( volunteer_roles_data.ajax_url, {action: 'volunteer_roles', user_id: user_id, event_id: event_id, key: key, hours: hours})
	.done(function( data ) {
  //alert(data);
  $( "#result_" +  key).html( data );
  $( "#cal_result_" +  key).html( '' );
	})
	.error(function( ) {
		alert("error");

});

}

function PostRSVPVolunteer_Withdraw(user_id, event_id, role, key ) {
//alert('user: ' + user_id + ' event id: ' + event_id + ' role: ' + role + ' ajax: ' + volunteer_roles_data.ajax_url); //

$.post( volunteer_roles_data.ajax_url, {action: 'volunteer_roles_withdraw', user_id: user_id, event_id: event_id, role: role, key: key})
	.done(function( data ) {
  //alert(data);
  $( "#result_" +  key).html( data )
  $( "#cal_result_" +  key).html( '' );
	})
	.error(function( ) {
		alert("error");

});

}


});
