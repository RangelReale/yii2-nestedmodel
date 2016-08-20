<?php

namespace RangelReale\nestedmodel;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\db\BaseActiveRecord;
use yii\base\InvalidValueException;

class NestedBehavior extends Behavior
{
    /**
     * @var string
     */    
    public $namingTemplate = '{relation}_nested';

    /**
     * @var array List of the model relations in one of the following formats:
     * ```php
     *  [
     *      'first', // This will use default configuration and virtual relation template
     *      'second' => 'target_second', // This will use default configuration with custom relation template
     *      'third' => [
     *          'relation' => 'thrid_rel', // Optional
     *          'targetAttribute' => 'target_third', // Optional
     *          // Rest of configuration
     *      ]
     *  ]
     * ```
     */
    public $nestedModels = [];
    
    /**
     * @var array
     */
    public $nestedModelConfig = ['class' => 'RangelReale\nestedmodel\NestedAttribute'];
    
    /**
     * @var $NestedModelAttribute[]
     */
    public $nestedModelValues = [];
    
    public function init()
    {
        $this->prepareNestedModels();
    }
    
    protected function prepareNestedModels()
    {
        foreach ($this->nestedModels as $key => $value) 
        {
            $config = $this->nestedModelConfig;
            if (is_integer($key)) {
                $originalRelation = $value;
                $targetRelation = $this->processTemplate($originalRelation);
            } else {
                $originalRelation = $key;
                if (is_string($value)) {
                    $targetRelation = $value;
                } else {
                    $targetRelation = ArrayHelper::remove($value, 'targetRelation', $this->processTemplate($originalRelation));
                    $originalRelation = ArrayHelper::remove($value, 'relation', $originalRelation);
                    $config = array_merge($config, $value);
                }
            }
            $config['behavior'] = $this;
            $config['originalRelation'] = $originalRelation;
            $config['targetRelation'] = $targetRelation;
            $this->nestedModelValues[$targetRelation] = $config;
        }
    }    
    
    protected function processTemplate($originalRelation)
    {
        return strtr($this->namingTemplate, [
            '{relation}' => $originalRelation,
        ]);
    }    
    
    public function events()
    {
        $events = [];
        $events[BaseActiveRecord::EVENT_BEFORE_VALIDATE] = 'onBeforeValidate';
        $events[BaseActiveRecord::EVENT_AFTER_FIND] = 'onAfterFind';
        $events[BaseActiveRecord::EVENT_AFTER_INSERT] = 'onAfterSave';
        $events[BaseActiveRecord::EVENT_AFTER_UPDATE] = 'onAfterSave';
        return $events;
    }
    
    /**
     * Performs validation for all the relations
     * @param Event $event
     */
    public function onBeforeValidate($event)
    {
        foreach (array_keys($this->nestedModelValues) as $targetRelation) {
            $value = $this->getNestedModel($targetRelation);
            if ($value instanceof NestedAttribute && $this->owner->isAttributeSafe($targetRelation)) {
                if (!$value->validate())
                    $event->isValid = false;
            }
        }
    }    

    /**
     * Reset when record changes
     * @param Event $event
     */
    public function onAfterFind($event)
    {
        foreach (array_keys($this->nestedModelValues) as $targetRelation) {
            $value = $this->getNestedModel($targetRelation);
            if ($value instanceof NestedAttribute) {
                $value->reset();
            }
        }
    }    

    /**
     * Save relation if safe
     * @param Event $event
     */
    public function onAfterSave($event)
    {
        foreach (array_keys($this->nestedModelValues) as $targetRelation) {
            $value = $this->getNestedModel($targetRelation);
            if ($value instanceof NestedAttribute && $this->owner->isAttributeSafe($targetRelation)) {
                $value->save();
            }
        }
    }    
    
    public function canGetProperty($name, $checkVars = true)
    {
        \Yii::trace('canGetProperty: '.$name, 'nestedmodel');
        if ($this->hasNestedModel($name)) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars);
    }
    
    public function hasNestedModel($name)
    {
        list($nested, $attr) = $this->parseNestedName($name);
        return isset($this->nestedModelValues[$nested]);
    }
    
    public function canSetProperty($name, $checkVars = true)
    {
        \Yii::trace('canSetProperty: '.$name, 'nestedmodel');
        if ($this->hasNestedModel($name)) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }
    
    public function __get($name)
    {
        \Yii::trace('__get: '.$name, 'nestedmodel');
        if ($this->hasNestedModel($name)) {
            list($nested, $attr) = $this->parseNestedName($name);
            return $this->getNestedModel($nested)->getValueAttr($attr);
        }
        return parent::__get($name);
    }
    
    public function __set($name, $value)
    {
        \Yii::trace('__set: '.$name.' = '.print_r($value, true), 'nestedmodel');
        if ($this->hasNestedModel($name)) {
            list($nested, $attr) = $this->parseNestedName($name);
            $this->getNestedModel($nested)->setValueAttr($value, $attr);
            return;
        }
        parent::__set($name, $value);
    }

    public function getNestedModel($name)
    {
        list($nested, $attr) = $this->parseNestedName($name);
        
        if (is_array($this->nestedModelValues[$nested])) {
            $this->nestedModelValues[$nested] = \Yii::createObject($this->nestedModelValues[$nested]);
        }
        return $this->nestedModelValues[$nested];
    }    
    
    public function parseNestedName($name)
    {
        if (($pos=strpos($name, '.'))!== FALSE)
        {
            return array(substr($name, 0, $pos), substr($name, $pos+1));
        }
        else
            return array($name, null);
    }
}