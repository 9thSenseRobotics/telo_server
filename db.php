<?php

//connect to the database

// database is set up to have a database named "telo" and a table named "phones"

function connectDatabase() {
  @mysql_connect("localhost", "telo", "9thsense&") or
    die("<div algin=\"center\"><b>Internal Server Error.  Please try again later.</b>");
  mysql_select_db("telo");
}


?>
