<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Items implements Iterator{
	
	private $position = 0;
    private $array = array(
        "firstelement",
        "secondelement",
        "lastelement",
    );
	public $items = array();
	
	public function __construct($domain, array $items){
		
		$this->position = 0;
		
		if(count($items) > 0){
			
			foreach ($items as $itemName => $values){
				$this->items[] = new FlexSDB_Item($domain, $itemName, $values);
			}
			
			$this->array = array_values($this->items);
			
		}else{
			
			return false;
		}
	}	

    function rewind(){
	 	reset($this->items);
		$this->has_next = count($this->items) > 0 ? true : false;
    }

    function current(){
        return current($this->items);
    }

    function key() {
        return $this->items[key($this->items)]->itemName();
    }

    function next() {
        $has_next = next($this->items);		
		$this->has_next = (!$has_next) ? false : true;
    }

    function valid() {
        return $this->has_next;
    }

}