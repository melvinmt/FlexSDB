<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Response {
	
	public $status = 0;
	public $success = false;
	public $date = 0;
	public $total_time = 0;
	public $size = '0 B';
	public $single = false;
	public $NextToken;
	public $has_next = false;
	public $is_empty = true;
	public $RequestId;
	public $BoxUsage;
	public $error_msg;
	public $error_code;
	public $count;
	public $body = array();
	private $longtexts = array();
		
	public function __construct(ResponseCore $obj, $single = false){
	
		$this->status = $obj->status;
		$this->date = strtotime($obj->header['date']);
		$this->total_time = $obj->header['_info']['total_time'];
		$this->size = FlexSDB_Strings::bytesConvert($obj->header['_info']['size_download']);
		$this->single = $single;
		
		// if status is OK
		if($this->status === 200){
			
			if(isset($obj->body->SelectResult->Item)){
			
				$content = $obj->body->SelectResult->Item;
				$this->NextToken = isset($obj->body->SelectResult->NextToken) ? (string) $obj->body->SelectResult->NextToken : NULL;
				$this->has_next = $this->NextToken !== NULL ? true : false; 
			
				// if there are multiple items			
				if(count($content) > 1){
				
					// loop through each item and add to contents
					foreach($content as $item){
						$this->add_item($item);
					}
				
				// single item result
				}elseif(count($content) === 1){
				
					$this->add_item($content);
				}
			
				// check if contents is empty
				$this->is_empty = empty($this->body) ?: false;
			
				// if single is true only return attributes of first item
				if($single AND !$this->is_empty AND count($content) == 1){
					$this->body = reset($this->body);
				}
				
			}elseif(isset($obj->body->ListDomainsResult->DomainName)){
				
				$this->NextToken = isset($obj->body->ListDomainsResult->NextToken) ? (string) $obj->body->ListDomainsResult->NextToken : NULL;
				$this->has_next = $this->NextToken !== NULL ? true : false; 
				
				$domains = $obj->body->ListDomainsResult->DomainName;
				
				if(count($domains) > 0){
					
					foreach ($domains as $domain){
						
						$this->body[] = (string) $domain;
					}
					
				}
				
			}
			
			$this->RequestId = (string) $obj->body->ResponseMetadata->RequestId;
			$this->BoxUsage = (double) $obj->body->ResponseMetadata->BoxUsage;
			$this->success = true;
			
		}else{
			
			// NOTOK 			
			$this->error_code = (string) $obj->body->Errors->Error->Code;
			$this->error_msg = (string) $obj->body->Errors->Error->Message;
			
			$this->RequestId = (string) $obj->body->RequestID;
			$this->BoxUsage = (double) $obj->body->Errors->Error->BoxUsage;
			
		}
		
		// don't need longtexts anymore
		unset($this->longtexts);
		
		$this->count = count($this->body);
	}
	
	private function add_item(SimpleXMLElement $item){
		
		$itemName = (string) $item->Name;
		
		// if item has multiple attributes
		if(count($item->Attribute) > 1){
			
			$attributes = $item->Attribute;
			
			// loop through each attribute
			foreach($attributes as $attribute){
				
				$this->add_attribute($itemName, $attribute);				
			}
			
		// if item has single attribute
		}elseif(count($item->Attribute) === 1){
			
			$this->add_attribute($itemName, $item->Attribute);
		}
		
		// check if longtexts fields match the sum_check, implode them and update contents
		foreach($this->longtexts as $long_itemName => $long_item){
			
			foreach ($long_item as $long_key => $long_field){
				
				$sum_check = $long_field['sum_check'];
			
				$i2 = 0; for ($i = 0; $i < count($long_field['strings']); $i ++){ $i2 = $i2 +$i;}
				
				if($sum_check === $i2){
					
					$this->body[$long_itemName][$long_key] = FlexSDB_Strings::implode($long_field['strings']);
				}
				
			}
			
		}
		
		
	}
	
	private function add_attribute($itemName, SimpleXMLElement $attribute){
		
		$key = (string) $attribute->Name;
		$value = FlexSDB_Strings::decode_val($attribute->Value); // converts numeric values to floats and simple xml to strings
		
		// check for longtext format
		if(preg_match('/^([0-9]) (.*)/', $value, $matches) AND isset($matches[0])){
			
			if(isset($this->longtexts[$itemName][$key]['sum_check'])){
				
				$this->longtexts[$itemName][$key]['sum_check'] += (int) $matches[1];
				
			}else{
				
				$this->longtexts[$itemName][$key]['sum_check'] = (int) $matches[1];
			}
			
			$this->longtexts[$itemName][$key]['strings'][] = $matches[0];
		}
		
		// add value to key if there aren't any key-value pairs (yet)
		if(!isset($this->body[$itemName][$key])){
			
			$this->body[$itemName][$key] = $value;
			
		// convert values to array if there already exists a key-value pair
		}else{
			
			if(is_array($this->body[$itemName][$key])){
				
				$this->body[$itemName][$key][] = $value;
				
			}else{
				
				$pre_value = $this->body[$itemName][$key];
				$this->body[$itemName][$key] = array($pre_value, $value);
			}
		}
		
	}
	

	

}