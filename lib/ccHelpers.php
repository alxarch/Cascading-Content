<?php

function path_to_title($path)
{
  $title = explode('/', $path);
  
  foreach($title as $i => $t)
  {
    $title[$i] = ucfirst($t);
  }
  
  return implode(' - ', array_filter($title));
}

/*
function breadcrumbs($path)
{
  //TODO: Breadcrumbs helper;
}
*/


function _ucfirst($string)
{
  if(function_exists('mb_ucfirst')) return mb_ucfirst($string);
  
  $enc = mb_detect_encoding($string);
  
  if($enc)
  {
    $first = mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc);
    
    $rest = mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    
    return $first.$rest;
    
  }
  
  return ucfirst($string);
  
}

function scripts($scripts, $defer=true)
{
  if(null === $scripts) return '';
  $result = array();
  
  foreach($scripts as $src)
  {
    $s = sprintf('<script %s src="%s"></script>', $defer?'defer':'',  $src );
    $result[] = $s;
  }
  
  return implode("\n", $result);
}

function meta($meta)
{
  $result = array();
  foreach($meta as $name => $content)
  {
    //Reserve 'special' @name for other uses.
    if(0 === strpos($name, '@')) continue;
    
    $result[] = sprintf('<meta name="%s" content="%s"/>', $name, $content);
  }
  
  return implode("\n", $result);
}

function styles($styles)
{
  if(null === $styles) return '';
  
  $result = array();
  foreach($styles as $href)
  {
    $s = sprintf('<link href="%s" rel="stylesheet" type="text/css">', $href);
    $result[] = $s;
  }
  
  return implode("\n", $result);
}
