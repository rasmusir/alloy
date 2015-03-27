<?php
namespace Alloy;
require("Compiler.php");

class View
{
    private $data = null;
    
    static function Get($file)
    {
        return new View($file);
    }
    
    function GetElementData($e)
    {
        $f = fopen($this->overhead->template,"r");
        
        fseek($f,$e->tagstart);
        
        $data = fread($f,$e->tagend - $e->tagstart);
        fclose($f);
        
        return $data;
    }
    
    function __construct($file)
    {
        $this->overhead = Compiler::Compile($file);
        
        $this->Elements = array();
        $this->loopElement($this->overhead);
        
    }
    
    function GetElement($id)
    {
        $e = $this->Elements[$id];
        return new Element($this->GetElementData($e),$e);
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
    
    function Render($data,$attr, $update)
    {
        if ($update)
        {
            $obj = new \StdClass;
            $obj->data = $data;
            $obj->attr = $attr;
            header('Content-Type: application/json');
            header("Cache-Control: max-age=0, no-cache, no-store");
            echo json_encode($obj);
        }
        else
        {
            $elements = $this->overhead->children;
            $cur = $this->overhead;
            
            $f = fopen($this->overhead->template,"r");
            
            $this->renderElement($data,$attr,$cur,$f);
            
            fclose($f);
        }
    }
    
    private function renderElement($data,$attr,$cur,$f)
    {
        fseek($f,$cur->tagstart);
        if ($cur->start - $cur->tagstart > 0)
        {
            $rawtag =  fread($f,$cur->start - $cur->tagstart);
            
            if ( isset($attr[$cur->id]) )
            {
                $attrs = $attr[$cur->id];
                $tag = new Tag($rawtag);
                foreach ($attrs as $key => $value )
                {
                    $tag->SetAttribute($key,$value);
                }
                $rawtag = $tag->ToString();
            }
            
            echo $rawtag;
        }
        
        $curpos = $cur->start;
        $lastchildend = $cur->end;
        
        if ( isset($data[$cur->id]) )
        {
            echo $data[$cur->id];
            $curpos = $cur->end;
        }
        else
        {
            foreach ($cur->children as $c)
            {
                fseek($f,$curpos);
                echo fread($f,$c->tagstart - $curpos);
                $this->renderElement($data,$attr,$c,$f);
                $curpos = $c->tagend;
            }
        }
        fseek($f,$curpos);
        
        if ($cur->tagend - $curpos > 0)
            echo fread($f,$cur->tagend - $curpos);
    }
}

class Element
{
    private $innerHTML;
    
    function __construct($data,$overhead)
    {
        $this->tag = substr($data,0,$overhead->start - $overhead->tagstart);
        $this->endtag = substr($data,$overhead->end - $overhead->tagstart);
        $this->innerHTML = substr($data,$overhead->start - $overhead->tagstart,$overhead->end - $overhead->start);
    }
    
    function GetRaw()
    {
        return $this->tag.$this->innerHTML.$this->endtag;
    }
    
    function GetInnerHTML()
    {
        return $this->innerHTML;
    }
    
    function SetInnerHTML($text)
    {
        $this->innerHTML = $text;
    }
}

?>