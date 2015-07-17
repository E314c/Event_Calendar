<?php
/*php Event Calendar by E314C*/
/*
Current Version: v0.3
Original source code and license info can be found at: https://github.com/E314c/Event_Calendar
*/


include "calendar_user_defines.php";

//Setup Date_time for calculations
$cur_year = date("Y");  //Current year in 0000 format
$cur_month = date("n"); //Current month in 1-12 format
$cur_day = date("j");   //Current day in 1-31 format
/*********************/
//check DB connection:
if (mysqli_connect_errno($GLOBALS[event_calendar_DB_connection]))
{
	echo "SQL Connection Error: Failed to connect to MySQL: " . mysqli_connect_error($GLOBALS[event_calendar_DB_connection]),"error";
}

//include calendar_style.css
echo '<link rel="stylesheet" href="/'.CALENDAR_DIRECTORY.'/calendar_style.css" type="text/css">';

/*Calendar Functions*/
function check_datetime_format_sql(&$datetime)
//input:string to be checked 
//return: true if string is in sql DATETIME format (YYYY-MM-DD HH:MM:SS)
    //as an extra, this will accept a string missing :ss, but will add zeros for the sql command
{
    if(preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/",$datetime))
        return true;
    else
        if(preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\s([0-1][0-9]|2[0-3]):[0-5][0-9]$/",$datetime))
            {
                $datetime=$datetime.':00';
                return true;
            }
        else
        return false;
}

function format_sql_datetime($sql_datetime)
//input: datetime data in MySQL datetime format (YYYY-MM-DD HH:MM:SS), 
//return: formatted datetime (currently set to format like 10 June 2014 10:31)
//purpose: SQL's datetime format looks a bit cumbersome, so reformat it
{
    $time = strtotime($sql_datetime);
    return (date("d M Y &#160;&#160; H:ia", $time));
}

function url_get_values_month_year($month, $year, $argument)
//input: month, year, argument (next, prev, same, rand)
//output: returns string of $_GET arguements for url ie/ '?month=4&year=2014'
{
    switch($argument)
    {
        case 'next':
            if($month==12)
            {
                $year++;
                $month=1;
            }
            else
                $month++;
        break;
        
        case 'prev':
            if($month==1)
            {
                $year--;
                $month=12;
            }
            else
                $month--;
        break;
        
        case 'same':
        default:
        break;
    }
    return('?month='.$month.'&year='.$year);
}



function generate_get_string($exclusions="")
//purpose: take current get values and create a string for them
//input: names of any GET values to be excluded
{
	$str="";
	if(is_array($exclusions))
	{
		foreach($_GET as $key => $val)
		{
			//check if exlusion
			$flag=1;
			foreach($exclusions as $var)
			{
				if($key==$var)	//if match set flag to 0
					$flag=0;	
			}
			
			if($flag) //only concatenate if not in exclusion
				$str.='&'.$key.'='.$val;
			
		}
	}
	else
	{
	foreach($_GET as $key => $val)
		{
			if($key!=$exclusions) //only concatenate if not in exclusion
				$str.='&'.$key.'='.$val;
		}
	}
	return $str;
}

function get_event_info_by_id($event_id, &$storage_array)
//input: database connection, event id, array to store event data
//output: storage array full of results
//return: results found (1 is okay, 0 or >1 is error)
{
	
	
	$result=mysqli_query($GLOBALS[event_calendar_DB_connection],'SELECT * FROM '.CALENDAR_TABLE.' WHERE id="'.$event_id.'"');
    $num = mysqli_num_rows($result);
	if($num==1)
	{
		$row = mysqli_fetch_array($result);
		foreach($row as $key => $val)
		{
			$storage_array[$key]=$row[$key];
		}
	}
	return $num;
}


function display_event_info()
//input: Connection to database, $_GET[event]
//Output: <div> containing event information 
//notes: At the moment this is just printing out an empty calendar while I get that working.
{
	
	
    if(isset($_GET[event]))
    {
        //retrieve event data
		if(get_event_info_by_id($_GET[event], $event)==1)
		{	
			//display info
			echo '<div class="calendar_event_info_display" style="position:relative;">';
			echo '<div style="position:absolute;top:5px;right:5px;"><a href="?'.generate_get_string("event").'">[X]</a></div>';
			echo '<h2 class="calendar_event_info_display">'.$event[event_title].'</h2>';
			echo '<p class="calendar_event_info_display" id="event_times">Start: '.format_sql_datetime($event[datetime_start]).'<br> Ends: '.format_sql_datetime($event[datetime_end]).'</p>';
			echo '<p class="calendar_event_info_display" id="event_location">Location: '.$event[location].'</p>';
			echo '<p class="calendar_event_info_display" id="event_description">'.str_replace("\n","<br>",$event[description]).'</p>';
			echo '</div>';
		}
		else
		{
			echo '<div class="calendar_event_info_display">Unfortunately no data was found for event "'.$_GET[event].'". Please check your URL for errors.</div>';
		}
    }
}


function calendar_display_month($dis_month,$dis_year)
//input: Connection to database.
//Output: Calendar (as html table) showing selected month's events
//notes: At the moment this is just printing out an empty calendar while I get that working.
{
	
	
    //Data about month/year
    $dis_month_name = date("F", mktime(0, 0, 0, $dis_month, 1, $dis_year));
    $dis_month_length = date("t", mktime(0, 0, 0, $dis_month, 1, $dis_year));
    $link_to_last_month = url_get_values_month_year($dis_month, $dis_year, "prev");
    $link_to_next_month = url_get_values_month_year($dis_month, $dis_year, "next");
    
    
    //calculate display offset
    $offset=date("N", mktime(0, 0, 0, $dis_month, 1, $dis_year))-1; //if we start on monday, offset=0.

    
    echo '<table class="event_calendar">';
    echo '<tr><th><a id="calendar_month_nav" href="'.$link_to_last_month.'">&lt;-</a></th><th colspan="5" id="calendar_title" >'.$dis_month_name.' '.$dis_year.'</th><th><a id="calendar_month_nav" href="'.$link_to_next_month.'">-&gt;</a></th></tr>';
    echo '<tr><th class="event_calendar">Mon</th><th class="event_calendar">Tue</th><th class="event_calendar">Wed</th><th class="event_calendar">Thu</th><th class="event_calendar">Fri</th><th class="event_calendar">Sat</th><th class="event_calendar">Sun</th></tr>';
    echo '<tr>';
    for ($x=1;$x<=($offset+$dis_month_length);$x++) //creates amount of cells dependendent on month length and initial day offset.
    {
        echo '<td ';
        if($x>$offset)
            echo 'class="event_calendar_day"';
        else
            echo 'style="border:none;"';
        
        if($x%7==6||$x%7==0)
            echo ' id="weekend"';
        echo '>';
        if(($x-$offset)>0)
        {
            //print date
            echo '<calendar-datestamp';
            if($x%7==6||$x%7==0)
                echo ' id="weekend"';
            echo '>'.($x-$offset).'</calendar-datestamp>';
            
            //Format date for SQL query
                $loop_sql_date=$dis_year.'-'.str_pad($dis_month,2,"0",STR_PAD_LEFT).'-'.str_pad(($x-$offset),2,"0",STR_PAD_LEFT);  //current loop date in MySQL datetime format (YYYY-MM-DD HH:MM:SS)
                
            //now send MySQL query
            $result=mysqli_query($GLOBALS[event_calendar_DB_connection],'SELECT * FROM '.CALENDAR_TABLE.' WHERE datetime_start < "'.$loop_sql_date.' 23:59:59" AND datetime_end > "'.$loop_sql_date.' 00:00:01"');    //checks for all events that start before or during the day AND ending on or after the day
            
            $num=mysqli_num_rows($result); //number of rows in result
            if($num>0)
            {
                for($y=0;$y<$num;$y++)
                {
                    $event = mysqli_fetch_array($result);
                    echo '<div class="event_calendar_event" id="'.$event[event_class].'">';   //Possibly best not to use a <div>
                    echo '<a class="calendar_event_link" href="'.url_get_values_month_year($dis_month, $dis_year, "same").'&event='.$event[id].'">'.$event[event_title].'</a>';
                    echo '</div>';
                }
            }
        }
        echo '</th>';
        
        //New row at end of week
        if($x%7==0)
            echo '</tr><tr>';
    }
    echo '</tr></table>';
}

function mysqli_table_exists($table)
//input: Connection to database, name of table to look for
//output:true if table exists, false if not
{
	
	echo '<!-- mysql con error:['.mysqli_connect_errno($GLOBALS[event_calendar_DB_connection]).']'.mysqli_connect_error($GLOBALS[event_calendar_DB_connection]).'-->';
	
    $exists = mysqli_query($GLOBALS[event_calendar_DB_connection],"SELECT 1 FROM ".$table." LIMIT 0;");
    if($exists) 
		return true;
    else
		return false;
}

function check_connection()
//input: Connection to database.
//return:true for connection established, false for error.
//Purpose: Checks whether a suitable CALENDAR_TABLE exists in the database. If not, creates a blank one.
{
	
    if (mysqli_connect_errno($GLOBALS[event_calendar_DB_connection]))
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error($GLOBALS[event_calendar_DB_connection]);
        return false;
    }
    if(!mysqli_table_exists(CALENDAR_TABLE))
    {
        echo "Table didn't exist <br>";
        
       //SQL Create table command
        $sql='CREATE TABLE `'.CALENDAR_TABLE.'` (
        id INT( 11 ) NOT NULL AUTO_INCREMENT,
        event_title TEXT COMMENT "Title of the Event",
        datetime_start DATETIME NOT NULL COMMENT "Start date-time of event",
        datetime_end DATETIME NOT NULL COMMENT "End date-time of event",
        location TEXT COMMENT "Event Location",
        description TEXT COMMENT "Description of event",
        event_class TEXT COMMENT "Class of event (for css styling). ie/ public, important, tbc ",
        PRIMARY KEY (id) )';
        
        // Execute query
        if (mysqli_query($GLOBALS[event_calendar_DB_connection],$sql))
        {
            echo "Table ".CALENDAR_TABLE." created successfully";
        }
        else
        {
            echo "Error creating ".CALENDAR_TABLE.": " . mysqli_error($GLOBALS[event_calendar_DB_connection]);
            return false;
        }
    }
    return true;
}

function create_calendar_legend()
//Purpose: prints out a a key of event classes for calendar
{
    echo'<div class="event_calendar_legend">';
    foreach($GLOBALS[calendar_event_classes] as $key => $val)
    {
        echo '<div id="'.$key.'" style="float:left;margin:2px;padding:2px;">'.$val.'</div>';
    }
    echo'</div>';
}

function create_calendar()
//input: Connection to database.
//Output: Calendar (as html table) showing current months events and arrow to change between months
{
	
	
    if(!check_connection())
        exit("Connection Error");
    if(isset($_GET[month])&&isset($_GET[year]))
    {
        //first validate the $_GET values
        if(!(is_numeric($_GET[year])&&is_numeric($_GET[month])&&($_GET[month]>0)&&($_GET[month]<13)&&$_GET[year]>2000&&$_GET[year]<10000))
        {
            echo 'URL Values not within range. Please Correct in address bar<br>';
            echo 'Year data received:'.$_GET[year].'<br>';
            echo 'Month data received:'.$_GET[month];
            return; //exit 'create_calendar()' function
        }
        //if they're okay, proceed to create calendar
        calendar_display_month($_GET[month],$_GET[year]);
    }
    else
    {
        //Display current month
        calendar_display_month($GLOBALS[cur_month],$GLOBALS[cur_year]);
    }
}
function events_db_count()
//returns: number of events on the database
{
	$result = mysqli_query($GLOBALS[event_calendar_DB_connection],'SELECT * FROM '.CALENDAR_TABLE.';');		//get list of all events
	return mysqli_num_rows($result);
}


/*Defines for Event_List*/
{
define("LIST_DISPLAY_GET_ID_LINKS",1<<0);	//List will be formatted with links to ?id=<event_id>
define("LIST_LINKS_PRESERVE_GET_VARS", 1<<1);	//the links in the list will preserve current get values (not default action)
define("LIST_AS_TABLE",1<<2);	//list will be in html table element
define("LIST_AS_<UL>",0<<2);	//list will be in html <ul> element (default action)
}
/************************/
function create_event_list($list_length,$list_start=0,$flags)
//input: Connection to database , list size,list start, flags (as defined above)
//Output: HTML element listing all upcoming events
{
	
	$result = mysqli_query($GLOBALS[event_calendar_DB_connection],'SELECT *, DATE_FORMAT(datetime_start,"%d %b %Y") as "datetime_start_formatted" FROM '.CALENDAR_TABLE.' ORDER BY datetime_start DESC LIMIT '.$list_start.' , '.$list_length.';');		//get a list of events within the target range.
	$res_num = mysqli_num_rows($result);
	
	if($res_num>0)
	{
		if($flags&LIST_AS_TABLE==LIST_AS_TABLE)//if displaying as a table
			echo '<table class="EventCalendar_event_list"><tr><th>Event Title</th><th>Event Date</th></tr>';
		else
			echo '<ul class="EventCalendar_event_list">';
			
		for($i=0;$i<$res_num;$i++)
		{
			
			//beginning of each list object
			$event = mysqli_fetch_array($result);
			if($flags&LIST_AS_TABLE==LIST_AS_TABLE)//if displaying as a table
					echo '<tr><td class="EventCalendar_event_list">';
			else
					echo '<li>';
			
			
			if($flags&LIST_DISPLAY_GET_ID_LINKS==LIST_DISPLAY_GET_ID_LINKS) //if we're adding links
			{
				echo '<a href="?';
				if($flags&LIST_LINKS_PRESERVE_GET_VARS==LIST_LINKS_PRESERVE_GET_VARS) //if we're preserving get values.
					echo generate_get_string().'&';
				
				echo 'id='.$event[id].'">';
			}
			
			//Echo the event title
			echo $event[event_title];
			
			if($flags&LIST_DISPLAY_GET_ID_LINKS==LIST_DISPLAY_GET_ID_LINKS)
				echo '</a>';
			
			if($flags&LIST_AS_TABLE==LIST_AS_TABLE)//if displaying as a table
				echo'</td><td class="EventCalendar_event_list">';
			else	
				echo ' \t ';
			
			//echo the event start time
			echo $event[datetime_start_formatted];
			
			//finish off list item
			if($flags&LIST_AS_TABLE==LIST_AS_TABLE)//if displaying as a table
				echo'</td></tr>';
			else
				echo '</li>';
		}//end of for loop
		
		if($flags&LIST_AS_TABLE==LIST_AS_TABLE)//if displaying as a table
			echo '</table>';
		else
			echo '</ul>';
	
	}//end of "if($res_num>1)"
}

function text_cleaner($str, $clean_spec)
//input: a piece of text to be cleaned and what kind of clean (possible cleans: event_title, event_description, htmlspecialchars)
//return: a string, cleaned and formatted as required
//purpose: to allow more dynamic str cleaning (because sometimes I need this code to allow me to write <a></a> tags into descriptions, but not titles)
{
	//Specific cleans
	switch($clean_spec)
	{
		case 'event_description':
			if(!defined("EVENT_DESCRIPTION_ALLOW_ALL_HTML")) //These conversions are for HTML, if we're allowing all, we don't need them
			{
				//incase the text comes in all silly escaped.
				$str=str_replace(array('\"',"\'"),array('"','"'),$str);
				
				$match_num=preg_match_all('#</{0,1}[\w]{1,}[^>]{0,}>#',$str,$matches,PREG_PATTERN_ORDER); //match all possible html tags
				if($match_num>0)
				{
					for($i=0;$i<$match_num;$i++)
					{
						$replacement[$i] = htmlspecialchars($matches[0][$i],ENT_QUOTES);
						$matches[0][$i]='#'.$matches[0][$i].'#'; //add new string encapsulation for next preg_replace (else it uses, and thus stips, the <> around the tag)
					}
					ksort($matches[0]); //first ksort
					ksort($replacement);
					$str=preg_replace($matches[0],$replacement,$str,PREG_PATTERN_ORDER);
				}
				$match_num=preg_match_all('#</{0,1}[abui](?![ >])[^>]{0,}>#',$str,$matches,PREG_PATTERN_ORDER); //match abui tags where next character isn't space or >
				if($match_num>0)
				{
					for($i=0;$i<$match_num;$i++)
					{
						$replacement[$i] = htmlspecialchars($matches[0][$i],ENT_QUOTES);
						$matches[0][$i]='#'.$matches[0][$i].'#'; //add new string encapsulation for next preg_replace (else it uses, and thus stips, the <> around the tag)
					}
					ksort($matches[0]); //first ksort
					ksort($replacement);
					$str=preg_replace($matches[0],$replacement,$str,PREG_PATTERN_ORDER);
				}
				
				//de-convert hyperlinks
				if(defined("EVENT_DESCRIPTION_ALLOW_HYPERLINKS"))
				{	//find all &lt;a(...)&gt; instances and convert back to <a(...)>
					$match_num=preg_match_all('#(&lt;)/{0,1}a(\s[\w\s="\-&/\?;:\d\.\\\\](?:(?!&gt;).){0,}){0,}(&gt;)#',$str,$matches,PREG_PATTERN_ORDER);
					if($match_num>0)
					{
						for($i=0;$i<$match_num;$i++)
						{
							$replacement[$i] = str_replace($GLOBALS[html_special_chars][replacements],$GLOBALS[html_special_chars][chars],$matches[0][$i]); //use the global defined html_special_chars
							$matches[0][$i]='#'.$matches[0][$i].'#'; //add new string encapsulation for next preg_replace (else it uses, and thus stips, the <> around the tag)
						}
						ksort($matches[0]); //ksort to make sure it's in the right order.
						ksort($replacement);
						$str=preg_replace($matches[0],$replacement,$str,PREG_PATTERN_ORDER);
					}
				}
				
				//de-convert BUI tags
				if(defined("EVENT_DESCRIPTION_ALLOW_B_U_I"))
				{
					$match_num=preg_match_all('#(&lt;)/{0,1}[bui](\s[\w\s="\-&/\?;:\d\.\\\\](?:(?!&gt;).){0,}){0,}(&gt;)#',$str,$matches,PREG_PATTERN_ORDER);
					if($match_num>0)
					{
						for($i=0;$i<$match_num;$i++)
						{
							$replacement[$i] = str_replace($GLOBALS[html_special_chars][replacements],$GLOBALS[html_special_chars][chars],$matches[0][$i]); //use the global defined html_special_chars
							$matches[0][$i]='#'.$matches[0][$i].'#'; //add new string encapsulation for next preg_replace (else it uses, and thus stips, the <> around the tag)
						}
						ksort($matches[0]); //ksort to make sure it's in the right order.
						ksort($replacement);
						$str=preg_replace($matches[0],$replacement,$str,PREG_PATTERN_ORDER);
					}
				}
			}	
			break;
		
		case 'event_title':
		default:
			$str=trim(stripslashes(htmlspecialchars($str,ENT_QUOTES)));
	}
	
	//Global conversions and return: 
	$str=str_replace('\\',"\\\\",$str);
	return str_replace("'","\'",$str); //always replace apostrophes as it's used in the SQL command
}


function validate_post_data(&$data,$mode)
//input: data to be posted, mode (update,insert)
//return: true for success, else error code (string)
//purpose: checks data and formats data in the parse array
{
    //check id for update posts
    if($mode=='update'||$mode=='delete')
	{
		if($data[id]==''||(!isset($data[id])))	//if not set or blank
			return("Event ID not present in data, cannot UPDATE/DELETE without ID.");
			
		if(!is_numeric($data[id]))		//if it's not numeric
			return("Event ID not numeric, cannot UPDATE/DELETE without valid ID.");
			
	}
    if($mode=='delete') //this is all that needs to be checked for delete is the ID 
		return true;
	
    //check and re-format the data
    $data[event_title]	=text_cleaner($data[event_title],'event_title');   
    $data[description]	=text_cleaner($data[description],'event_description');
    $data[location]		=text_cleaner($data[location],"");
    $data[event_class]	=text_cleaner($data[event_class],"");
    
    //check date
    if(!(check_datetime_format_sql($data[datetime_start])&&check_datetime_format_sql($data[datetime_end])))
        return("Date is in the wrong format.Use YYYY-MM-DD hh:mm:ss");
	if(!(checkdate(date("n",strtotime($data[datetime_start])),date("j",strtotime($data[datetime_start])),date("Y",strtotime($data[datetime_start])))))
		return("Start Date '".$data[datetime_start]."' is not a valid date");
	if(!(checkdate(date("n",strtotime($data[datetime_end])),date("j",strtotime($data[datetime_end])),date("Y",strtotime($data[datetime_end])))))
		return("End Date '".$data[datetime_start]."' is not a valid date");
	
	
    return true;
}



function post_event_data($data,$mode)
//input: Connection to database, data to be posted, mode (update,insert)
//return: true for success, else error code (string)
//purpose: Posts data to event_table 
{
	
    if(event_calendar_authenication($data))
    {
        if(mysqli_connect_errno($GLOBALS[event_calendar_DB_connection]))
        {
            return("Failed to connect to MySQL: " . mysqli_connect_error());
        }
        else
        {
            switch($mode)
            {
                case 'insert':
                    $result= mysqli_query($GLOBALS[event_calendar_DB_connection], "INSERT INTO `".CALENDAR_TABLE."`
                                                    (`id` , `event_title`               , `datetime_start`              , `datetime_end`              , `location`				, `description`             , `event_class`           )
                                            VALUES  (NULL , '".$data[event_title]."'    , '".$data[datetime_start]."'   , '".$data[datetime_end]."'   , '".$data[location]."'	, '".$data[description]."'  , '".$data[event_class]."');
                                                "
                                         );
                    break;
                case 'update':
                    $result= mysqli_query($GLOBALS[event_calendar_DB_connection],"UPDATE `".CALENDAR_TABLE."`
                                                SET     event_title='".$data[event_title]."',
                                                        datetime_start='".$data[datetime_start]."',
                                                        datetime_end='".$data[datetime_end]."',
                                                        location='".$data[location]."',
                                                        description='".$data[description]."',
                                                        event_class='".$data[event_class]."'
                                                WHERE   id=".$data[id].";");
                    break;
				case 'delete':
					$result= mysqli_query($GLOBALS[event_calendar_DB_connection],"DELETE FROM `".CALENDAR_TABLE."`
                                                WHERE   id=".$data[id].";");
				
					break;
                default:
                    return("Database method not recognised.");
                break;
            }
            
            if($result)
            {
                return true;
            }
            else
            {
                return("Error posting to database. Please contact E314c");
            }
        }
    }
    else
    {
        return("Authentification Failed, no data posted");
    }
}
function create_new_event_form()
//input: Connection to database 
//Output: HTML form to submit data to database
{
	
    echo '<div class="calendar_new_event_form">';
    //the form handling
    if ($_SERVER['REQUEST_METHOD'] == 'POST'&&isset($_POST[event_calendar_new_submit]))
    {
		//concatenate the date together
		$_POST[datetime_start]=$_POST[datetime_start_year].'-'.$_POST[datetime_start_month].'-'.$_POST[datetime_start_day].' '.$_POST[datetime_start_hour].':'.$_POST[datetime_start_mins];
		$_POST[datetime_end]=$_POST[datetime_end_year].'-'.$_POST[datetime_end_month].'-'.$_POST[datetime_end_day].' '.$_POST[datetime_end_hour].':'.$_POST[datetime_end_mins];
		
        echo '<div class="event_calendar_notification"';
        if(is_string($error=validate_post_data($_POST,'insert'))) //validate data
            echo '>'.$error;
        else
        {
            if(is_string($error=post_event_data($_POST,'insert'))) //post data
                echo '>'.$error;
            else
                {
                    //unset post data
                    /*//Originally I had the code unset all $_POST variables, but whilst adding in 20+ events for a website, I decided I just wanted the title, location and description to be cleared
					foreach($_POST as $key => $val)
                    {
                        unset($_POST[$key]);
                    }
					*/
					{
					unset($_POST[event_title]);
					unset($_POST[description]);
					unset($_POST[location]);
					unset($_POST[pass]);
					}
                    echo ' id="event_calendar_notification_data_correct">Data Posted Correctly';
                }
        }
        echo '</div>';
    }
	
	//Some default values for the form:
		//day
	if(!isset($_POST[datetime_start_day]))
		$_POST[datetime_start_day]=date('d');
	if(!isset($_POST[datetime_end_day]))
		$_POST[datetime_end_day]=date('d');
		//month
	if(!isset($_POST[datetime_start_month]))
		$_POST[datetime_start_month]=date('m');
	if(!isset($_POST[datetime_end_month]))
		$_POST[datetime_end_month]=date('m');
		//year
	if(!isset($_POST[datetime_start_year]))
		$_POST[datetime_start_year]=date('Y');
	if(!isset($_POST[datetime_end_year]))
		$_POST[datetime_end_year]=date('Y');
		
	
    
    //actual form
    echo '<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?'.generate_get_string().'" method="post" enctype="application/x-www-form-urlencoded;charset=UTF-8"><table class="calendar_layout_table">';
    //Event title
	echo '<tr><td class="calendar_layout_table">Event Title:</td><td class="calendar_layout_table"><input type="text" name="event_title" value="'.$_POST[event_title].'"></td></tr>';
    //Event Class
	echo '<tr><td class="calendar_layout_table">Event Type:</td><td class="calendar_layout_table"><select name="event_class">';
    foreach($GLOBALS[calendar_event_classes] as $key => $val)
    {
        echo '<option value="'.$key.'"';
        if($_POST[event_class]==$key)
            echo' selected="selected"';
        echo'>'.$val.'</option>';
    }
    echo '</select></td></tr>';
	//Start datetime
    echo '<tr><td class="calendar_layout_table">Start Time:</td><td class="calendar_layout_table">';
		echo '<select name="datetime_start_hour">';
		for($x=0;$x<24;$x++)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_start_hour]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
		}
		echo '</select>&nbsp;:&nbsp;<select name="datetime_start_mins">';
		for($x=0;$x<60;)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_start_mins]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
                        //step by minute accuracy level
                        $x+=INPUT_FORM_MINUTE_ACCURACY;
		}
		echo '</select>&nbsp;&nbsp;&nbsp;';
		
		echo ' Date:&nbsp;<select name="datetime_start_day">';
		for($x=1;$x<32;$x++)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_start_day]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
		}
		echo '</select>&nbsp;-&nbsp;<select name="datetime_start_month">';
		for($x=1;$x<13;$x++)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_start_month]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.date("M",mktime(0,0,0,$x,1,2000)).'</option>';
		}
		echo '</select>&nbsp;-&nbsp;';
		echo '<input type="text" name="datetime_start_year" maxlength="4" value="'.$_POST[datetime_start_year].'">';
	echo	'</td></tr>';
	//end datetime
	    echo '<tr><td class="calendar_layout_table">End Time:</td><td class="calendar_layout_table">';
		echo '<select name="datetime_end_hour">';
		for($x=0;$x<24;$x++)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_end_hour]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo '>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
		}
		echo '</select>&nbsp;:&nbsp;<select name="datetime_end_mins">';
                $y=0;
		for($x=0;$x<60;)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_end_mins]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
                        //step by minute accuracy level
                        $x+=INPUT_FORM_MINUTE_ACCURACY;
		}
		echo '</select>&nbsp;&nbsp;&nbsp;';
		
		echo ' Date:&nbsp;<select name="datetime_end_day">';
		for($x=1;$x<32;$x++)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_end_day]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
		}
		echo '</select>&nbsp;-&nbsp;<select name="datetime_end_month">';
		for($x=1;$x<13;$x++)
		{
			echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
			if($_POST[datetime_end_month]==str_pad($x,2,"0",STR_PAD_LEFT))
				echo' selected="selected"';
			echo'>'.date("M",mktime(0,0,0,$x,1,2000)).'</option>';
		}
		echo '</select>&nbsp;-&nbsp;';
		echo '<input type="text" name="datetime_end_year" maxlength="4" value="'.$_POST[datetime_end_year].'">';
	echo	'</td></tr>';
	//Location
    echo '<tr><td class="calendar_layout_table">Location: </td><td class="calendar_layout_table"><input type="text" name="location" value="'.$_POST[location].'"></td></tr>';
	//Description
    echo '<tr><td class="calendar_layout_table">Event Description:</td><td class="calendar_layout_table"><textarea name="description" rows="5" cols="40">'.$_POST[description].'</textarea></td></tr>';
	//User authenication part:
	global $authenication_fields;
	foreach($authenication_fields as $key => $val)
	{
		echo '<tr><td class="calendar_layout_table">'.$key.':</td><td class="calendar_layout_table"><input type="'.$val[type].'" name="'.$val[varname].'"></td></tr>';
	}
	//Submit
    echo '<tr><td class="calendar_layout_table" colspan="2"><input type="submit" name="event_calendar_new_submit" value="Submit"></td></tr>';
    echo '</table></form></div>';
}

function create_event_edit_form()
//input: connection to database
//output: HTML form that allows editing of current events
{
	echo '<div class="calendar_edit_event_form">';
	if(isset($_POST[event_calendar_cancel_submit]))
	{
		unset($_GET[id]);
		unset($_POST);
	}
    //the form handling
    if ($_SERVER['REQUEST_METHOD'] == 'POST'&&isset($_POST[event_calendar_edit_submit]))
    {
		//debug
		$temp=$_POST[description];
		if($_POST[delete_event]=='y')
		{
			echo '<div class="event_calendar_notification"';
			if(is_string($error=validate_post_data($_POST,'delete'))) //validate data
				echo '>'.$error;
			else
			{
				if(is_string($error=post_event_data($_POST,'delete'))) //post data
					echo '>'.$error;
				else
					echo ' id="event_calendar_notification_data_correct">Event Deleted';
				unset($_GET[id]);
			}
			echo '</div>';
		}
		else
		{
			//concatenate the date together
			$_POST[datetime_start]=$_POST[datetime_start_year].'-'.$_POST[datetime_start_month].'-'.$_POST[datetime_start_day].' '.$_POST[datetime_start_hour].':'.$_POST[datetime_start_mins];
			$_POST[datetime_end]=$_POST[datetime_end_year].'-'.$_POST[datetime_end_month].'-'.$_POST[datetime_end_day].' '.$_POST[datetime_end_hour].':'.$_POST[datetime_end_mins];
			
			echo '<div class="event_calendar_notification"';
			if(is_string($error=validate_post_data($_POST,'update'))) //validate data
				echo '>'.$error;
			else
			{
				if(is_string($error=post_event_data($_POST,'update'))) //post data
					echo '>'.$error;
				else
					echo ' id="event_calendar_notification_data_correct">Data Posted Correctly';
			}
			echo '</div>';
		}
    }
    
	if(isset($_GET[id])&&is_numeric($_GET[id])) //if the event for editing has been selected.
	{
		//get event data.
		get_event_info_by_id($_GET[id], $_POST);
		
		//de-concatenate date
		{
			//start time
			$start=explode(" ",$_POST[datetime_start]);
			$start_date=explode("-",$start[0]);
			$start_time=explode(":",$start[1]);
			$_POST[datetime_start_year]=$start_date[0];		$_POST[datetime_start_month]=$start_date[1];	$_POST[datetime_start_day]=$start_date[2];
			$_POST[datetime_start_hour]=$start_time[0];		$_POST[datetime_start_mins]=$start_time[1];

			
			//end time
			$end=explode(" ",$_POST[datetime_end]);
			$end_date=explode("-",$end[0]);
			$end_time=explode(":",$end[1]);
			$_POST[datetime_end_year]=$end_date[0];		$_POST[datetime_end_month]=$end_date[1];	$_POST[datetime_end_day]=$end_date[2];
			$_POST[datetime_end_hour]=$end_time[0];		$_POST[datetime_end_mins]=$end_time[1];
		}
		
		//actual form
		echo '<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?'.generate_get_string().'" method="post" ><table class="calendar_layout_table">';
		echo '<input type="hidden" name="id" value="'.$_GET[id].'">';
		//Event title
		echo '<tr><td class="calendar_layout_table">Event Title:</td><td class="calendar_layout_table"><input type="text" name="event_title" value="'.$_POST[event_title].'"></td></tr>';
		//Event Class
		echo '<tr><td class="calendar_layout_table">Event Type:</td><td class="calendar_layout_table"><select name="event_class">';
		foreach($GLOBALS[calendar_event_classes] as $key => $val)
		{
			echo '<option value="'.$key.'"';
			if($_POST[event_class]==$key)
				echo' selected="selected"';
			echo'>'.$val.'</option>';
		}
		echo '</select></td></tr>';
		//Start datetime
		echo '<tr><td class="calendar_layout_table">Start Time:</td><td class="calendar_layout_table">';
			echo '<select name="datetime_start_hour">';
			for($x=0;$x<24;$x++)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_start_hour]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
			}
			echo '</select>&nbsp;:&nbsp;<select name="datetime_start_mins">';
			for($x=0;$x<60;)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_start_mins]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
							//step by minute accuracy level
							$x+=INPUT_FORM_MINUTE_ACCURACY;
			}
			echo '</select>&nbsp;&nbsp;&nbsp;';
			
			echo ' Date:&nbsp;<select name="datetime_start_day">';
			for($x=1;$x<32;$x++)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_start_day]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
			}
			echo '</select>&nbsp;-&nbsp;<select name="datetime_start_month">';
			for($x=1;$x<13;$x++)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_start_month]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.date("M",mktime(0,0,0,$x,1,2000)).'</option>';
			}
			echo '</select>&nbsp;-&nbsp;';
			echo '<input type="text" name="datetime_start_year" maxlength="4" value="'.$_POST[datetime_start_year].'">';
		echo	'</td></tr>';
		//end datetime
			echo '<tr><td class="calendar_layout_table">End Time:</td><td class="calendar_layout_table">';
			echo '<select name="datetime_end_hour">';
			for($x=0;$x<24;$x++)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_end_hour]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo '>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
			}
			echo '</select>&nbsp;:&nbsp;<select name="datetime_end_mins">';
					$y=0;
			for($x=0;$x<60;)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_end_mins]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
							//step by minute accuracy level
							$x+=INPUT_FORM_MINUTE_ACCURACY;
			}
			echo '</select>&nbsp;&nbsp;&nbsp;';
			
			echo ' Date:&nbsp;<select name="datetime_end_day">';
			for($x=1;$x<32;$x++)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_end_day]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.str_pad($x,2,"0",STR_PAD_LEFT).'</option>';
			}
			echo '</select>&nbsp;-&nbsp;<select name="datetime_end_month">';
			for($x=1;$x<13;$x++)
			{
				echo '<option value="'.str_pad($x,2,"0",STR_PAD_LEFT).'"';
				if($_POST[datetime_end_month]==str_pad($x,2,"0",STR_PAD_LEFT))
					echo' selected="selected"';
				echo'>'.date("M",mktime(0,0,0,$x,1,2000)).'</option>';
			}
			echo '</select>&nbsp;-&nbsp;';
			echo '<input type="text" name="datetime_end_year" maxlength="4" value="'.$_POST[datetime_end_year].'">';
		echo	'</td></tr>';
		//Location
		echo '<tr><td class="calendar_layout_table">Location: </td><td class="calendar_layout_table"><input type="text" name="location" value="'.$_POST[location].'"></td></tr>'."\n";
		//Description
		echo '<tr><td class="calendar_layout_table">Event Description:</td><td class="calendar_layout_table"><textarea name="description" rows="5" cols="40">'.$_POST[description].'</textarea></td></tr>'."\n";
		echo '<tr><td class="calendar_layout_table">Delete event?</td><td class="calendar_layout_table"><input type="checkbox" name="delete_event" value="y"></td></tr>'."\n";
		//User authenication part:
		global $authenication_fields;
		foreach($authenication_fields as $key => $val)
		{
			echo '<tr><td class="calendar_layout_table">'.$key.':</td><td class="calendar_layout_table"><input type="'.$val[type].'" name="'.$val[varname].'"></td></tr>';
		}
		//Submit
		echo '<tr><td class="calendar_layout_table" ><input type="submit" name="event_calendar_edit_submit" value="Submit"></td><td><input type="submit" name="event_calendar_cancel_submit" value="Cancel"></td></tr>'."\n";
		
		echo '</table></form>';
	}//end of 'if(isset($_GET[id])'
	else //default to showing off list of events.
	{
		echo 'Please select the event you would like to edit:'."<br>\n";
		
		//links forward and back a page	
		if($_GET[list_page]!=0)
			echo '<a href="?'.generate_get_string().'&list_page='.($_GET[list_page]-1).'">Previous page</a>&nbsp;&nbsp;';
		if((($_GET[list_page]+1)*EDIT_EVENT_LIST_LENGTH)<events_db_count($GLOBALS[event_calendar_DB_connection]))
		{//only display next page if the full data list hasn't been reached
			echo "\t\t".'Displaying '.(($_GET[list_page]*EDIT_EVENT_LIST_LENGTH)+1).' - '.(($_GET[list_page]+1)*EDIT_EVENT_LIST_LENGTH)."\t\t";
			echo '&nbsp;&nbsp;<a href="?'.generate_get_string().'&list_page='.($_GET[list_page]+1).'">Next Page</a>'."\n";
		}
		else
		{ //if the final chunk of data is on screen, don't show next page
			echo "\t\t".'Displaying '.(($_GET[list_page]*EDIT_EVENT_LIST_LENGTH)+1).'-'.events_db_count($GLOBALS[event_calendar_DB_connection]);
		}
		
		
		//list of events
		create_event_list(EDIT_EVENT_LIST_LENGTH,($_GET[list_page]*EDIT_EVENT_LIST_LENGTH),(LIST_DISPLAY_GET_ID_LINKS|LIST_LINKS_PRESERVE_GET_VARS|LIST_AS_TABLE));
	}
	echo '</div>'; //end of <div class="calendar_edit_event_form">
}



//Misc GLOBALS
$html_special_chars = array(
	'chars' => array('<','>',"'",'"','&'),
	'replacements' => array('&lt;','&gt;','&apos;','&quot;','&amp;')
);
?>