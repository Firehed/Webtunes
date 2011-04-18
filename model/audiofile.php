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
	private $size;
	private $startOfMusic;

	function __construct($path) {
		$this->path = $path;
		$fh = fopen($path, 'r');

		// Tag header is first 10 bytes of file
		$header = fread($fh, 10);

		$signature     = substr($header, 0 ,3);
		$version_major = ord(substr($header, 3, 1));
		$version_minor = ord(substr($header, 4, 1));
		$flags         = ord(substr($header, 5, 1));
		$this->size    = decode_synchsafe(substr($header, 6, 4));

		if ($signature != 'ID3') {
			return false;
		}

		$this->version_major = $version_major;
		$this->version_minor = $version_minor;

		$this->flags['unsynchronization'] = (bool) ($flags & 0x80);
		$this->flags['extended']          = (bool) ($flags & 0x40);
		$this->flags['experimental']      = (bool) ($flags & 0x20);
		$this->flags['footer']            = (bool) ($flags & 0x10);

		// Length of header += 10, length of footer (if present) += 10
		// -= 1 to handle position of *next* byte read
		$this->startOfMusic = $this->size + ($this->flags['footer'] ? 20 : 10) - 1;

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
	 * ID3 tag frame format:
	 * XXXXYYYYZZ....
	 * XXXX = frame identifier
	 * YYYY = frame content length bytes (synchsafe int or unsigned long, 
	 * depending on version)
	 * ZZ   = flags
	 * .... = actual frame content
	 */
	function parseTagsId3v23x($fh) {
		$filesize = filesize($this->path);
		while (!feof($fh)) {
			// No padding after previous frame, hit music. We're done here.
			if (ftell($fh) >= $this->startOfMusic) {
				break;
			}

			$header = fread($fh, 10);

			$tag = substr($header, 0, 4);
			if ($this->version_major == 3) {
				$size = unpack('N', substr($header, 4, 4));
				$size = $size[1];
			}
			else {
				$size = decode_synchsafe(substr($header, 4, 4));
			}

			$flags = unpack('n', substr($header, 8, 2));
			$flags = $flags[1];

			// We've probably hit the padding leading into the music...
			if (!trim($tag)) {
				break;
			}

			if ($size >= $this->size) {
				throw new Exception('Size overload ' . $size);
			}
			elseif (!$size) {
				// There is something invalid with this tag - by definition 
				// they must be at least 1 byte long
				throw new UnexpectedValueException("Tag $tag has no size");
			}
			else {
				$value = fread($fh, $size);
			}
			$this->tags[] = new Tag($tag, $flags, $value);
#			if ($tag == 'TIT2')
#				$this->frames = new frame\TIT2($flags, $value);
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

