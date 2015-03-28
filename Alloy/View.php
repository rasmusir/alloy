<?php
namespace Alloy;
require("Compiler.php");

class View
{
    private $data = array();
    
    private static $update = null;
    private static $views = array();
    private static $first = true;
    
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
        return new View($file,$id);
    }
    
    function GetElementData($e)
    {
        $f = fopen($this->overhead->template,"r");
        
        fseek($f,$e->tagstart);
        
        $data = fread($f,$e->tagend - $e->tagstart);
        fclose($f);
        
        return $data;
    }
    
    function __construct($file,$vid)
    {
        
        $this->overhead = Compiler::Compile($file);
        
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
    
    function SetAttribute($id,$attr,$value)
    {
        if (!isset($this->data[$id]))
            $this->data[$id] = new  \StdClass;
            
        if (!isset($this->data[$id]->a))
            $this->data[$id]->a[$attr] = $value;
        else
            $this->data[$id]->a = array($attr => $value);
    }
    
    private function loopElement($e)
    {
        $sibling = 0;
        foreach ($e->children as $c)
        {
            $c->sibling = $sibling;
            $c->parent = $e;
            $this->Elements[$c->id] = $c;
            $this->loopElement($c);
            $sibling++;
        }
    }
    
    function getRenderData()
    {
        $obj = new \StdClass;
        $obj->v = $this->vid;
        $obj->data = array();
        foreach ($this->data as $t => $data)
        {
            if (isset($data->v) && gettype($data->v) == "object")
            {
                array_push($obj->data,array("t" => $t, "v" => $data->v->getRenderData()));
            }
            else
                array_push($obj->data,array("t" => $t, "d" => $data));
        }
        
        return $obj;
    }
    
    function Render()
    {
        if (self::$update)
        {
            $obj = new \StdClass;
            $obj->views = self::$views;
            $obj->data = array();
            foreach ($this->data as $t => $data)
            {
                if (isset($data->v) && gettype($data->v) == "object")
                {
                    array_push($obj->data,array("t" => $t, "v" => $data->v->getRenderData()));
                }
                else
                    array_push($obj->data,array("t" => $t, "d" => $data));
            }
                
            
            #$obj->data = $this->data;
            header('Content-Type: application/json');
            header("Cache-Control: max-age=0, no-cache, no-store");
            echo json_encode($obj);
        }
        else
        {
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
        
        if ( isset($this->data[$cur->id]) && isset($this->data[$cur->id]->v))
        {
            if (isset($this->data[$cur->id]->v))
            {
                $d = $this->data[$cur->id]->v;
                if (gettype($d) == "object")
                {
                    if (get_class($d) == "Alloy\View")
                        $d->Render();
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
                    echo fread($f,$c->tagstart - $curpos);
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
                    echo fread($f,$c->tagstart - $curpos);
                $this->renderElement($c,$f);
                $curpos = $c->tagend;
            }
        }
        fseek($f,$curpos);
        
        if ($cur->tagend - $curpos > 0)
            echo fread($f,$cur->tagend - $curpos);
    }
}
?>