<?php
$path = explode("/",$_SERVER["REQUEST_URI"]);


$_DATA = array();
$i = 0;
foreach ($path as $d)
{
    $i++;
    if ($i < 3) continue; 
    $_DATA[$i-3] = $d;
}

$view = $path[1];

switch ($view)
{
    default:
        $_VIEW = "home";
        include("test.php");
        break;
    case "hem":
        $_VIEW = "home";
        include("test.php");
        break;
    case "om":
        $_VIEW = "about";
        include("test.php");
        break;
    case "kontakt":
        $_VIEW = "contact";
        include("test.php");
        break;
    case "bilder":
        $_VIEW = "pictures";
        include("test.php");
        break;
}


?>