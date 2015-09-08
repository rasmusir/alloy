<?php
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

?>