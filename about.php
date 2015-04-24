<?php
    chdir("alloy");
    require("view.php");
    
    use Alloy\View;
    
    $master = View::Get("about.html");
    $subview = "";
    if (isset($path[2]))
        $subview = $path[2];
    switch ($subview)
    {
        case "script":
            $view = View::Get("../views/script.html");
            $master->SetData("test",$view);
            $master->SetAttribute("test","test","aloha");
            break;
        default:
        case "noscript":
            $view = View::Get("../views/noscript.html");
            $master->SetData("test",$view);
            $master->SetAttribute("test","test","");
            break;
    }
    
    $master->render();

?>