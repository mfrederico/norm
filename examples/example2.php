<?php
//------------------------------------
// Example 2
//------------------------------------
// This assumes you've already run example 1 and have created
// at least one costume object in the database.
// 
// Lets get those costumes out of the database!

// Include Norm
include('../norm.php');
include('../../norm_db_config.php');

// Create a norm instance - mysql for now, more coming later
$norm = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);

// My container object [NOTE: no vars specified - that's OK!]
class Costume { } 

// Instantiate my container object
$costume = new Costume();

// ** Doesn't this all look familiar? **

// So far, I know costume in the database should at least have a "title", a "sku" a "price" and an "id"
// Hopefully, we have at least ONE in there with the id of .. 1 <wow!>

$costume->id = 1;
$costume_list = $norm->get($costume);

print "<h3>My costume:</h3>";
print "<pre>".print_pre($costume_list->results,$norm->lastQuery)."</pre>";

// If you added more than one costume lets try it this way:
// if we don't want to limit the results NORM will grab EVERYTHING.  So we unset the ID
unset($costume->id); 
$costume_list = $norm->get($costume);

print "<h3>My LIST of costumes:</h3>";
print "<pre>".print_pre($costume_list->results,$norm->lastQuery)."</pre>";

//------------------------------------
// NEXT UP: example3.php 
// - Lets change the price of a costume
//------------------------------------
?>
