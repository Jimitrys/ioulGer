<?php
defined( 'ABSPATH' ) || exit;

/**
 * Conservative static checks for administrator-authored PHP snippets.
 * This is a safety gate, not a process-level PHP sandbox.
 */
final class IGC_Snippet_Validator {
	private const BLOCKED_FUNCTIONS = array(
		'exec',
		'shell_exec',
		'system',
		'passthru',
		'proc_open',
		'popen',
		'pcntl_exec',
		'putenv',
		'unlink',
		'rmdir',
		'chmod',
		'chown',
		'chgrp',
	);

	private const BLOCKED_TOKENS = array(
		T_EVAL,
		T_EXIT,
		T_INCLUDE,
		T_INCLUDE_ONCE,
		T_REQUIRE,
		T_REQUIRE_ONCE,
	);

	public static function validate( string $code ): bool|WP_Error {
		$code = trim( $code );
		if ( '' === $code ) {
			return new WP_Error( 'empty_snippet', __( 'The PHP snippet is empty.', 'igc-builder' ) );
		}

		if ( str_contains( $code, '<?php' ) || str_contains( $code, '?>' ) ) {
			return new WP_Error( 'php_tags', __( 'Do not include PHP opening or closing tags.', 'igc-builder' ) );
		}

		try {
			$tokens = token_get_all( "<?php\n" . $code, TOKEN_PARSE );
		} catch ( ParseError $error ) {
			return new WP_Error( 'parse_error', sprintf( __( 'PHP syntax error: %s', 'igc-builder' ), $error->getMessage() ) );
		}

		$count = count( $tokens );
		for ( $index = 0; $index < $count; $index++ ) {
			$token = $tokens[ $index ];
			if ( ! is_array( $token ) ) {
				continue;
			}

			if ( in_array( $token[0], self::BLOCKED_TOKENS, true ) ) {
				return new WP_Error( 'blocked_construct', sprintf( __( 'Blocked PHP construct near line %d.', 'igc-builder' ), (int) $token[2] ) );
			}

			if ( T_STRING !== $token[0] ) {
				continue;
			}

			$function = strtolower( $token[1] );
			if ( ! in_array( $function, self::BLOCKED_FUNCTIONS, true ) || ! self::next_token_is_call( $tokens, $index ) ) {
				continue;
			}

			return new WP_Error(
				'blocked_function',
				sprintf( __( 'The function %1$s() is blocked for safety near line %2$d.', 'igc-builder' ), $function, (int) $token[2] )
			);
		}

		if ( preg_match( '/\b(?:DROP|TRUNCATE)\s+(?:TABLE|DATABASE)\b/i', $code ) ) {
			return new WP_Error( 'destructive_sql', __( 'Destructive DROP/TRUNCATE SQL is blocked.', 'igc-builder' ) );
		}

		return true;
	}

	private static function next_token_is_call( array $tokens, int $index ): bool {
		$count = count( $tokens );
		for ( $next = $index + 1; $next < $count; $next++ ) {
			$token = $tokens[ $next ];
			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			return '(' === $token;
		}
		return false;
	}
}
