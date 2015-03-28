<?php
error_reporting(E_ALL); ini_set('display_errors', '1');

require("Alloy/View.php");

use Alloy\View;

$view = View::Get("views/test.html");

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
    global $view;
    resetbuttons();
    
    $view->SetData("content","Hemsida");
    $view->SetData("title","Alloy - Hem");
    
    $view->SetAttribute("homebutton","class","active");
}

function Om()
{
    global $view;
    resetbuttons();
    
    $view->SetData("content","Detta är om mig!");
    $view->SetData("title","Alloy - Om");
    
    $view->SetAttribute("ombutton","class","active");
}

function Kontakt()
{
    global $view,$_DATA;
    resetbuttons();
    $name = "oss";
    if (isset($_DATA[0]))
        $name = $_DATA[0];
        
    $contact = View::Get("views/kontakt.html");
    
    $phant = View::Get("views/list.html");
    
    $contact->SetData("name",$name);
    $contact->SetData("text","Bara lite random text!");
    $contact->SetData("container",$phant);
    
    $phant->SetAttribute("text","style","background: tomato");
    $phant->SetAttribute("image", "src", "http://i.imgur.com/ItTEj.jpg");
    $phant->SetData("text", "testing something else");
    
    $view->SetData("content",$contact);
    $view->SetData("title","Alloy - Kontakt");
    
    $view->SetAttribute("kontaktbutton","class","active");
}

function Bilder()
{
    global $view;
    resetbuttons();
    
    $phant = View::Get("views/list.html");
    $phant->SetData("text","No so much hello now");
    $phant->SetAttribute("text","style","background: red");
    
    $view->SetData("content",$phant);
    $view->SetData("title","Alloy - Bilder");
    
    $view->SetAttribute("bilderbutton","class","active");
}

function resetbuttons()
{
    global $view;
    $view->SetAttribute("ombutton","class","");
    $view->SetAttribute("homebutton","class","");
    $view->SetAttribute("kontaktbutton","class","");
    $view->SetAttribute("bilderbutton","class","");
}

$view->Render();

?>