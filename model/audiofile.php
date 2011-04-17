<?php

class AudioFile {

	public $tags = array();
	private $path;
	private $version;
	private $flags = array(
		'unsynchronization' => false,
		'extended'          => false,
		'experimental'      => false,
		'footer'            => false,
	);

	function __construct($path) {
		$this->path = $path;
		$fh = fopen($path, 'r');

		// Tag header is first 10 bytes of file
		$header = unpack("a3signature/c1version_major/c1version_minor/c1flags/Nsize", fread($fh, 10));
		if ($header['signature'] != 'ID3') {
			return false;
		}

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

	/*
	 * ID3 tag frame format:
	 * XXXXYYYYZZ....
	 * XXXX = frame identifier
	 * YYYY = frame content length bytes (unsigned long 32-bit big-endian)
	 * ZZ   = flags
	 * .... = actual frame content
	 */
	function parseTagsId3v23x($fh) {
		$filesize = filesize($this->path);
		while (!feof($fh)) {
			$header = fread($fh, 10);

			$tag   = substr($header, 0, 4);
			$size  = unpack('N', substr($header, 4, 4));
			$size  = $size[1];
			$flags = unpack('n', substr($header, 8, 2));
			$flags = $flags[1];

			// I've seen ID3 tags where the APIC tag has a bogus length, casuing 
			// the next seek to go crazy. Hence the check
			// Reported: "10110110111001111001"
			// Actual:   "1011 1101110 1111001"
			if ($size > $filesize) {
				throw new Exception("Invalid size while trying to parse header in file $this->path (tried to read $size bytes)");
			}

			// We've probably hit the padding leading into the music...
			if (!trim($tag)) {
				break;
			}

			$value = fread($fh, $size);
			$this->tags[] = new Tag($tag, $flags, $value);
		}
	}

	function import(SQLite3 $db) {
		$stmt = $db->prepare('INSERT INTO `tracks` (`name`) VALUES (:name)');
		$stmt->bindParam(':name', $this->path, SQLITE3_STRING);
		$stmt->execute();
		#$db->exec('INSERT INTO tracks (`name`) VALUES ("' . sqlite_escape_string($this->path) . '");');
		$id = $db->lastInsertRowId();
		$stmt->close();
		$stmt = $db->prepare('INSERT INTO `tags` (`track_id`, `tag`, `value`) VALUES(:track_id, :tag, :value)');

		foreach ($this->tags as $tag) {
			if ($tag->tag == 'APIC')
					continue; // don't store picture!
			$stmt->bindParam(':track_id', $id, SQLITE3_INTEGER);
			$stmt->bindParam(':tag', $tag->tag, SQLITE3_STRING);
			$stmt->bindParam(':value', $tag->value, SQLITE3_STRING);
			$res = $stmt->execute();
		}
		$stmt->close();
	} // import

} // AudioFile

