<?php
/**
 * Pre-deploy check for Site Studio snippets.
 *
 * Site Studio stores snippet code through update_post_meta(), which unslashes
 * it, so one level of backslashes is stripped between this repository and the
 * running site. The plugin's validator runs against the file, not against what
 * it stores, so a snippet broken this way imports cleanly and only fails when
 * it executes.
 *
 * This reproduces the import and parses the result the same way the validator
 * does, which is what the file check misses.
 *
 *   docker run --rm -v "$PWD":/w -w /w php:8.2-cli php tools/check-import.php
 *
 * Exits non-zero if any snippet changes under the import or fails to parse.
 */

$failures = 0;

foreach ( glob( dirname( __DIR__ ) . '/runtime/snippets/*/snippet.php' ) as $file ) {
	$name = basename( dirname( $file ) );

	// The importer drops the leading tag before storing the snippet.
	$code   = preg_replace( '/^\s*<\?php\s*/', '', file_get_contents( $file ), 1 );
	$stored = stripslashes( $code );

	if ( $stored !== $code ) {
		echo "MANGLED  $name — contains backslashes the importer will strip\n";
		$failures++;
		continue;
	}

	try {
		token_get_all( "<?php\n" . $stored, TOKEN_PARSE );
		echo "ok       $name\n";
	} catch ( ParseError $error ) {
		echo "PARSE    $name — " . $error->getMessage() . "\n";
		$failures++;
	}
}

echo $failures ? "\n$failures snippet(s) would break on import.\n" : "\nAll snippets survive the import unchanged.\n";

exit( $failures ? 1 : 0 );
