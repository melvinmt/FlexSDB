<?php defined('SYSPATH') or die('No direct access allowed.');

abstract class FlexSDB_Core{
	
	public static $longtext;
	
	public static function bytesConvert($bytes){
	    $ext = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $unitCount = 0;
	    for(; $bytes > 1024; $unitCount++) $bytes /= 1024;
	    return round($bytes, 2) ." ". $ext[$unitCount];
	}
	
	public static function decode_val(SimpleXMLElement $value){
		
		$value = strval($value);
		
		if(is_numeric($value)){
			
			return (double) $value;

		}else{
			
			return $value;
		}
		
	}
	
}