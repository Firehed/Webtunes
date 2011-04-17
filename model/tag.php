<?php

class Tag {

	function __construct($tag, $flags, $value) {
		$this->tag   = $tag;
		$this->flags = $flags;
		$this->value = $value;
		$this->parseValue();
	}

	/*
	 * First bytes of frame are encoding format when text
	 * Byte = Character set     = Terminator
	 * \000 = ISO-8859-1        = \000
	 * \001 = UTF16 with BOM    = \000\000
	 * \002 = UTF16-BE, no BOM  = \000\000
	 * \003 = UTF8              = \000
	 */
	private function parseValue() {
		switch ($this->tag) {
			case $this->tag[0] == 'T': // Any text field
			case 'IPLS': // Involved people list
			case 'USLT': // Unsynchronized lyrics
			case 'SYLT': // Synchronized lyrics
			case 'COMM': // Comments
			case 'GEOB': // General encapsulated object
			case 'USER': // Terms of use
			case 'OWNE': // Ownership
			case 'COMR': // Commercial
				break;
			default:
				return;
		}
		// First character indicates text encoding
		switch ($this->value[0]) {
			case 0:
				$this->value = iconv('ISO-8859-1', 'UTF-8', substr($this->value, 1));
				break;
			case 1:
				$this->value = iconv('UTF-16LE', 'UTF-8', substr($this->value, 3));
				break;
			case 2:
				$this->value = iconv('UTF-16BE', 'UTF-8', substr($this->value, 3));
				break;
			case 3:
				$this->value = substr($this->value, 1);
				break;
		}

		$this->value = rtrim($this->value); // Remove padding null bytes
	}

	function getTagName() {
		return array_key_exists($this->tag, self::$tags) ? self::$tags[$this->tag] : '';
	}

	private static $tags = array(
		'AENC' => 'Audio encryption',
		'APIC' => 'Attached picture',
		'COMM' => 'Comments',
		'COMR' => 'Commercial frame',
		'ENCR' => 'Encryption method registration',
		'EQUA' => 'Equalization (replaced by EQU2 in v2.4)',
		'ETCO' => 'Event timing codes',
		'GEOB' => 'General encapsulated object',
		'GRID' => 'Group identification registration',
		'IPLS' => 'Involved people list (replaced by TMCL and TIPL in v2.4)',
		'LINK' => 'Linked information',
		'MCDI' => 'Music CD identifier',
		'MLLT' => 'MPEG location lookup table',
		'OWNE' => 'Ownership frame',
		'PRIV' => 'Private frame',
		'PCNT' => 'Play counter',
		'POPM' => 'Popularimeter',
		'POSS' => 'Position synchronisation frame',
		'RBUF' => 'Recommended buffer size',
		'RVAD' => 'Relative volume adjustment (replaced by RVA2 in v2.4)',
		'RVRB' => 'Reverb',
		'SYLT' => 'Synchronized lyric/text',
		'SYTC' => 'Synchronized tempo codes',
		'TALB' => 'Album/Movie/Show title',
		'TBPM' => 'BPM (beats per minute)',
		'TCOM' => 'Composer',
		'TCON' => 'Content type',
		'TCOP' => 'Copyright message',
		'TDAT' => 'Date (replaced by TDRC in v2.4)',
		'TDLY' => 'Playlist delay',
		'TENC' => 'Encoded by',
		'TEXT' => 'Lyricist/Text writer',
		'TFLT' => 'File type',
		'TIME' => 'Time (replaced by TDRC in v2.4)',
		'TIT1' => 'Content group description',
		'TIT2' => 'Title/songname/content description',
		'TIT3' => 'Subtitle/Description refinement',
		'TKEY' => 'Initial key',
		'TLAN' => 'Language(s)',
		'TLEN' => 'Length',
		'TMED' => 'Media type',
		'TOAL' => 'Original album/movie/show title',
		'TOFN' => 'Original filename',
		'TOLY' => 'Original lyricist(s)/text writer(s)',
		'TOPE' => 'Original artist(s)/performer(s)',
		'TORY' => 'Original release year (replaced by TDOR in v2.4)',
		'TOWN' => 'File owner/licensee',
		'TPE1' => 'Lead performer(s)/Soloist(s)',
		'TPE2' => 'Band/orchestra/accompaniment',
		'TPE3' => 'Conductor/performer refinement',
		'TPE4' => 'Interpreted, remixed, or otherwise modified by',
		'TPOS' => 'Part of a set',
		'TPUB' => 'Publisher',
		'TRCK' => 'Track number/Position in set',
		'TRDA' => 'Recording dates (replaced by TDRC in v2.4)',
		'TRSN' => 'Internet radio station name',
		'TRSO' => 'Internet radio station owner',
		'TSIZ' => 'Size (deprecated in v2.4)',
		'TSRC' => 'ISRC (international standard recording code)',
		'TSSE' => 'Software/Hardware and settings used for encoding',
		'TYER' => 'Year (replaced by TDRC in v2.4)',
		'TXXX' => 'User defined text information frame',
		'UFID' => 'Unique file identifier',
		'USER' => 'Terms of use',
		'USLT' => 'Unsynchronized lyric/text transcription',
		'WCOM' => 'Commercial information',
		'WCOP' => 'Copyright/Legal information',
		'WOAF' => 'Official audio file webpage',
		'WOAR' => 'Official artist/performer webpage',
		'WOAS' => 'Official audio source webpage',
		'WORS' => 'Official internet radio station homepage',
		'WPAY' => 'Payment',
		'WPUB' => 'Publishers official webpage',
		'WXXX' => 'User defined URL link frame',
	);

} // class Tag


