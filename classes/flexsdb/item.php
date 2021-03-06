<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Item implements ArrayAccess{
	
	private $domain;
	private $itemName;
	private $data = array();
	private $_data = array();
	private $current_state = 1;
	private $states = NULL;
	private $delete_attributes = array();
	
	public function __construct($domain, $itemName, array $values = array()){
		
		if(stripos($domain, FlexSDB::prefix()) === false){
			$this->domain = FlexSDB::prefix().$domain;
			
		}else{
			$this->domain = $domain;
		}
		$this->itemName = $itemName;
		
		if(!empty($values)){
			$this->multiset($values);
		}	
	}
	
	public function set_domain($domain){
		
		return (bool) $this->domain = FlexSDB::prefix().$domain;
		
	}
	
	public function save(){	
		
		// $this->states = clone $this;
		$this->current_state = count($this->states);
				
		FlexSDB::handle(Amazon::SDB()->put_attributes($this->domain, $this->itemName, $this->data, $overwrite = true, $returncurl = true), $this->itemName, $this->data, $this->domain);
				
		if(!empty($this->delete_attributes)){
					
			FlexSDB::handle(Amazon::SDB()->delete_attributes($this->domain, $this->itemName, array_keys($this->delete_attributes), $returncurl = true), $this->itemName.'_delete');

		}
		
		return true;
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
			$this->multiset($first->as_array());			
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
	
	public function as_array(){		
				
		$array = array();
		
		foreach($this->data as $key => $var){
		    
		    if(strpos($key, '___') === 0){
		        
		        $nkey = str_replace('___', '', $key);
		        
		        $array[$nkey] = $this->{$nkey};
		        
	        }elseif(strpos($key, '__') === 0){
	            
	            // ignore
	            
            }else{
		    
    			$array[$key] = $this->{$key};
			}
		}		
		
		return $array;
	}
	
	
	public function delete_vars(){
		
		$this->data = array();
	}
	
	public function duplicate($domain = NULL, $itemName = NULL){
		
		$domain = $domain !== NULL ? FlexSDB::prefix().$domain : $this->domain;
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
		
        $this->{$name} = $value;
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
		
		if(!isset($this->data[$name])){
			
			if($this->__unset_array($name)){
				
				return NULL;
			}
		}
		
		$this->delete_attributes[$name] = true;
 		unset($this->data[$name]);
	}
	
	private function __unset_array($name){
		
		// check for multidimensional field
		if(isset($this->data['___'.$name])){
			
			foreach ($this->data as $key => $value){
				
				if(strpos($key, '__'.$name) === 0){
					
					unset($this->data[$key]);
					$this->delete_attributes[$key] = true;
				}
				
			}
			
			unset($this->data['___'.$name]);
			$this->delete_attributes['___'.$name] = true;
			
			return true;
		}
		
	}
	
	
	public function __get($name){
		
		if(isset($this->data[$name]) AND is_array($this->data[$name])){
			
			$longtext = array();
			// check for longtext format
			
			foreach ($this->data[$name] as $value){
			
				if(preg_match('/^([0-9]) (.*)/sm', $value, $matches) AND isset($matches[0])){

					if(isset($longtext['sum_check'])){

						$longtext['sum_check'] += (int) $matches[1];

					}else{

						$longtext['sum_check'] = (int) $matches[1];
					}

					$longtext['strings'][] = $matches[0];
				}
			}
			
			if(isset($longtext['sum_check']) AND isset($longtext['strings'])){
						
				$sum_check = $longtext['sum_check'];

				$i2 = 0; for ($i = 0; $i < count($longtext['strings']); $i ++){ $i2 = $i2 +$i;}

				if($sum_check === $i2){

					return FlexSDB_Strings::implode($longtext['strings']);
				}else{
				
					return $this->data[$name];
				}
			}
			
            return $this->data[$name];
	
		}else{
		    			
			if(isset($this->data['___'.$name])){
			    
				// return multidimensional array
				
				return FlexSDB::implode($name, $this->data);
				
			}else{
			
				return FlexSDB_Strings::decode_val($this->data[$name]);
			}
		}
	}
	

	public function __set($name, $value){
		
		if(count($this->data) < 256){
						
			// check if array is multidimensional
			
			if(is_array($value)){
				
				foreach ($value as $k => $v){

					if(is_array($v)){
						
						// multidimensional!
						
						$vars = FlexSDB::explode($name, $value);
												
						// remove eventual existing array
						$this->__unset_array($name);
						
						foreach ($vars as $k2 => $v2){
							
							if(isset($this->delete_attributes[$k2])){
								unset($this->delete_attributes[$k2]);
							}
							$this->{$k2} = $v2;

						}
						
						return true;
						
					}elseif(!is_numeric($k)){
						
						// multidimensional!
						
						$vars = FlexSDB::explode($name, $value);
						
						
						// remove eventual existing array
						$this->__unset_array($name);

						foreach ($vars as $k2 => $v2){
							
							if(isset($this->delete_attributes[$k2])){
								unset($this->delete_attributes[$k2]);
							}
							$this->{$k2} = $v2;

						}
						
						return true;
						
					}
						
				}
				
			}	
				
			if($value === NULL){
				unset($this->{$name});
				return  NULL;
			}
			
			$setval = $this->setval($value);
			
			if($setval !== NULL){
								
				// remove eventual existing array
				$this->__unset_array($name);
				
				if(isset($this->delete_attributes[$name])){
					unset($this->delete_attributes[$name]);
				}
				
				return $this->data[$name] = $setval;
				
			}else{
				
				if(isset($this->$name)){
					unset($this->$name);
					return NULL;
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
			
			return $value;
			
		}elseif(is_object($value)){
			
			return NULL;
			
		}elseif(is_numeric($value) OR is_bool($value)){
			
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