<?php
include './model/tag.php';
include './model/audiofile.php';

#include './model/frame.php';
#include './model/frame/tit2.php';

function dump($s) {
	foreach (str_split($s) as $l) {
		echo ord($l), ": ", sprintf('0x%02s', dechex(ord($l))), ' (', $l, ')', "\n";
	}
}

function decode_synchsafe($ss) {
	$v = 0;
	for ($i = 0; $i < 4; $i++) {
		$v |= (ord($ss[$i]) & 0x7F) << ((3 - $i) * 7);
	}
	return $v;
}

error_reporting(-1);
ini_set('display_errors', true);
set_error_handler(function($a,$b,$c,$d){throw new ErrorException($b,0,$a,$c,$d);}, -1);

$db = new SQLite3('./webtunes.db');
/*
$f = '/Volumes/Drobo/iTunes/Music/AFI/Sing the Sorrow/12 ....but home is nowhere.mp3';

$af = new AudioFile($f);

exit;

 */

$dir = new RecursiveDirectoryIterator('/Volumes/Drobo/iTunes/Music');
$it = new RecursiveIteratorIterator($dir);

$i = 0;
$errors = $warnings = array();

foreach ($it as $file) {
	if ($file->isFile()) {
		try {
			$af = new AudioFile($file);
			#$af->import($db);
#			foreach ($af->tags as $tag) {
#				echo "$tag->tag: " . ($tag->tag[0] != 'T' ? '<random/binary data>' : $tag->value) . "\n";
#			}
#			echo "$file OK!\n";
			echo '.';
		}
		catch (UnexpectedValueException $e) {
			echo 'W';
			$warnings[] = array($file, $e);
		}
		catch (Exception $e) {
			echo 'E';
			$errors[] = array($file, $e);
		}
		$i++;
		if ($i % 60 == 0) {
			echo " $i\n";
		}
		#$af = new AudioFile($file->getPathName());
		#$af->import($db);
		#echo "Imported {$file->getPathName()}\n";
		#unset($af);
	}
}

foreach ($errors as $e) {
	echo $e[0] . ' threw ' . $e[1];
}
foreach ($warnings as $w) {
	echo $w[0] . ' threw ' . $w[1];
}
