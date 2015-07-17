<html>
    <head>
        <title>Event_Calendar Example Page</title>
        <?php
        include("calendar.php");
        ?>
		<style>
		a.quick_nav{
			display:inline-block;
			height:20px;
			width:150px;
		}
		</style>
    </head>
    <body>
	<div style="background-color: transparent; width:1000px;height:50px; margin:auto;padding:auto;" >
	<a href="?display=calendar" class="quick_nav">Calendar View</a> <a href="?display=new" class="quick_nav">Create New Event</a> <a href="?display=edit" class="quick_nav">Edit Event</a>
	<div>
        <div style="background-color: transparent; border: solid black thick; width:1000px; margin:auto;padding:10px;" >
		
            <?php
                switch($_GET[display])
				{
					case 'new':
						create_new_event_form();
					break;
					
					case 'edit':
						create_event_edit_form();
					break;
						
					default:
					case 'calendar':
						create_calendar();
						echo '<br>';
						create_calendar_legend();
						echo '<br>';
						display_event_info();
					break;
                }
            ?>
        </div>
    </body>
</html>