/****************/
/*Event Calendar*/
/******v0.2******/
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
-I suggest sharing your modifications of this with others aswell, but 


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
-Handles Multiday events
-Most objects can be styled using the CSS style sheet and supplied tags
-Checks for specified table in database. Creates a new one to the specification if it's not found.
-Required user defines all in one file, no need to mess with calendar.php


/*************/
/*How to use:*/
/*************/

Please see example_page.php

Essentially you need to call the inlucde(calendar.php) in the html header (as the code inserts a <link stylesheet> tag)
Make sure you've got your database connection established with mysqli_connect()
Then just call the various "create" functions to generate the code on your webpage.

During intial setup of the calendar, the "create_event_calendar" function will require a connection with 'CREATE TABLE' 
priviledges on the database, but after it has setup the table you can change it to a 'SELECT' only connection