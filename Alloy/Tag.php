<?php
namespace Alloy;

class Tag
{
    private $attr = array();
    
    function __construct($str)
    {
        $split = explode(" ",$str);
        $this->tag = substr($split[0],1);
        $first = true;
        foreach ($split as $a)
        {
            if ($first)
            {
                $first = false;
                continue;
            }
            $s = explode("=",$a);
            $attr = $s[0];
            if (isset($s[1]))
            {
                $this->attr[$attr] = str_replace("\"","",$s[1]);
                $this->attr[$attr] = str_replace(">","",$this->attr[$attr]);
            }
            else
            {
                $attr = str_replace(">","",$s[0]);
                $this->attr[$attr] = null;
            }
        }
    }
    
    function SetAttribute($attr,$value)
    {
        $this->attr[$attr] = $value;
    }
    
    function GetAttribute($attr)
    {
        if (isset($this->attr[$attr]))
            return $this->attr[$attr];
        return null;
    }
    
    function ToString()
    {
        $s = "<".$this->tag . " ";
        
        foreach ($this->attr as $attr => $value)
        {
            if ($value != null)
                $s .= $attr."=\"".$value."\" ";
            else
                $s .= $attr." ";
        }
        
        return $s.">";
    }
}
?>