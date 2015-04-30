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
            View::UseJSModule("/script/test.js");
            View::Fire("boom",array("Message from server on event"));
            break;
        default:
        case "noscript":
            break;
    }
    
    $master->render();

?>