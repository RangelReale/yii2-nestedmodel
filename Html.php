<?php

namespace yii\helpers;

class Html extends BaseHtml
{
    public static function getNestedAttributeValue($model, $name)
    {
        $curvalue = $model;
        if ($name !== null)
        {
            foreach (explode('.', $name) as $a)
            {
                if (is_array($curvalue))
                    $curvalue = $curvalue[$a];
                else
                    $curvalue = $curvalue->$a;
            }
        }
        return $curvalue;
    }
    
    public static function setNestedAttributeValue(&$model, $name, $value)
    {
        if ($name !== null)
        {
            $aname = explode('.', $name);
            $last = array_pop($aname);
            $curvalue = &$model;
            foreach ($aname as $a)
            {
                if (is_array($curvalue))
                    $curvalue = &$curvalue[$a];
                else
                    $curvalue = &$curvalue->$a;
            }
            if (is_array($curvalue))
                $curvalue[$last] = $value;
            else
                $curvalue->$last = $value;
        }        
    }
    
    public static function getInputName($model, $attribute)
    {
        if (strpos($attribute, '.')!==FALSE)
        {
            $attr = '';
            foreach (explode('.', $attribute) as $a)
            {
                if ($attr == '') 
                    $attr = $a;
                else
                    $attr .= '['.$a.']';
            }
            \Yii::trace('getInputName: '.$attribute.' = '.$attr, 'nestedmodel');
            
            $attribute = $attr;
        }
        return BaseHtml::getInputName($model, $attribute);
    }
}
