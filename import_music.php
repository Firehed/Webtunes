<?php
$db = new SQLite3('./webtunes.db');

$file = '/Volumes/Drobo/iTunes/Music/3 Doors Down/Away From The Sun/01 When I\'m Gone.mp3';
#$file = '/Volumes/Drobo/iTunes/Music/30 Seconds To Mars/This Is War/01 Escape.mp3';
$file = '/Volumes/Drobo/iTunes/Music/AC_DC/T.N.T_/01 It\'s A Long Way To The Top (If You Wanna Rock \'n\' Roll).mp3';

$af = new AudioFile($file);
$af->import($db);
exit;

class Tag {

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

	function __construct($tag, $flags, $value) {
		$this->tag = $tag;
		$this->flags = $flags;
		$this->value = $value;
		$this->parseValue();
	}

	private function parseValue() {
		if ($this->tag == 'APIC')
			return;

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

	function __toString() {
		if ($this->tag == 'APIC')
			return '';
		return "$this->tag ({$this->getTagName()}): $this->value\n";
	}

}

class AudioFile {

	public $tags = array();
	private $path;
	private $version;
	private $flags = array(
		'unsynchronization' => false,
		'extended' => false,
		'experimental' => false,
		'footer' => false,
	);
	function __construct($path) {
		$this->path = $path;
		$fh = fopen($path, 'r');

		// Tag header is first 10 bytes of file
		$header = unpack("a3signature/c1version_major/c1version_minor/c1flags/Nsize", fread($fh, 10));
		if ($header['signature'] != 'ID3')
			return false;
		$flags = $header['flags'];
		$this->flags['unsynchronization'] = (bool) ($flags & 0x80);
		$this->flags['extended']          = (bool) ($flags & 0x40);
		$this->flags['experimental']      = (bool) ($flags & 0x20);
		$this->flags['footer']            = (bool) ($flags & 0x10);
		switch ($header['version_major']) {
			case 3:
			case 4:
				$this->parseTagsId3v23x($fh);
				break;
			case 4:
				echo 'ID3v2.4.x - unhandled right now';
				break;
		}
		fclose($fh);
	}

	function parseTagsId3v23x($fh) {
		/*
		 * ID3 tag format:
		 * XXXXYYYYZZA...A
		 * XXXX = tag identifier
		 * YYYY = tag content length bytes (unsigned long 32-bit big-endian)
		 * ZZ   = flags
		 * A...A = actual tag content
		 *
		 * First bytes of tag are encoding format when text
		 * Byte = Character set     = Terminator
		 * \000 = ISO-8859-1        = \000
		 * \001 = UTF16 with BOM    = \000\000
		 * \002 = UTF16-BE, no BOM  = \000\000
		 * \003 = UTF8              = \000
		 */

		while (!feof($fh)) {
			// First 4 bytes of tag header are the tag string itself
			$tag = fread($fh, 4);

			// We've probably hit the padding leading into the music...
			if (!trim($tag)) {
				break;
			}
			
			// debugging
			if (!preg_match('/[A-Z0-9]{4}/', $tag)) {
				echo $value; //from prev
				#while (!feof($fh)) {
				#	echo fread($fh, 60) . "\n";
				#}
				break;
			}

			// Next 4 bytes are size
			$size = unpack('N', fread($fh, 4));
			$size = $size[1];

			// Last 2 bytes of tag header are flags
			$flags = fread($fh, 2);

			// Actual value of tag, finally
			$value = fread($fh, $size);
			$this->tags[] = new Tag($tag, $flags, $value);
		}
	}

	function import(SQLite3 $db) {
		$db->exec('INSERT INTO tracks (`name`) VALUES ("' . sqlite_escape_string($this->path) . '");');
		$id = $db->lastInsertRowId();
		foreach ($this->tags as $tag) {
			if ($tag->tag == 'APIC')
					continue; // don't store picture!
			$db->exec(sprintf(
				"INSERT INTO `tags` (`track_id`, `tag`, `value`) VALUES (%d, '%s', '%s');"
				, $id
				, sqlite_escape_string($tag->tag)
				, sqlite_escape_string($tag->value)
			));
		}
	} // import

} // AudioFile


$dir = new RecursiveDirectoryIterator('/Volumes/Drobo/iTunes/Music');
$it = new RecursiveIteratorIterator($dir);

foreach ($it as $file) {
	if ($file->isFile()) {
		$af = new AudioFile($file->getPathName());
		$af->import($db);
		echo "Imported {$file->getPathName()}\n";
		unset($af);
	}
}


