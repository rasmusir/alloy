<?php
namespace Alloy;
require("tag.php");

class Compiler
{
    static function Get($filename)
    {
        $ohm = $filename.".json";
        if (file_exists($ohm))
        {
            $file = fopen($ohm,"r");
            $size = filesize($ohm);
            $string = fread($file,$size);
            fclose($file);
            $overhead = json_decode($string);
            if ($overhead->timestamp == filemtime($filename))
                return $overhead;
        }
        
        $file = fopen($ohm,"w");
        $overhead = self::Compile($filename);
        $overhead->timestamp = filemtime($filename);
        $string = fwrite($file,json_encode($overhead));
        fclose($file);
        
        return $overhead;
    }
    
    static function Compile($filename)
    {
        $file = fopen($filename,"r");
        
        $size = filesize($filename);
        
        $string = fread($file,$size);
        
        $notdone = true;
        $offset = 0;
        
        $root = new \stdClass;
        $root->start = 0;
        $root->tagstart = 0;
        $root->tagend = $size;
        $root->end = $size;
        $root->children = array();
        $root->template = $filename;
        $root->id = "root";
        
        $root->headend = stripos($string,"</head>");
        
        $parent = $root;
        
        while ($notdone)
        {
            $pos = stripos($string,"active",$offset);
            
            if ($pos == false)
                $notdone = false;
            else
            {
                $found = true;
                $i = $pos;
                $tagstart = $pos;
                
                $start = $pos;
                $end = 0;
                $back = true;
                $lastspace = 0;
                $tag = "";
                while ($found)
                {
                    if ($back)
                    {
                        $c = substr($string,$i,1);
                        if ( $c == '<')
                        {
                            $back = false;
                            $tagstart = $i;
                            $i = $pos + 6;
                        }
                        else if ( ($i<=$offset) || $c == '>')
                            $found = false;
                        else if ($c == " ")
                            $lastspace = $i;
                        $i--;
                    }
                    else
                    {
                        if (substr($string,$i,1) == '>')
                        {
                            $start = $i;
                            $tag = substr($string,$tagstart,$start-$tagstart);
                            break;
                        }
                        else if (substr($string,$i,1) == '<')
                        {
                            $found = false;
                        }
                        $i++;
                    }
                }
                $tag = new Tag($tag);
                $tagtype = $tag->tag;
                
                $searchstring = substr($string,$start);
                $opentagtype = "<".$tagtype;
                $closetagtype = "</".$tagtype;
                
                $openoffset = 0;
                $closeoffset = 0;
                
                $opentag = 99999999999;
                $closetag = stripos($searchstring,$closetagtype,$closeoffset);
                $previousclosetag = $closetag;
                $end = $start+1;
                $tagend = $end;
                if ($closetag)
                {
                    while ($previousclosetag > $opentag)
                    {
                        $openoffset = $opentag + strlen($opentagtype);
                        $closeoffset = $closetag + strlen($closetagtype);
                        
                        
                        $previousclosetag = $closetag;
                        
                        $opentag = stripos($searchstring,$opentagtype,$openoffset);
                        $closetag = stripos($searchstring,$closetagtype,$closeoffset);
                        if ($closetag == false)
                            break;
                    }
                    $end = $start + $previousclosetag;
                    $tagend = $start + stripos($searchstring,">",$previousclosetag) +1;
                }
                
                if ($found)
                {
                    $obj = new \stdClass;
                    $obj->tagstart = $tagstart;
                    $obj->start = $start+1;
                    $obj->id=$tag->GetAttribute("name");
                    $obj->end = $end;
                    $obj->tagend = $tagend;
                    $obj->children = array();
                    $type = $tag->GetAttribute("type");
                    while ($parent->tagend < $obj->start)
                    {
                        $pp = $parent->parent;
                        unset($parent->parent);
                        $parent = $pp;
                    }
                    
                    if ($type && $type == "text/template")
                        $obj->istemplate = true;
                    if (isset($parent->istemplate))
                        $obj->ignore = true;
                    array_push($parent->children,$obj);
                    $obj->parent = $parent;
                    $parent = $obj;
                }
                $offset = $pos + 6;
            }
        }
        if (isset($parent->parent))
            $p = $parent->parent;
        else
            $p = null;
        while($p)
        {
            if (isset($parent->parent))
            {
                $p = $parent->parent;
            }
            else
                $p = false;
            unset($parent->parent);
            $parent = $p;
        }
        fclose($file);
        
        return $root;
    }
}

?>