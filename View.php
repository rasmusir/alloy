<?php
namespace Alloy;
require("Compiler.php");

class View
{
    private $data = array();
    public $istemplate = false;
    
    private static $update = null;
    private static $views = array();
    private static $first = true;
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
            
            if(isset($data->append) && $data->append)
                $datatosend->append = true;
            array_push($obj->data,$datatosend);
        }
        
        return $obj;
    }
    
    function Render()
    {
        if (self::$update)
        {
            $obj = $this->getRenderData();
            $obj->views = self::$views;
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
                        $d->Render();
                }
                elseif (is_array($d))
                {
                    foreach($d as $data)
                    {
                        if (gettype($data) == "object")
                        {
                            if (get_class($data) == "Alloy\View")
                                $data->Render();
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
        
        
        if ($cur->tagend - $curpos > 0 && !$this->istemplate)
            echo fread($f,$cur->tagend - $curpos);
        elseif($cur->tagend - $curpos > 0 && isset($cur->ignore))
            echo fread($f,$cur->tagend - $curpos);
        elseif ($cur->end - $curpos > 0 && $this->istemplate)
            echo fread($f,$cur->end - $curpos);
            
    }
}
?>