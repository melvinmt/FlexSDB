<?php defined('SYSPATH') or die('No direct access allowed.');

abstract class FlexSDB_Core{
	
	protected static $handles = array();
	protected static $debug = array();
	protected static $show_debug = false;
	
	public static function get($domain){
		
		return new FlexSDB_Query($domain);
	}	
	
	public static function delete($domain, $itemName, $keys = NULL){
		
		$handle = Amazon::SDB()->delete_attributes($domain, $itemName, $keys, $returnCurl = true);
		
		FlexSDB::handle($handle);
		
	}
	
	public static function add_debug(array $data){
		
		self::$debug[] = $data;
		
		Event::add('system.shutdown', array('FlexSDB', 'show_debug'));		
	}
	
	public static function show_debug(){
		
		if(self::$show_debug){
			echo Kohana::debug(self::$debug);
		}
		
	}
	
	public static function debug($show_debug = true){
		
		return self::$show_debug = (bool) $show_debug;
	}
	
	public static function handle($callback, $key = NULL){
		
		if($key != NULL){
			self::$handles[$key] = $callback; 	
		}else{
			self::$handles[] = $callback;
		}
		
		// add debug
		FlexSDB::add_debug(array('type' => 'handle', 'curl' => $callback, 'key' => $key));
		
		// Zeelot event
		Event::add('system.shutdown', array('FlexSDB', 'exec_handles'));		
	}
	
	public static function handles(array $callbacks){
		
		if(array_values($callbacks) === $callbacks){
			
			// numeric array
			foreach ($callbacks as $callback){
				
				self::$handles[] = $callback;
			}

		}else{
			
			// associative array
			foreach ($callbacks as $key => $callback){
				
				self::$handles[$key] = $callback;
			}
			
		}
		// Zeelot event
		Event::add('system.shutdown', array('FlexSDB', 'exec_handles'));
	}
	
	public static function rm_handles(){
		
		self::$handles = array();
	}
	
	public static function exec_handles(){
		
		$start_time = microtime(true);
		
		if(isset(self::$handles) && !empty(self::$handles)){
		  	
			$execs = curl::multi_exec(self::$handles);

		}
		
		$end_time = microtime(true);
				
		$exec_time = $end_time - $start_time;
		
		if(self::$show_debug){
			echo Kohana::debug(array('type' => 'all_handles', 'exec_time' => $exec_time.' s'));
		}
		
		return $execs;
		
	}
	
	public static function create_domain($domain){
		
		$request = Amazon::SDB()->create_domain($domain, $curl = null);
		
		// echo Kohana::debug($request);
		
		$response = new FlexSDB_Response($request);
		
		echo Kohana::debug($response);
		
	}
	
	public static function list_domains(){

		$opt = array();
		$opt['MaxNumberOfDomains'] = 2; // 1 to 100
		// $opt['NextToken'] = ''; // optional
		// $opt['returnCurlHandle'] = false; 

		$request = Amazon::SDB()->list_domains($opt);
		
		echo Kohana::debug($request);
		
		$response = new FlexSDB_Response($request);
		
		echo Kohana::debug($response);
		
	}
	
	
	public static function where($field, $operator, $value){
		
		return "`{$field}` {$operator} '{$value}'";
		
	}
		
	public function action_text(){
		
		// GET
			
			// shortcut for single item with itemName(); ID is shortcut for itemName(); // NOTE to self: should use get_attributes, it's faster
			$item = FlexDB::get_item('products', $item_id);
			
			// shortcut for single item icm with get params; effectively is the same as get limit 1
			$item = FlexSDB::get_item('products', array('time' => 4));
		
			// shortcut for multiple items
			$item = FlexSDB::get_item('products', array('id' => 4));
		
			// shortcuts with caching
			$item = FlexSDB::get_item('products', array('id' => 4), $cache_time = 60);
			$item = FlexSDB::get_items('products', array('id' => 4), $cache_time = 60);
		
			// advanced get query
			$items = FlexSDB::get('products')
				->and_where('price', '>', 5)
				->or_where('lat', 'like', '%ab')
				->and_wheres(array(FlexSDB::where('price', '>', '4'), FlexSDB::where('date', '!=', 'today')))
				->or_wheres(array(FlexSDB::where('price', '>', '4'), FlexSDB::where('date', '!=', 'today')))
				->orderby('time', 'desc')
				->between('year', array(1999, 2000))
				->in('year', array(1999, 2000, 2001)) 
				->every('keyword', 'like', '%book%') // *all* defined keywords must have this property 
				->cache(60) // minutes
				->limit(1)
				->all() // returns ALL items; might time out your resources!
				->all(5) // returns 5 iterations of NextToken
				->all($items->NextToken) // get specific $NextToken results
				->delete() // delete all items that get returned
				->execute();
		
		// OUTPUT
		
			// Output values (MAGIC GET)
			$name = $item->name;
			
			// Please note that output from GET always result in FlexSDB_Item objects // Multiple items are contained in a FlexSDB_Items object
	
			// Item as array
			$item->array();
			
			// Items as array
			$items->array();
			
			// Get count from request
			$items->count();
		
		// EDIT
		
			// Modify items (MAGIC SET)
			$item->name = 'Melvin';
		
			// Save state
			$item->save();
		
		// INSERT
		
			// Insert item (which domain?)
			$item = new FlexSDB_Item('products');
		
			// Fill single property
			$item->name = 'Melvin';
			
			// Fill array property
			$item->tags = array('tag1', 'tag2', 'tag3');
		
			// Give ID:
			$item->ID = random::text(5);
		
			// Save item:
			$item->save();
			
			// BATCH insert items
			FlexSDB::insert(array($item1, $item2, $item3));
		
		// DELETE
		
			// Delete property
			$item->delete('name');
		
			// Delete properties
			$item->delete(array('name', 'time'));
		
			// Delete entire item from DB
			$item->delete();
		
			// Delete multiple items from DB
			FlexSDB::delete(array($item1, $item2, $item3));
			
			FlexSDB::delete_item('products', $ID);
			FlexSDB::delete_items('products', array($ID1, $ID2));
		
		// Create DOMAIN
			
			FlexSDB::create_domain('products');
			
		// Delete DOMAIN
		
			FlexSDB::delete_domain('orders');
			
		// Get info DOMAIN
		
			FlexSDB::info_domain('products');
			
		// Get info DOMAINS
		
			FlexSDB::info_domains();
					
	}
	
	public static function explode($name, array $input){
		
		$output = array();
		$index = array();
		
		$output['___'.$name] = 'multidimensional';
	
		// explode
		self::explode_data($name, $input, $output, $index);
			
		unset($array);
		unset($index);
		
		return $output;
	}
	
	public static function explode_data($name, array $data, &$array, &$index){
			
		foreach ($data as $key => $value){
			
			if(is_array($value)){
				
				$func = __FUNCTION__;
				self::$func($name.'__'.$key, $value, $array, $index);
				
			}else{
				
				$array['__'.$name.'__'.$key] = $value;
				$index[] = $key;
				
			}	
			
		}
		
	}
	
	public static function implode($name, array $input){
		
		$output = array();
		
		$group = array();
		foreach ($input as $key => $value){
			
			if(strpos($key, '__'.$name) === 0){
				
				// add to group
				$group[$key] = $value;
				
				$str = '$output'.substr(str_replace('__', '"]["', str_replace('__'.$name, '', $key)), 2, -2).' = $value;';
				
				$key = str_replace('__'.$name.'__', '', $key);
				
				$keys = explode('__', $key);
				
				$str = '$output["'.implode('"]["', $keys).'"] = FlexSDB_Strings::decode_val($value);';
				
				eval($str);
			}
			
		}	
		
		return $output;
		
	}
	
	
	
	
	
	
	
}
