<?php

class AudioFile {

	public $tags = array();
	private $path;
	private $version_major;
	private $version_minor;
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

		$this->version_major = $header['version_major'];
		$this->version_minor = $header['version_minor'];

		$flags = $header['flags'];
		$this->flags['unsynchronization'] = (bool) ($flags & 0x80);
		$this->flags['extended']          = (bool) ($flags & 0x40);
		$this->flags['experimental']      = (bool) ($flags & 0x20);
		$this->flags['footer']            = (bool) ($flags & 0x10);

		if ($this->flags['extended']) {
			$this->parseExtendedHeader($fh);
		}

		switch ($this->version_major) {
			case 3:
			case 4:
				$this->parseTagsId3v23x($fh);
				break;
			case 4:
				echo 'ID3v2.4.x - unhandled right now';
				break;
		}

		if ($this->flags['footer'] && $this->version_major >= 4) {
			$this->parseFooter($fh);
		}

		fclose($fh);
	}

	private function parseExtendedHeader($fh) {
		throw new Exception('parseExtendedHeader not yet implemented');
	} // parseExtendedHeader

	private function parseFooter($fh) {
		throw new Excption('parseFooter not yet implemented');
	} // parseFooter

	/*
	 * Depending on placement and major version, size may be encoded as either 
	 * $xx xx xx xx (4 standard bytes)
	 * or 
	 * 4 * %0xxxxxxx (each byte has high bit discarded, total 28 bits)
	 */
	private function decodeSize($rawsize) {
		// ID3v2.3.x uses a logical approach for handling sizes
		if ($this->version_major == 3) {
			$size = unpack('N', $rawsize);
			return $size[1];
		}

		// ID3v2.4.x does the absurd "drop the 7th bit" thing as described 
		// above
		$binary = ''; // bindec this later
		$bytes = str_split($rawsize);
		foreach ($bytes as $byte) {
			$byte = ord($byte) & 0x7F; // Drop the high bit of raw (ordinal) value
			$binary .= sprintf('%07s', decbin($byte)); // Append to binary string maintaining left zero-padding
		}
		return bindec($binary);
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
			$size  = $this->decodeSize(substr($header, 4, 4));

			$flags = unpack('n', substr($header, 8, 2));
			$flags = $flags[1];

			// We've probably hit the padding leading into the music...
			if (!trim($tag)) {
				break;
			}

			if ($size <= 0) {
				var_dump($this->version_major);
				foreach (str_split($tag) as $l) var_dump(ord($l));
				throw new Exception("Tag $tag is empty (size $size)!");
				break;
			}
			$value = fread($fh, $size);
			$this->tags[] = new Tag($tag, $flags, $value);
#			if ($tag == 'TIT2')
#				$this->frames = new frame\TIT2($flags, $value);
		}
		#print_r($this->tags);
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

