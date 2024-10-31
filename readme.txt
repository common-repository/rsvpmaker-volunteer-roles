=== RSVPMaker Volunteer Roles ===
Contributors: davidfcarr
Donate: http://www.rsvpmaker.com
Tags: event, calendar, rsvp, volunteer
Donate link: http://rsvpmaker.com/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.0
Tested up to: 5.8.1
Stable tag: 1.5.1

A volunteer tracking add-on to RSVPMaker

== Description ==

RSVPMaker Volunteer Roles extends the calendar and attendee signup functions of the [RSVPMaker](http://wordpress.org/extend/plugins/rsvpmaker) plugin. The scenario here is to allow registered users of a website, who have at least subscriber rights, to sign up for specific volunteer roles.

Whereas RSVPMaker is usually used to sign up anonymous users on the public website, with RSVPMaker Volunteer Roles we pull names from the user profiles rather than having users type them in.

Users can easily sign up for multiple roles with a few clicks, without having to submit a form. Administrators can then pull reports on who is signed up for upcoming events and track hours volunteered in past events.

Roles are specified in the body of the event post using this shortcode format:

[rsvpvolunteer role="ROLE NAME AND TIMESLOT HERE" hours="2" count="1"]

The hours parameter (for tracking volunteer hours) and the count parameter (1 or multiple people needed to volunteer for this role) are optional. The defaults are 2 hours, 1 instance of the role.

To display an interactive calendar that shows upcoming volunteer openings (but no other event details), you can use the shortcode

[volunteer_calendar]

To display a summary of volunteers signed up for the coming week on the public website, you can use this shortcode
[volunteer report]

or for a different period such as a month

[volunteer_report days="30"]

The same report can be accessed from the Volunteer Report menu item on the administrator's dashboard.

This is a generalized version of role signup techniques also used by the [RSVPMaker for Toastmasters](http://wordpress.org/extend/plugins/rsvpmaker-for-toastmasters) plugin.

Plugin home page: [__RSVPMaker.com__](http://www.rsvpmaker.com/)

== Installation ==

1. Upload the entire `rsvpmaker-volunteer-roles` folder to the `/wp-content/plugins/` directory. RSVPMaker should be installed first.
1. Activate the plugin through the 'Plugins' menu in WordPress.

For basic usage, you can also have a look at the [plugin homepage](http://www.rsvpmaker.com/).

== Screenshots ==

1. Signup buttons on the daily schedule for a rifle range.

== Credits ==

    RSVPMaker
    Copyright (C) 2015David F. Carr

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    See the GNU General Public License at <http://www.gnu.org/licenses/gpl-2.0.html>.
	
== Changelog ==

= 1.5 =

* Fix withdraw message.

= 1.4.8 =

* Updating code that was out of sync with RSVPMaker base plugin

= 1.4.6 =

* Shows date signup was recorded on the Volunteer Report screen
* Signup slots from the past are tagged with class="past" or in the current month view class="past currentviewpast" -- making it possible to hide the "old" opportunities with a CSS display: none rule 

= 1.4.1 =

Compatibility with changes in RSVPMaker date formatting

= 1.2 =

Fix to calendar display

= 1.0 =

First release