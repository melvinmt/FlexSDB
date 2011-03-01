<?php defined('SYSPATH') OR die('No direct access allowed.');

class FlexSDB_Strings{
	
	public static function decode_val($value){
		
		$value = strval($value);
		
		if(is_numeric($value) AND $value < PHP_INT_MAX){
			
			return (double) $value;

		}else{
			
			return $value;
		}
		
	}
	
	public static function bytesConvert($bytes){
	    $ext = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $unitCount = 0;
	    for(; $bytes > 1024; $unitCount++) $bytes /= 1024;
	    return round($bytes, 2) ." ". $ext[$unitCount];
	}
	
	public static function explode($string, $length = 140, $array_length = 60){
				
		mb_internal_encoding( 'UTF-8'); 
		mb_regex_encoding( 'UTF-8');  
			
		$strlen = mb_strlen($string, 'utf-8');
	
		if($strlen > $length){
			
			$array = array();
			
			$i = 0;
			
			while($i < $strlen){
				
				$array[] = mb_substr( $string, $i, $length); 
				
				$i = $i + $length;				
			}
	
			foreach ($array as $key => &$part){
		
				$part = $key.' '.$part;
			}
		
			if(count($array) > $array_length){
			
				$array = array_slice($array, 0, $array_length);
			}
		
			return $array;
		}else{
			
			return $string;
		}
		
	}
	
	public static function implode($data){
		
		if(is_array($data)){
			
			$array = array();
			
			foreach($data as $value){
		
				if(preg_match("/^(\d+)/sm", $value, $match)){
				
					$key = $match[0];
				
					$value = preg_replace("/^(\d+)(\s{1})/s", '', $value);
				}
				
				$array[$key] = $value;	
			}
			
			$string = '';
			
			ksort($array);
				
			foreach ($array as $part){
			
				$string .= $part;
			}
			
			return $string;
			
		}else{
			
			return $data;
		}
		
	}
	
}