<?php
/**
 * @package    CCT
 * @file       overloadable.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 *
 * $Source:  $
 */

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('autoloader.php');  // includes/autoloader.php
}

class overloadable
{
    static function call($obj, $method, $params=null)
    {
        $class = get_class($obj);

        // Get real method name
        $suffix_method_name = $method.self::getMethodSuffix($method, $params);

        if (method_exists($obj, $suffix_method_name))
        {
            // Call method
            return call_user_func_array(array($obj, $suffix_method_name), $params);

            #return $obj->$suffix_method_name($params);
        }
        else
        {
            throw new Exception('Tried to call unknown method '.$class.'::'.$suffix_method_name);
        }
    }

    static function getMethodSuffix($method, $params_ary=array())
    {
        $c = '__';

        if( is_array($params_ary) )
        {
            foreach($params_ary as $i=>$param)
            {
                // Adding special characters to the end of method name
                switch(gettype($param))
                {
                    case 'array':       $c .= 'a'; break;
                    case 'boolean':     $c .= 'b'; break;
                    case 'double':      $c .= 'd'; break;
                    case 'integer':     $c .= 'i'; break;
                    case 'NULL':        $c .= 'n'; break;
                    case 'object':      $c .= 'o'; break;
                    case 'resource':    $c .= 'r'; break;
                    case 'string':      $c .= 's'; break;
                }
            }
        }

        return $c;
    }

    // Get a reference variable by name
    static function &refAccess($var_name)
    {
        $r =& $GLOBALS["$var_name"];

        return $r;
    }
}