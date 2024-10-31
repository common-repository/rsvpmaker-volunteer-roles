<?php
/*
Plugin Name: RSVPMaker Volunteer Roles
Plugin URI: http://www.rsvpmaker.com
Description: RSVPMaker add-on for role signups.
Author: David F. Carr
Version: 1.5.1
Author URI: http://www.carrcommunications.com
*/

add_shortcode('rsvpvolunteer','rsvpvolunteer');

function rsvpvolunteer ($atts) {
global $post;
global $current_user;
$role = $atts["role"];
$count = empty($atts["count"]) ? 1 : (int) $atts["count"];
$hours = empty($atts["hours"]) ? 2 : (int) $atts["hours"];
$future = is_rsvpmaker_future($post->ID, 12);
$output = '';
for($i = 1; $i <= $count; $i++)
	{
	$vmeta = volunteer_meta($post->ID, $role, $i);
	$signup = (int) get_post_meta($post->ID, $vmeta, true);
	if($_GET["edit"] && current_user_can('edit_others_posts'))
		{
		$o = volunteer_user_dropdown ($vmeta, $signup, $post->ID, $hours);
		$output .= sprintf('<p><span id="result_%s">%s</span> %s</p>',$vmeta, $o, $role); //
		}
	elseif($signup)
		{
		$user = get_userdata($signup);
		if(is_user_logged_in() && ($signup == $current_user->ID))
			$w = sprintf('<button class="rsvpvolunteer_withdraw"  user_id="%s" event_id="%s" role="%s" key="%s">Withdraw</button>', $current_user->ID, $post->ID, $role, $vmeta);
		else
			$w = '';
		$output .= sprintf('<p><span id="result_%s">%s %s signed up for</span> %s %s</p>',$vmeta, $user->user_firstname, $user->user_lastname, $role, $w);
		}
	elseif(! $future)
		$output .= sprintf('<p>%s</p>',$role);
	elseif(is_user_logged_in())
		$output .= sprintf('<p><span id="result_%s"><button class="rsvpvolunteer" user_id="%s" event_id="%s" role="%s" key="%s" hours="%s">Volunteer</button></span> %s</p>',$vmeta,$current_user->ID, $post->ID, $role, $vmeta, $hours, $role);
	else
		$output .= sprintf('<p><a href="%s">Login</a> to volunteer for %s</p>',wp_login_url( get_post_permalink( $post->ID ) ), $role);
	}
return $output;
}

add_shortcode('vcal','vcal');

function vcal ($atts) {

global $post;
global $current_user;
$role = $atts["role"];
$count = empty($atts["count"]) ? 1 : (int) $atts["count"];
$hours = empty($atts["hours"]) ? 2 : (int) $atts["hours"];
$p = get_post_permalink($post->ID);
$future = is_rsvpmaker_future($post->ID, 12);
$output = '';

for($i = 1; $i <= $count; $i++)
	{
	$vmeta = volunteer_meta($post->ID, $role, $i);	
	$signup = (int) get_post_meta($post->ID, $vmeta, true);
	$pastclass = (isset($_GET['cm'])) ? 'past' : 'past currentviewpast';
	if($future)
		$pastclass = '';
	if($signup)
		{
		$user = get_userdata($signup);
		if(is_user_logged_in() && ($signup == $current_user->ID) && $future)
			$output .= sprintf('<div><span id="cal_result_%s"><button class="rsvpvolunteer_withdraw smallbutton"  user_id="%s" event_id="%s" role="%s" key="%s" style="color: red">-</button></span> <a href="%s" title="%s" class="%s">%s</a></div>', $vmeta, $current_user->ID, $post->ID, $role, $vmeta, $p, 'You: '.htmlentities($role), htmlentities($role), $role);
		else
			$output .= sprintf('<div class="%s"><a href="%s" title="%s" class="%s">%s</a></div>', $pastclass, $p, htmlentities($user->user_firstname.' '.$user->user_lastname.": ".$role), htmlentities($role), $role);
		}
	elseif(! $future)
		$output .= sprintf('<div class="%s"><a href="%s" title="%s" class="%s">%s</a></div>', $pastclass, $p, htmlentities($role), htmlentities($role), $role);
	elseif(is_user_logged_in())
		{
		$output .= sprintf('<div><span id="cal_result_%s"><button class="rsvpvolunteer smallbutton" user_id="%s" event_id="%s" role="%s" key="%s" hours="%s" style="color: green">+</button></span>',$vmeta,$current_user->ID, $post->ID, htmlentities($role), $vmeta, $hours);
		$output .=sprintf(' <a href="%s" title="%s" class="%s">%s</a> </div>', $p, htmlentities($role), htmlentities($role), $role);
		}
	else
		$output .= sprintf('<div><a href="%s" title="%s" class="%s">%s</a></div>',wp_login_url( get_post_permalink( $post->ID ) ), htmlentities($role), htmlentities($role), $role);
	}
return $output;
}

function volunteer_meta($event_id, $role, $counter) {
$result = '_rsvpv-'.preg_replace("/[^A-Za-z0-9]/",'_',$role);
$result .= '-'.$event_id.'_'.$counter;
return $result;
}

function vmeta_to_role($meta) {
$p = explode("-",$meta);
return str_replace('_',' ',$p[1]);
}

function rsvpvolunteer_scripts() {
global $post;
if(($post->post_type != 'rsvpmaker') && !strpos($post->post_content,'_upcoming') && !strpos($post->post_content,'volunteer_calendar') )
	return;

	wp_enqueue_script(
		'volunteer_roles',
		plugins_url( 'volunteer_roles.js' , __FILE__ ),
		array( 'jquery' )
	);
	wp_localize_script('volunteer_roles','volunteer_roles_data',array('ajax_url' => admin_url('admin-ajax.php') ) );

}

add_action( 'wp_enqueue_scripts', 'rsvpvolunteer_scripts' );
add_action( 'wp_ajax_volunteer_roles', 'volunteer_roles_ajax' );
add_action( 'wp_ajax_nopriv_volunteer_roles', 'volunteer_roles_ajax' );
add_action( 'wp_ajax_volunteer_roles_withdraw', 'volunteer_roles_withdraw_ajax' );
add_action( 'wp_ajax_nopriv_volunteer_roles_withdraw', 'volunteer_roles_withdraw_ajax' );

function volunteer_roles_ajax() { 

$key = $_POST["key"];
$user_id = (int) $_POST["user_id"];
$event_id = (int) $_POST["event_id"];
$hours = (int) $_POST["hours"];
global $wpdb;
global $rsvp_options;
global $post;

update_post_meta($event_id, $key, $user_id);
update_post_meta($event_id, '_hours'.$key, $hours);
update_post_meta($event_id, '_timestamp'.$key, time());

if($user_id == 0)
	{
		echo "<strong>Cleared:</strong>";
		exit();
	}
$user = get_userdata($user_id);

$event = get_rsvpmaker_event($event_id);
$t = $event->ts_start;
$date = rsvpmaker_date($rsvp_options["long_date"],$t);
$start_ts = $event->ts_start;
$duration_ts = $event->ts_end;

$meta_duration = get_post_meta($post->ID,'_end'.$date, true);
if(!empty($meta_duration))
	{
	$p = explode(' ',$date);
	$meta_duration = $p[0].$meta_duration;
	}
$kp = explode("-",$key);
$role = str_replace('_',' ',$kp[1]);
echo $subject = $rsvp_confirm = sprintf('%s %s signed up for %s on %s',$user->user_firstname, $user->user_lastname,$role,$date );
$post = get_post($event_id);

preg_match_all('/[0-9]{1,2}\s{0,1}[ap]m/',$subject,$matches);

$description .= $subject;
$summary = $role.' '.$post->post_title;
$venue = 'See: '. get_permalink($event_id);
$rsvp_to = $from_email = $rsvp_options["rsvp_to"];

$start = gmdate('Ymd',$start_ts);
$start_time = gmdate('His',$start_ts);
$end = gmdate('Ymd',$duration_ts);
$end_time = gmdate('His',$duration_ts);
$event_id = $post->ID;
$sequence = 0;
$status = 'CONFIRMED';
$ical = "BEGIN:VCALENDAR\r\n";
$ical .= "VERSION:2.0\r\n";
$ical .= "PRODID:-//WordPress//RSVPMaker//EN\r\n";
$ical .= "METHOD:REQUEST\r\n";
$ical .= "BEGIN:VEVENT\r\n";
$ical .= "ORGANIZER;SENT-BY=\"MAILTO:".$from_email."\":MAILTO:".$from_email."\r\n";
$ical .= "ATTENDEE;CN=".$rsvp_email.";ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:mailto:".$from_email."\r\n";
$ical .= "UID:".strtoupper(md5($event_id))."-rsvpmaker.com\r\n";
$ical .= "SEQUENCE:".$sequence."\r\n";
$ical .= "STATUS:".$status."\r\n";
$ical .= "DTSTART:".$start."T".$start_time."Z\r\n";
$ical .= "DTEND:".$end."T".$end_time."Z\r\n";
$ical .= "LOCATION:".$venue."\r\n";
$ical .= "SUMMARY:".$summary."\r\n";
$ical .= "DESCRIPTION:".$description."\r\n";
$ical .= "BEGIN:VALARM\r\n";
$ical .= "TRIGGER:-PT15M\r\n";
$ical .= "ACTION:DISPLAY\r\n";
$ical .= "DESCRIPTION:Reminder\r\n";
$ical .= "END:VALARM\r\n";
$ical .= "END:VEVENT\r\n";
$ical .= "END:VCALENDAR\r\n";

$rsvp = array('first' => $user->user_firstname,'last' => $user->user_lastname,'email' => $user->user_email);

$message = wpautop($message);
if(!empty($rsvp_confirm))
	$rsvp_confirm = wpautop($rsvp_confirm);

	$mail["to"] = $rsvp_to;
	$mail["from"] = $rsvp["email"];
	$mail["fromname"] = $rsvp["first"].' '.$rsvp["last"];
	$mail["subject"] = $subject;
	$mail["html"] = $message;
	
	rsvpmailer($mail);

	if(!empty($rsvp_confirm))
	{
	$mail["html"] = $rsvp_confirm . "\n\n".$message;
	}
	
	$mail["ical"] = $ical;
	$mail["to"] = $rsvp["email"];
	$mail["from"] = $rsvp_to;
	$mail["fromname"] = get_bloginfo('name');
	$mail["subject"] = "Confirming ".$subject;
	rsvpmailer($mail);

exit();
}


function volunteer_roles_withdraw_ajax() {

$key = $_POST["key"];
$user_id = (int) $_POST["user_id"];
$event_id = $_POST["event_id"];
$event = get_rsvpmaker_event($event_id);
$t = $event->ts_start;
$date = rsvpmaker_date($rsvp_options["long_date"],$t);
$start_ts = $event->ts_start;
$duration_ts = $event->ts_end;

delete_post_meta($event_id, $key, $user_id);
delete_post_meta($event_id, '_hours'.$key);
delete_post_meta($event_id, '_timestamp'.$key);

$user = get_userdata($user_id);

printf('<strong>%s %s withdrawn: </strong>',$user->user_firstname, $user->user_lastname);

global $wpdb;
global $rsvp_options;
$kp = explode("-",$key);
$subject = $cleanmessage = sprintf('%s %s withdrawn: %s on %s',$user->user_firstname, $user->user_lastname,str_replace('_',' ',$kp[1]),$date );
$rsvp = array('first' => $user->user_firstname,'last' => $user->user_lastname,'email' => $user->user_email);
rsvp_notifications ($rsvp ,$rsvp_options["rsvp_to"],$subject,$cleanmessage);

exit();
}

function volunteer_user_dropdown ($role, $assigned, $event_id, $hours) {
global $wpdb;
global $sortmember;
global $fnamesort;

$options = '<option value="0">'.__('Open','rsvptoast').'</option>';

$blogusers = get_users('blog_id='.get_current_blog_id() );

    foreach ($blogusers as $user) {	
		$member = get_userdata($user->ID);
		$index = preg_replace('/[^a-zA-Z]/','',$member->last_name.$member->first_name);
		$findex = preg_replace('/[^a-zA-Z]/','',$member->first_name.$member->last_name);
		$sortmember[$index] = $member;
		$fnamesort[$findex] = $member;
	}	
	
	ksort($sortmember);
	ksort($fnamesort);

	$options .= '<optgroup label="Members by First Name">';

	foreach($fnamesort as $member)
		{
			if($member->ID == $assigned)
				$s = ' selected="selected" ';
			else
				$s = '';
			$options .= sprintf('<option %s value="%d">%s</option>',$s, $member->ID,$member->first_name.' '.$member->last_name);
		}

	$options .= "</optgroup>";

	$options .= '<optgroup label="Members by Last Name">';
	foreach($sortmember as $member)
		{
			if($member->ID == $assigned)
				$s = ' selected="selected" ';
			else
				$s = '';
			$options .= sprintf('<option %s value="%d">%s</option>',$s, $member->ID,$member->first_name.' '.$member->last_name);
		}
	$options .= "</optgroup>";

	return '<select class="rsvpvolunteer_edit" name="'.$role.'" event_id="'.$event_id.'" hours="'.$hours.'">'.$options.'</select>';
}

add_filter('the_content','edit_rsvpvolunteers',1);

function edit_rsvpvolunteers ($content) {
	if(!is_user_logged_in())
		return $content;
	if(isset($_GET["edit"]))
		return $content;
	if(!strpos($content,'rsvpvolunteer'))
		return $content;
global $post;
if(($post->post_type != 'rsvpmaker') && !strpos($post->post_content,'_upcoming') )
	return $content;

$url = $_SERVER['REQUEST_URI'];
$url .= ( strpos($url,'?') ) ? '&' : '?';

$editlink = sprintf('<div style="text-align: right;"><a href="%sedit=1">Edit Volunteer Signups</a></div>', $url);
return $editlink . $content;
}

add_shortcode('volunteer_calendar','volunteer_calendar');

function volunteer_calendar($atts) {
	
global $post;
global $wp_query;
global $wpdb;
global $showbutton;
global $startday;
$cal = array();

if(isset($atts["startday"]))
	{
    $startday = $atts["startday"];
	}

$showbutton = true;

$backup = $wp_query;

add_filter('posts_join', 'rsvpmaker_join' );
add_filter('posts_where', 'rsvpmaker_calendar_where' );
add_filter('posts_orderby', 'rsvpmaker_orderby' );
add_filter('posts_groupby', 'rsvpmaker_calendar_clear' );
add_filter('posts_distinct', 'rsvpmaker_calendar_clear' );
add_filter('posts_fields', 'rsvpmaker_select' );

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$querystring = "post_type=rsvpmaker&post_status=publish&paged=$paged&posts_per_page=-1";
if(isset($atts["type"]))
	$querystring .= "&rsvpmaker-type=".$atts["type"];
if(isset($atts["add_to_query"]))
	{
		if(!strpos($atts["add_to_query"],'&'))
			$atts["add_to_query"] = '&'.$atts["add_to_query"];
		$querystring .= $atts["add_to_query"];
	}

$wpdb->show_errors();

$wp_query = new WP_Query($querystring);

// clean up so this doesn't interfere with other operations
remove_filter('posts_join', 'rsvpmaker_join' );
remove_filter('posts_where', 'rsvpmaker_calendar_where' );
remove_filter('posts_orderby', 'rsvpmaker_orderby' );
remove_filter('posts_groupby', 'rsvpmaker_calendar_clear' );
remove_filter('posts_distinct', 'rsvpmaker_calendar_clear' );
remove_filter('posts_fields', 'rsvpmaker_select' );

if ( have_posts() ) {
while ( have_posts() ) : the_post();
rsvpmaker_debug_log('volunteer calendar post_content: '.$post->post_title.var_export($post->post_content, true).' '.get_permalink($post->ID));
$content = str_replace('rsvpvolunteer','vcal',$post->post_content);
$pattern = get_shortcode_regex();
preg_match_all( '/'. $pattern .'/s', $content, $matches );
    if( is_array( $matches ) && array_key_exists( 2, $matches ) && in_array( 'vcal', $matches[2] ) )
    {
    // get the corresponding date
	$date = get_rsvp_date($post->ID);
	$vtext = '';
	$dindex = date('Y-m-d',strtotime($date));
	    foreach ($matches[0] as $value) {
            $vtext .= $value."\n";
        }
	$cal[$dindex] = do_shortcode($vtext);
    } else {
        // Do Nothing
    }
endwhile;
	
$wp_query = $backup;	
wp_reset_postdata();
}
rsvpmaker_debug_log('volunteer calendar post_content: '.var_export($post->post_content, true));
rsvpmaker_debug_log('volunteer calendar cal: '.var_export($cal, true));
rsvpmaker_debug_log('volunteer calendar atts: '.var_export($atts, true));
return vol_show_calendar($cal, $atts);
}

function vol_show_calendar($eventarray, $atts) 
{
fix_timezone();
$content = '';
$cm = isset($_REQUEST["cm"]) ? $_REQUEST["cm"] : '';
$cy = isset($_REQUEST["cy"]) ? $_REQUEST["cy"] : '';
$self = $req_uri = get_permalink();
$req_uri .= (strpos($req_uri,'?') ) ? '&' : '?';

if (!isset($cm) || $cm == 0)
	$nowdate = date("Y-m-d");
else
	$nowdate = date("Y-m-d", mktime(0, 0, 1, $cm, 1, $cy) );

// Check if month and year is valid
if ($cm && $cy && !checkdate($cm,1,$cy)) {
   $errors[] = "The specified year and month (".htmlentities("$cy, $cm").") are not valid.";
   unset($cm); unset($cy);
}

// Give defaults for the month and day values if they were invalid
if (!isset($cm) || $cm == 0) { $cm = date("m"); }
if (!isset($cy) || $cy == 0) { $cy = date("Y"); }

// Start of the month date
$date = mktime(0, 0, 1, $cm, 1, $cy);

// Beginning and end of this month
$bom = mktime(0, 0, 1, $cm,  1, $cy);
$eom = mktime(0, 0, 1, $cm+1, 0, $cy);
$eonext = date("Y-m-d",mktime(0, 0, 1, $cm+2, 0, $cy) );

// Link to previous month (but do not link to too early dates)
$lm = mktime(0, 0, 1, $cm, 0, $cy);
   $prev_link = '<a href="' . $req_uri . strftime('cm=%m&cy=%Y">%B %Y</a>', $lm);

// Link to next month (but do not link to too early dates)
$nm = mktime(0, 0, 1, $cm+1, 1, $cy);
   $next_link = '<a href="' . $req_uri . strftime('cm=%m&cy=%Y">%B %Y</a>', $nm);

$monthafter = mktime(0, 0, 1, $cm+2, 1, $cy);

	$page_id = (isset($_GET["page_id"])) ? '<input type="hidden" name="page_id" value="'. (int) $_GET["page_id"].'" />' : '';
   $jump_form = sprintf('<form id="jumpform" action="%s" method="post"> Month/Year <input type="text" name="cm" value="%s" size="4" />/<input type="text" name="cy" value="%s" size="4" /><button>Go</button>%s</form>', $self,date('m',$monthafter),date('Y',$monthafter),$page_id);

// $Id: cal.php,v 1.47 2003/12/31 13:04:27 goba Exp $

// Print out navigation links for previous and next month
//$content .= '<table id="calnav"  width="100%" border="0" cellspacing="0" cellpadding="3">'.
//   "\n<tr>". '<td align="left" width="33%">'. $prev_link. '</td>'.
//     '<td align="center" width="34">'. strftime('<b>%B, %Y</b></td>', $bom).
//     '<td align="right" width="33%">' . $next_link . "</td></tr>\n</table>\n";

// Begin the calendar table
if(!is_user_logged_in())
	$content .= sprintf('<p><a href="%s">Login to sign up</a></p>',wp_login_url( $_SERVER['REQUEST_URI'] ));

// calendar display routine
$nav = isset($atts["nav"]) ? $atts["nav"] : 'bottom';

if(($nav == 'top') || ($nav == 'both')) // either it's top or both
$content .= '<div style="width: 100%; text-align: right;" class="nav"><span class="navprev">'. $prev_link. '</span> / <span class="navnext">'.
     '' . $next_link . "</span></div>";

$content .= '

<style>
#volunteer_calendar div {font-size: 10px;}
button.smallbutton {width: 25px; }
</style>

<table id="volunteer_calendar" width="100%" cellspacing="0" cellpadding="3"><caption>'.strftime('<b>%B %Y</b>', $bom)."</caption>\n".'<tr>'."\n";

$content .= '<thead>
<tr> 
<th>'.__('Sunday','rsvpmaker').'</th> 
<th>'.__('Monday','rsvpmaker').'</th> 
<th>'.__('Tuesday','rsvpmaker').'</th> 
<th>'.__('Wednesday','rsvpmaker').'</th> 
<th>'.__('Thursday','rsvpmaker').'</th> 
<th>'.__('Friday','rsvpmaker').'</th> 
<th>'.__('Saturday','rsvpmaker').'</th> 
</tr>
</thead>
';

$content .= "\n<tbody><tr id=\"rsvprow1\">\n";
$rowcount = 1;
// Generate the requisite number of blank days to get things started
for ($days = $i = date("w",$bom); $i > 0; $i--) {
   $content .= '<td class="notaday">&nbsp;</td>';
}

// Print out all the days in this month
for ($i = 1; $i <= date("t",$bom); $i++) {
  
   // Print out day number and all events for the day
	$thisdate = date("Y-m-",$bom).sprintf("%02d",$i);
   $content .= '<td valign="top">';
   if(!empty($eventarray[$thisdate]) )
   {
   $content .= $i;
   $content .= $eventarray[$thisdate];
   $t = strtotime($thisdate);
   }
   else
   	$content .= "<div class=\"day\">" . $i . "</div><p>&nbsp;</p>";
   $content .= '</td>';
   if (++$days % 7 == 0)
   	{
		$content .= "</tr>\n";
		$rowcount++;
		$content .= '<tr id="rsvprow'.$rowcount.'" >';
	}
}

// Generate the requisite number of blank days to wrap things up
for (; $days % 7; $days++) {
   $content .= '<td class="notaday">&nbsp;</td>';
}

$content .= "\n</tr>";

$content .= "<tbody>\n";

// End HTML table of events
$content .= "\n</table>\n".$jump_form;

if($nav != 'top') // either it's bottom or both
$content .= '<div style="float: right;" class="nav"><span class="navprev">'. $prev_link. '</span> / <span class="navnext">'.
     '' . $next_link . "</span></div>";

return $content;
}


add_action('admin_menu', 'rsvpv_menu');
function rsvpv_menu() {
add_submenu_page('edit.php?post_type=rsvpmaker', __("Volunteer History",'rsvpmaker'), __("Volunteer History",'rsvpmaker'), 'manage_options', "rsvp_history", "rsvp_history" );
add_submenu_page('edit.php?post_type=rsvpmaker', __("Volunteer Report",'rsvpmaker'), __("Volunteer Report",'rsvpmaker'), 'manage_options', "rsvp_vreport", "rsvp_vreport" );
}

function rsvp_history() {
?>
<h1>Volunteer History</h1>
<p>This report lets you see the volunteer activity by each member over the past year.</p>

<form action="<?php echo admin_url('edit.php'); ?>" method="get">
<input type="hidden" name="post_type" value="rsvpmaker" /><input type="hidden" name="page" value="rsvp_history" />
Sort by <input type="radio" name="sort" id="sort" value="hours" <?php if(isset($_GET["sort"]) && ($_GET["sort"] == 'hours') ) echo ' checked="checked"' ?>>
<label for="sort">Hours </label>
<input type="radio" name="sort" id="sort" value="name" <?php if(!isset($_GET["sort"]) || ($_GET["sort"] == 'name') ) echo ' checked="checked"'; ?> >
<label for="sort">Name </label>
<br /> Start Date: <input type="text" name="startdate" value="<?php if(isset($_GET["startdate"])) echo $_GET["startdate"]; else echo date('Y-m-d',strtotime('-1 year')); ?>" />
End Date: <input type="text" name="enddate" value="<?php if(isset($_GET["enddate"])) echo $_GET["enddate"]; else echo date('Y-m-d'); ?>" />
<button>Show</button>
</form>
<?php
global $wpdb;

$start = (isset($_GET["startdate"])) ? "'".$_GET["startdate"]."'" : 'DATE_SUB(CURDATE(),INTERVAL 1 YEAR)';
$end = (isset($_GET["enddate"])) ? "'".$_GET["enddate"]."'" : 'CURDATE()';

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, a2.meta_key as role, a2.meta_value as volunteer
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key LIKE '_rsvpv%'  
	 WHERE a1.meta_value > $start AND a1.meta_value <= $end  AND post_status='publish'
	 ORDER BY a1.meta_value ";

$result = $wpdb->get_results($sql);

if($result)
$details = '<table  class="widefat fixed" cellspacing="0">
<thead>
<tr><th>Name</th><th>Role</th><th>date</th><th>Hours</th></tr>
</thead>
<tbody>
';
$volunteers = array();
foreach ($result as $row)
	{
		$member = get_userdata($row->volunteer);
		if(empty($member)) continue;
		$t = strtotime($row->datetime);
		$date = date('l F j h:i A',$t);
		$sql = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_hours".$row->role."'";
		$hours = (int) $wpdb->get_var($sql);
		$role = vmeta_to_role($row->role);
		$details .= sprintf('<tr><td>%s %s</td><td>%s</td><td>%s</td><td>%s</td></tr>',$member->first_name,$member->last_name,$role, $date, $hours);
		$volunteers[$row->volunteer] = (isset($volunteers[$row->volunteer])) ? $volunteers[$row->volunteer] + $hours : $hours;
	}
$details .= '</tbody></table>';

if($volunteers)
foreach($volunteers as $user_id => $hours)
	{
		$user_id = (int) $user_id;
		$member = get_userdata($user_id);
		$index = preg_replace('/[^a-zA-Z]/','',$member->last_name.$member->first_name).$member->user_login.$user_id;
		$name = ( !empty( $member->last_name ) ) ? $member->first_name.' '.$member->last_name : $member->user_login.' (ID#'.$user_id.')';
		if(isset($_GET["sort"]) && ($_GET["sort"] == 'hours') )
			$vhours[$hours] = '<tr><td>'.$name.'</td><td>'.$hours.'</td></tr>';
		else
			$vhours[$index] = '<tr><td>'.$name.'</td><td>'.$hours.'</td></tr>';
	}

if(!empty($vhours)) {
	if(isset($_GET["sort"]) && ($_GET["sort"] == 'hours') )
	krsort($vhours);
else
	ksort($vhours);

echo '<h2>Totals</h2>
<table>
<tr><th>Name</th><th>Hours</th></tr>';
foreach($vhours as $value)
	echo $value;
echo '</table>';
}

echo '<h2>Details</h2>'.$details;

}

function rsvp_vreport () {
?>
<div class="wrap"> 
	<div id="icon-edit" class="icon32"><br /></div>
<h2><?php _e('Volunteer Report','rsvpmaker'); ?></h2> 
<p><em>Members scheduled to volunteer.</em></p>
<?php
$days = (!empty($_POST["days"])) ? $_POST["days"] : 7;
echo sprintf('<form action="%s" method="post">
<p>Show <input type="text" name="days" size="4" value="%s" /> days ahead
<input type="submit" value="Set"  />
</form>
',admin_url('edit.php?post_type=rsvpmaker&page=rsvp_vreport'),$days );
$atts["days"] = $days;
vreport ($atts);
?>
</div>
<?php
}

function vreport ($atts)
{
global $wpdb;
$details = '';
	$days = (empty($atts["days"])) ? 7 : (int) $atts["days"];
$days++; // we want to go past midnight of end date

	$sql = "SELECT DISTINCT $wpdb->posts.ID as postID, $wpdb->posts.*, a1.meta_value as datetime, a2.meta_key as role, a2.meta_value as volunteer
	 FROM ".$wpdb->posts."
	 JOIN ".$wpdb->postmeta." a1 ON ".$wpdb->posts.".ID =a1.post_id AND a1.meta_key='_rsvp_dates'
	 JOIN ".$wpdb->postmeta." a2 ON ".$wpdb->posts.".ID =a2.post_id AND a2.meta_key LIKE '_rsvpv%'  
	 WHERE a1.meta_value > CURDATE() AND a1.meta_value <= DATE_ADD(CURDATE(),INTERVAL $days DAY)  AND post_status='publish'
	 ORDER BY a1.meta_value ";

$result = $wpdb->get_results($sql);
foreach($result as $row)
{
		$member = get_userdata($row->volunteer);
		$t = strtotime($row->datetime);
		$date = date('l F j',$t);
		$role = vmeta_to_role($row->role);
		$timestamp = get_post_meta($row->ID,'_timestamp'.$row->role, true);
		$time = '';
		if(!empty($timestamp))
			$time = date('F j, Y', (int) $timestamp);
		$p = get_post($row->postID);
		$details .= sprintf('<tr><td>%s %s</td><td>%s</td><td>%s</td><td>Recorded: %s</td></tr>',$member->first_name,$member->last_name,$role, $date,$time);
}

return printf('<style>
table#vweek td {
border: 2px solid #CCCCCC; padding: 6px;
}
</style>
<table id="vweek">
%s
</table>',$details);

}


add_shortcode('vreport','vreport');

?>