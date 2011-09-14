<?php
//------------------------------------
// Example 5
//------------------------------------
// This assumes you've already run example 1,2,3,4 and have created
// at least one costume object, and one renter in the database.
// 
// Introducing: Dynamic column creation

// Include Norm stuff
include('../norm.php');
include('../../norm_db_config.php');
$norm = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);

// Now lets introduce a new object called Renter and do 
// some fun stuff .. 

class Renter {}
$renter			= new Renter;
$renter->name	= "Joe Bloggs";
$renter->id		= 1;

// So, we need to be able to call Joe in case the costume comes back .. uh .. messy .. 
$renter->phone	= '801-801-8001';
$norm->store($renter);

// ... 
unset($renter);
$renter = new Renter;

// Check this out:
$renter->id =1;
print "<pre>".print_pre($norm->get($renter,'renter_id,renter_name,renter_phone')->results)."</pre>";
//------------------------------------
// example6 - nuke EVERYTHING and start over!
// NORM is like Magic.. 
// ...  or not .. whatever
//------------------------------------
?>
