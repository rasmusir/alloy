<?php

class Profiler
{
    static $data;
    static $profiler;
    static $current = null;
    static $fn;
    static function Initialize($filename)
    {
        self::$data = new stdClass;
        self::$data->sub = array();
        self::$fn = $filename;
        if (file_exists($filename))
        {
            $serialized = file_get_contents($filename);
            $d = unserialize($serialized);
            if ($d != null)
                self::$data = $d;
        }
        
        self::$data->parent = null;
        self::$current = self::$data;
    }
    
    static function Start($name)
    {
        if (!isset(self::$current->sub[$name]))
        {
            $current = new stdClass();
            $current->sub = array();
            $current->count = 0;
            $current->total = 0;
            self::$current->sub[$name] = $current;
        }
        else
            $current = self::$current->sub[$name];
        $current->parent = self::$current;
        self::$current = $current;
        $current->start = microtime(true);
    }
    
    static function Stop()
    {
        $stop = microtime(true);
        $current = self::$current;
        if ($current->parent == self::$data)
        {
            
            $current->total += ($stop - $current->start) * 1000;
            $current->count += 1;
            self::$current = self::$data;
            unset($current->start);
            unset($current->parent);
            file_put_contents("alloy/".self::$fn,serialize(self::$data));
        }
        else
        {
            self::$current = $current->parent;
            $current->total += ($stop - $current->start) * 1000;
            $current->count += 1;
            unset($current->parent);
            unset($current->start);
        }
        
    }
    
    static function ClearAll()
    {
        file_put_contents(self::$fn,"");
    }
    
    private static function prepare($profile)
    {
        
        foreach ($profile->sub as $p)
        {
            self::prepare($p);
        }
        unset($profile->parent);
    }
    
    static function GetData()
    {
        return self::$data;
    }
}

Profiler::Initialize("profile");

?>