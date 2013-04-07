<?php

require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(TOOLKIT . '/class.fieldmanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');

Class REST_Update {
	
	private static $_sections = NULL;
	private static $_field_attributes = array('id', 'label', 'type', 'sortorder', 'location', 'show_column');
	
	public function init() {
		
		if(REST_API::getOutputFormat() == 'csv') {
			REST_API::sendError(sprintf('%s output format not supported.', strtoupper(REST_API::getOutputFormat())), 401, 'xml');
		}
		
		$request_uri = REST_API::getRequestURI();
		
		$section_reference = $request_uri[0];
		$sections = NULL;
		
		if(is_null($section_reference)) {
			$sections = SectionManager::fetch();
		} elseif(is_numeric($section_reference)) {
			$sections = SectionManager::fetch($section_reference);
		} else {
			$section_id = SectionManager::fetchIDFromHandle($section_reference);
			if($section_id) $sections = SectionManager::fetch($section_id);
		}
		
		if(!is_array($sections)) $sections = array($sections);
		
		if (!reset($sections) instanceOf Section) REST_API::sendError(sprintf("Section '%s' not found.", $section_reference), 404);
		
		self::$_sections = $sections;
	}
	
	public function get() {
			
		$response = new XMLElement('updateResponse');
		$sectionsXml = new XMLElement('section');

		foreach(self::$_sections as $section) {
			
			$section_xml = new XMLElement('section');
			
			$meta = $section->get();
			
			$section_xml->setAttribute('id',$meta['id']);
			$section_xml->setAttribute('handle',$meta['handle']);
			
			$entriesXml = new XMLElement('entry');
			
			$entries = EntryManager::fetchByPage(1,$meta['id'],999999999);
			
			foreach($entries['records'] as $sectionEntry){

				$entryXml = new XMLElement('entry');
				
				$entryMeta = $sectionEntry->get();
				
				$entryXml->setAttribute('id',$entryMeta['id']);
				$entryXml->setAttribute('dateCreated',$entryMeta['creation_date']);
				$entryXml->setAttribute('dateModified',$entryMeta['modification_date']);
				
				$entriesXml->appendChild($entryXml);
				
			}
			$section_xml->appendChild($entriesXml);
			$sectionsXml->appendChild($section_xml);
			
		}
		
		$response->appendChild($sectionsXml);
		
		REST_API::sendOutput($response);
		
	}
	
}