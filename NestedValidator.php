<?php

namespace RangelReale\nestedmodel;

use yii\validators\Validator;

class NestedValidator extends Validator
{
    public function init()
    {
        parent::init();
        $this->skipOnEmpty = false;
    }
    
    public function validateAttribute($model, $attribute)
    {
    }
}
