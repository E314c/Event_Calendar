Event Calendar
v0.1

Project info:
A simple calendar project I came up with for 2 websites I was asked to build.
My primary goal was simplicity: in ideas, in code and in use. 

License info:
I beleive it's the 'GNU public license': Please feel free to fork, update and modify, but release the new code under the same license so that others can do the same.


Database Table Structure:
NAME			TYPE		NOTES
id			int		primary key, auto increment
event_title		text		
datetime_start		datetime	
datetime_end		datetime	
location		text		
description		text		
event_class		text



Current Features:

-Simple php functions to create instances:
	- calendar		-calendar key
	- "new event" form	
-Handles Multiday events
-Most objects can be styled using the CSS style sheet and supplied tags
-Checks for specified table in database. Creates a new one to the specification if it's not found.
-Required user defines all in one file, no need to mess with calendar.php
