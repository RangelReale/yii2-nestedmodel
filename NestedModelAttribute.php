<?php

namespace RangelReale\nestedmodel;

use yii\base\Object;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

class NestedModelAttribute extends Object
{
    /**
     * @var NestedModelBehavior
     */    
    public $behavior;

    /**
     * @var string
     */
    public $originalRelation; 

    /**
     * @var string
     */
    public $targetRelation; 

    /**
     * @var boolean
     */
    public $isArray = false;
    
    /**
     * @var boolean
     */
    public $clearOnSet = false;
    
    /**
     * @var array|false|Closure($behavior, $targetRelation, $index)
     */
    public $newItem = false;

    /**
     * @var Closure($behavior, $targetRelation)
     */
    public $clearBeforeSave;
    
    /**
     * @var Closure($behavior, $targetRelation, $index, $model)
     */
    public $processSaveModel;

    protected $_value;

    public function validate()
    {
        if (!isset($this->_value))
            return true;
        
        $isValid = true;
        if ($this->isArray) {
            foreach ($this->_value as $arrayIndex => $arrayItem) {
                if (!$arrayItem->validate()) {
                    foreach ($arrayItem->getErrors() as $eattr => $evalue) {
                        foreach ($evalue as $eerror) {
                            $this->behavior->owner->addError($this->targetRelation.'.'.$arrayIndex.'.'.$eattr, $eerror);
                        }
                    }
                    $isValid = false;
                }
            }
        } else {
            if (!$this->_value->validate()) {
                foreach ($this->_value->getErrors() as $eattr => $evalue) {
                    foreach ($evalue as $eerror) {
                        $this->behavior->owner->addError($this->targetRelation.'.'.$eattr, $eerror);
                    }
                }
            }
            $isValid = false;
        }
        return $isValid;
    }   
    
    public function save()
    {
        if ($this->clearOnSet) {
            if ($this->clearBeforeSave instanceof \Closure) {
                call_user_func($this->clearBeforeSave, $this->behavior, $this->targetRelation);
            } else {
                throw new InvalidConfigException('Must set clearBeforeSave when clearOnSet');
            }
        }

        if (!isset($this->_value))
            return true;
        
        $isValid = true;
        if ($this->isArray) {
            foreach ($this->_value as $arrayIndex => $arrayItem) {
                if ($this->processSaveModel instanceof \Closure) {
                    call_user_func($this->processSaveModel, $this->behavior, $this->targetRelation, $arrayIndex, $arrayItem);
                }
                if (!$arrayItem->save()) {
                    $isValid = false;
                }
            }
        } else {
            if ($this->processSaveModel instanceof \Closure) {
                call_user_func($this->processSaveModel, $this->behavior, $this->targetRelation, null, $this->behavior->owner->{$this->originalRelation});
            }
            if (!$this->_value->save())
                $isValid = false;
        }
        return $isValid;
    }   

    public function getValue()
    {
        if (isset($this->_value)) {
            return $this->_value;
        }
        
        return $this->behavior->owner->{$this->originalRelation};
    }   
    
    public function getValueAttr($attr = null)
    {
        return Html::getNestedAttributeValue($this->getValue(), $attr);
    }

    public function setValue($value)
    {
        if (is_null($value) || !is_array($value))
            return;

        $currentValue = isset($this->_value)?$this->_value:$this->behavior->owner->{$this->originalRelation};
        
        if ($this->isArray) {
            if (is_null($currentValue) || $this->clearOnSet) {
                $currentValue = [];
            }
            
            foreach ($value as $vindex => $vvalue) {
                if (!isset($currentValue[$vindex])) {
                    $newItem = $this->createNewItem($vindex);
                    if (is_null($newItem)) {
                        continue;
                    }
                    $currentValue[$vindex] = $newItem;
                }
                $currentValue[$vindex]->setAttributes($vvalue);
            }
        } else {
            if (!isset($currentValue)) {
                $newItem = $this->createNewItem();
                if (is_null($newItem)) {
                    return;
                }
                $currentValue = $newItem;
            }
            
            $currentValue->setAttributes($vvalue);
        }        
        
        $this->_value = $currentValue;
    }   
    
    public function setValueAttr($value, $attr = null)
    {
        if ($attr === null) {
            $this->setValue($value);
            return;
        }
        if (isset($this->_value))
            $currentValue = &$this->_value;
        else
            $currentValue = &$this->behavior->owner->{$this->originalRelation};
        Html::setNestedAttributeValue($currentValue, $attr, $value);
    }
    
    public function reset()
    {
        $this->_value = null;
    }
    
    protected function createNewItem($index = null)
    {
        if ($this->newItem === false) {
            if (YII_DEBUG) {
                \Yii::trace("Nested model {$this->targetRelation} cannot create new item in '" . get_class($this) . "'.", __METHOD__);
            }
            return null;
        }
        if (is_null($this->newItem)) {
            throw new InvalidConfigException('New item class not configured');
        }
        
        if ($this->newItem instanceof \Closure) {
            return call_user_func($this->newItem, $this->behavior, $this->targetRelation, $index);
        }        
            
        return \Yii::createObject($this->newItem);
    }
}