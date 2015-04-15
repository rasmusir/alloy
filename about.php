<?php
    chdir("alloy");
    require("view.php");
    
    use Alloy\View;
    
    $master = View::Get("about.html");
    $master->render();

?>