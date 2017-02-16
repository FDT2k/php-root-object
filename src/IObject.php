<?php


class IObject{
	/**
	 *
	 * @access protected
	 * @var string
	 * */
	protected $error_message='';
	/**
	 * @var boolean
	 * */
	protected $bError = false;
	protected $options;
	protected $caller = '';
	protected $error_code=0;
	protected $ice_magic_datas=array();
	protected $group = 'global';
	protected $internalHash="";
	protected $forwardErrorTo= NULL;

	/**
	 * @see IMCoreObject
	 * @return boolean
	 * */
	public function errorOccured(){
		return $this->bError;
	}

	function isEmpty($val) {
		//return not empty if ==0 or == "0"
		if(is_string($val)){
			$val = trim($val);
		}
		return empty($val) && $val !== 0 && $val !=="0";
	}


	/**
	 * @see IMCoreObject
	 * @param string $error_message
	 * @return void
	 * */
	public function setError($error_message,$code=1){

		$this->error_message =$error_message;
		$this->error_code = $code;
		$this->bError = true;
		if(NULL !== $this->forwardErrorTo){
			$this->forwardErrorTo->forwardError($this);
		}
	}

	public function forwardError($object){
		//var_dump($object);
		if(is_object($object)){
			$this->setError($object->getError(),$object->getErrorCode());
		}
	}

	public function setDefaultForwardErrorTo($object){
		$this->forwardErrorTo = $object;

	}

	public function setInfo($message){
		$this->info_message= $message;
	}

	public function clearError(){
		$this->error_message='';
		$this->error_code = 0;
		$this->bError = false;
	}
	/**
	 * @see IMCoreObject
	 * @return string
	 * */
	public function getError(){
		return $this->error_message;
	}
	public function getErrorCode(){
		return $this->error_code;
	}
	function getInfo(){
		return $this->info_message;
	}
	function hasInfo(){
		return !empty($this->info_message);
	}
	public function hasError(){
		return $this->bError;
	}
	/**
	 * Return current class name
	 * @see IMCoreObject
	 * @return string
	 * */
	public function getClassName(){
		return get_class($this);
	}

	/*public function setCaller($class){
		if(empty($this->caller)){

			$this->caller = $class;
		}
	}

	public function getCaller(){
		return $this->caller;
	}*/
	/**
	 * Get all the parents classes
	 * @see IMCoreObject
	 * @return array
	 * */

	function getImplementations ($instance){

		$rc = new ReflectionClass(get_class($instance));
		$interfaces = $rc->getInterfaces();
		$i = array();
		foreach($interfaces as $name => $crap){
			$i[]=$name;
		}
		return $i;
	}


	function isImplementing($instance,$interface){
	//var_dump($this->getImplementations($instance));
		return in_array($interface,$this->getImplementations($instance));
	}

	function getAncestors(){
		$class = $this->getClassName();
		$classes = array($class);
		while($class = get_parent_class($class)) {
			$classes[] = $class;
		}
		return $classes;
	}

	function isAncestorOf($class){
		return in_array($class,$this->getAncestors());

	}

	/**
	 * Setting default option group
	 * @see IMCoreObject
	 * @param string $group
	 * @return array
	 * */
	protected function setDefaultOptionGroup($group='core'){
		$this->configGroup =$group;

	}



	public function setOption($option,$value){
		$this->options[$this->configGroup][$option]=$value;

	}


	/**
	chain constructor
	Allows you to instansiate an object and directly chain your functions
	**/
	static function create(){
		$class = get_called_class();
		//var_dump();

		$o = new $class();
		return $o->chain_constructor();
	}

	function chain_constructor(){

		return $this;
	}

	function recompute_internal_hash(){
		//var_dump($this->getDatas());
		//$this->internalHash = md5(var_export(,true));
	}

	public function __call($name, $args){
		//magic setter / getter /tester

		if(($pos = strpos($name, 'set'))===0 && strlen($name)>3){ //starting with set ?
			$property = substr($name, 3);
			$property = lcfirst($property);
			$this->recompute_internal_hash();
			return $this->__ice_magic_set($property,$args[0]);
		}else if(($pos = strpos($name, 'get'))===0 && strlen($name)>3){
			$property = substr($name, 3);
			$property = lcfirst($property);
			return $this->__ice_magic_get($property);
		}
		else if (  ($pos = strpos($name, 'with'))===0 && strlen($name)>4      ) {
			// with keyword
			$property = substr($name, 4);

			$property = lcfirst($property);
		//	var_dump($property);
			$data = $this->__ice_magic_get($property);
		//	var_dump($data);
			$callback = $args[0];
			if(is_callable($callback)){
					if(is_array($data)){
						foreach($data as $o){
							$callback($o);
						}
					}else{
						$callback($data);
					}
			}
		}
		/*else{
			return parent::__call($name,$args);
		}*/
		else if (($pos = strpos($name, 'add'))===0 && strlen($name)>3){
			$property = substr($name, 3);
			$property = lcfirst($property);
			$data = $this->__ice_magic_get($property);

			if(!is_array($data) && !empty($data)){
				$data = array($data);
			}else if (empty($data)){
				$data = array();
			}
			$data[]=$args[0];
			$this->recompute_internal_hash();
			return $this->__ice_magic_set($property,$data);

		}else if (($pos = strpos($name, 'has'))===0 && strlen($name)>3){ // return true if not empty
			$property = substr($name, 3);
			$property = lcfirst($property);
			$data = $this->__ice_magic_get($property);

			if (!$this->isEmpty($data)){
				return true;
			}
			return false;
		}else if (($pos = strpos($name, 'is'))===0 && strlen($name)>2){ // return boolean value
			$property = substr($name, 2);
			$property = lcfirst($property);
			$data = $this->__ice_magic_get($property);

			return $data === true;
		}else{
			Throw new Exception('magic call failed, You used a wrong keyword',0);
		}
		// should i implement push pop and del here ?

	}


	public function get($name){

		return $this->__ice_magic_get($name);
	}

	public function __ice_magic_get($property){ // do not return object
		if(!empty($property)){
			if(isset($this->ice_magic_datas[$this->group][$property])){
				return $this->ice_magic_datas[$this->group][$property];
			}
		}
		return false;
	}

	public function __ice_magic_set($property,$value){
		//$this->setted[$property]=true;
		$this->setDatas(array($property=>$value));
		return $this;

	}


	/*data management*/

	function setGroupDefaultDatas($datas){
		$set=false;
		if(is_array($datas)){
			$this->ice_magic_datas = $datas;

			foreach($this->ice_magic_datas as $group=>$value){
				$this->hasData[$group]=true;
			}
			//$set = true;
		}

	}

	function setDefaultDatas($datas,$normalize=true){
		$set = false;
		if(is_array($datas)){
			if($normalize){
				$datas = $this->normalizeDatas($datas);
			}
			foreach($datas as $key=>$value){
				$this->ice_magic_datas[$this->group][$key] = $value;
				$set = true;
			}

		}
		$this->hasData[$this->group]=$set;
		return $this;
	}

	function normalizeDatas($datas,$sourceKeys=array(),$destKeys=array()){
		$newData = array();
		//if(empty($sourceKeys)&& !empty($this->))
		if(is_array($datas)){
			if(is_array($sourceKeys) && !empty($sourceKeys) && is_array($destKeys) && !empty($destKeys)){
				foreach($destKeys as $idx=> $key){
					$newData[$destKeys[$idx]]=$datas[$sourceKeys[$idx]];
				}
			}else{
				$newData = $datas;
			}
		}
		return $newData;
	}

	function setDatas($datas,$normalize=true){
		if(is_array($datas)){
			if($normalize){
				$datas = $this->normalizeDatas($datas);
			}
			foreach($datas as $key=>$value){
				$this->ice_magic_datas[$this->group][$key] = $value;
				$set = true;
			}
		}
		return $this;
	}

	function clearData(){
		$this->ice_magic_datas = array();
		return $this;
	}
//alias
	function removeData($data){
		return $this->removeDatas($data);
	}
	function removeDatas($datas){
		if(is_array($datas)){
			foreach($datas  as $value){

				unset($this->ice_magic_datas[$this->group][$value] );
				//var_dump($this->ice_magic_datas[$this->group]);
			///	var_dump($this);
			}
		}
		return $this;

	}

	function getDatas($filter='',$exclude=''){
		//throw new \ICE\core\Exception('test',9);
		//var_dump($filter);
		$datas =  $this->ice_magic_datas[$this->group];
		if(is_array($filter) && sizeof($filter)>0){
			$d = $datas;
			$datas = array();
		//	var_dump($filter);
			foreach($d as $key=>$value){
				if(in_array($key, $filter)){
					$datas[$key]=$value;
				}
			}
		}

		if(is_array($exclude) && sizeof($exclude)>0){
			$d = $datas;
			$datas = array();
		//	var_dump($filter);
			foreach($d as $key=>$value){
				if(!in_array($key, $exclude)){
					$datas[$key]=$value;
				}
			}
		}
		return $datas;
	}

	function getData($filter='',$exclude=''){
		return $this->getDatas($filter,$exclude);
	}

	function hasDatas(){

		if(empty($this->hasData[$this->group])){
			return false;
		}
		return $this->hasData[$this->group];


	}

	function hasData(){
		return $this->hasDatas();
	}

	function setGroup($group='global'){
		$this->group = $group;
		return $this;
	}
}
