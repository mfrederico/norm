<?php
//------------------------------------
// Example 4
//------------------------------------
// This assumes you've already run example 1,2,3 and have created
// at least one costume object in the database.
// 
// Introducing: ties! 

// Include Norm stuff
include('../norm.php');
include('../../norm_db_config.php');
$norm = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);


// Now lets introduce a new object called Renter and do 
// some fun stuff .. 

class Renter {}
$renter = new Renter;

$renter->name		= "Joe Bloggs";
$renter->address	= "1234 Test Street";
$renter->city		= "Test City";
$renter->state		= "TN";
$renter->zip		= "12345";

// Save him to the database
$norm->store($renter);

// Wow, that was neat - Now Joe wants to rent a costume:
// We want him to be renting costume id 2 - "Bunny Outfit" from example3.php
class Costume { } 
$costume = new Costume();
$costume->id = 2; // <- should be bunny outfit .. right?

// Now we want our "renter" to have a "costume" see:
$renter->costume = $costume;

// And this is where the magic happens:
//$norm->store($renter);

// Now lets retrieve our renter object -
// Lets make it TRICKY:
unset($renter);
unset($costume);

// Get all Joe's rentals
$renter			= new Renter();
$renter->name	= "Joe Bloggs";
print "<h3>Full Structure</h3>";
print "<pre>".print_pre($norm->get($renter)->results,true)."</pre>";
print_r($norm->lastQuery);

// Pretty neato eh?

// NOTE: What if I just want to see Just Joes' record only - maybe just his id and name?
$renter			= new Renter();
$renter->name	= "Joe Bloggs";
print "<h3>Just Joe</h3>";
print "<pre>".print_pre($norm->get($renter,'renter_id,renter_name',Norm::SINGLE)->results,true)."</pre>";

//------------------------------------
// NEXT UP: example5.php 
// Woops, forgot a phone number field for my renter!
//------------------------------------
?>
