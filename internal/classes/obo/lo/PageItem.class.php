<?php

namespace obo\lo;
class PageItem
{
	public $pageItemID;
	public $component;
	public $data;
	public $media;
	public $advancedEdit;
	public $options;

	function __construct($pageItemID=0, $component='', $data='', $media=Array(), $advancedEdit=0, $options=NULL)
	{
		$this->pageItemID = $pageItemID;
		$this->component = $component;
		$this->data = $data;
		$this->media = $media;
		$this->advancedEdit = $advancedEdit;
		$this->options = $options;
	}

	public function __sleep()
	{
		if(isset($this->options) && $this->options instanceof \stdClass) $this->options = (array) $this->options;
		return ['pageItemID', 'component', 'data', 'media', 'advancedEdit', 'options'];
	}
}
