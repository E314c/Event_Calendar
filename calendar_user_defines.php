<?php
/**USER DEFINES**/
define("CALENDAR_TABLE","event_calendar");          //name of table for data.
define("CALENDAR_DIRECTORY","event_calendar");      //directory where the this calendar code is stored (relative to root) [no leading or trailing slashes please]
$event_calendar_DB_connection=mysqli_connect("localhost","db_user","db_pass","database");	//define the database connection.


define("INPUT_FORM_MINUTE_ACCURACY",15);            //Accuracy/Step for minutes in "create_event_form". ie/ 15 means events can only start/end at hh:00, hh:15, hh:30, hh:45
define("EDIT_EVENT_LIST_LENGTH",15);				//default length for list of events created in "edit event" form

$authenication_fields=array( //an array of additional form fields needed for authentication in format "name of field"=>("type","variable name")
'User name'=>array('type'=>'text', 'varname'=>'user_name'),
'Password'=>array('type'=>'password', 'varname'=>'password')
);

function event_calendar_authenication($data)
//purpose: checks whether user is okay to post data to database
// MUST BE DEFINED TO RETURN TRUE IF AUTHENTICATED
{
	//Example authentication function
	if($data['user_name']==$data['password'])
		return true;
	else
		return false;
}


/***************/

/**USER OPTIONS**/
//comment or uncomment these defines to change how events are handled
//define("EVENT_DESCRIPTION_ALLOW_HYPERLINKS",1);	//allow hyperlinks in event description
//define("EVENT_DESCRIPTION_ALLOW_B_U_I",1);		//allow <b>,<u> or <i> tags in event description
//define("EVENT_DESCRIPTION_ALLOW_ALL_HTML",1);		//allow all html tags in event description (this can be dangerous)

/****************/

/***Global Variables **/
//Array of Users event_classes
$calendar_event_classes = array(
                                //class tag => class name (for display)
                                'event_1' => "Public Event",
                                'event_2' => "Members Only",
                                'event_3' => "event_3 description",
                                );

?>