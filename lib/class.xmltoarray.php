<?php
/*
Converts a string of XML to a keyed array
Adapted from http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
*/
Class XMLToArray {
	
	public static function convert($string) {
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);	
		xml_parse_into_struct($parser, $string, $vals, $index);
		xml_parser_free($parser);

		$return = array();
		$ary = &$return;

		foreach ($vals as $r) {

			$t = $r['tag'];

			if ($r['type'] == 'open' || $r['type']=='complete') {
				
				if (isset($ary[$t])) {
					if (isset($ary[$t][0])) {
						$ary[$t][] = array();
					} else {
						$ary[$t] = array($ary[$t], array());
					}
					$cv = &$ary[$t][count($ary[$t])-1];
				} else {
					$cv = &$ary[$t];
				}
				if (isset($r['attributes'])) {
					foreach ($r['attributes'] as $k => $v) {
						$cv['_' . $k] = $v;
					}
				}
			}

			if ($r['type'] == 'open') {
				//$cv[] = array();
				$cv['_p'] = &$ary;
				$ary = &$cv;
			}
			elseif ($r['type']=='complete') {
				$cv['value'] = (isset($r['value']) ? $r['value'] : '');
			}
			elseif ($r['type']=='close') {
				$ary = &$ary['_p'];
			}
		}    

		self::deleteParents($return);
		return $return;
	}
	
	/*
		Remove any junk
	*/
	public static function cleanArrayForJSON($array){
	
		//remove any empty strings
		foreach($array as $key => $val){
			
			//remove empty
			if($val == ''){
				unset($array[$key]);
			}
			
			//remove unneeded keys
			else if(substr($key,0,1) == '_'){
				$shortenedKey = substr($key,1);
				if(!array_key_exists($shortenedKey,$array)){ //if it doesn't exist
				
					$array[$shortenedKey] = $val;
					unset($array[$key]);
					$key = $shortenedKey;
				}
			}
			
			//recurse
			if(is_array($val)){
				$array[$key] = XMLToArray::cleanArrayForJSON($array[$key]);	
			}
			
		}	
		return $array;
	}
	
	
	/*
		Ensure arrays get rendered correctly
	*/
	
	public static function processArrayForJSON($array){
		
		foreach($array as $key => $val){
			//remove 'nested arrays'
			if(is_array($val) && count($val) == 1 && array_key_exists($key,$val)){
				
				//ensure that the nested array is an array
				$keys = array_keys($val[$key]);
				
				if(count($val[$key]) == 0){
					unset($array[$key]);	continue;
				}
				else if($keys[0] === 0){
					$array[$key] = $val[$key];	
				}
				else{
					$array[$key] = array($val[$key]);
				}
				
			}
						
			//recurse
			if(is_array($val)){
				$array[$key] = XMLToArray::processArrayForJSON($array[$key]);	
			}
		
		
		}
		
		return $array;
	}
	
	/**
	
		Simplify the output structure for JSON for where relationships id
		
	*/
	public static function processJSONRelations($array){
		foreach($array as $key => $val){
			if(is_array($val) && count($val) == 1 && array_key_exists('id', $val)){
				$array[$key] = $val['id'];
			}
		
				
			//recurse
			if(is_array($array[$key])){
				$array[$key] = XMLToArray::processJSONRelations($array[$key]);	
			}	
		}
		return $array;
		
	}
	
	// Remove recursion in result array
	private function deleteParents(&$ary) {
		foreach ($ary as $k => $v) {
			if ($k === '_p') {
				unset($ary[$k]);
			}
			elseif (is_array($ary[$k])) {
				self::deleteParents($ary[$k]);
			}
		}
	}
	
}