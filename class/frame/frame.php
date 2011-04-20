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

	public static $frames = array(
		'aenc', 'apic', 'aspi',
		'buf',
		'cnt',  'com',  'comm', 'comr', 'cra',  'crm',
		'encr', 'equ',  'equ2', 'equa', 'etc',  'etco',
		'geo',  'geob', 'grid',
		'ipl',  'ipls',
		'link', 'lnk',
		'mcdi', 'mci',  'mll',  'mllt',
		'owne',
		'pcnt', 'pic',  'pop',  'popm', 'poss', 'priv',
		'rbuf', 'rev',  'rva',  'rva2', 'rvad', 'rvrb',
		'seek', 'sign', 'slt',  'stc',  'sylt', 'sytc',
		'tal',  'talb', 'tbp',  'tbpm', 'tcm',  'tcmp', 'tco',  'tcom', 'tcon', 'tcop', 'tcp',  'tcr',  'tda',  'tdat', 'tden', 'tdly',
		'tdor', 'tdrc', 'tdrl', 'tdtg', 'tdy',  'ten',  'tenc', 'text', 'tflt', 'tft',  'tim',  'time', 'tipl', 'tit1', 'tit2', 'tit3',
		'tke',  'tkey', 'tla',  'tlan', 'tle',  'tlen', 'tmcl', 'tmed', 'tmoo', 'tmt',  'toa',  'toal', 'tof',  'tofn', 'tol',  'toly',
		'tope', 'tor',  'tory', 'tot',  'town', 'tp1',  'tp2',  'tp3',  'tp4',  'tpa',  'tpb',  'tpe1', 'tpe2', 'tpe3', 'tpe4', 'tpos',
		'tpro', 'tpub', 'trc',  'trck', 'trd',  'trda', 'trk',  'trsn', 'trso', 'tsi',  'tsiz', 'tsoa', 'tsop', 'tsot', 'tsrc', 'tss',
		'tsse', 'tsst', 'tt1',  'tt2',  'tt3',  'txt',  'txx',  'txxx', 'tye',  'tyer',
		'ufi',  'ufid', 'ult',  'user', 'uslt',
		'waf',  'war',  'was',  'wcm',  'wcom', 'wcop', 'wcp',  'woaf', 'woar', 'woas', 'wors', 'wpay', 'wpb',  'wpub', 'wxx',  'wxxx',
	);
}
