<?php

namespace frame;

abstract class Frame {

	const Encoding_ISO88591 = 0;
	const Encoding_UTF16LE  = 1;
	const Encoding_UTF16BE  = 2;
	const Encoding_UTF8     = 3;

	protected static $description = null;
	protected static $is_text     = false;
	protected static $name        = null;
	protected static $version     = null;

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
		$this->value = $rawValue;
		if (static::$is_text) {
			switch ($this->value[0]) {
				case self::Encoding_ISO88591:
					$this->value = iconv('ISO-8859-1', 'UTF-8', substr($this->value, 1));
					break;
				case self::Encoding_UTF16LE:
					$this->value = iconv('UTF-16LE', 'UTF-8', substr($this->value, 3));
					break;
				case self::Encoding_UTF16BE:
					$this->value = iconv('UTF-16BE', 'UTF-8', substr($this->value, 3));
					break;
				case self::Encoding_UTF8:
					$this->value = substr($this->value, 1);
					break;
			}
			$this->value = rtrim($this->value); // Remove any padding null bytes
		}
	}

	public static $frames = array(
		'AENC', 'APIC', 'ASPI',
		'BUF',
		'CNT',  'COM',  'COMM', 'COMR', 'CRA',  'CRM',
		'ENCR', 'EQU',  'EQU2', 'EQUA', 'ETC',  'ETCO',
		'GEO',  'GEOB', 'GRID',
		'IPL',  'IPLS',
		'LINK', 'LNK',
		'MCDI', 'MCI',  'MLL',  'MLLT',
		'OWNE',
		'PCNT', 'PIC',  'POP',  'POPM', 'POSS', 'PRIV',
		'RBUF', 'REV',  'RVA',  'RVA2', 'RVAD', 'RVRB',
		'SEEK', 'SIGN', 'SLT',  'STC',  'SYLT', 'SYTC',
		'TAL',  'TALB', 'TBP',  'TBPM', 'TCM',  'TCMP', 'TCO',  'TCOM', 'TCON', 'TCOP', 'TCP',  'TCR',  'TDA',  'TDAT', 'TDEN', 'TDLY',
		'TDOR', 'TDRC', 'TDRL', 'TDTG', 'TDY',  'TEN',  'TENC', 'TEXT', 'TFLT', 'TFT',  'TIM',  'TIME', 'TIPL', 'TIT1', 'TIT2', 'TIT3',
		'TKE',  'TKEY', 'TLA',  'TLAN', 'TLE',  'TLEN', 'TMCL', 'TMED', 'TMOO', 'TMT',  'TOA',  'TOAL', 'TOF',  'TOFN', 'TOL',  'TOLY',
		'TOPE', 'TOR',  'TORY', 'TOT',  'TOWN', 'TP1',  'TP2',  'TP3',  'TP4',  'TPA',  'TPB',  'TPE1', 'TPE2', 'TPE3', 'TPE4', 'TPOS',
		'TPRO', 'TPUB', 'TRC',  'TRCK', 'TRD',  'TRDA', 'TRK',  'TRSN', 'TRSO', 'TSI',  'TSIZ', 'TSOA', 'TSOP', 'TSOT', 'TSRC', 'TSS',
		'TSSE', 'TSST', 'TT1',  'TT2',  'TT3',  'TXT',  'TXX',  'TXXX', 'TYE',  'TYER',
		'UFI',  'UFID', 'ULT',  'USER', 'USLT',
		'WAF',  'WAR',  'WAS',  'WCM',  'WCOM', 'WCOP', 'WCP',  'WOAF', 'WOAR', 'WOAS', 'WORS', 'WPAY', 'WPB',  'WPUB', 'WXX',  'WXXX',
	);
}
