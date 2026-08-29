<?php
defined( 'ABSPATH' ) || exit;

/**
 * File-backed workspace and guarded Git bridge for Site Studio runtime code.
 *
 * The Git repository lives outside the public web root whenever possible. Database
 * content is exported to deterministic files; imports validate every PHP snippet
 * before applying any changes. Git commands are fixed argument arrays and never
 * pass user input through a shell.
 */
final class IGC_Git_Workspace {
	private const PAGE = 'igc-git-workspace';
	private const NOTICE_PREFIX = 'igc_git_notice_';
	private const TYPES = array(
		'igc_canvas'         => 'canvases',
		'igc_code_block'     => 'blocks',
		'igc_theme_template' => 'templates',
		'igc_php_snippet'    => 'snippets',
	);

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 22 );
		add_action( 'admin_post_igc_git_action', array( self::class, 'handle_action' ) );
		add_action( 'admin_post_igc_git_quick_action', array( self::class, 'handle_quick_action' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'admin_bar_menu', array( self::class, 'admin_bar' ), 95 );
	}

	public static function admin_bar( WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_connected() ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'igc-git-sync',
				'title' => '<span class="ab-icon dashicons dashicons-update" style="top:2px"></span>' . esc_html__( 'Site Sync', 'igc-builder' ),
				'href'  => admin_url( 'admin.php?page=' . self::PAGE ),
				'meta'  => array( 'title' => __( 'Sync Site Studio with GitHub', 'igc-builder' ) ),
			)
		);
		$admin_bar->add_node(
			array(
				'parent' => 'igc-git-sync',
				'id'     => 'igc-git-quick-push',
				'title'  => __( '↑ Push Site → GitHub', 'igc-builder' ),
				'href'   => self::quick_action_url( 'export_push' ),
			)
		);
		$admin_bar->add_node(
			array(
				'parent' => 'igc-git-sync',
				'id'     => 'igc-git-quick-pull',
				'title'  => __( '↓ Pull GitHub → Site', 'igc-builder' ),
				'href'   => self::quick_action_url( 'pull_import' ),
				'meta'   => array( 'onclick' => "return confirm('" . esc_js( __( 'Pull and import the latest GitHub runtime code into this site?', 'igc-builder' ) ) . "');" ),
			)
		);
	}

	public static function menu(): void {
		add_submenu_page(
			'igc-builder',
			__( 'Git Workspace', 'igc-builder' ),
			__( 'Git Workspace', 'igc-builder' ),
			'manage_options',
			self::PAGE,
			array( self::class, 'page' )
		);
	}

	public static function assets(): void {
		if ( self::PAGE !== sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) ) {
			return;
		}
		wp_enqueue_style( 'igc-admin', IGC_BUILDER_URL . 'assets/admin.css', array(), IGC_BUILDER_VERSION );
	}

	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::settings();
		$workspace = self::workspace_dir();
		$git = self::git_binary();
		$is_repo = is_dir( $workspace . '/.git' );
		$github_ready = ! $git && self::github_ready();
		$is_connected = $git ? $is_repo : $github_ready;
		$status = $is_repo && $git ? self::run_git( array( 'status', '--short', '--branch' ), 8 ) : array( 'code' => 1, 'output' => '' );
		$last_commit = $is_repo && $git ? self::run_git( array( 'log', '-1', '--pretty=format:%h · %s · %cr' ), 8 ) : array( 'code' => 1, 'output' => '' );
		if ( $github_ready ) {
			$status = array( 'code' => 0, 'output' => __( 'GitHub API mode is ready. No server Git binary is required.', 'igc-builder' ) );
			$last_commit = array( 'code' => 0, 'output' => (string) get_option( 'igc_github_last_commit', '' ) );
		}
		$notice = get_transient( self::NOTICE_PREFIX . get_current_user_id() );
		if ( $notice ) {
			delete_transient( self::NOTICE_PREFIX . get_current_user_id() );
		}
		?>
		<div class="wrap igc-admin igc-git-admin">
			<section class="igc-page-heading">
				<div><span class="igc-eyebrow">CODE WORKSPACE</span><h1><?php esc_html_e( 'Git Workspace', 'igc-builder' ); ?></h1></div>
				<p><?php esc_html_e( 'Keep Site Studio HTML, CSS, JavaScript and PHP in reviewable files without moving products, orders or bookings out of WordPress.', 'igc-builder' ); ?></p>
			</section>

			<?php if ( is_array( $notice ) ) : ?>
				<div class="notice <?php echo ! empty( $notice['ok'] ) ? 'notice-success' : 'notice-error'; ?> inline"><p><strong><?php echo esc_html( (string) $notice['title'] ); ?></strong></p><?php if ( ! empty( $notice['message'] ) ) : ?><pre class="igc-git-output"><?php echo esc_html( (string) $notice['message'] ); ?></pre><?php endif; ?></div>
			<?php endif; ?>

			<?php if ( $is_connected ) : ?>
				<section class="igc-panel igc-quick-sync">
					<div class="igc-panel__heading">
						<div><span class="igc-eyebrow">TWO-BUTTON SYNC</span><h2><?php esc_html_e( 'Choose the direction', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Push publishes the current Site Studio code to GitHub. Pull validates, backs up and imports the latest GitHub runtime into WordPress.', 'igc-builder' ); ?></p></div>
					</div>
					<div class="igc-quick-sync__actions">
						<div><span>1</span><strong><?php esc_html_e( 'Site → GitHub', 'igc-builder' ); ?></strong><?php self::push_form(); ?><small><?php esc_html_e( 'Commit message is created automatically.', 'igc-builder' ); ?></small></div>
						<div><span>2</span><strong><?php esc_html_e( 'GitHub → Site', 'igc-builder' ); ?></strong><?php self::action_form( 'pull_import', __( 'Pull & Import to Site', 'igc-builder' ), 'primary', false, __( 'Pull and import the latest GitHub runtime code into this site?', 'igc-builder' ) ); ?><small><?php esc_html_e( 'Validation and a runtime backup run first.', 'igc-builder' ); ?></small></div>
					</div>
				</section>
			<?php endif; ?>

			<div class="igc-grid igc-grid--three">
				<section class="igc-card"><div class="igc-card__count"><?php echo $git || $github_ready ? '✓' : '—'; ?></div><h2><?php echo $git ? esc_html__( 'Git binary', 'igc-builder' ) : esc_html__( 'GitHub API', 'igc-builder' ); ?></h2><p><?php echo $git ? esc_html( $git ) : ( $github_ready ? esc_html__( 'Connected without a server Git binary.', 'igc-builder' ) : esc_html__( 'Git was not found. Add a GitHub token below to use API mode.', 'igc-builder' ) ); ?></p></section>
				<section class="igc-card"><div class="igc-card__count"><?php echo $is_connected ? '✓' : '—'; ?></div><h2><?php esc_html_e( 'Repository', 'igc-builder' ); ?></h2><p><?php echo $is_connected ? esc_html__( 'Connected', 'igc-builder' ) : esc_html__( 'Not connected', 'igc-builder' ); ?></p></section>
				<section class="igc-card"><div class="igc-card__count"><?php echo esc_html( (string) self::code_item_count() ); ?></div><h2><?php esc_html_e( 'Database code items', 'igc-builder' ); ?></h2><p><?php esc_html_e( 'Canvases, blocks, templates and PHP snippets ready to export.', 'igc-builder' ); ?></p></section>
			</div>

			<section class="igc-panel">
				<h2><?php esc_html_e( 'Repository settings', 'igc-builder' ); ?></h2>
				<p><?php echo $git ? esc_html__( 'Use an empty private repository. Authentication must be configured on the server with an SSH deploy key or Git credential helper.', 'igc-builder' ) : esc_html__( 'This hosting has no Git binary, so Site Studio will connect through the GitHub API. Use a private repository initialized with a README and a fine-grained token limited to that repository with Contents read/write permission.', 'igc-builder' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="igc-styles-form">
					<input type="hidden" name="action" value="igc_git_action"><input type="hidden" name="igc_git_operation" value="save_settings"><?php wp_nonce_field( 'igc_git_action', 'igc_git_nonce' ); ?>
					<div class="igc-grid igc-grid--two">
						<label class="igc-field"><span><?php esc_html_e( 'Repository URL', 'igc-builder' ); ?></span><input type="text" name="repository" placeholder="git@github.com:owner/repository.git" value="<?php echo esc_attr( $settings['repository'] ); ?>"></label>
						<label class="igc-field"><span><?php esc_html_e( 'Branch', 'igc-builder' ); ?></span><input type="text" name="branch" value="<?php echo esc_attr( $settings['branch'] ); ?>"></label>
						<label class="igc-field"><span><?php esc_html_e( 'Commit author', 'igc-builder' ); ?></span><input type="text" name="author_name" value="<?php echo esc_attr( $settings['author_name'] ); ?>"></label>
						<label class="igc-field"><span><?php esc_html_e( 'Commit email', 'igc-builder' ); ?></span><input type="email" name="author_email" value="<?php echo esc_attr( $settings['author_email'] ); ?>"></label>
					</div>
					<?php if ( ! $git ) : ?>
						<label class="igc-field"><span><?php esc_html_e( 'GitHub fine-grained token', 'igc-builder' ); ?></span><input type="password" name="github_token" autocomplete="new-password" placeholder="<?php echo self::github_token() ? esc_attr__( 'Token saved — leave blank to keep it', 'igc-builder' ) : esc_attr__( 'github_pat_…', 'igc-builder' ); ?>"><small><?php esc_html_e( 'Encrypted with the WordPress security keys. You may instead define IGC_GITHUB_TOKEN in wp-config.php.', 'igc-builder' ); ?></small></label>
						<?php if ( self::github_token() && ! defined( 'IGC_GITHUB_TOKEN' ) ) : ?><label><input type="checkbox" name="forget_github_token" value="1"> <?php esc_html_e( 'Forget the saved GitHub token', 'igc-builder' ); ?></label><?php endif; ?>
					<?php endif; ?>
					<?php submit_button( __( 'Save Git Settings', 'igc-builder' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>

			<div class="igc-grid igc-grid--two">
				<section class="igc-panel">
					<h2><?php esc_html_e( 'WordPress → files → Git', 'igc-builder' ); ?></h2>
					<p><?php esc_html_e( 'Exports current database code and a plugin source snapshot. Export refuses to overwrite uncommitted repository edits.', 'igc-builder' ); ?></p>
					<div class="igc-git-actions">
						<?php self::action_form( 'export', __( 'Export to Files', 'igc-builder' ), 'secondary' ); ?>
						<?php if ( ! $is_connected ) : self::action_form( 'initialize', $git ? __( 'Initialize & Push', 'igc-builder' ) : __( 'Connect & Push', 'igc-builder' ), 'primary' ); else : self::push_form(); endif; ?>
					</div>
				</section>
				<section class="igc-panel">
					<h2><?php esc_html_e( 'Git → files → WordPress', 'igc-builder' ); ?></h2>
					<p><?php esc_html_e( 'Pulls with fast-forward only, validates the complete workspace, creates a local backup and then imports the runtime code.', 'igc-builder' ); ?></p>
					<div class="igc-git-actions"><?php self::action_form( 'pull_import', __( 'Pull & Import', 'igc-builder' ), 'primary', ! $is_connected, __( 'Pull and import the latest GitHub runtime code into this site?', 'igc-builder' ) ); ?></div>
				</section>
			</div>

			<section class="igc-panel">
				<h2><?php esc_html_e( 'Workspace status', 'igc-builder' ); ?></h2>
				<dl class="igc-status">
					<div><dt><?php esc_html_e( 'Path', 'igc-builder' ); ?></dt><dd><code><?php echo esc_html( $workspace ); ?></code></dd></div>
					<div><dt><?php esc_html_e( 'Remote', 'igc-builder' ); ?></dt><dd><?php echo $settings['repository'] ? esc_html( $settings['repository'] ) : '—'; ?></dd></div>
					<div><dt><?php esc_html_e( 'Last sync', 'igc-builder' ); ?></dt><dd><?php echo esc_html( (string) get_option( 'igc_git_last_sync', '—' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Last commit', 'igc-builder' ); ?></dt><dd><?php echo $last_commit['code'] === 0 ? esc_html( trim( $last_commit['output'] ) ) : '—'; ?></dd></div>
				</dl>
				<pre class="igc-git-output"><?php echo esc_html( $status['output'] ?: __( 'No Git status available yet.', 'igc-builder' ) ); ?></pre>
			</section>

			<div class="igc-notice"><strong><?php esc_html_e( 'Safety boundary:', 'igc-builder' ); ?></strong> <?php esc_html_e( 'Pull & Import updates runtime code stored by Site Studio. Plugin source is exported for review but is never self-deployed by this screen.', 'igc-builder' ); ?></div>
		</div>
		<?php
	}

	private static function action_form( string $operation, string $label, string $class = 'secondary', bool $disabled = false, string $confirmation = '' ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="igc_git_action"><input type="hidden" name="igc_git_operation" value="<?php echo esc_attr( $operation ); ?>"><?php wp_nonce_field( 'igc_git_action', 'igc_git_nonce' ); ?>
			<button class="button button-<?php echo esc_attr( $class ); ?>" type="submit" <?php disabled( $disabled ); ?><?php echo $confirmation ? ' onclick="return confirm(' . esc_attr( wp_json_encode( $confirmation ) ) . ');"' : ''; ?>><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	private static function push_form(): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="igc-git-push-form igc-git-push-form--simple">
			<input type="hidden" name="action" value="igc_git_action"><input type="hidden" name="igc_git_operation" value="export_push"><?php wp_nonce_field( 'igc_git_action', 'igc_git_nonce' ); ?>
			<button class="button button-primary" type="submit"><?php esc_html_e( 'Push Site to GitHub', 'igc-builder' ); ?></button>
		</form>
		<?php
	}

	public static function handle_action(): void {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['igc_git_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igc_git_nonce'] ) ), 'igc_git_action' ) ) {
			wp_die( esc_html__( 'Git action was not authorised.', 'igc-builder' ) );
		}

		try {
			self::perform_operation( sanitize_key( wp_unslash( $_POST['igc_git_operation'] ?? '' ) ) );
		} catch ( Throwable $error ) {
			self::notice( false, __( 'The operation stopped safely.', 'igc-builder' ), $error->getMessage() );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
		exit;
	}

	public static function handle_quick_action(): void {
		$operation = sanitize_key( wp_unslash( $_GET['igc_git_operation'] ?? '' ) );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Git action was not authorised.', 'igc-builder' ) );
		}
		check_admin_referer( 'igc_git_quick_' . $operation );
		try {
			self::perform_operation( $operation );
		} catch ( Throwable $error ) {
			self::notice( false, __( 'The operation stopped safely.', 'igc-builder' ), $error->getMessage() );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
		exit;
	}

	private static function perform_operation( string $operation ): void {
		switch ( $operation ) {
			case 'save_settings':
				self::save_settings();
				self::notice( true, __( 'Git settings saved.', 'igc-builder' ) );
				break;
			case 'export':
				self::assert_clean_if_repository();
				$count = self::export_workspace();
				self::notice( true, sprintf( __( 'Exported %d code items.', 'igc-builder' ), $count ), self::workspace_dir() );
				break;
			case 'initialize':
				self::initialize_repository();
				break;
			case 'export_push':
				self::export_commit_push();
				break;
			case 'pull_import':
				self::pull_import();
				break;
			default:
				throw new RuntimeException( __( 'Unknown Git operation.', 'igc-builder' ) );
		}
	}

	private static function quick_action_url( string $operation ): string {
		return wp_nonce_url(
			add_query_arg(
				array( 'action' => 'igc_git_quick_action', 'igc_git_operation' => $operation ),
				admin_url( 'admin-post.php' )
			),
			'igc_git_quick_' . $operation
		);
	}

	private static function save_settings(): void {
		$repository = trim( sanitize_text_field( wp_unslash( $_POST['repository'] ?? '' ) ) );
		$branch = trim( sanitize_text_field( wp_unslash( $_POST['branch'] ?? 'main' ) ) );
		$author_name = trim( sanitize_text_field( wp_unslash( $_POST['author_name'] ?? '' ) ) );
		$author_email = sanitize_email( wp_unslash( $_POST['author_email'] ?? '' ) );
		if ( $repository && ! self::valid_repository( $repository ) ) {
			throw new RuntimeException( __( 'Use a valid HTTPS or SSH Git repository URL without embedded credentials.', 'igc-builder' ) );
		}
		if ( ! self::valid_branch( $branch ) ) {
			throw new RuntimeException( __( 'The branch name is not valid.', 'igc-builder' ) );
		}
		if ( ! $author_name || ! is_email( $author_email ) ) {
			throw new RuntimeException( __( 'A commit author name and valid email are required.', 'igc-builder' ) );
		}
		update_option( 'igc_git_settings', compact( 'repository', 'branch', 'author_name', 'author_email' ), false );
		if ( ! defined( 'IGC_GITHUB_TOKEN' ) ) {
			if ( ! empty( $_POST['forget_github_token'] ) ) {
				delete_option( 'igc_github_token_encrypted' );
			} else {
				$token = trim( sanitize_text_field( wp_unslash( $_POST['github_token'] ?? '' ) ) );
				if ( $token ) {
					if ( strlen( $token ) < 20 || preg_match( '/\s/', $token ) ) {
						throw new RuntimeException( __( 'The GitHub token format is not valid.', 'igc-builder' ) );
					}
					update_option( 'igc_github_token_encrypted', self::encrypt_secret( $token ), false );
				}
			}
		}
	}

	private static function settings(): array {
		return wp_parse_args(
			(array) get_option( 'igc_git_settings', array() ),
			array(
				'repository'   => '',
				'branch'       => 'main',
				'author_name'  => wp_get_current_user()->display_name ?: 'Site Studio',
				'author_email' => get_option( 'admin_email' ),
			)
		);
	}

	private static function valid_repository( string $repository ): bool {
		if ( preg_match( '/^git@[A-Za-z0-9.-]+:[A-Za-z0-9._\/-]+(?:\.git)?$/', $repository ) ) {
			return true;
		}
		if ( ! preg_match( '#^(?:https|ssh)://#i', $repository ) ) {
			return false;
		}
		$parts = wp_parse_url( $repository );
		return is_array( $parts ) && ! empty( $parts['host'] ) && empty( $parts['user'] ) && empty( $parts['pass'] );
	}

	private static function valid_branch( string $branch ): bool {
		return 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,99}$/', $branch )
			&& ! str_contains( $branch, '..' )
			&& ! str_contains( $branch, '@{' )
			&& ! str_ends_with( $branch, '/' )
			&& ! str_ends_with( $branch, '.lock' );
	}

	private static function github_ready(): bool {
		return (bool) self::github_token() && (bool) self::github_repository();
	}

	private static function is_connected(): bool {
		$git = self::git_binary();
		return $git ? is_dir( self::workspace_dir() . '/.git' ) : self::github_ready();
	}

	private static function github_repository(): string {
		$repository = (string) self::settings()['repository'];
		if ( preg_match( '#^https://github\.com/([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?)(?:\.git)?/?$#i', $repository, $matches ) ) {
			return $matches[1];
		}
		if ( preg_match( '#^git@github\.com:([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?)(?:\.git)?$#i', $repository, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	private static function github_token(): string {
		if ( defined( 'IGC_GITHUB_TOKEN' ) ) {
			return trim( (string) IGC_GITHUB_TOKEN );
		}
		$encrypted = (string) get_option( 'igc_github_token_encrypted', '' );
		return $encrypted ? self::decrypt_secret( $encrypted ) : '';
	}

	private static function secret_key(): string {
		$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' ) . ( defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : '' );
		return hash( 'sha256', $material ?: wp_salt( 'auth' ), true );
	}

	private static function encrypt_secret( string $secret ): string {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			throw new RuntimeException( __( 'The Sodium PHP extension is required to save a token. Define IGC_GITHUB_TOKEN in wp-config.php instead.', 'igc-builder' ) );
		}
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return base64_encode( $nonce . sodium_crypto_secretbox( $secret, $nonce, self::secret_key() ) );
	}

	private static function decrypt_secret( string $encrypted ): string {
		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return '';
		}
		$payload = base64_decode( $encrypted, true );
		if ( false === $payload || strlen( $payload ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}
		$nonce = substr( $payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain = sodium_crypto_secretbox_open( substr( $payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), $nonce, self::secret_key() );
		return false === $plain ? '' : $plain;
	}

	private static function initialize_repository(): void {
		$settings = self::settings();
		if ( ! $settings['repository'] ) {
			throw new RuntimeException( __( 'Save the private repository URL first.', 'igc-builder' ) );
		}
		if ( ! self::git_binary() ) {
			if ( ! self::github_ready() ) {
				throw new RuntimeException( __( 'Save a GitHub repository URL and fine-grained token first.', 'igc-builder' ) );
			}
			self::export_workspace();
			$commit = self::github_push( 'Initial Site Studio export' );
			update_option( 'igc_git_last_sync', current_time( 'mysql' ), false );
			self::notice( true, __( 'Connected and pushed through the GitHub API.', 'igc-builder' ), $commit );
			return;
		}
		if ( is_dir( self::workspace_dir() . '/.git' ) ) {
			throw new RuntimeException( __( 'The workspace is already a Git repository.', 'igc-builder' ) );
		}
		self::export_workspace();
		self::must_git( array( 'init', '-b', $settings['branch'] ), 20 );
		self::must_git( array( 'config', 'user.name', $settings['author_name'] ), 8 );
		self::must_git( array( 'config', 'user.email', $settings['author_email'] ), 8 );
		self::must_git( array( 'remote', 'add', 'origin', $settings['repository'] ), 8 );
		self::must_git( array( 'add', '--all' ), 20 );
		self::must_git( array( 'commit', '-m', 'Initial Site Studio export' ), 30 );
		self::must_git( array( 'push', '-u', 'origin', $settings['branch'] ), 60 );
		update_option( 'igc_git_last_sync', current_time( 'mysql' ), false );
		self::notice( true, __( 'Repository initialized and pushed.', 'igc-builder' ) );
	}

	private static function export_commit_push(): void {
		if ( ! self::git_binary() ) {
			if ( ! self::github_ready() ) {
				throw new RuntimeException( __( 'GitHub API mode is not configured.', 'igc-builder' ) );
			}
			self::export_workspace();
			$message = self::commit_message();
			$commit = self::github_push( $message );
			update_option( 'igc_git_last_sync', current_time( 'mysql' ), false );
			self::notice( true, __( 'Site Studio code exported and pushed through the GitHub API.', 'igc-builder' ), $commit );
			return;
		}
		if ( ! is_dir( self::workspace_dir() . '/.git' ) ) {
			throw new RuntimeException( __( 'Initialize the repository first.', 'igc-builder' ) );
		}
		self::assert_clean_if_repository();
		self::export_workspace();
		self::must_git( array( 'add', '--all' ), 20 );
		$diff = self::run_git( array( 'diff', '--cached', '--quiet' ), 10 );
		if ( 0 === $diff['code'] ) {
			self::notice( true, __( 'Everything is already in sync. Nothing was pushed.', 'igc-builder' ) );
			return;
		}
		$message = self::commit_message();
		self::must_git( array( 'commit', '-m', $message ), 30 );
		self::must_git( array( 'push', 'origin', self::settings()['branch'] ), 60 );
		update_option( 'igc_git_last_sync', current_time( 'mysql' ), false );
		self::notice( true, __( 'Site Studio code exported, committed and pushed.', 'igc-builder' ) );
	}

	private static function commit_message(): string {
		$message = trim( sanitize_text_field( wp_unslash( $_POST['commit_message'] ?? '' ) ) );
		if ( '' === $message ) {
			$message = sprintf( 'Site Studio sync - %s', wp_date( 'Y-m-d H:i T' ) );
		}
		return function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 120 ) : substr( $message, 0, 120 );
	}

	private static function pull_import(): void {
		if ( ! self::git_binary() ) {
			if ( ! self::github_ready() ) {
				throw new RuntimeException( __( 'GitHub API mode is not configured.', 'igc-builder' ) );
			}
			self::github_pull();
			self::backup_runtime();
			$count = self::import_workspace();
			update_option( 'igc_git_last_sync', current_time( 'mysql' ), false );
			do_action( 'igc_clear_cache' );
			wp_cache_flush();
			self::notice( true, sprintf( __( 'Downloaded from GitHub and imported %d code items.', 'igc-builder' ), $count ) );
			return;
		}
		if ( ! is_dir( self::workspace_dir() . '/.git' ) ) {
			throw new RuntimeException( __( 'Initialize the repository first.', 'igc-builder' ) );
		}
		self::assert_clean_if_repository();
		$settings = self::settings();
		self::must_git( array( 'pull', '--ff-only', 'origin', $settings['branch'] ), 60 );
		self::backup_runtime();
		$count = self::import_workspace();
		update_option( 'igc_git_last_sync', current_time( 'mysql' ), false );
		do_action( 'igc_clear_cache' );
		wp_cache_flush();
		self::notice( true, sprintf( __( 'Pulled and imported %d code items.', 'igc-builder' ), $count ) );
	}

	private static function assert_clean_if_repository(): void {
		if ( ! is_dir( self::workspace_dir() . '/.git' ) ) {
			return;
		}
		$result = self::must_git( array( 'status', '--porcelain' ), 10 );
		if ( trim( $result['output'] ) ) {
			throw new RuntimeException( __( 'The repository has uncommitted file changes. Commit or resolve them before syncing.', 'igc-builder' ) . "\n\n" . $result['output'] );
		}
	}

	public static function export_workspace(): int {
		$workspace = self::ensure_workspace();
		$tmp_runtime = $workspace . '/.runtime-export-' . wp_generate_password( 8, false, false );
		$tmp_plugin = $workspace . '/.plugin-export-' . wp_generate_password( 8, false, false );
		wp_mkdir_p( $tmp_runtime );
		wp_mkdir_p( $tmp_plugin . '/igc-builder' );
		$count = self::export_runtime_to( $tmp_runtime );
		self::copy_directory( IGC_BUILDER_DIR, $tmp_plugin . '/igc-builder' );
		self::replace_directory( $tmp_runtime, $workspace . '/runtime' );
		self::replace_directory( $tmp_plugin, $workspace . '/plugin' );
		self::write_file( $workspace . '/README.md', self::repository_readme() );
		self::write_file( $workspace . '/.gitignore', ".DS_Store\n*.log\n.env\n.env.*\n" );
		self::write_file( $workspace . '/site-studio.json', wp_json_encode( array( 'schema' => 1, 'plugin_version' => IGC_BUILDER_VERSION, 'site_url' => home_url( '/' ), 'exported_at_gmt' => gmdate( DATE_ATOM ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
		return $count;
	}

	private static function export_runtime_to( string $runtime ): int {
		$count = 0;
		foreach ( self::TYPES as $post_type => $directory ) {
			$posts = get_posts( array( 'post_type' => $post_type, 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
			foreach ( $posts as $post ) {
				$item_dir = $runtime . '/' . $directory . '/' . self::item_directory( $post );
				wp_mkdir_p( $item_dir );
				$properties = array();
				if ( 'igc_canvas' === $post_type ) {
					$properties = array( 'target_page_id' => absint( get_post_meta( $post->ID, '_igc_target_page_id', true ) ), 'route_active' => (bool) get_post_meta( $post->ID, '_igc_route_active', true ) );
				} elseif ( 'igc_theme_template' === $post_type ) {
					$properties = array( 'location' => (string) get_post_meta( $post->ID, '_igc_location', true ), 'include' => (string) get_post_meta( $post->ID, '_igc_include', true ), 'priority' => (int) get_post_meta( $post->ID, '_igc_priority', true ) );
				} elseif ( 'igc_php_snippet' === $post_type ) {
					$properties = array( 'scope' => (string) get_post_meta( $post->ID, '_igc_scope', true ) ?: 'everywhere', 'enabled' => (bool) get_post_meta( $post->ID, '_igc_enabled', true ) );
				}
				$meta = array( 'schema' => 1, 'id' => $post->ID, 'post_type' => $post_type, 'title' => $post->post_title, 'slug' => $post->post_name, 'status' => $post->post_status, 'menu_order' => (int) $post->menu_order, 'properties' => $properties );
				self::write_file( $item_dir . '/meta.json', wp_json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
				if ( 'igc_php_snippet' === $post_type ) {
					self::write_file( $item_dir . '/snippet.php', "<?php\n" . ltrim( (string) get_post_meta( $post->ID, '_igc_php', true ) ) . "\n" );
				} else {
					self::write_file( $item_dir . '/index.html', (string) get_post_meta( $post->ID, '_igc_html', true ) );
					self::write_file( $item_dir . '/style.css', (string) get_post_meta( $post->ID, '_igc_css', true ) );
					self::write_file( $item_dir . '/script.js', (string) get_post_meta( $post->ID, '_igc_js', true ) );
				}
				$count++;
			}
		}

		$global = $runtime . '/global';
		wp_mkdir_p( $global );
		self::write_file( $global . '/styles.css', (string) get_option( 'igc_global_css', '' ) );
		self::write_file( $global . '/scripts.js', (string) get_option( 'igc_global_js', '' ) );
		self::write_file( $global . '/design-tokens.json', wp_json_encode( get_option( 'igc_design_tokens', IGC_Admin::default_tokens() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
		self::write_file( $global . '/settings.json', wp_json_encode( array( 'external_stylesheet' => (string) get_option( 'igc_external_stylesheet', '' ), 'external_scripts' => array_values( (array) get_option( 'igc_external_scripts', array() ) ), 'bundled_lenis' => (bool) get_option( 'igc_bundled_lenis', false ), 'site_mode' => (bool) get_option( 'igc_site_mode', false ), 'remove_emoji' => (bool) get_option( 'igc_remove_emoji', false ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
		return $count;
	}

	private static function import_workspace(): int {
		$runtime = self::workspace_dir() . '/runtime';
		if ( ! is_dir( $runtime ) ) {
			throw new RuntimeException( __( 'The repository does not contain a runtime directory.', 'igc-builder' ) );
		}
		$records = array();
		foreach ( self::TYPES as $post_type => $directory ) {
			foreach ( glob( $runtime . '/' . $directory . '/*/meta.json' ) ?: array() as $meta_file ) {
				$meta = self::read_json( $meta_file );
				if ( ( $meta['post_type'] ?? '' ) !== $post_type || empty( $meta['title'] ) || empty( $meta['slug'] ) ) {
					throw new RuntimeException( sprintf( __( 'Invalid metadata file: %s', 'igc-builder' ), $meta_file ) );
				}
				$dir = dirname( $meta_file );
				$record = array( 'meta' => $meta, 'post_type' => $post_type );
				if ( 'igc_php_snippet' === $post_type ) {
					$php = self::read_file( $dir . '/snippet.php' );
					$php = preg_replace( '/^\s*<\?php\s*/', '', $php, 1 );
					$validation = IGC_Snippet_Validator::validate( $php );
					if ( is_wp_error( $validation ) ) {
						throw new RuntimeException( $meta['title'] . ': ' . $validation->get_error_message() );
					}
					$record['php'] = $php;
				} else {
					$record['html'] = self::read_file( $dir . '/index.html' );
					$record['css'] = self::read_file( $dir . '/style.css' );
					$record['js'] = self::read_file( $dir . '/script.js' );
				}
				$records[] = $record;
			}
		}

		$tokens = self::read_json( $runtime . '/global/design-tokens.json' );
		$global_settings = self::read_json( $runtime . '/global/settings.json' );
		$global_css = self::read_file( $runtime . '/global/styles.css' );
		$global_js = self::read_file( $runtime . '/global/scripts.js' );

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		try {
			foreach ( $records as $record ) {
				self::import_record( $record );
			}
			update_option( 'igc_design_tokens', $tokens, false );
			update_option( 'igc_global_css', $global_css, false );
			update_option( 'igc_global_js', $global_js, false );
			update_option( 'igc_external_stylesheet', esc_url_raw( $global_settings['external_stylesheet'] ?? '' ), false );
			update_option( 'igc_external_scripts', array_values( array_filter( array_map( 'esc_url_raw', (array) ( $global_settings['external_scripts'] ?? array() ) ) ) ), false );
			update_option( 'igc_bundled_lenis', ! empty( $global_settings['bundled_lenis'] ) ? 1 : 0, false );
			update_option( 'igc_site_mode', ! empty( $global_settings['site_mode'] ) ? 1 : 0, false );
			update_option( 'igc_remove_emoji', ! empty( $global_settings['remove_emoji'] ) ? 1 : 0, false );
			$wpdb->query( 'COMMIT' );
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			throw $error;
		}
		return count( $records );
	}

	private static function import_record( array $record ): void {
		$meta = $record['meta'];
		$post_type = $record['post_type'];
		$post_id = absint( $meta['id'] ?? 0 );
		$post = $post_id ? get_post( $post_id ) : null;
		if ( ! $post || $post->post_type !== $post_type ) {
			$post = get_page_by_path( sanitize_title( $meta['slug'] ), OBJECT, $post_type );
			$post_id = $post ? $post->ID : 0;
		}
		$status = in_array( $meta['status'] ?? '', array( 'publish', 'draft', 'private' ), true ) ? $meta['status'] : 'draft';
		$post_data = array( 'post_type' => $post_type, 'post_title' => sanitize_text_field( $meta['title'] ), 'post_name' => sanitize_title( $meta['slug'] ), 'post_status' => $status, 'menu_order' => (int) ( $meta['menu_order'] ?? 0 ) );
		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			$result = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$result = wp_insert_post( wp_slash( $post_data ), true );
		}
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( $result->get_error_message() );
		}
		$post_id = (int) $result;
		$properties = (array) ( $meta['properties'] ?? array() );
		if ( 'igc_php_snippet' === $post_type ) {
			update_post_meta( $post_id, '_igc_php', $record['php'] );
			update_post_meta( $post_id, '_igc_scope', in_array( $properties['scope'] ?? '', array( 'everywhere', 'frontend', 'admin' ), true ) ? $properties['scope'] : 'everywhere' );
			update_post_meta( $post_id, '_igc_enabled', ! empty( $properties['enabled'] ) ? 1 : 0 );
			update_post_meta( $post_id, '_igc_validation_status', 'passed' );
			update_post_meta( $post_id, '_igc_last_error', '' );
			update_post_meta( $post_id, '_igc_last_validated', current_time( 'mysql', true ) );
		} else {
			update_post_meta( $post_id, '_igc_html', $record['html'] );
			update_post_meta( $post_id, '_igc_css', $record['css'] );
			update_post_meta( $post_id, '_igc_js', $record['js'] );
		}
		if ( 'igc_canvas' === $post_type ) {
			update_post_meta( $post_id, '_igc_target_page_id', absint( $properties['target_page_id'] ?? 0 ) );
			update_post_meta( $post_id, '_igc_route_active', ! empty( $properties['route_active'] ) ? '1' : '0' );
		} elseif ( 'igc_theme_template' === $post_type ) {
			update_post_meta( $post_id, '_igc_location', sanitize_key( $properties['location'] ?? '' ) );
			update_post_meta( $post_id, '_igc_include', sanitize_text_field( $properties['include'] ?? '' ) );
			update_post_meta( $post_id, '_igc_priority', (int) ( $properties['priority'] ?? 0 ) );
		}
		wp_save_post_revision( $post_id );
	}

	private static function backup_runtime(): void {
		$backup_root = dirname( self::workspace_dir() ) . '/site-studio-backups';
		wp_mkdir_p( $backup_root );
		$destination = $backup_root . '/runtime-' . gmdate( 'Ymd-His' );
		wp_mkdir_p( $destination );
		self::export_runtime_to( $destination );
		$backups = glob( $backup_root . '/runtime-*', GLOB_ONLYDIR ) ?: array();
		rsort( $backups, SORT_STRING );
		foreach ( array_slice( $backups, 5 ) as $old ) {
			self::remove_directory( $old );
		}
	}

	private static function ensure_workspace(): string {
		$workspace = self::workspace_dir();
		if ( ! is_dir( $workspace ) && ! wp_mkdir_p( $workspace ) ) {
			throw new RuntimeException( sprintf( __( 'Could not create the workspace at %s. Define IGC_GIT_WORKSPACE_DIR as a writable directory outside the public web root.', 'igc-builder' ), $workspace ) );
		}
		self::write_file( $workspace . '/index.php', "<?php\nhttp_response_code( 404 );\nexit;\n" );
		self::write_file( $workspace . '/.htaccess', "Require all denied\nDeny from all\n" );
		return $workspace;
	}

	private static function workspace_dir(): string {
		if ( defined( 'IGC_GIT_WORKSPACE_DIR' ) ) {
			return untrailingslashit( wp_normalize_path( (string) IGC_GIT_WORKSPACE_DIR ) );
		}
		$preferred = trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'site-studio-workspace';
		$preferred_parent = dirname( $preferred );
		if ( ( is_dir( $preferred ) && is_writable( $preferred ) ) || ( ! is_dir( $preferred ) && is_writable( $preferred_parent ) ) ) {
			return untrailingslashit( wp_normalize_path( $preferred ) );
		}
		$salt = defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : wp_salt( 'auth' );
		$fallback = trailingslashit( get_temp_dir() ) . '.site-studio-workspace-' . substr( hash( 'sha256', home_url( '/' ) . $salt ), 0, 12 );
		return untrailingslashit( wp_normalize_path( $fallback ) );
	}

	private static function item_directory( WP_Post $post ): string {
		$slug = sanitize_title( $post->post_name ?: $post->post_title ) ?: 'item';
		return $slug . '--' . $post->ID;
	}

	private static function write_file( string $path, string $contents ): void {
		$directory = dirname( $path );
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			throw new RuntimeException( sprintf( __( 'Could not create directory: %s', 'igc-builder' ), $directory ) );
		}
		$tmp = $path . '.tmp-' . wp_generate_password( 6, false, false );
		if ( false === file_put_contents( $tmp, $contents, LOCK_EX ) || ! rename( $tmp, $path ) ) {
			throw new RuntimeException( sprintf( __( 'Could not write file: %s', 'igc-builder' ), $path ) );
		}
	}

	private static function read_file( string $path ): string {
		if ( ! is_file( $path ) || filesize( $path ) > 5 * MB_IN_BYTES ) {
			throw new RuntimeException( sprintf( __( 'Missing or oversized workspace file: %s', 'igc-builder' ), $path ) );
		}
		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			throw new RuntimeException( sprintf( __( 'Could not read file: %s', 'igc-builder' ), $path ) );
		}
		return $contents;
	}

	private static function read_json( string $path ): array {
		$data = json_decode( self::read_file( $path ), true );
		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			throw new RuntimeException( sprintf( __( 'Invalid JSON file: %s', 'igc-builder' ), $path ) );
		}
		return $data;
	}

	private static function replace_directory( string $source, string $target ): void {
		$old = $target . '.old-' . wp_generate_password( 6, false, false );
		if ( is_dir( $target ) && ! rename( $target, $old ) ) {
			throw new RuntimeException( sprintf( __( 'Could not replace directory: %s', 'igc-builder' ), $target ) );
		}
		if ( ! rename( $source, $target ) ) {
			if ( is_dir( $old ) ) {
				rename( $old, $target );
			}
			throw new RuntimeException( sprintf( __( 'Could not activate exported directory: %s', 'igc-builder' ), $target ) );
		}
		if ( is_dir( $old ) ) {
			self::remove_directory( $old );
		}
	}

	private static function copy_directory( string $source, string $destination ): void {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $iterator as $item ) {
			$relative = ltrim( str_replace( wp_normalize_path( $source ), '', wp_normalize_path( $item->getPathname() ) ), '/' );
			if ( str_starts_with( $relative, '.git/' ) || $item->isLink() ) {
				continue;
			}
			$target = $destination . '/' . $relative;
			if ( $item->isDir() ) {
				wp_mkdir_p( $target );
			} elseif ( ! copy( $item->getPathname(), $target ) ) {
				throw new RuntimeException( sprintf( __( 'Could not copy plugin file: %s', 'igc-builder' ), $relative ) );
			}
		}
	}

	private static function remove_directory( string $directory ): void {
		if ( ! is_dir( $directory ) ) {
			return;
		}
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		rmdir( $directory );
	}

	private static function code_item_count(): int {
		$total = 0;
		foreach ( array_keys( self::TYPES ) as $type ) {
			$count = wp_count_posts( $type );
			foreach ( array( 'publish', 'draft', 'private' ) as $status ) {
				$total += isset( $count->{$status} ) ? (int) $count->{$status} : 0;
			}
		}
		return $total;
	}

	private static function github_push( string $message ): string {
		$repository = self::github_repository();
		$branch = (string) self::settings()['branch'];
		$head = self::github_head();
		$remote = self::github_tree( $head );
		$local = self::workspace_files();
		$additions = array();
		foreach ( $local as $path => $contents ) {
			$blob_sha = sha1( 'blob ' . strlen( $contents ) . "\0" . $contents );
			if ( ( $remote[ $path ] ?? '' ) !== $blob_sha ) {
				$additions[ $path ] = $contents;
			}
		}
		$deletions = array();
		foreach ( array_keys( $remote ) as $path ) {
			$managed = str_starts_with( $path, 'runtime/' ) || str_starts_with( $path, 'plugin/igc-builder/' ) || in_array( $path, array( 'site-studio.json', 'README.md', '.gitignore' ), true );
			if ( $managed && ! array_key_exists( $path, $local ) ) {
				$deletions[] = array( 'path' => $path );
			}
		}
		if ( ! $additions && ! $deletions ) {
			return __( 'Everything is already in sync. No GitHub commit was needed.', 'igc-builder' );
		}

		$addition_chunks = array_chunk( $additions, 60, true );
		$deletion_chunks = array_chunk( $deletions, 40 );
		$chunk_count = max( 1, count( $addition_chunks ), count( $deletion_chunks ) );
		$commit_url = '';
		for ( $index = 0; $index < $chunk_count; $index++ ) {
			$chunk = $addition_chunks[ $index ] ?? array();
			$files = array();
			foreach ( $chunk as $path => $contents ) {
				$files[] = array( 'path' => $path, 'contents' => base64_encode( $contents ) );
			}
			$file_changes = array();
			if ( $files ) {
				$file_changes['additions'] = $files;
			}
			if ( ! empty( $deletion_chunks[ $index ] ) ) {
				$file_changes['deletions'] = $deletion_chunks[ $index ];
			}
			$headline = $chunk_count > 1 ? sprintf( '%s (%d/%d)', $message, $index + 1, $chunk_count ) : $message;
			$query = 'mutation CreateSiteStudioCommit($input: CreateCommitOnBranchInput!) { createCommitOnBranch(input: $input) { commit { oid url } } }';
			$data = self::github_graphql(
				$query,
				array(
					'input' => array(
						'branch'          => array( 'repositoryNameWithOwner' => $repository, 'branchName' => $branch ),
						'message'         => array( 'headline' => $headline ),
						'expectedHeadOid' => $head,
						'fileChanges'     => $file_changes,
					),
				)
			);
			$commit = $data['data']['createCommitOnBranch']['commit'] ?? array();
			if ( empty( $commit['oid'] ) ) {
				throw new RuntimeException( __( 'GitHub did not return the new commit ID.', 'igc-builder' ) );
			}
			$head = (string) $commit['oid'];
			$commit_url = (string) ( $commit['url'] ?? '' );
		}
		update_option( 'igc_github_last_commit', substr( $head, 0, 12 ) . ( $commit_url ? ' · ' . $commit_url : '' ), false );
		return $commit_url ?: substr( $head, 0, 12 );
	}

	/**
	 * Bring up the WordPress filesystem API before unzipping.
	 *
	 * unzip_file() starts by checking $wp_filesystem and returns
	 * "Could not access filesystem." if nobody has initialised it. WordPress does
	 * that for you on its own install and update screens; on ours nothing does,
	 * so the pull failed with an error that reads like a server permissions
	 * problem when it is simply a missing call.
	 *
	 * WP_Filesystem() picks the direct method whenever the web user owns the
	 * files, which is the normal case. When it cannot, it is asking for FTP
	 * credentials, and the honest fix is FS_METHOD in wp-config.php rather than
	 * anything this plugin can do.
	 */
	private static function require_filesystem(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			return;
		}

		if ( ! WP_Filesystem() ) {
			throw new RuntimeException(
				__( 'WordPress could not get write access to the filesystem. Add: define( "FS_METHOD", "direct" ); to wp-config.php, or check that the web server user owns wp-content.', 'igc-builder' )
			);
		}
	}

	private static function github_pull(): void {
		$repository = self::github_repository();
		$branch = (string) self::settings()['branch'];
		$zip = wp_tempnam( 'site-studio-github.zip' );
		if ( ! $zip ) {
			throw new RuntimeException( __( 'Could not create a temporary download file.', 'igc-builder' ) );
		}
		$response = wp_remote_get(
			'https://api.github.com/repos/' . $repository . '/zipball/' . rawurlencode( $branch ),
			array(
				'timeout'     => 60,
				'redirection' => 5,
				'stream'      => true,
				'filename'    => $zip,
				'headers'     => self::github_headers(),
			)
		);
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) < 200 || wp_remote_retrieve_response_code( $response ) >= 300 ) {
			@unlink( $zip );
			throw new RuntimeException( is_wp_error( $response ) ? $response->get_error_message() : __( 'GitHub archive download failed.', 'igc-builder' ) );
		}
		self::require_filesystem();
		$extract = dirname( self::workspace_dir() ) . '/.site-studio-pull-' . wp_generate_password( 8, false, false );
		wp_mkdir_p( $extract );
		$unzipped = unzip_file( $zip, $extract );
		@unlink( $zip );
		if ( is_wp_error( $unzipped ) ) {
			self::remove_directory( $extract );
			throw new RuntimeException( $unzipped->get_error_message() );
		}
		$roots = glob( $extract . '/*', GLOB_ONLYDIR ) ?: array();
		$root = $roots[0] ?? '';
		if ( ! $root || ! is_dir( $root . '/runtime' ) ) {
			self::remove_directory( $extract );
			throw new RuntimeException( __( 'The GitHub repository does not contain a Site Studio runtime directory.', 'igc-builder' ) );
		}
		$workspace = self::ensure_workspace();
		self::replace_directory( $root . '/runtime', $workspace . '/runtime' );
		if ( is_dir( $root . '/plugin' ) ) {
			self::replace_directory( $root . '/plugin', $workspace . '/plugin' );
		}
		foreach ( array( 'README.md', '.gitignore', 'site-studio.json' ) as $file ) {
			if ( is_file( $root . '/' . $file ) ) {
				self::write_file( $workspace . '/' . $file, self::read_file( $root . '/' . $file ) );
			}
		}
		self::remove_directory( $extract );
		$head = self::github_head();
		update_option( 'igc_github_last_commit', substr( $head, 0, 12 ), false );
	}

	private static function workspace_files(): array {
		$workspace = self::ensure_workspace();
		$files = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $workspace, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $item ) {
			if ( ! $item->isFile() || $item->isLink() ) {
				continue;
			}
			$relative = ltrim( str_replace( wp_normalize_path( $workspace ), '', wp_normalize_path( $item->getPathname() ) ), '/' );
			if ( str_starts_with( $relative, '.git/' ) || str_starts_with( $relative, '.runtime-export-' ) || str_starts_with( $relative, '.plugin-export-' ) || in_array( $relative, array( 'index.php', '.htaccess' ), true ) ) {
				continue;
			}
			$files[ $relative ] = self::read_file( $item->getPathname() );
		}
		ksort( $files, SORT_STRING );
		return $files;
	}

	private static function github_head(): string {
		$data = self::github_api( 'GET', '/repos/' . self::github_repository() . '/git/ref/heads/' . rawurlencode( (string) self::settings()['branch'] ) );
		$head = (string) ( $data['object']['sha'] ?? '' );
		if ( ! $head ) {
			throw new RuntimeException( __( 'The configured GitHub branch does not exist. Initialize the repository with a README first.', 'igc-builder' ) );
		}
		return $head;
	}

	private static function github_tree( string $commit_sha ): array {
		$commit = self::github_api( 'GET', '/repos/' . self::github_repository() . '/git/commits/' . rawurlencode( $commit_sha ) );
		$tree_sha = (string) ( $commit['tree']['sha'] ?? '' );
		if ( ! $tree_sha ) {
			throw new RuntimeException( __( 'Could not read the GitHub commit tree.', 'igc-builder' ) );
		}
		$tree = self::github_api( 'GET', '/repos/' . self::github_repository() . '/git/trees/' . rawurlencode( $tree_sha ) . '?recursive=1' );
		$files = array();
		foreach ( (array) ( $tree['tree'] ?? array() ) as $entry ) {
			if ( 'blob' === ( $entry['type'] ?? '' ) && ! empty( $entry['path'] ) && ! empty( $entry['sha'] ) ) {
				$files[ (string) $entry['path'] ] = (string) $entry['sha'];
			}
		}
		return $files;
	}

	private static function github_graphql( string $query, array $variables ): array {
		$response = wp_remote_post(
			'https://api.github.com/graphql',
			array( 'timeout' => 60, 'headers' => self::github_headers(), 'body' => wp_json_encode( compact( 'query', 'variables' ) ) )
		);
		return self::decode_github_response( $response );
	}

	private static function github_api( string $method, string $path, ?array $body = null ): array {
		$args = array( 'method' => $method, 'timeout' => 60, 'headers' => self::github_headers() );
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		return self::decode_github_response( wp_remote_request( 'https://api.github.com' . $path, $args ) );
	}

	private static function github_headers(): array {
		return array(
			'Authorization'        => 'Bearer ' . self::github_token(),
			'Accept'               => 'application/vnd.github+json',
			'Content-Type'         => 'application/json',
			'X-GitHub-Api-Version' => '2022-11-28',
			'User-Agent'           => 'Site-Studio/' . IGC_BUILDER_VERSION . '; ' . home_url( '/' ),
		);
	}

	private static function decode_github_response( array|WP_Error $response ): array {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) || ! empty( $data['errors'] ) ) {
			$message = (string) ( $data['message'] ?? ( $data['errors'][0]['message'] ?? __( 'Unknown GitHub API error.', 'igc-builder' ) ) );
			throw new RuntimeException( sprintf( 'GitHub API %d: %s', $code, $message ) );
		}
		return $data;
	}

	private static function git_binary(): string {
		if ( defined( 'IGC_GIT_BINARY' ) && is_executable( (string) IGC_GIT_BINARY ) ) {
			return (string) IGC_GIT_BINARY;
		}
		foreach ( array( '/usr/bin/git', '/usr/local/bin/git', '/opt/homebrew/bin/git' ) as $binary ) {
			if ( is_executable( $binary ) ) {
				return $binary;
			}
		}
		return '';
	}

	private static function run_git( array $arguments, int $timeout = 30 ): array {
		$binary = self::git_binary();
		if ( ! $binary || ! function_exists( 'proc_open' ) ) {
			return array( 'code' => 127, 'output' => __( 'Git or proc_open is unavailable.', 'igc-builder' ) );
		}
		$command = array_merge( array( $binary ), array_values( $arguments ) );
		$descriptors = array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) );
		$environment = getenv();
		$environment = is_array( $environment ) ? $environment : array();
		$environment['GIT_TERMINAL_PROMPT'] = '0';
		$process = proc_open( $command, $descriptors, $pipes, self::ensure_workspace(), $environment );
		if ( ! is_resource( $process ) ) {
			return array( 'code' => 126, 'output' => __( 'Could not start Git.', 'igc-builder' ) );
		}
		fclose( $pipes[0] );
		stream_set_blocking( $pipes[1], false );
		stream_set_blocking( $pipes[2], false );
		$output = '';
		$started = microtime( true );
		$exit_code = null;
		$timed_out = false;
		do {
			$output .= stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
			$status = proc_get_status( $process );
			if ( ! $status['running'] ) {
				$exit_code = (int) $status['exitcode'];
				break;
			}
			if ( microtime( true ) - $started > $timeout ) {
				proc_terminate( $process );
				$output .= "\n" . __( 'Git command timed out.', 'igc-builder' );
				$timed_out = true;
				break;
			}
			usleep( 50000 );
		} while ( true );
		$output .= stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		$closed_code = proc_close( $process );
		$code = $timed_out ? 124 : ( null !== $exit_code && $exit_code >= 0 ? $exit_code : $closed_code );
		$output = function_exists( 'mb_substr' ) ? mb_substr( $output, 0, 20000 ) : substr( $output, 0, 20000 );
		return array( 'code' => $code, 'output' => trim( $output ) );
	}

	private static function must_git( array $arguments, int $timeout = 30 ): array {
		$result = self::run_git( $arguments, $timeout );
		if ( 0 !== $result['code'] ) {
			throw new RuntimeException( sprintf( "git %s\n\n%s", implode( ' ', $arguments ), $result['output'] ) );
		}
		return $result;
	}

	private static function notice( bool $ok, string $title, string $message = '' ): void {
		set_transient( self::NOTICE_PREFIX . get_current_user_id(), compact( 'ok', 'title', 'message' ), 120 );
	}

	private static function repository_readme(): string {
		$template = IGC_BUILDER_DIR . 'templates/repository-readme.md';
		$readme   = is_readable( $template ) ? file_get_contents( $template ) : false;
		if ( false === $readme ) {
			return "# Site Studio workspace\n\nEdit `runtime/`, commit and push, then use Site Sync → Pull GitHub → Site in WordPress. Do not edit `plugin/igc-builder/` expecting it to deploy automatically.\n";
		}
		return str_replace(
			array( '{{SITE_URL}}', '{{PLUGIN_VERSION}}' ),
			array( home_url( '/' ), IGC_BUILDER_VERSION ),
			$readme
		);
	}
}
