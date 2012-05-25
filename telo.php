<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>9th Sense</title>
</head>
<body>
Hi, Dan!<p>
Select a robot to control:
</p>
<!-- form action sets that we'll go to the next page when we click submit -->
<form action="teloui.php">

    <!-- note that the name here is phoneid, which we will use on teloui.php -->
    <select name="phoneid">
    <?php
    
    // connect to the database
    require_once("db.php");
    connectDatabase();
    
    // load all rows of the database, to print all phones
    // TODO: if we have user authentication, here is where we will filter out what robots can be used
    // based on username
    $sql = "SELECT * FROM phones WHERE 1";
    $result = mysql_query($sql);
    
    // loop through the results and produce an option for each row
    while ($myrow = mysql_fetch_array($result)) {
        
        // check to make sure row is valid
        if (strlen($myrow["name"]) > 0 && strlen($myrow["deviceid"]) > 0) {
        
            // print HTML for the option for this robot.  We use the value here to be the registration key
            // that will get sent to the next page
            echo "<option value=\"" . $myrow["deviceid"] . "\">" . $myrow["name"] . "</option>";
        }
    }
    ?>
    </select>
    <br /><br />
    <!-- submit button -->
    <input type="submit" value="Submit" />
</form>

</body>
</html>

