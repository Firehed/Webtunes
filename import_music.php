<?php
include './model/tag.php';
include './model/audiofile.php';
include './class/exceptions.php';

function __autoload($c) {
	$path = strtolower('./class/' . str_replace('\\', '/', $c) . '.php');
	if (file_exists($path)) {
		include $path;
	}
}

$target = end($_SERVER['argv']);
if (!is_dir($target)) {
	echo "$target is not a valid directory.";
	exit(1);
}

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

$dir = new RecursiveDirectoryIterator($target);
$it = new RecursiveIteratorIterator($dir);

$i = 0;
$errors = $warnings = array();

foreach ($it as $file) {
	if ($file->isFile()) {
		try {
			$af = new AudioFile($file);
			#$af->import($db);
			echo '.';
		}
		catch (FileSkippedException $e) {
			echo 'S';
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
