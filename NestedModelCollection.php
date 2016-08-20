<?php

namespace RangelReale\nestedmodel;

class NestedModelCollection extends \yii\base\Component implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private $_data = [];
    
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }
    
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_data[$this->uniqueId()] = $value;
        } else {
            $this->_data[$offset] = $value;
        }        
    }
    
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);        
    }
        
    public function count()
    {
        return count($this->_data);
    }
    
    public function getIterator()
    {
        return new \ArrayIterator($this->_data);
    }
    
    protected function uniqueId()
    {
        return uniqid('nm'.rand(), false);
    }
}
