<?php
include './model/tag.php';
include './model/audiofile.php';
error_reporting(-1);
ini_set('display_errors', true);
set_error_handler(function($a,$b,$c,$d){throw new ErrorException($b,0,$a,$c,$d);}, -1);

$db = new SQLite3('./webtunes.db');

$dir = new RecursiveDirectoryIterator('/Volumes/Drobo/iTunes/Music/3 Doors Down');
$it = new RecursiveIteratorIterator($dir);

foreach ($it as $file) {
	if ($file->isFile()) {
		try {
			$af = new AudioFile($file);
			#$af->import($db);
			foreach ($af->tags as $tag) {
				echo "$tag->tag: " . ($tag->tag == 'APIC' ? '<binary data>' : $tag->value) . "\n";
			}
		}
		catch (Exception $e) {
			echo $e;
		}

		#$af = new AudioFile($file->getPathName());
		#$af->import($db);
		#echo "Imported {$file->getPathName()}\n";
		#unset($af);
	}
}


