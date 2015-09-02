/****************/
/*Event Calendar*/
/*****v0.3.5*****/
/****************/


/***************/
/*Project info:*/
/***************/
A simple calendar project I came up with for 2 websites I was asked to build.
My primary goal was simplicity: in ideas, in code and in use. 


/***************/
/*License info:*/
/***************/
I should really look into the correct license to use, but essentially what I want is this:
-Feel free to use and modify this code for any purpose.
-If you're going to make money off of it, atleast let the people you're selling it to know which bits I made.
-I suggest sharing your modifications of this with others aswell.


/***************************/
/*Database Table Structure:*/
/***************************/
NAME			TYPE		NOTES
id				int			primary key, auto increment
event_title		text		
datetime_start	datetime	
datetime_end	datetime	
location		text		
description		text		
event_class		text


/*******************/
/*Current Features:*/
/*******************/
-Simple php functions to create instances:
	- calendar		-calendar key
	- "new event" form	-"edit event" form
	- event_list
-Handles Multiday events
-Most objects can be styled using the CSS style sheet and supplied tags
-Checks for specified table in database. Creates a new one to the specification if it's not found.
-Required user defines all in one file, no need to mess with calendar.php


/*************/
/*How to use:*/
/*************/

1)Setup the user_defines.php page with the appropriate data and external functions.

2) Refer to example_page.php to see how it works.

Essentially you need to call the inlucde(calendar.php) in the html header (as the code inserts a <link stylesheet> tag)
Make sure you've setup your database connection in the user_defines.
Then just call the various "create" functions to generate the code on your webpage.

During intial setup of the calendar, the "create_event_calendar" function will require a connection with 'CREATE TABLE' 
privileges on the database, but after it has setup the table you can change it to an "VIEW/INSERT/UPDATE" connection