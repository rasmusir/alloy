<?php
namespace Alloy;
require_once("compiler.php");
<<<<<<< HEAD
require_once("profiler.php");
=======
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0

class View
{
    private $data = array();
    public $istemplate = false;
    
<<<<<<< HEAD
    public static $update = null;
=======
    private static $update = null;
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0
    private static $views = array();
    private static $modules = array();
    private static $events = array();
    private static $first = true;
    private static $metadata;
    public static $postdata;
    public $file;
    
    static function Get($file)
    {
        
        $id = -1;
        if (self::$update == null)
        {
            $headers = getallheaders();
            self::$update = isset($headers["Content-Type"]) && $headers["Content-Type"] == "application/json";
        }
        $id = in_array($file,self::$views);
        if ($id == false && !self::$first)
        {
            $id = sizeof(self::$views);
            array_push(self::$views,$file);
        }
        self::$first = false;
        $view = new View(Compiler::Get($file),$id);
        $view->file = $file;
        return $view;
    }
    
    function GetTemplate($template)
    {
        $this->Elements[$template]->parent = null;
        $this->Elements[$template]->template = $this->file;
        $view = new View($this->Elements[$template],$template);
        $view->istemplate = true;
        return $view;
    }
    
    function GetElementData($e)
    {
        $f = fopen($this->overhead->template,"r");
        
        fseek($f,$e->tagstart);
        
        $data = fread($f,$e->tagend - $e->tagstart);
        fclose($f);
        
        return $data;
    }
    
    static function UseJSModule($module)
    {
        if (!in_array($module,self::$modules))
            array_push(self::$modules,$module);
    }
    
    static function Fire($event,$args)
    {
        array_push(self::$events,array("event" => $event,"args"=>$args));
    }
    
    static function SendEvents()
    {
        $obj = new \StdClass;
        $obj->events = self::$events;
        header('Content-Type: application/json');
        header("Cache-Control: max-age=0, no-cache, no-store");
        die(json_encode($obj));
    }
    
    static function Redirect($loc)
    {
<<<<<<< HEAD
        header("Location: $loc");
        if (self::$update)
            http_response_code(201);
=======
        header("location: $loc");
        if (self::$update)
            http_response_code(200);
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0
    }
    
    function __construct($overhead,$vid)
    {
        
        $this->overhead = $overhead;
        
        $this->Elements = array();
        $this->loopElement($this->overhead);
        
        $this->vid = $vid;
        
    }
    
    function SetData($id,$value)
    {
        if (!isset($this->data[$id]))
            $this->data[$id] = new  \StdClass;
        $this->data[$id]->v = $value;
    }
    
    function SetAppend($id,$append)
    {
        if (!isset($this->data[$id]))
            $this->data[$id] = new \StdClass;
        $this->data[$id]->append = $append;
    }
    
    function AddData($id,$value)
    {
        if (!isset($this->data[$id]->v))
        {
            if (!isset($this->data[$id]))
            $this->data[$id] = new \StdClass;
            $this->data[$id]->v = array();
        }
        array_push($this->data[$id]->v,$value);
    }
    
    function SetAttribute($id,$attr,$value)
    {
        if (!isset($this->data[$id]))
            $this->data[$id] = new  \StdClass;
            
        if (!isset($this->data[$id]->a))
            $this->data[$id]->a = array($attr => $value);
        else
            $this->data[$id]->a[$attr] = $value;
    }
    
    private function loopElement($e)
    {
        $sibling = 0;
        foreach ($e->children as $c)
        {
            $c->sibling = $sibling;
            //$c->parent = $e;
            $this->Elements[$c->id] = $c;
            $this->loopElement($c);
            $sibling++;
        }
    }
    
    function getRenderData()
    {
        $obj = new \StdClass;
        if ($this->istemplate)
            $obj->t = $this->vid;
        else
            $obj->v = $this->vid;
        $obj->data = array();
        foreach ($this->data as $t => $data)
        {
            $datatosend = new \StdClass;
            $datatosend->t = $t;
            if (isset($data->v) && is_array($data->v))
            {
                $arrayobj = new \StdClass;
                $arrayobj->data = array();
                foreach ($data->v as $d)
                {
                    array_push($arrayobj->data,array("t" => $t, "v" => $d->getRenderData(), "a"=>true));
                }
                $datatosend->v = $arrayobj;
            }
            else
            if (isset($data->v) && gettype($data->v) == "object")
            {
                $datatosend->v = $data->v->getRenderData();
            }
            else
                $datatosend->d = $data;
                
            if (isset($data->a))
            {
                $datatosend->a = $data->a;
            }
            
            if(isset($data->append) && $data->append)
                $datatosend->append = true;
            array_push($obj->data,$datatosend);
        }
        
        return $obj;
    }
    
    function Render()
    {
<<<<<<< HEAD
        self::_render();
    }
    
    private function _render()
    {
=======
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0
        if (self::$update)
        {
            $obj = $this->getRenderData();
            $obj->views = self::$views;
            $obj->modules = self::$modules;
            $obj->events = self::$events;
            header('Content-Type: application/json');
            header("Cache-Control: max-age=0, no-cache, no-store");
            echo json_encode($obj);
        }
        else
        {
            self::$metadata = array("modules" => self::$modules,"events" => self::$events);
            
            $elements = $this->overhead->children;
            $cur = $this->overhead;
            
            $f = fopen($this->overhead->template,"r");
            
            $this->renderElement($cur,$f);
            
            fclose($f);
        }
    }
    
    private function renderElement($cur,$f)
    {
        
        $curpos = $cur->start;
        $lastchildend = $cur->end;
        
        fseek($f,$cur->tagstart);
        if (! (isset($cur->istemplate) && $this->istemplate))
        if ($cur->start - $cur->tagstart > 0)
        {
            $rawtag =  fread($f,$cur->start - $cur->tagstart);
            
            if ( isset($this->data[$cur->id]) && isset($this->data[$cur->id]->a) )
            {
                $attrs = $this->data[$cur->id]->a;
                $tag = new Tag($rawtag);
                foreach ($attrs as $key => $value )
                {
                    $tag->SetAttribute($key,$value);
                }
                $rawtag = $tag->ToString();
            }
            
            echo $rawtag;
        }
        
        if ( isset($this->data[$cur->id]) && isset($this->data[$cur->id]->v) && !(isset($cur->ignore) && !$this->istemplate))
        {
            if (isset($this->data[$cur->id]->v))
            {
                $d = $this->data[$cur->id]->v;
                if (gettype($d) == "object")
                {
                    if (get_class($d) == "Alloy\View")
<<<<<<< HEAD
                        $d->_render();
=======
                        $d->Render();
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0
                }
                elseif (is_array($d))
                {
                    foreach($d as $data)
                    {
                        if (gettype($data) == "object")
                        {
                            if (get_class($data) == "Alloy\View")
<<<<<<< HEAD
                                $data->_render();
=======
                                $data->Render();
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0
                        }
                        else
                            echo $data;
                    }
                }
                else
                    echo $d;
                $curpos = $cur->end;
            }
            else
            {
                foreach ($cur->children as $c)
                {
                    fseek($f,$curpos);
                    echo "HERE".fread($f,$c->tagstart - $curpos);
                    $this->renderElement($c,$f);
                    $curpos = $c->tagend;
                }
            }
        }
        else
        {
            foreach ($cur->children as $c)
            {
                fseek($f,$curpos);
                if ($c->tagstart - $curpos > 0)
                {
                    $out = fread($f,$c->tagstart - $curpos);
                    if (isset($this->overhead->headend) && $c->tagstart > $this->overhead->headend && $curpos <= $this->overhead->headend)
                    {
                        $metadata = json_encode(self::$metadata);
                        $out = substr_replace($out, "<script type=\"alloy/metadata\" id=\"ALLOYMETADATA\">$metadata</script>", $this->overhead->headend - $curpos, 0);
                    }
                    
                    echo $out;
                }
                $this->renderElement($c,$f);
                $curpos = $c->tagend;
            }
        }
        fseek($f,$curpos);
        
        
        if ($cur->tagend - $curpos > 0 && (!$this->istemplate || isset($cur->ignore)))
            echo fread($f,$cur->tagend - $curpos);
        elseif ($cur->end - $curpos > 0 && $this->istemplate)
            echo fread($f,$cur->end - $curpos);
            
    }
    
    public static function On($event, $callback)
    {
        if (isset(self::$postdata["event"]))
        {
<<<<<<< HEAD
            if (isset(self::$postdata["args"]))
                die( json_encode($callback(self::$postdata["args"])));
            else
                die( json_encode($callback()));
=======
            die( json_encode($callback(self::$postdata["args"])));
>>>>>>> c270cf36b2964427683b18c8d9bf269e298dcbb0
        }
    }
    
    public static function OnPost($callback)
    {
        if (isset(self::$postdata["post"]))
        {
            $callback(self::$postdata["post"]);
        }
    }
}
?>