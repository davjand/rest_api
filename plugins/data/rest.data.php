<?php

require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');

Class REST_Data {
		
	private static $_section_handle = NULL;
	private static $_section_id = NULL;
	private static $_section = NULL;
	private static $_entry_id = NULL;
	private static $_ds_params = array();
	
	public function setDatasourceParam($name, $value) {
		self::$_ds_params[$name] = $value;
	}
	
	public function getDatasourceParam($name) {
		return self::$_ds_params[$name];
	}
	
	public function getSectionId() {
		return self::$_section_id;
	}
	
	public function getSectionHandle() {
		return self::$_section_handle;
	}
	
	public function getEntryId() {
		return self::$_entry_id;
	}
	
	public function init() {
		
		if(REST_API::getOutputFormat() == 'csv') {
			REST_API::sendError(sprintf(
				'%s output format not supported for %s requests.',
				strtoupper(REST_API::getOutputFormat()),
				strtoupper(REST_API::getHTTPMethod())
			), 401, 'xml');
		}
		
		$request_uri = REST_API::getRequestURI();
		
		self::$_section_handle = $request_uri[0];
		self::$_entry_id = $request_uri[1];
		
		$section_id = SectionManager::fetchIDFromHandle(self::$_section_handle);
		
		if (!$section_id) REST_API::sendError('Section not found.', 404);

		self::$_section_id = $section_id;
		self::$_section = SectionManager::fetch($section_id);
				
	}

	
	public function get() {
		
		$response = new XMLElement('dataResponse');
		
		//set up the section data
		
		$sectionMeta = self::$_section->get();		
		$response->setAttribute('id',$sectionMeta['id']);		
		$response->setAttribute('handle',$sectionMeta['handle']);


		//build the entries data

		$entriesXml = new XMLElement('entry');
		$entries = EntryManager::fetchByPage(1,self::$_section_id,999999999);
		
		$fields = self::$_section->fetchFields();
		
		if(is_array($entries['records'])){
		
			foreach($entries['records'] as $entry){
				$entryXml = new XMLElement('entry');
				$entryMeta = $entry->get();
					
				$entryXml->setAttribute('id',$entryMeta['id']);
				$entryXml->setAttribute('dateCreated',$entryMeta['creation_date']);
				$entryXml->setAttribute('dateModified',$entryMeta['modification_date']);
				foreach($fields as $field){
					
					$e = $entry->getData($field->get('id'),false);
					$f = $field->get();
					$fName = $f['element_name'];
					
					$fieldXml = null;
					
					switch($field->get('type')){
					
						case 'input':
							
							$val = $e['value'];
							$val = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $val);
							$val = str_replace('&','',htmlentities($val));
							
							$entryXml->setAttribute($fName,$val);
							break;
							
						case 'textarea':
							
							if($f['formatter'] == ''){
								$entryXml->setAttribute($fName,htmlentities($e['value']));
							}
							else{
								$entryXml->setAttribute($fName,htmlentities($e['value_formatted']));
							}
							break;
							
						case 'subsectionmanager':
							
							
							$fieldXml = new XMLElement($fName);
							
							if(is_array($e['relation_id'])){
								
								foreach($e['relation_id'] as $relId){
									$item = new XMLElement($fName);
									$item->setAttribute('id',$relId);
									$fieldXml->appendChild($item);
								}	
							}
							else{
							
								$item = new XMLElement($fName);
								$item->setAttribute('id',$e['relation_id']);
								$fieldXml->appendChild($item);	
							}
							
							break;
						case 'checkbox':
							$entryXml->setAttribute($fName,$e['value']);	
							break;
							
						case 'publish_tabs':
							break;
							
						case 'uniqueupload':
							$fieldXml = new XMLElement($fName);
							$fieldXml->setAttribute('file',$e['file']);
							$fieldXml->setAttribute('size',$e['size']);
							$fieldXml->setAttribute('mime',$e['mimetype']);
							
							//$fieldXml->appendChild(new XMLElement('meta',$e['meta']));
							break;
							
						case 'selectbox_link':
							$fieldXml = new XMLElement($fName);
							
							if(is_array($e['relation_id'])){
								
								foreach($e['relation_id'] as $relId){
									$item = new XMLElement($fName);
									$item->setAttribute('id',$relId);
									$fieldXml->appendChild($item);
								}	
							}
							else{
							
								$item = new XMLElement($fName);
								$item->setAttribute('id',$e['relation_id']);
								$fieldXml->appendChild($item);	
							}
							break;
						case 'select':

							$fieldXml = new XMLElement($fName);
							
							if(is_array($e['handle'])){
								
								foreach($e['handle'] as $relId){
									$item = new XMLElement($fName);
									$item->setAttribute('id',$relId);
									$fieldXml->appendChild($item);
								}	
							}
							else{
							
								$item = new XMLElement($fName);
								$item->setAttribute('id',$e['handle']);
								$fieldXml->appendChild($item);	
							}
							break;
							
						case 'entry_order':
							$entryXml->setAttribute($fName,$e['value']);	
							break;
							
						case 'number':
							$entryXml->setAttribute($fName,$e['value']);	
							break;
							
						}
					
					if($fieldXml != null){
						$entryXml->appendChild($fieldXml);
					}
				}
				$entriesXml->appendChild($entryXml);
			}
			
		}
		else{
			$entriesXml->appendChild(new XMLElement('error','No Entries Found'));
		}
		
		$response->appendChild($entriesXml);
		REST_API::sendOutput($response);	
	}
	

	
}