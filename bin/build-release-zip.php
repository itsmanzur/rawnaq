<?php
/**
 * Build a release zip using .distignore rules.
 * Usage: php bin/build-release-zip.php
 */
$root = dirname( __DIR__ );
$slug = 'rawnaq';
$ver  = '1.18.0';
$main = $root . '/rawnaq.php';
if ( is_readable( $main ) && preg_match( '/Version:\s*([0-9.]+)/', file_get_contents( $main ), $m ) ) {
	$ver = $m[1];
}

$ignore_file = $root . '/.distignore';
$patterns    = [];
if ( is_readable( $ignore_file ) ) {
	foreach ( file( $ignore_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || 0 === strpos( $line, '#' ) ) {
			continue;
		}
		$patterns[] = str_replace( '\\', '/', $line );
	}
}

/**
 * @param string $rel Relative path with forward slashes.
 */
function rawnaq_zip_should_ignore( $rel, $patterns ) {
	$rel = ltrim( str_replace( '\\', '/', $rel ), '/' );
	foreach ( $patterns as $pat ) {
		$pat = ltrim( $pat, '/' );
		// Directory prefix (docs, bin, assets/demo).
		if ( false === strpos( $pat, '*' ) ) {
			if ( $rel === $pat || 0 === strpos( $rel, $pat . '/' ) ) {
				return true;
			}
			continue;
		}
		// Glob: *.md, rawnaq-*-mockup.html
		$regex = '#^' . str_replace(
			[ '\*\*', '\*' ],
			[ '.*', '[^/]*' ],
			preg_quote( $pat, '#' )
		) . '$#';
		if ( preg_match( $regex, $rel ) || preg_match( $regex, basename( $rel ) ) ) {
			return true;
		}
	}
	return false;
}

$out_dir = $root . '/dist';
if ( ! is_dir( $out_dir ) ) {
	mkdir( $out_dir, 0755, true );
}
$zip_path = $out_dir . '/' . $slug . '-' . $ver . '.zip';
if ( file_exists( $zip_path ) ) {
	unlink( $zip_path );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Cannot create zip\n" );
	exit( 1 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

$count = 0;
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$full = $file->getPathname();
	$rel  = substr( $full, strlen( $root ) + 1 );
	$rel  = str_replace( '\\', '/', $rel );
	if ( 0 === strpos( $rel, 'dist/' ) ) {
		continue;
	}
	if ( rawnaq_zip_should_ignore( $rel, $patterns ) ) {
		continue;
	}
	$zip->addFile( $full, $slug . '/' . $rel );
	$count++;
}

$zip->close();
echo "Created {$zip_path} ({$count} files)\n";
