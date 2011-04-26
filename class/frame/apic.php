<?php

namespace frame;

class APIC extends Frame {

	protected static $description = 'Attached picture';
	protected static $name        = 'APIC';
	protected static $version     = 3;

	protected $image_type = null;

	/**
	 * <Header for 'Attached picture', ID: "APIC">
	 * Text encoding      $xx
	 * MIME type          <text string> $00
	 * Picture type       $xx
	 * Description        <text string according to encoding> $00 (00)
	 * Picture data       <binary data>
	 */
	protected function parseValue($rawValue) {
		$encoding = $rawValue[0];
		switch ($encoding) {
			case self::Encoding_UTF16LE:
			case self::Encoding_UTF16BE:
				$endOfDescText = "\x00\x00";
				$EODL = 2;
				break;
			case self::Encoding_ISO88591:
			case self::Encoding_UTF8:
			default:
				$endOfDescText = "\x00";
				$EODL = 1;
				break;
		}
		$value = substr($rawValue, 1); // Drop first text encoding byte
		$endOfMime = strpos($value, "\x00");
		$mime = substr($value, 0, $endOfMime);
		switch (substr($mime, 6)) {
			case 'jpg':
			case 'jpeg':
				$this->image_type = 'jpg';
				break;
			case 'png':
				$this->image_type = 'png';
				break;
			case 'gif':
				$this->image_type = 'gif';
				break;
		}
		$value = substr($value, $endOfMime + 1); // Remove MIME type
		$value = substr($value, 1); // Remove Picture type
		$endOfDesc = strpos($value, $endOfDescText);
		$this->value = substr($value, $endOfDesc + $EODL); // Take everything after Description as data
	}

	public function save($path) {
		file_put_contents($path, $this->value);
	}

}
