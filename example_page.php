<html>
    <head>
        <title>Calendar Test</title>
        <?php
        include("calendar.php");
        $con=mysqli_connect("localhost","db_user","db_pass","database");
        ?>
    </head>
    <body>
        <div style="background-color: transparent; border: solid black thick; width:1000px; margin:auto;" >
            <?php
                if($_GET[type]=='form')
                    create_new_event_form($con);
                else
                {
                    create_calendar($con);
                    echo '<br>';
                    create_calendar_legend();
                    echo '<br>';
                    display_event_info($con);
                }
            ?>
        </div>
    </body>
</html>