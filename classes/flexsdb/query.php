<?php defined('SYSPATH') or die('No direct access allowed.');

class FlexSDB_Query{
	
	private $domain;
	private $wheres = array();
	private $order_by;
	private $select = '*';
	private $limit;
	private $all = false;
	private $all_opts = NULL;
	private $cache = false;
	private $cache_expire;
	public $items = array();
	private $pagination = false;
	private $page = 1;
	public $response;
	public $success = false;
	
	public function __construct($domain = NULL){
		
		if($domain != NULL){
			$this->domain = $domain;
		}
		
	}
	
	public function cache($expire){
		
		$this->cache = true;
		$this->cache_expire = (int) $expire;
		
	}
	
	public function all(){
		
		$this->all = true;
		
		return $this;
	}
	
	private function repeat($times = 1){
		
		$this->all = true;
		$this->all_opts = (int) $times;
		
		return $this;
	}
	
	private function NextToken($next_token){
		
		$this->all = true;
		$this->all_opts = strval($next_token);
		
		return $this;	
	}
	
	public function from($domain){
		
		$this->domain = $domain;
		
		return $this;
	}
	
	private function add_where($logical, $clause){
		
		$this->wheres[] = array('logical' => $logical, 'clause' => $clause);
		
	}
	
	public function and_where($field, $operator, $value){
		
		$this->add_where('AND',  "`{$field}` ".strtoupper($operator)." '{$value}'");
		
		return $this;
		
	}
	
	public function or_where($field, $operator, $value){
	
		$this->add_where('AND', "`{$field}` ".strtoupper($operator)." '{$value}'");
		
		return $this;
		
	}
	
	public function and_wheres(){
		
		$wheres = func_get_args();
		
		if(count($wheres) > 0){
		
			$this->add_where('AND', "(".implode(' OR ', $wheres).")");
		}
		
		return $this;
	}
	
	public function or_wheres(){
		
		$wheres = func_get_args();
		
		if(count($wheres) > 0){
		
			$this->add_where('OR', "(".implode(' AND ', $wheres).")");
		}
		
		return $this;
	}
	
	public function order_by($field, $order){
		
		$this->add_where('AND', "`{$field}` IS NOT NULL");
		
		$this->order_by = " `{$field}` ".strtoupper($order)." ";
		
		return $this;
	}
	
	public function and_between($field, $min, $max){
		
		$this->add_where('AND', "(`{$field}` BETWEEN '{$min}' AND '{$max}')");
		
		return $this;
	}
	
	
	public function or_between($field, $min, $max){
		
		$this->add_where('OR', "(`{$field}` BETWEEN '{$min}' AND '{$max}')");
		
		return $this;
	}
	
	public function and_in($field, array $values){
		
		$this->add_where('AND', "(`{$field}` IN ('".implode("', '", $values)."'))");
		
		return $this;
	}
	
	public function or_in($field, array $values){
		
		$this->add_where('OR', "`{$field}` IN ('".implode("', '", $values)."')");
		
		return $this;
	}
	
	public function and_every($field, $operator, $value){
		
		$this->add_where('AND', "(EVERY(`{$field}`) ".strtoupper($operator)." '{$value}')");
		
		return $this;
	}
	
	
	public function or_every($field, $operator, $value){
		
		$this->add_where('OR', "(EVERY(`{$field}`) ".strtoupper($operator)." '{$value}')");
		
		return $this;
	}
	
	public function select(){
	
		$fields = func_get_args();
		
		if(count($fields) > 0){
		
			$this->select = implode(', ', $fields);
		}
		
		return $this;
	}
	
	public function limit($int){
		
		$this->limit = $int;
		
		return $this;
	}
	
	public function page($int){
		
		if((int) $int < 1){
			$int = 1;
		}
		
		$this->pagination = true;
		$this->page = (int) $int;
		
		return $this;
		
	}
	
	public function execute(){
		
		$this->sql = '';
		
		if($this->pagination AND $this->all == false){
			
			if(!empty($this->limit)){
				$new_limit = $this->limit * ($this->page - 1);
				$original_limit = $this->limit;
				
			}else{
				$new_limit = 10 * ($this->page - 1);
				$original_limit = 10;
			}
			
			
			if($new_limit != 0){			
				echo Kohana::debug('new limit: '.$new_limit);
				
				$original_select = $this->select;
				$this->select("count(*)");
				
				$this->limit($new_limit);
			}else{
				$this->pagination = false;
			}
				
		}
		
		$this->sql .= "SELECT {$this->select} ";
		
		$this->sql .= "FROM `{$this->domain}` ";
		
		if(count($this->wheres) > 0){
			
			$this->sql .= " WHERE ";
			
			$i = 0;
			foreach ($this->wheres as $where){
				
				if($i == 0){
					
					$this->sql .= " {$where['clause']} ";
					
				}elseif($i < 20){
					
					$this->sql .= " {$where['logical']} {$where['clause']} ";
					
				}else{
					break;
				}
				
				$i ++;
			}
		}
		
		if(!empty($this->order_by)){
			$this->sql .= "ORDER BY {$this->order_by} ";
		}
		
		if(!empty($this->limit)){
			$this->sql .= "LIMIT {$this->limit} ";
		}
		
		$this->sql = trim(str_replace('  ', ' ', $this->sql));
		
		$opt = array();
		
		if($this->all == true){
			
			if($this->all_opts == NULL){
				
				$has_next = true;
				
				while($has_next == true){
				
					$result = Amazon::SDB()->select($this->sql, $opt);
					$this->response = new FlexSDB_Response($result);
					$has_next = $this->response->has_next;
					$opt['NextToken'] = $this->response->NextToken;
					
					$this->items = array_merge($this->items, $this->response->body);
				}
				
			}elseif(is_int($this->all_opts)){ 
				
				$i = 0;
				$has_next = true;
				
				while($i < $this->all_opts AND $has_next == true){
					
					$result = Amazon::SDB()->select($this->sql, $opt);
					$this->response = new FlexSDB_Response($result);
					
					$has_next = $this->response->has_next;
					$opt['NextToken'] = $this->response->NextToken;

					$this->items = array_merge($this->items, $this->response->body);
					$i ++;
					
				}
								
			}elseif(is_string($this->all_opts)){
				
				$opt['NextToken'] = $this->all_opts;
				
				$result = Amazon::SDB()->select($this->sql, $opt);
				$this->response = new FlexSDB_Response($result);
				$has_next = $this->response->has_next;
				$opt['NextToken'] = $this->response->NextToken;
				$this->items = array_merge($this->items, $this->response->body);
				
			}
		}else{
			
			$result = Amazon::SDB()->select($this->sql, $opt);
			$this->response = new FlexSDB_Response($result);
			
			$this->items = array_merge($this->items, $this->response->body);
			
		}
		
		if($this->pagination AND isset($this->response->NextToken) AND isset($original_limit) AND isset($original_select)){
			
			$this->items = array();
			$this->pagination = false;
			$this->NextToken($this->response->NextToken);			
			$this->limit($original_limit);
			$this->select($original_select);
				
			return $this->execute();
			
		}
		
		$this->success = $this->response->success;
		
		if($this->success AND $this->response->count > 0){
			
			return new FlexSDB_Items($this->domain, $this->items);
			
		}else{
			
			return $this->response;
		}
		
	}
}