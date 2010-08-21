<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Item implements ArrayAccess{
	
	public $data = array();
	private $itemName;
	private $domain;
	
	public function __construct($domain, $itemName){
		
		$this->domain = $domain;
		$this->itemName = $itemName;
		
	}
	
	public function offsetSet($name, $value){
		
        $this->$name = $value;
    }

    public function offsetExists($name){
	
        return isset($this->$name);
    }
    
	public function offsetUnset($name){
		
        unset($this->$name);
    }

    public function offsetGet($name){
	
        return $this->$name;
    }
	
	public function __isset($name){
		
		return isset($this->data[$name]);
	}
	
	public function __unset($name){
		
 		unset($this->data[$name]);
	}
	
	public function __get($name){
		
		return is_numeric($this->data[$name]) ? (float) $this->data[$name] : $this->data[$name];
	}
	
	public function __set($name, $value){
		
		if(count($this->data) < 256){
			
			$setval = $this->setval($value);
			
			if($setval !== NULL){
			
				return $this->data[$name] = $setval;
				
			}else{
				
				if(isset($this->$name)){
					unset($this->$name);
				}
				
			}
			
		}else{
			
			throw new Kohana_Exception('Max amount of key-value pairs exceeded');
		}
		
	}
		
	private function setval($value){
				
		if(is_string($value)){
			
			if(mb_strlen($value) > 140){
						
				return FlexSDB_Strings::explode($value);
			}else{
				return $value;
			}
			
		}elseif(is_array($value)){
			
			foreach ($value as $k => $v){
				
				// no multidimensional arrays allowed
				if(is_array($v)){
					
					unset($value[$k]);
				}
			}
			
			return $value;
			
		}elseif(is_object($value)){
			
			return NULL;
			
		}elseif(is_bool($value)){
			
			return (int) $value ?: 0;
			
		}elseif(is_numeric($value)){
			
			return sprintf('%016.6f', $value);
			
		}elseif($value === NULL){
			
			return NULL;
			
		}else{
			
			return $value;
		}
		
	}
	
	public function multiset(array $data){
		
		foreach($data as $name => $value){
			
			$this->$name = $value;
		}
		
	}
	
	public function save(){
		
		return Amazon::SDB()->put_attributes($this->domain, $this->itemName, $this->data, $overwrite = true, $returncurl = false);
		
	}
	
}