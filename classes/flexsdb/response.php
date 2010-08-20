<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Response {
	
	public $contents = array();
		
	public function __construct(ResponseCore $obj, $single = true){
		
		$this->status = $obj->status;
		$this->date = strtotime($obj->header['date']);
		$this->total_time = $obj->header['_info']['total_time'];
		$this->size = $obj->header['_info']['size_download'];
		$this->single = $single;
		$this->success = false;
		
		if($this->status === 200){
			
			$content = $obj->body->SelectResult->Item;
			$this->NextToken = isset($this->SelectResult->NextToken) ? $this->SelectResult->NextToken : NULL;
			$this->has_next = $this->NextToken !== NULL ? true : false; 
			
			echo Kohana::debug(count($content));
						
			if(count($content) > 1){
				
				echo Kohana::debug('is_array');
				
				foreach($content as $item){
					
					$this->add_item($item);
				}
				
				// echo Kohana::debug($this->contents);
				
			}elseif(count($content) === 1){
				
				$this->add_item($content);
				
				echo Kohana::debug('is_string');
				
			}
			
			$this->is_empty = empty($this->contents) ?: false;
			
			if($single AND !$this->is_empty){
				$this->contents = reset($this->contents);
			}
			
			$this->RequestId = (string) $obj->body->ResponseMetadata->RequestId;
			$this->BoxUsage = (double) $obj->body->ResponseMetadata->BoxUsage;
			$this->success = true;
			
		}else{
			
			// NOTOK 			
			$this->error_code = (string) $obj->body->Errors->Error->Code;
			$this->error_msg = (string) $obj->body->Errors->Error->Message;
			
		}
		
		
	}
	
	public function add_item(SimpleXMLElement $item){
		
		$itemName = (string) $item->Name;
		
		if(count($item->Attribute) > 1){
			
			$attributes = $item->Attribute;
			
			foreach($attributes as $attribute){
				
				$this->add_attribute($itemName, $attribute);				
			}
			
		}elseif(count($item->Attribute) === 1){
			
			$this->add_attribute($itemName, $item->Attribute);
			
		}
		
	}
	
	public function add_attribute($itemName, SimpleXMLElement $attribute){
		
		$key = (string) $attribute->Name;
		$value = FlexSDB_Response::decode($attribute->Value);
		
		if(!isset($contents[$itemName][$key])){
			
			$this->contents[$itemName][$key] = $value;
			
		}else{
			
			if(is_array($contents[$itemName][$key])){
				
				$this->contents[$itemName][$key][] = $value;
				
			}else{
				
				$pre_value = $contents[$itemName][$key];
				$this->contents[$itemName][$key] = array($pre_value, $value);
			}
		}
		
	}
	
	public static function decode($value){
	
		return (string) $value;
		
	}
}