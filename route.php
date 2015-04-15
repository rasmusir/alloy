<?php

chdir("..");

error_reporting(E_ALL);
ini_set('display_errors', '1');

$cfn = ".route";
$routecache = new stdClass;
$routecache->timestamp = 0;
if (file_exists($cfn))
{
    $file = fopen($cfn,"r");
    $size = filesize($cfn);
    $string = fread($file,$size);
    $routecache = json_decode($string);
    fclose($file);
}

$code = "";

$path = explode("/",explode("?",$_SERVER["REQUEST_URI"])[0]);
$_VIEW = $path[1];

$_DATA = array();
$i = 0;
foreach ($path as $d)
{
    $i++;
    if ($i < 3) continue; 
    $_DATA[$i-3] = $d;
}

$fn = "routes.json";
if (!file_exists($fn))
{
    $file = fopen($fn,"w");
    $temp = array("default" => array("target"=>"alloy/about.php","alias"=>"/"),"routes" => array(array("alias"=>"about","target" => "alloy/about.php")));
    
    fwrite($file,json_encode($temp,JSON_PRETTY_PRINT));
    
    fclose($file);
}

if ($routecache->timestamp != filemtime($fn))
{
    $file = fopen($fn,"r");
    $size = filesize($fn);
    $string = fread($file,$size);
    $routes = json_decode($string);
    fclose($file);
    
    $default = $routes->default->target;
    $defalias = $routes->default->alias;
    $code .= "switch (\$_VIEW) {default:";
    $code .= "\$_VIEW='$defalias';include('$default');break;";
    $code .= "case '_view':\$path=str_replace('/_view/','',\$_SERVER['REQUEST_URI']);include(\$path);break;";
    foreach ($routes->routes as $route)
    {
        if (is_array($route->alias))
        foreach ($route->alias as $alias)
        {
            $code .=  "case '$alias': ";
            
        }
        else
            $code .=  "case '$route->alias': ";
        $code .=  "include('$route->target');";
        $code .=  "break;";
    }
    $code .=  "}";
    
    $routecache->timestamp = filemtime($fn);
    $routecache->code = $code;
    $file = fopen($cfn,"w");
    fwrite($file,json_encode($routecache));
    fclose($file);
}
else
    $code = $routecache->code;
//echo $code;

eval($code);

?>