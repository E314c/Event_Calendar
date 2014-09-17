<?php
include "calendar_user_defines.php";
//Setup Date_time for calculations
$cur_year = date("Y");  //Current year in 0000 format
$cur_month = date("n"); //Current month in 1-12 format
$cur_day = date("j");   //Current day in 1-31 format
/*********************/

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
                $year++;
            $month++;
        break;
        
        case 'prev':
            if($month==1)
                $year--;
            $month--;
        break;
        
        case 'same':
        default:
        break;
    }
    return('?month='.$month.'&year='.$year);
}



function generate_get_string()
//purpose: takes all the url's $_GET data and outputs a string to add to url. This means we can maintain _GET values where needed
{
    $url_string = '?';
    foreach($_GET as $key=>$val)
    {
        $url_string = $url_string.$key.'='.$val;
    }
    return($url_string);
}

function display_event_info($db_connection)
//input: Connection to database, $_GET[event]
//Output: <div> containing event informatino 
//notes: At the moment this is just printing out an empty calendar while I get that working.
{
    if(isset($_GET[event]))
    {
        //retreive event data
        $result=mysqli_query($db_connection,'SELECT * FROM '.CALENDAR_TABLE.' WHERE id="'.$_GET[event].'"');
        $event = mysqli_fetch_array($result);
        
        //display info
        echo '<div class="calendar_event_info_display">';
        echo '<h2 class="calendar_event_info_display">'.$event[event_title].'</h2>';
        echo '<p class="calendar_event_info_display" id="event_times">Start: '.format_sql_datetime($event[datetime_start]).'<br> Ends: '.format_sql_datetime($event[datetime_end]).'</p>';
        echo '<p class="calendar_event_info_display" id="event_location">Location: '.$event[location].'</p>';
        echo '<p class="calendar_event_info_display" id="event_description">'.$event[description].'</p>';
        echo '</div>';
    }
}


function calendar_display_month($con,$dis_month,$dis_year)
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
            //leading 0's for date
            if(($x-$offset)<10)
                $loop_day='0'.($x-$offset);
            else
                $loop_day=($x-$offset);
            //leading 0's on month
            if($dis_month>9)
                $loop_sql_date=$dis_year.'-'.$dis_month.'-'.$loop_day;  //current loop date in MySQL datetime format (YYYY-MM-DD HH:MM:SS)
            else
                $loop_sql_date=$dis_year.'-0'.$dis_month.'-'.$loop_day; //current loop date in MySQL datetime format (YYYY-MM-DD HH:MM:SS)
                
            //now send MySQL query
            $result=mysqli_query($con,'SELECT * FROM '.CALENDAR_TABLE.' WHERE datetime_start < "'.$loop_sql_date.' 23:59:59" AND datetime_end > "'.$loop_sql_date.' 00:00:01"');    //checks for all events that start before or during the day AND ending on or after the day
            
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

function mysqli_table_exists($con,$table)
//input: Connection to database, name of table to look for
//output:true if table exists, false if not
{
    $exists = mysqli_query($con,"SELECT 1 FROM $table LIMIT 0");
    if ($exists) return true;
    else
    return false;
}

function check_connection($con)
//input: Connection to database.
//return:true for connection established, false for error.
//Purpose: Checks whether a suitable CALENDAR_TABLE exists in the database. If not, creates a blank one.
{
    if (mysqli_connect_errno($con))
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        return false;
    }
    if(!mysqli_table_exists($con, CALENDAR_TABLE))
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
        if (mysqli_query($con,$sql))
        {
            echo "Table ".CALENDAR_TABLE." created successfully";
        }
        else
        {
            echo "Error creating ".CALENDAR_TABLE.": " . mysqli_error($con);
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

function create_calendar($db_connection)
//input: Connection to database.
//Output: Calendar (as html table) showing current months events and arrow to change between months
{
    if(!check_connection($db_connection))
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
        calendar_display_month($db_connection,$_GET[month],$_GET[year]);
    }
    else
    {
        //Display current month
        calendar_display_month($db_connection,$GLOBALS[cur_month],$GLOBALS[cur_year]);
    }
}

function create_event_list($db_connection,$list_length)
//input: Connection to database , list size
//Output: HTML Table listing all upcoming events
{
    /*I'll get around to adding this if I feel it's necessary. At the moment it's just an idea*/
}


function validate_post_data(&$data,$mode)
//input: data to be posted, mode (update,insert)
//return: true for success, else error code (string)
//purpose: checks data and formats data in the parse array
{
    //check id for update posts
    if($mode=='update'&&$data[id]=='')
        return("Event ID not present, cannot UPDATE without ID.");
    
    //check and re-format the data
    $data[event_title]=trim(stripslashes(htmlspecialchars($data[event_title])));;   
    $data[description]=trim(stripslashes(htmlspecialchars($data[description],ENT_QUOTES)));;
    $data[location]=trim(stripslashes(htmlspecialchars($data[location],ENT_QUOTES)));;
    $data[event_class]=trim(stripslashes(htmlspecialchars($data[event_class])));;
    
    //check date
    if(!(check_datetime_format_sql($data[datetime_start])&&check_datetime_format_sql($data[datetime_end])))
    {
        return("Date is in the wrong format.Use YYYY-MM-DD hh:mm:ss");
    }
    return true;
}



function post_event_data($con,$data,$mode)
//input: Connection to database, data to be posted, mode (update,insert)
//return: true for success, else error code (string)
//purpose: Posts data to event_table 
{
    if($data[pass]==PASSWORD)
    {
        if(mysqli_connect_errno($con))
        {
            return("Failed to connect to MySQL: " . mysqli_connect_error());
        }
        else
        {
            switch($mode)
            {
                case 'insert':
                    $result= mysqli_query($con, "INSERT INTO `".CALENDAR_TABLE."`
                                                    (`id` , `event_title`               , `datetime_start`              , `datetime_end`              , `location`             , `description`             , `event_class`           )
                                            VALUES  (NULL , '".$data[event_title]."'    , '".$data[datetime_start]."'   , '".$data[datetime_end]."'   , '".$data[location]."' , '".$data[description]."'  , '".$data[event_class]."');
                                                "
                                         );
                    break;
                case 'update':
                    $result= mysqli_query($con,"UPDATE `".CALENDAR_TABLE."`
                                                SET     event_title='".$data[event_title]."',
                                                        datetime_start='".$data[datetime_start]."',
                                                        datetime_end='".$data[datetime_end]."',
                                                        location='".$data[location]."',
                                                        description='".$data[description]."',
                                                        event_class='".$data[event_class]."',
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
                return("Error posting to database");
            }
        }
    }
    else
    {
        return("Password incorrect, no data posted");
    }
}
function create_new_event_form($db_connection)
//input: Connection to database 
//Output: HTML form to submit data to database
{
    echo '<div class="calendar_new_event_form">';
    //the form handling
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        echo '<div class="event_calendar_notification"';
        if(is_string($error=validate_post_data($_POST,'insert'))) //validate data
            echo $error;
        else
        {
            if(is_string($error=post_event_data($db_connection,$_POST,'insert'))) //post data
                echo '>'.$error;
            else
                {
                    //unset post data
                    foreach($_POST as $key => $val)
                    {
                        unset($_POST[$key]);
                    }
                    echo ' id="event_calendar_notification_data_correct">Data Posted Correctly';
                }
        }
        echo '</div>';
    }
    
    //actual form
    echo '<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).generate_get_string().'" method="post"><table class="calendar_layout_table">';
    echo '<tr><td class="calendar_layout_table">Event Title:</td><td class="calendar_layout_table"><input type="text" name="event_title" value="'.$_POST[event_title].'"></td></tr>';
    echo '<tr><td class="calendar_layout_table">Event Type:</td><td class="calendar_layout_table"><select name="event_class">';
    foreach($GLOBALS[calendar_event_classes] as $key => $val)
    {
        echo '<option value="'.$key.'"';
        if($_POST[event_class]==$key)
            echo' selected="selected"';
        echo'>'.$val.'</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><td class="calendar_layout_table">Start Date/Time:</td><td class="calendar_layout_table"><input type="datetime" name="datetime_start" value="'.$_POST[datetime_start].'">(use YYYY-MM-DD hh:mm)</td></tr>';
    echo '<tr><td class="calendar_layout_table">End Date/Time:</td><td class="calendar_layout_table"><input type="datetime" name="datetime_end" value="'.$_POST[datetime_end].'">(use YYYY-MM-DD hh:mm)</td></tr>';
    echo '<tr><td class="calendar_layout_table">Location: </td><td class="calendar_layout_table"><input type="text" name="location" value="'.$_POST[location].'"></td></tr>';
    echo '<tr><td class="calendar_layout_table">Event Description:</td><td class="calendar_layout_table"><textarea name="description" rows="5" cols="40">'.$_POST[description].'</textarea></td></tr>';
    echo '<tr><td class="calendar_layout_table">Password:</td><td class="calendar_layout_table"><input type="password" name="pass"></td></tr>';
    echo '<tr><td class="calendar_layout_table" colspan="2"><input type="submit" name="event_calendar_submit" value="Submit"></td></tr>';
    echo '</table></form></div>';
}

?>