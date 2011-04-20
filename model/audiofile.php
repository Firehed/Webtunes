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

	private function parseheader() {
		$this->version_major = ord(fread($this->fh, 1));
		$this->version_minor = ord(fread($this->fh, 1));
		$flags               = ord(fread($this->fh, 1));
		$this->size          = decode_synchsafe(fread($this->fh, 4));

		$this->flags['unsynchronization'] = (bool) ($flags & 0x80);
		$this->flags['extended']          = (bool) ($flags & 0x40);
		$this->flags['experimental']      = (bool) ($flags & 0x20);
		$this->flags['footer']            = (bool) ($flags & 0x10);

		// Length of header += 10, length of footer (if present) += 10
		// -= 1 to handle position of *next* byte read
		$this->startOfMusic = $this->size + ($this->flags['footer'] ? 20 : 10) - 1;

		if ($this->flags['extended']) {
			$this->parseExtendedHeader();
		}

	}


	function __construct($path) {
		$this->path = $path;
		$this->fh = fopen($path, 'r');

		$signature = fread($this->fh, 3);
		if ($signature != 'ID3') {
			fclose($this->fh);
			throw new FileSkippedException;
		}

		$this->parseHeader();
		$this->parseFrames();

		if ($this->flags['footer'] && $this->version_major >= 4) {
			$this->parseFooter();
		}

		fclose($this->fh);
	}

	private function parseExtendedHeader() {
		throw new Exception('parseExtendedHeader not yet implemented');
	} // parseExtendedHeader

	private function parseFooter() {
		throw new Exception('parseFooter not yet implemented');
	} // parseFooter

	private function parseFrames() {
		switch ($this->version_major) {
			case 2:
				$parser = 'v22'; 
				break;
			case 3:
				$parser = 'v23';
				break;
			case 4:
				$parser = 'v24';
				break;
			default:
				throw new Exception("ID3 v2.$this->version_major currently unsupported.");
		}
		while (!feof($this->fh)) {
			// No padding after previous frame, hit music. We're done here
			if (ftell($this->fh) >= $this->startOfMusic) {
				break;
			}

			if (FALSE === $frame = $this->$parser()) {
				break;
			}

			list($tag, $flags, $value) = $frame;
			$this->tags[] = new Tag($tag, $flags, $value);
		}
	}

	/*
	 * ID3v2.2.x tag frame format:
	 * XXXYYY....
	 * XXX = frame identifier
	 * YYY = frame content length bytes
	 * ... = actual frame content
	 */
	private function v22() {
		$header = fread($this->fh, 6);

		$tag = substr($header, 0, 3);
		$size = unpack('cbig/nsmall', substr($header, 3, 3));
		$size = 65536 * $size['big'] + $size['small'];

		// We've probably hit the padding leading into the music...
		if (!trim($tag)) {
			return false;
		}

		$value = fread($this->fh, $size);
		return array($tag, 0, $value);
	}

	/*
	 * ID3v2.3.x tag frame format:
	 * XXXXYYYYZZ....
	 * XXXX = frame identifier
	 * YYYY = frame content length bytes (unsigned long)
	 * ZZ   = flags
	 * .... = actual frame content
	 */
	private function v23() {
		$header = fread($this->fh, 10);

		$tag   = substr($header, 0, 4);
		$size  = unpack('N', substr($header, 4, 4));
		$size  = $size[1];
		$flags = unpack('n', substr($header, 8, 2));
		$flags = $flags[1];

		// We've probably hit the padding leading into the music...
		if (!trim($tag)) {
			return false;
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
			$value = fread($this->fh, $size);
		}
		return array($tag, $flags, $value);
	}

	/*
	 * ID3v2.4.x tag frame format:
	 * XXXXYYYYZZ....
	 * XXXX = frame identifier
	 * YYYY = frame content length bytes (synchsafe int)
	 * ZZ   = flags
	 * .... = actual frame content
	 */
	private function v24() {
		$header = fread($this->fh, 10);

		$tag   = substr($header, 0, 4);
		$size  = decode_synchsafe(substr($header, 4, 4));
		$flags = unpack('n', substr($header, 8, 2));
		$flags = $flags[1];

		// We've probably hit the padding leading into the music...
		if (!trim($tag)) {
			return false;
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
			$value = fread($this->fh, $size);
		}
		return array($tag, $flags, $value);
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

