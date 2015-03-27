<?php
error_reporting(E_ALL); ini_set('display_errors', '1');

require("Alloy/View.php");

use Alloy\View;

$view = View::Get("views/test.html");
$data = array();
$attr = array();

$headers = getallheaders();
$update = isset($headers["Content-Type"]) && $headers["Content-Type"] == "application/json";

$page = $_VIEW;
switch ($page)
{
    case "home":
        Home();
    break;
    case "about":
        Om();
    break;
    case "contact":
        Kontakt();
    break;
    case "pictures":
        Bilder();
    break;
}

function Home()
{
    global $data,$attr;
    resetbuttons();
    $data["content"] = "Hemside";
    $data["title"] = "Alloy - Hem";
    
    $attr["homebutton"] = array("class"=>"active");
}

function Om()
{
    global $data,$attr;
    resetbuttons();
    $data["content"] = "Detta är om mig!";
    $data["title"] = "Alloy - Om";
    
    $attr["ombutton"] = array("class"=>"active");
}

function Kontakt()
{
    global $data,$attr,$_DATA;
    resetbuttons();
    $name = "oss";
    if (isset($_DATA[0]))
        $name = $_DATA[0];
    $data["content"] = "Ring inte $name >:C";
    $data["title"] = "Alloy - Kontakt";
    
    $attr["kontaktbutton"] = array("class"=>"active");
}

function Bilder()
{
    global $data,$attr;
    resetbuttons();
    $data["content"] = "VI HAR INGA BILDER, YO";
    $data["title"] = "Alloy - Bilder";
    
    $attr["bilderbutton"] = array("class"=>"active");
}

function resetbuttons()
{
    global $data,$attr;
    $attr["ombutton"] = array("class" => "");
    $attr["homebutton"] = array("class" => "");
    $attr["kontaktbutton"] = array("class" => "");
    $attr["bilderbutton"] = array("class" => "");
}

$view->Render($data,$attr,$update);

?>