<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Item implements ArrayAccess{
	
	private $domain;
	private $itemName;
	public $data = array();
	private $current_state = NULL;
	private $states = NULL;
	
	public function __construct($domain, $itemName, array $values = array()){
		
		$this->domain = $domain;
		$this->itemName = $itemName;
		
		if(!empty($values)){
			$this->multiset($values);
		}	
	}
	
	public function save(){
		
		$this->states[] = clone $this;
		$this->current_state = count($this->states) - 1;
		
		// return Amazon::SDB()->put_attributes($this->domain, $this->itemName, $this->data, $overwrite = true, $returncurl = false);
	}
	
	public function states(){
		
		return $this->states;
	}
	
	public function state($state_id){
		
		if(isset($this->states[$state_id])){
			
			$first = $this->states[$state_id];
		 	$this->domain = $first->domain();
			$this->itemName = $first->itemName();
			$this->states = $first->states();
			$this->delete_vars();
			$this->multiset($first->vars());			
			$this->current_state = $state_id;
			return true;
		}else{
			return false;
		}
		
	}
	
	public function previous(){
		
		return $this->state($this->current_state - 1);
	}
	
	public function next(){
		
		return $this->state($this->current_state + 1);
	}
	
	public function first(){
		
		return $this->state(0, $reset = false);
	}
	
	public function last(){
		
		return $this->state(count($this->states) - 1);
	}
	
	public function vars(){
		return $this->data;
	}
	
	
	public function delete_vars(){
		
		$this->data = array();
	}
	
	public function duplicate($domain = NULL, $itemName = NULL){
		
		$domain = $domain !== NULL ? $domain :  $this->domain;
		$itemName = $itemName !== NULL ? $itemName : $this->itemName;
		
		return new FlexSDB_Item($domain, $itemName, $this->data);
	}
	
	public function domain(){
		return $this->domain;
	}
	
	public function itemName(){
		return $this->itemName;
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
	
	
	
	
}