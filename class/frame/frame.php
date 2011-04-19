<?php

namespace frame;

abstract class Frame {

	protected static $is_text  = false;

	protected $encoding = false;
	protected $language = false;

	protected $flags = array(
		'discard_tag_alterations'  => false,
		'discard_file_alterations' => false,
		'read_only'                => false,
		'compression'              => false,
		'encryption'               => false,
		'grouped'                  => false,
	);

	final public function __construct($rawFlags, $rawValue) {
		$this->parseFlags($rawFlags);
		$this->parseValue($rawValue);
	}

	protected function parseFlags($flags) {
		$this->flags['discard_tag_alterations']  = (bool) ($flags & 0x8000);
		$this->flags['discard_file_alterations'] = (bool) ($flags & 0x4000);
		$this->flags['read_only']                = (bool) ($flags & 0x2000);
		$this->flags['compression']              = (bool) ($flags & 0x0800);
		$this->flags['encryption']               = (bool) ($flags & 0x0400);
		$this->flags['grouped']                  = (bool) ($flags & 0x0200);
	}

	protected function parseValue($rawValue) {
		if (static::$is_text) {
			// do the encoding thing
		}
		$this->value = $rawValue;
	}

}

