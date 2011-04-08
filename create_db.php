<?php
$db = new SQLite3('./webtunes.db');

// Create tracks table
$db->exec('CREATE TABLE tracks ( 
	id        INTEGER PRIMARY KEY AUTOINCREMENT,
	name      TEXT,
	album_id  INTEGER,
	track     INTEGER,
	disc      INTEGER,
	year      INTEGER,
	seconds   INTEGER 
);');

// Albums
$db->exec('CREATE TABLE albums (
	id        INTEGER PRIMARY KEY AUTOINCREMENT,
	name      TEXT,
	tracks    INTEGER,
	discs     INTEGER,
	artist_id INTEGER,
	artwork   TEXT
);');

// Artists
$db->exec('CREATE TABLE artists (
	id    INTEGER PRIMARY KEY AUTOINCREMENT,
	name  TEXT
);');

