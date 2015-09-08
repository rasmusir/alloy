<?php
<<<<<<< HEAD
chdir("alloy");
require_once("view.php");

use Alloy\View;

$master = View::Get("about.html");

View::On("clear",function() {
    \Profiler::ClearAll();
    View::Redirect("/profiler");
});

$profiler = "";

$data = \Profiler::GetData();

PrintData("root",$data);

function PrintData($name,$data)
{
    global $profiler;
    if (isset($data->count))
        $profiler .= $name . " " . round($data->total/max($data->count,1),2)."ms";
    $profiler .= "<ul>";
    foreach ($data->sub as $key=>$sub)
    {
        $profiler .= "<li>";
        PrintData($key,$sub);
        $profiler .= "</li>";
        
    }
    $profiler .= "</ul>";
}

$master->SetData("profiler",$profiler);

$master->render();
=======
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
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0

?>