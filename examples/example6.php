<?php
//------------------------------------
// Example 6
//------------------------------------
// This assumes you've already run all the examples
// 
// Introducing: relationship deletion

// Include Norm stuff
include('../norm.php');

// Create a norm instance - sqlite
$norm = new Norm("sqlite:costume.sqlite");

// Now lets introduce a new object called Renter and do 
// some fun stuff .. 

// Renter has everything .. right?
class Renter {}
$renter			= new Renter;
$renter->id		= 1;

$norm->del($renter);

//------------------------------------
// NORM is like Magic.. 
// ...  or not .. whatever
//------------------------------------
?>
