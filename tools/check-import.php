<?php
/**
 * Pre-deploy check for Site Studio snippets.
 *
 * Run before committing snippet changes:
 *
 *   docker run --rm -v "$PWD":/w -w /w php:8.2-cli php tools/check-import.php
 *
 * Simulate a Site Studio import: strip the leading <?php, unslash (what
// update_post_meta does), then check the code that would actually be eval'd.
$fail = 0;
foreach ( glob( dirname( __DIR__ ) . '/runtime/snippets/*/snippet.php' ) as $file ) {
    $name = basename( dirname( $file ) );
    $code = preg_replace( '/^\s*<\?php\s*/', '', file_get_contents( $file ), 1 );
    $stored = stripslashes( $code );            // update_post_meta() unslashes
    if ( $stored !== $code ) {
        echo "CHANGED BY IMPORT: $name\n";
        $fail++;
    }
    try {
        token_get_all( "<?php\n" . $stored, TOKEN_PARSE );
        echo "ok   $name\n";
    } catch ( ParseError $e ) {
        echo "FAIL $name: " . $e->getMessage() . "\n";
        $fail++;
    }
}
exit( $fail ? 1 : 0 );
