<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require("Alloy/View.php");

use Alloy\View;

$view = View::Get("views/test.html");

$page = $_VIEW;
switch ($page)
{
    case "hem":
        Home();
    break;
    case "om":
        Om();
    break;
    case "kontakt":
        Kontakt();
    break;
    case "bilder":
        Bilder();
    break;
}

function Home()
{
    global $view;
    resetbuttons();
    
    $view->SetData("content","<a href=\"/kontakt/rasmus\">Rasmus Israelsson</a>");
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
    $name = "";
    if (isset($_DATA[0]))
        $name = $_DATA[0];
        
    $contact = View::Get("views/kontakt.html");
    $phant = View::Get("views/list.html");
    
    
    $phant->SetAttribute("text","style","background: tomato");
    if($name == "peter")
    {
        $contact->SetData("name","Peter Kjellén");
        $contact->SetData("text","Peter är en dromedar!");
        $contact->SetData("container",$phant);
        $phant->SetAttribute("image", "src", "http://i.imgur.com/ItTEj.jpg");
        $phant->SetAttribute("image", "style", "width: 15em");
    }
    elseif ($name == "rasmus")
    {
        $contact->SetData("name","Rasmus Israelsson");
        $contact->SetData("text","Rasmus är inte en dromedar!");
        $contact->SetData("container",$phant);
        $phant->SetAttribute("image", "src", "http://puu.sh/gTjDf/d2385f0ddc.jpg");
        $phant->SetAttribute("image", "style", "width: 15em");
    }
    else
    {
        $phant->SetAttribute("image", "src", "http://i.imgur.com/ItTEj.jpg");
    }
    
    $view->SetData("content",$contact);
    $view->SetData("title","Alloy - Kontakt");
    
    $view->SetAttribute("kontaktbutton","class","active");
}

function Bilder()
{
    global $view, $_DATA;
    resetbuttons();
    
    $bug = "";
    if (isset($_DATA[0]))
        $bug = $_DATA[0];
        
    $phant = View::Get("views/list.html");
    
    if($bug == "bug")
    {
        $phant->SetData("text","This is what happened to me, beautiful right?");
        $phant->SetAttribute("image","src","/bug.gif");
        $phant->SetAttribute("image","style","width:50em");
        
    }
    else
    {
        $phant->SetData("text","No so much hello now");
        $phant->SetAttribute("text","style","background: red");
    }
    
    
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