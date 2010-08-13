<?php 

// Define your object structures
class artist
{
	var $name;
	var $birthday;
}

class album
{
	var $title;
	var $time;
}

class track
{
	var $trackname;
	var $length;
	var $format;
	var $genre;
}

// Load DB vars
include('../../norm_db_config.php');

/* I like Norm, Norm is easy and wears a tie. :-) */
include('../norm.php');
$w = new Norm("mysql:host=localhost;dbname={$dbname}",$login,$pass);

// Lets create an artist!
$artist = new artist();
$artist->name = 'm4tZi11a';
$artist->birthday = '12/24/1975';

// Save the initial parent artist 
$w->store($artist);

// ok, now lets create an album!
$album = new album();
$album->title = 'infinity over unity';
$album->url = 'http://www.infinityoverunity.com';
$album->time  = '30min';

// Lets set a 1:1 for this artist and his album
$w->tie($artist,$album);

// Now for some tracks for this album
$tracks[0] = new track();
$tracks[0]->trackname='Breath';
$tracks[0]->length='4:00';
$tracks[0]->genre='Hardstyle';

$tracks[1] = new track();
$tracks[1]->trackname='Naked';
$tracks[1]->genre='Dance / Techno';
// Leaving out some of the vars for fun

// This album has many tracks (autodetects if array of objects)
$w->tie($album,$tracks); //(or  tieMany)

// Yay, we're done!

// Ok, now lets start over:
unset ($tracks);
unset ($artist);
unset ($album);

// Set up a new artist
$artist = new artist();

// This is the criteria I will be looking for:
$artist->id = 1;

// Get the artist with accompanying album and tracks heirarchy
$fullSet = $w->get($artist,'*',NORM_FULL);
print "<pre>\n* Complete dataset";
print_r($fullSet);

// Or if I just want the artist only
$fullSet = $w->get($artist);
print "<br />\n* Artist Only *";
print_r($fullSet);

// And that's norm!

// Oh - one more thing: dynamic column creation
$artist->id			= $fullSet[0]['artist_id'];
$artist->name		= $fullSet[0]['artist_name'];
$artist->birthday	= $fullSet[0]['artist_birthday'];
$artist->updated	= date('Y-m-d H:i:s');
/// HERE IT GOES!
$artist->image = 'http://www.infinityoverunity.com/overunitylogo.png';

// purge schema cache:
unset($w->tableSchema['artist']);

// Store it and check your DB!
$w->store($artist);

?>
