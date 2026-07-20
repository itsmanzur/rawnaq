<?php
/**
 * One-shot POT generator for Rawnaq (dev helper — excluded via .distignore if under bin/).
 */
$dir = dirname( __DIR__ );
if ( basename( __DIR__ ) !== 'bin' ) {
	$dir = __DIR__;
}
// When run as languages/../bin/make-pot.php
if ( file_exists( dirname( __DIR__ ) . '/rawnaq.php' ) ) {
	$dir = dirname( __DIR__ );
}

$files    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
$strings  = [];
$pattern  = '/(?:__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e|_x)\s*\(\s*([\'"])((?:(?!\1).|\\\\\1)*)\1\s*,\s*[\'"]rawnaq[\'"]/s';

foreach ( $files as $f ) {
	if ( ! $f->isFile() || $f->getExtension() !== 'php' ) {
		continue;
	}
	$path = $f->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$code = file_get_contents( $path );
	if ( ! preg_match_all( $pattern, $code, $m, PREG_OFFSET_CAPTURE ) ) {
		continue;
	}
	foreach ( $m[2] as $hit ) {
		$str  = stripcslashes( $hit[0] );
		$line = substr_count( substr( $code, 0, $hit[1] ), "\n" ) + 1;
		$rel  = str_replace( '\\', '/', substr( $path, strlen( $dir ) + 1 ) );
		if ( ! isset( $strings[ $str ] ) ) {
			$strings[ $str ] = [];
		}
		$strings[ $str ][] = $rel . ':' . $line;
	}
}

ksort( $strings );

$esc = static function ( $s ) {
	return str_replace(
		[ '\\', '"', "\n", "\r", "\t" ],
		[ '\\\\', '\\"', '\\n', '', '\\t' ],
		$s
	);
};

$out  = '# Copyright (C) ' . gmdate( 'Y' ) . " Rawnaq\n";
$out .= "# This file is distributed under the same license as the Rawnaq plugin.\n";
$out .= "msgid \"\"\nmsgstr \"\"\n";
$out .= "\"Project-Id-Version: Rawnaq 1.17.5\\n\"\n";
$out .= "\"Report-Msgid-Bugs-To: https://github.com/itsmanzur/rawnaq/issues\\n\"\n";
$out .= '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:iO' ) . "\\n\"\n";
$out .= "\"MIME-Version: 1.0\\n\"\n";
$out .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
$out .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
$out .= "\"X-Domain: rawnaq\\n\"\n\n";

foreach ( $strings as $msgid => $refs ) {
	foreach ( array_slice( $refs, 0, 8 ) as $ref ) {
		$out .= '#: ' . $ref . "\n";
	}
	$out .= 'msgid "' . $esc( $msgid ) . "\"\n";
	$out .= "msgstr \"\"\n\n";
}

$lang = $dir . '/languages';
if ( ! is_dir( $lang ) ) {
	mkdir( $lang, 0755, true );
}
file_put_contents( $lang . '/rawnaq.pot', $out );
echo 'Wrote ' . count( $strings ) . " strings to languages/rawnaq.pot\n";
