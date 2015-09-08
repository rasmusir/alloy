<?php
require_once("alloy/view.php");
use Alloy\View;

$master = View::Get("alloy/default/welcome.html");

$master->Render();
?>
