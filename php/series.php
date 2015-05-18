<?php

header("Access-Control-Allow-Origin: *");


//input fields
if (isset($_GET['table'])){$table = $_GET['table'];}   //custom time series table
if (isset($_GET['geonum'])){$geonum = $_GET['geonum'];} //comma delimited list

$type='json';

require '../../CensusAPI/connect.php';



//load metadata file with all information you will need to query each specific database
$str = file_get_contents('../js/timeseriesmeta.js');

$json = json_decode($str, true); // decode the JSON into an associative array

//echo json_encode($json);

//metadata variables
$description=''; //table description
$master=''; //geoname will be taken from this db
$c1980=''; //schema of 1980 table (none if Not Applicable)
$c1990='';
$c2000='';
$c2010='';
$acs0812='';
$acs0913='';
$fields1980=''; //field list (for SQL) of fields to pull from 1980 database
$fields1990='';
$fields2000='';
$fields2010='';
$fieldsacs0812='';
$fieldsacs0913='';

//field header
$ttlfields=[];

//field meta
$metacsv=[];

//error array - later
$errorarray=[];


//iterate through metadata file
foreach($json['data'] as $obj){

  //look for table in metadata that matches name of table that user gave
  if($obj['table']==$table){
    
    $description=$obj['description'];
    
    $c1980=$obj['years'][0]['c1980'];  //save schema information where each table is located
    $c1990=$obj['years'][1]['c1990'];
    $c2000=$obj['years'][2]['c2000'];
    $c2010=$obj['years'][3]['c2010'];
    $acs0812=$obj['years'][4]['acs0812']; 
    $acs0913=$obj['years'][5]['acs0913'];
    
    foreach($obj['columnmeta'] as $ecm){   //iterate through column metadata
      array_push($ttlfields, $ecm['colname']);
      array_push($metacsv, $ecm['cdesc']);     
      if($ecm['year']=='c1980'){$fields1980 = ' '.$fields1980.$ecm['colname'].', ';}  //compile list of fields to pull from each db
      if($ecm['year']=='c1990'){$fields1990 = ' '.$fields1990.$ecm['colname'].', ';}
      if($ecm['year']=='c2000'){$fields2000 = ' '.$fields2000.$ecm['colname'].', ';}      
      if($ecm['year']=='c2010'){$fields2010 = ' '.$fields2010.$ecm['colname'].', ';}
      if($ecm['year']=='acs0812'){$fieldsacs0812 = ' '.$fieldsacs0812.$ecm['colname'].', ';}   
      if($ecm['year']=='acs0913'){$fieldsacs0913 = ' '.$fieldsacs0913.$ecm['colname'].', ';}         
    }
  }
     
}


//elaborate
$wherestatement='';


//break the comma delimited records from geonum into an array  
$geonumarray=explode(",", $geonum);
  
//iterate through all geonum's
foreach ($geonumarray as $geonumlist){
$wherestatement=$wherestatement." geonum=".$geonumlist." or";
}
  
//trim last trailing 'or'
$wherestatement=substr($wherestatement,0,-2);
  



//start constructing sql statements to query each db
$sql1980='SELECT '.$fields1980.'geonum, geoname FROM search.'.$c1980.' natural join '.$c1980.'.'.$table.' WHERE '.$wherestatement.';';
$sql1990='SELECT '.$fields1990.'geonum, geoname FROM search.'.$c1990.' natural join '.$c1990.'.'.$table.' WHERE '.$wherestatement.';';
$sql2000='SELECT '.$fields2000.'geonum, geoname FROM search.'.$c2000.' natural join '.$c2000.'.'.$table.' WHERE '.$wherestatement.';';
$sql2010='SELECT '.$fields2010.'geonum, geoname FROM search.'.$c2010.' natural join '.$c2010.'.'.$table.' WHERE '.$wherestatement.';';
$sqlacs0812='SELECT '.$fieldsacs0812.'geonum, geoname FROM search.'.$acs0812.' natural join '.$acs0812.'.'.$table.' WHERE '.$wherestatement.';';
$sqlacs0913='SELECT '.$fieldsacs0913.'geonum, geoname FROM search.'.$acs0913.' natural join '.$acs0913.'.'.$table.' WHERE '.$wherestatement.';';


//chech sql statements
//echo $sql1980;
//echo $sql1990;
//echo $sql2000;
//echo $sql2010;
//echo $sqlacs0812;
//echo $sqlacs0913;


//turn field lists into arrays
$c1980array=explode(",", substr($fields1980,0,-2));
$c1990array=explode(",", substr($fields1990,0,-2));
$c2000array=explode(",", substr($fields2000,0,-2));
$c2010array=explode(",", substr($fields2010,0,-2));
$acs0812array=explode(",", substr($fieldsacs0812,0,-2));
$acs0913array=explode(",", substr($fieldsacs0913,0,-2));

//declare main arrays
$c1980fullarray=[];
$c1990fullarray=[];
$c2000fullarray=[];
$c2010fullarray=[];
$acs0812fullarray=[];
$acs0913fullarray=[];



//execute sql statements as long as schema variables for each db<>'none'

//c1980
if($c1980<>'none'){
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=c1980 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}
  
$c1980result = pg_query($dbh, $sql1980);

  //flag error
if (!$c1980result) {
    die("Error in SQL query: " . pg_last_error());
}

  
  while ($row = pg_fetch_array($c1980result)) {
    
  //add geoname as first element in every result record array
  $c1980arr=array('geoname' => $row['geoname'], 'geonum' => $row['geonum']);
  
  $c1980arr3=[];
  
  //iterate over every field in query result row
      foreach($c1980array as $t){
        $c1980arr2=array(trim($t) => $row[trim($t)]);
       $c1980arr3 = array_merge($c1980arr3, $c1980arr2);
      }
  
       $c1980arr = array_merge($c1980arr, $c1980arr3);
  
  //add current array (record) to results array
  array_push($c1980fullarray, $c1980arr);

    //echo json_encode($c1980fullarray);
  }
  
  
    pg_close($dbh);
  
  
  //print_r($c1980fullarray);
  
  $tempcopy=$geonumarray;
  
  foreach($tempcopy as $key => $value){

    foreach($c1980fullarray as $cfa){
      if($cfa['geonum']==$value){unset($tempcopy[$key]);}
      //delete all instances of geonum that you have records for.  remaining are empty  
    }
    
  }
  
  foreach($tempcopy as $tc){
    
      //add geoname as first element in every result record array
  $c1980arr=array('geoname' => null, 'geonum' => $tc);
  
  $c1980arr3=[];
  
  //iterate over every field in query result row
      foreach($c1980array as $t){
        $c1980arr2=array(trim($t) => null);
       $c1980arr3 = array_merge($c1980arr3, $c1980arr2);
      }
  
       $c1980arr = array_merge($c1980arr, $c1980arr3);
  
  //add current array (record) to results array
  array_push($c1980fullarray, $c1980arr);
    
  }
  
  //print_r($tempcopy);
  //print_r($c1980fullarray);

  

  
  
}  //end c1980


//c1990
if($c1990<>'none'){
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=c1990 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}
  
$c1990result = pg_query($dbh, $sql1990);

  //flag error
if (!$c1990result) {
    die("Error in SQL query: " . pg_last_error());
}

 
  while ($row = pg_fetch_array($c1990result)) {

  //add geoname as first element in every result record array
  $c1990arr=array('geoname' => $row['geoname'], 'geonum' => $row['geonum']);
  
  $c1990arr3=[];
  
  //iterate over every field in query result row
      foreach($c1990array as $t){
        $c1990arr2=array(trim($t) => $row[trim($t)]);
       $c1990arr3 = array_merge($c1990arr3, $c1990arr2);
      }
  
       $c1990arr = array_merge($c1990arr, $c1990arr3);
  
  //add current array (record) to results array
  array_push($c1990fullarray, $c1990arr);

  }
  
  //print_r($c1990fullarray);
  
  pg_close($dbh);
  
  
  $tempcopy=$geonumarray;
  
  foreach($tempcopy as $key => $value){

    foreach($c1990fullarray as $cfa){
      if($cfa['geonum']==$value){unset($tempcopy[$key]);}
      //delete all instances of geonum that you have records for.  remaining are empty  
    }
    
  }
  
  foreach($tempcopy as $tc){
    
      //add geoname as first element in every result record array
  $c1990arr=array('geoname' => null, 'geonum' => $tc);
  
  $c1990arr3=[];
  
  //iterate over every field in query result row
      foreach($c1990array as $t){
        $c1990arr2=array(trim($t) => null);
       $c1990arr3 = array_merge($c1990arr3, $c1990arr2);
      }
  
       $c1990arr = array_merge($c1990arr, $c1990arr3);
  
  //add current array (record) to results array
  array_push($c1990fullarray, $c1990arr);
    
  }
  
  
}  //end c1990


//c2000
if($c2000<>'none'){
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=c2000 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}
  
$c2000result = pg_query($dbh, $sql2000);

  //flag error
if (!$c2000result) {
    die("Error in SQL query: " . pg_last_error());
}

 
  while ($row = pg_fetch_array($c2000result)) {

  //add geoname as first element in every result record array
  $c2000arr=array('geoname' => $row['geoname'], 'geonum' => $row['geonum']);
  
  $c2000arr3=[];
  
  //iterate over every field in query result row
      foreach($c2000array as $t){
        $c2000arr2=array(trim($t) => $row[trim($t)]);
       $c2000arr3 = array_merge($c2000arr3, $c2000arr2);
      }
  
       $c2000arr = array_merge($c2000arr, $c2000arr3);
  
  //add current array (record) to results array
  array_push($c2000fullarray, $c2000arr);

  }
  
  
  $tempcopy=$geonumarray;
  
  foreach($tempcopy as $key => $value){

    foreach($c2000fullarray as $cfa){
      if($cfa['geonum']==$value){unset($tempcopy[$key]);}
      //delete all instances of geonum that you have records for.  remaining are empty  
    }
    
  }
  
  foreach($tempcopy as $tc){
    
      //add geoname as first element in every result record array
  $c2000arr=array('geoname' => null, 'geonum' => $tc);
  
  $c2000arr3=[];
  
  //iterate over every field in query result row
      foreach($c2000array as $t){
        $c2000arr2=array(trim($t) => null);
       $c2000arr3 = array_merge($c2000arr3, $c2000arr2);
      }
  
       $c2000arr = array_merge($c2000arr, $c2000arr3);
  
  //add current array (record) to results array
  array_push($c2000fullarray, $c2000arr);
    
  }
  
  
  pg_close($dbh);
}  //end c2000


//c2010
if($c2010<>'none'){
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=c2010 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}
  
$c2010result = pg_query($dbh, $sql2010);

  //flag error
if (!$c2010result) {
    die("Error in SQL query: " . pg_last_error());
}
  
 
  while ($row = pg_fetch_array($c2010result)) {

  //add geoname as first element in every result record array
  $c2010arr=array('geoname' => $row['geoname'], 'geonum' => $row['geonum']);
  
  $c2010arr3=[];
  
  //iterate over every field in query result row
      foreach($c2010array as $t){
        $c2010arr2=array(trim($t) => $row[trim($t)]);
       $c2010arr3 = array_merge($c2010arr3, $c2010arr2);
      }
  
       $c2010arr = array_merge($c2010arr, $c2010arr3);
  
  //add current array (record) to results array
  array_push($c2010fullarray, $c2010arr);

  }
  
  //print_r($c2010fullarray);
  
  pg_close($dbh);
  
    
  $tempcopy=$geonumarray;
  
  foreach($tempcopy as $key => $value){

    foreach($c2010fullarray as $cfa){
      if($cfa['geonum']==$value){unset($tempcopy[$key]);}
      //delete all instances of geonum that you have records for.  remaining are empty  
    }
    
  }
  
  foreach($tempcopy as $tc){
    
      //add geoname as first element in every result record array
  $c2010arr=array('geoname' => null, 'geonum' => $tc);
  
  $c2010arr3=[];
  
  //iterate over every field in query result row
      foreach($c2010array as $t){
        $c2010arr2=array(trim($t) => null);
       $c2010arr3 = array_merge($c2010arr3, $c2010arr2);
      }
  
       $c2010arr = array_merge($c2010arr, $c2010arr3);
  
  //add current array (record) to results array
  array_push($c2010fullarray, $c2010arr);
    
  }
  
  
  
}  //end c2010



//acs0812
if($acs0812<>'none'){
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=acs0812 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}
  
$acs0812result = pg_query($dbh, $sqlacs0812);

  //flag error
if (!$acs0812result) {
    die("Error in SQL query: " . pg_last_error());
}

  
  while ($row = pg_fetch_array($acs0812result)) {

  //add geoname as first element in every result record array
  $acs0812arr=array('geoname' => $row['geoname'], 'geonum' => $row['geonum']);
  
  $acs0812arr3=[];
  
  //iterate over every field in query result row
      foreach($acs0812array as $t){
        $acs0812arr2=array(trim($t) => $row[trim($t)]);
       $acs0812arr3 = array_merge($acs0812arr3, $acs0812arr2);
      }
  
       $acs0812arr = array_merge($acs0812arr, $acs0812arr3);
  
  //add current array (record) to results array
  array_push($acs0812fullarray, $acs0812arr);

  }
  
  
  $tempcopy=$geonumarray;
  
  foreach($tempcopy as $key => $value){

    foreach($acs0812fullarray as $cfa){
      if($cfa['geonum']==$value){unset($tempcopy[$key]);}
      //delete all instances of geonum that you have records for.  remaining are empty  
    }
    
  }
  
  foreach($tempcopy as $tc){
    
      //add geoname as first element in every result record array
  $acs0812arr=array('geoname' => null, 'geonum' => $tc);
  
  $acs0812arr3=[];
  
  //iterate over every field in query result row
      foreach($acs0812array as $t){
        $acs0812arr2=array(trim($t) => null);
       $acs0812arr3 = array_merge($acs0812arr3, $acs0812arr2);
      }
  
       $acs0812arr = array_merge($acs0812arr, $acs0812arr3);
  
  //add current array (record) to results array
  array_push($acs0812fullarray, $acs0812arr);
    
  }
  
  
  pg_close($dbh);
}  //end acs0812




//acs0913
if($acs0913<>'none'){
// attempt a connection
$dbh = pg_connect("host=".$server." dbname=acs0913 user=".$user." password=".$password);

if (!$dbh) {
    die("Error in connection: " . pg_last_error());
}
  
$acs0913result = pg_query($dbh, $sqlacs0913);

  //flag error
if (!$acs0913result) {
    die("Error in SQL query: " . pg_last_error());
}

  
  while ($row = pg_fetch_array($acs0913result)) {

  //add geoname as first element in every result record array
  $acs0913arr=array('geoname' => $row['geoname'], 'geonum' => $row['geonum']);
  
  $acs0913arr3=[];
  
  //iterate over every field in query result row
      foreach($acs0913array as $t){
        $acs0913arr2=array(trim($t) => $row[trim($t)]);
       $acs0913arr3 = array_merge($acs0913arr3, $acs0913arr2);
      }
  
       $acs0913arr = array_merge($acs0913arr, $acs0913arr3);
  
  //add current array (record) to results array
  array_push($acs0913fullarray, $acs0913arr);

  }
  
  
  $tempcopy=$geonumarray;
  
  foreach($tempcopy as $key => $value){

    foreach($acs0913fullarray as $cfa){
      if($cfa['geonum']==$value){unset($tempcopy[$key]);}
      //delete all instances of geonum that you have records for.  remaining are empty  
    }
    
  }
  
  foreach($tempcopy as $tc){
    
      //add geoname as first element in every result record array
  $acs0913arr=array('geoname' => null, 'geonum' => $tc);
  
  $acs0913arr3=[];
  
  //iterate over every field in query result row
      foreach($acs0913array as $t){
        $acs0913arr2=array(trim($t) => null);
       $acs0913arr3 = array_merge($acs0913arr3, $acs0913arr2);
      }
  
       $acs0913arr = array_merge($acs0913arr, $acs0913arr3);
  
  //add current array (record) to results array
  array_push($acs0913fullarray, $acs0913arr);
    
  }
  
  
  pg_close($dbh);
}  //end acs0913



//looks like sorting a non-existing array is not a problem
usort($c1980fullarray, "cmp"); //sort 
usort($c1990fullarray, "cmp");
usort($c2000fullarray, "cmp");
usort($c2010fullarray, "cmp");
usort($acs0812fullarray, "cmp");
usort($acs0913fullarray, "cmp");


$fields1980=trim($fields1980);
$fields1990=trim($fields1990);
$fields2000=trim($fields2000);
$fields2010=trim($fields2010);
$fieldsacs0812=trim($fieldsacs0812);
$fieldsacs0913=trim($fieldsacs0913);

$fields1980 = str_replace(",", "", $fields1980);
$fields1990 = str_replace(",", "", $fields1990);
$fields2000 = str_replace(",", "", $fields2000);
$fields2010 = str_replace(",", "", $fields2010);
$fieldsacs0812 = str_replace(",", "", $fieldsacs0812);
$fieldsacs0913 = str_replace(",", "", $fieldsacs0913);

//echo $fields1980;
//echo $fields1990;
//echo $fields2000;
//echo $fields2010;
//echo $fieldsacs0812;
//echo $fieldsacs0913;

$arrFinal=[];
$last='';  //grab geoname and geonum from last census year with valid data

if($c1980fullarray<>[]){
  $evalarray=$evalarray.'$fields1980=>$c1980fullarray[0][$fields1980], ';
  $last='$arr = array(  "geoname"=>$c1980fullarray[0]["geoname"], "geonum"=>$c1980fullarray[0]["geonum"], ';
}

if($c1990fullarray<>[]){
  $evalarray=$evalarray.'$fields1990=>$c1990fullarray[0][$fields1990], ';
  $last='$arr = array(  "geoname"=>$c1990fullarray[0]["geoname"], "geonum"=>$c1990fullarray[0]["geonum"], ';  
}

if($c2000fullarray<>[]){
  $evalarray=$evalarray.'$fields2000=>$c2000fullarray[0][$fields2000], ';
  $last='$arr = array(  "geoname"=>$c2000fullarray[0]["geoname"], "geonum"=>$c2000fullarray[0]["geonum"], ';  
}

if($c2010fullarray<>[]){
  $evalarray=$evalarray.'$fields2010=>$c2010fullarray[0][$fields2010], ';
  $last='$arr = array(  "geoname"=>$c2010fullarray[0]["geoname"], "geonum"=>$c2010fullarray[0]["geonum"], ';  
}

if($acs0812fullarray<>[]){
  $evalarray=$evalarray.'$fieldsacs0812=>$acs0812fullarray[0][$fieldsacs0812], ';
  $last='$arr = array(  "geoname"=>$acs0812fullarray[0]["geoname"], "geonum"=>$acs0812fullarray[0]["geonum"], ';  
}

if($acs0913fullarray<>[]){
  $evalarray=$evalarray.'$fieldsacs0913=>$acs0913fullarray[0][$fieldsacs0913], ';
  $last='$arr = array(  "geoname"=>$acs0913fullarray[0]["geoname"], "geonum"=>$acs0913fullarray[0]["geonum"], ';  
}


$evalarray = substr($evalarray, 0, -2);
$evalarray=$evalarray.');';
//echo ($last.$evalarray);

eval(($last.$evalarray));


array_push($arrFinal, $arr);

  //add geonum to front of fields row array
  array_unshift($ttlfields, "geonum");        
  array_unshift($ttlfields, "geoname"); 

      
    //add geonum description to front of metadata row array
  array_unshift($metacsv, "Unique ID");      
  array_unshift($metacsv, "Geographic Area Name");



      
      header('Content-Type: application/json');
      
        //header, meta combined with data
  $withmeta=array('title'=>$description, 'fields'=>$ttlfields, 'meta' => $metacsv, 'data'=> $arrFinal, 'error'=> $errorarray);
      
      echo json_encode($withmeta);

  


//sort array based on geonum
function cmp($a, $b)
{
    return strcmp($a["geonum"], $b["geonum"]);
}




?>
