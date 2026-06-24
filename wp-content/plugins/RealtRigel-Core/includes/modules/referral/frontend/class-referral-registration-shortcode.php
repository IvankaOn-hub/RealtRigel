<?php
/**
 * Frontend partner registration shortcode.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Registration_Shortcode {

	/**
	 * Registration rate limit transient prefix.
	 */
	private const RATE_LIMIT_PREFIX = 'rr_partner_register_limit_';

	/**
	 * Login rate limit transient prefix.
	 */
	private const LOGIN_RATE_LIMIT_PREFIX = 'rr_partner_login_limit_';

	/**
	 * Password reset rate limit transient prefix.
	 */
	private const RESET_RATE_LIMIT_PREFIX = 'rr_partner_reset_limit_';

	/**
	 * Partner service.
	 *
	 * @var RR_Referral_Partner_Service
	 */
	private RR_Referral_Partner_Service $partner_service;

	/**
	 * Last submission errors.
	 *
	 * @var string[]
	 */
	private array $errors = array();

	/**
	 * Sticky form values.
	 *
	 * @var array<string, string>
	 */
	private array $values = array();

	/**
	 * Login errors.
	 *
	 * @var string[]
	 */
	private array $login_errors = array();

	/**
	 * Sticky login values.
	 *
	 * @var array<string, string>
	 */
	private array $login_values = array();

	/**
	 * Password reset errors.
	 *
	 * @var string[]
	 */
	private array $reset_errors = array();

	/**
	 * Sticky password reset values.
	 *
	 * @var array<string, string>
	 */
	private array $reset_values = array();

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Partner_Service $partner_service Partner service.
	 */
	public function __construct( RR_Referral_Partner_Service $partner_service ) {
		$this->partner_service = $partner_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'handle_submission' ) );
		add_shortcode( 'rr_partner_register', array( $this, 'render' ) );
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	public function handle_submission(): void {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		if ( isset( $_POST['rr_partner_register_action'] ) && '1' === (string) wp_unslash( $_POST['rr_partner_register_action'] ) ) {
			$this->handle_registration_submission();
		}

		if ( isset( $_POST['rr_partner_login_action'] ) && '1' === (string) wp_unslash( $_POST['rr_partner_login_action'] ) ) {
			$this->handle_login_submission();
		}

		if ( isset( $_POST['rr_partner_reset_request_action'] ) && '1' === (string) wp_unslash( $_POST['rr_partner_reset_request_action'] ) ) {
			$this->handle_reset_request_submission();
		}

		if ( isset( $_POST['rr_partner_reset_password_action'] ) && '1' === (string) wp_unslash( $_POST['rr_partner_reset_password_action'] ) ) {
			$this->handle_reset_password_submission();
		}
	}

	/**
	 * Handle partner registration submission.
	 *
	 * @return void
	 */
	private function handle_registration_submission(): void {
		if ( is_user_logged_in() ) {
			$this->errors[] = __( 'Вы уже авторизованы.', 'realtrigel-core' );
			return;
		}

		$this->values = array(
			'name'  => isset( $_POST['rr_partner_name'] ) ? sanitize_text_field( wp_unslash( $_POST['rr_partner_name'] ) ) : '',
			'email' => isset( $_POST['rr_partner_email'] ) ? sanitize_email( wp_unslash( $_POST['rr_partner_email'] ) ) : '',
			'phone' => isset( $_POST['rr_partner_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['rr_partner_phone'] ) ) : '',
		);

		if ( ! isset( $_POST['rr_partner_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rr_partner_register_nonce'] ) ), 'rr_partner_register' ) ) {
			$this->errors[] = __( 'Ошибка проверки безопасности. Попробуйте еще раз.', 'realtrigel-core' );
			return;
		}

		if ( $this->is_honeypot_filled() ) {
			$this->errors[] = __( 'Не удалось завершить регистрацию.', 'realtrigel-core' );
			return;
		}

		if ( $this->is_rate_limited() ) {
			$this->errors[] = __( 'Слишком много попыток регистрации. Попробуйте снова через несколько минут.', 'realtrigel-core' );
			return;
		}

		$this->bump_rate_limit();

		$result = $this->partner_service->register_partner_account(
			array(
				'name'     => $this->values['name'] ?? '',
				'email'    => $this->values['email'] ?? '',
				'phone'    => $this->values['phone'] ?? '',
				'password' => isset( $_POST['rr_partner_password'] ) ? (string) wp_unslash( $_POST['rr_partner_password'] ) : '',
				'consent'  => isset( $_POST['rr_partner_consent'] ) ? '1' === (string) wp_unslash( $_POST['rr_partner_consent'] ) : false,
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->errors[] = $result->get_error_message();
			return;
		}

		$user_id = (int) $result;
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'rr_partner_registered' => '1',
				),
				$redirect_to
			)
		);
		exit;
	}

	/**
	 * Handle partner login submission.
	 *
	 * @return void
	 */
	private function handle_login_submission(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		$this->login_values = array(
			'login' => isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '',
		);

		if ( ! isset( $_POST['rr_partner_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rr_partner_login_nonce'] ) ), 'rr_partner_login' ) ) {
			$this->login_errors[] = __( 'Ошибка проверки безопасности. Попробуйте еще раз.', 'realtrigel-core' );
			return;
		}

		if ( $this->is_login_rate_limited() ) {
			$this->login_errors[] = __( 'Слишком много попыток входа. Попробуйте снова через несколько минут.', 'realtrigel-core' );
			return;
		}

		$this->bump_login_rate_limit();

		$creds = array(
			'user_login'    => $this->login_values['login'] ?? '',
			'user_password' => isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '',
			'remember'      => true,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			$this->login_errors[] = $user->get_error_message();
			return;
		}

		wp_set_current_user( (int) $user->ID );

		$redirect_to = $this->get_dashboard_url();

		if ( '' === $redirect_to ) {
			$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Handle partner password reset email request.
	 *
	 * @return void
	 */
	private function handle_reset_request_submission(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		$this->reset_values = array(
			'email' => isset( $_POST['rr_partner_reset_email'] ) ? sanitize_email( wp_unslash( $_POST['rr_partner_reset_email'] ) ) : '',
		);

		if ( ! isset( $_POST['rr_partner_reset_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rr_partner_reset_request_nonce'] ) ), 'rr_partner_reset_request' ) ) {
			$this->reset_errors[] = __( 'Invalid key.', 'default' );
			return;
		}

		if ( $this->is_reset_rate_limited() ) {
			$this->reset_errors[] = __( 'Please wait before trying again.', 'default' );
			return;
		}

		$this->bump_reset_rate_limit();

		$email       = $this->reset_values['email'] ?? '';
		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

		if ( is_email( $email ) ) {
			$user = get_user_by( 'email', $email );

			if ( $user instanceof WP_User && in_array( RR_Referral_Role_Service::ROLE, (array) $user->roles, true ) ) {
				$this->send_password_reset_email( $user, $redirect_to );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'rr_partner_tab'             => 'forgot',
					'rr_partner_reset_requested' => '1',
				),
				$redirect_to
			)
		);
		exit;
	}

	/**
	 * Handle partner password reset submission.
	 *
	 * @return void
	 */
	private function handle_reset_password_submission(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( ! isset( $_POST['rr_partner_reset_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rr_partner_reset_password_nonce'] ) ), 'rr_partner_reset_password' ) ) {
			$this->reset_errors[] = __( 'Invalid key.', 'default' );
			return;
		}

		$login    = isset( $_POST['rr_partner_reset_login'] ) ? sanitize_text_field( wp_unslash( $_POST['rr_partner_reset_login'] ) ) : '';
		$key      = isset( $_POST['rr_partner_reset_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rr_partner_reset_key'] ) ) : '';
		$password = isset( $_POST['rr_partner_new_password'] ) ? (string) wp_unslash( $_POST['rr_partner_new_password'] ) : '';

		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) || ! in_array( RR_Referral_Role_Service::ROLE, (array) $user->roles, true ) ) {
			$this->reset_errors[] = __( 'Invalid key.', 'default' );
			return;
		}

		if ( strlen( $password ) < 8 ) {
			$this->reset_errors[] = __( 'Password must be at least 8 characters long.', 'realtrigel-core' );
			return;
		}

		reset_password( $user, $password );

		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'rr_partner_tab'            => 'login',
					'rr_partner_password_reset' => '1',
				),
				remove_query_arg( array( 'rr_partner_reset_key', 'rr_partner_login' ), $redirect_to )
			)
		);
		exit;
	}

	/**
	 * Render registration form.
	 *
	 * @return string
	 */
	public function render(): string {
		$action = get_permalink() ?: home_url( '/' );

		wp_enqueue_style(
			'rr-referral-register',
			plugins_url( 'assets/css/referral-register.css', RR_CORE_PLUGIN_FILE ),
			array(),
			filemtime( plugin_dir_path( RR_CORE_PLUGIN_FILE ) . 'assets/css/referral-register.css' )
		);

		if ( is_user_logged_in() ) {
			$dashboard_url = $this->get_dashboard_url();
			$is_partner    = in_array( RR_Referral_Role_Service::ROLE, (array) wp_get_current_user()->roles, true );
			$success = isset( $_GET['rr_partner_registered'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rr_partner_registered'] ) );

			ob_start();
			?>
			<div class="rr-referral-register rr-referral-register--state">
				<?php if ( $is_partner ) : ?>
					<p><?php echo esc_html( $success ? __( 'Ваш партнерский аккаунт успешно создан. Теперь вы можете открыть личный кабинет партнера.', 'realtrigel-core' ) : __( 'Вы уже вошли как партнер.', 'realtrigel-core' ) ); ?></p>
					<?php if ( '' !== $dashboard_url ) : ?>
						<p><a class="rr-referral-register__submit" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Перейти в кабинет партнера', 'realtrigel-core' ); ?></a></p>
					<?php endif; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'Вы уже авторизованы под аккаунтом, который не является партнерским.', 'realtrigel-core' ); ?></p>
					<p><a class="rr-referral-register__secondary-link" href="<?php echo esc_url( wp_logout_url( $action ) ); ?>"><?php esc_html_e( 'Выйти и продолжить как партнер', 'realtrigel-core' ); ?></a></p>
				<?php endif; ?>
			</div>
			<?php

			return (string) ob_get_clean();
		}

		$success         = isset( $_GET['rr_partner_registered'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rr_partner_registered'] ) );
		$reset_requested = isset( $_GET['rr_partner_reset_requested'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rr_partner_reset_requested'] ) );
		$password_reset  = isset( $_GET['rr_partner_password_reset'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rr_partner_password_reset'] ) );
		$reset_key       = isset( $_GET['rr_partner_reset_key'] ) ? sanitize_text_field( wp_unslash( $_GET['rr_partner_reset_key'] ) ) : '';
		$reset_login     = isset( $_GET['rr_partner_login'] ) ? sanitize_text_field( wp_unslash( $_GET['rr_partner_login'] ) ) : '';
		$active_tab      = $this->get_active_tab();

		ob_start();
		?>
		<div class="rr-referral-register">
			<div class="rr-referral-register__shell">
				<div class="rr-referral-register__intro">
					<p class="rr-referral-register__eyebrow"><?php esc_html_e( 'Партнерская программа', 'realtrigel-core' ); ?></p>
					<h2><?php esc_html_e( 'Доступ для партнеров', 'realtrigel-core' ); ?></h2>
					<p><?php esc_html_e( 'Создайте партнерский аккаунт или войдите, чтобы управлять своей реферальной ссылкой и отслеживать закрепленные лиды.', 'realtrigel-core' ); ?></p>
				</div>

				<div class="rr-referral-register__panel">
					<div class="rr-referral-register__switcher" role="tablist" aria-label="<?php esc_attr_e( 'Переключатель доступа для партнеров', 'realtrigel-core' ); ?>">
						<button type="button" class="rr-referral-register__switch<?php echo 'register' === $active_tab ? ' is-active' : ''; ?>" data-rr-panel-trigger="register" aria-pressed="<?php echo 'register' === $active_tab ? 'true' : 'false'; ?>">
							<?php esc_html_e( 'Регистрация', 'realtrigel-core' ); ?>
						</button>
						<button type="button" class="rr-referral-register__switch<?php echo 'login' === $active_tab ? ' is-active' : ''; ?>" data-rr-panel-trigger="login" aria-pressed="<?php echo 'login' === $active_tab ? 'true' : 'false'; ?>">
							<?php esc_html_e( 'Вход', 'realtrigel-core' ); ?>
						</button>
					</div>

					<?php if ( $success ) : ?>
						<div class="rr-referral-register__notice rr-referral-register__notice--success">
							<?php esc_html_e( 'Ваш партнерский аккаунт успешно создан.', 'realtrigel-core' ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $reset_requested && 'forgot' === $active_tab ) : ?>
						<div class="rr-referral-register__notice rr-referral-register__notice--success">
							<?php esc_html_e( 'Check your email for the confirmation link.', 'default' ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $password_reset && 'login' === $active_tab ) : ?>
						<div class="rr-referral-register__notice rr-referral-register__notice--success">
							<?php esc_html_e( 'Your password has been reset.', 'default' ); ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $this->errors ) && 'register' === $active_tab ) : ?>
						<div class="rr-referral-register__notice rr-referral-register__notice--error">
							<ul>
								<?php foreach ( $this->errors as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $this->login_errors ) && 'login' === $active_tab ) : ?>
						<div class="rr-referral-register__notice rr-referral-register__notice--error">
							<ul>
								<?php foreach ( $this->login_errors as $error ) : ?>
									<li><?php echo wp_kses_post( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $this->reset_errors ) && in_array( $active_tab, array( 'forgot', 'reset' ), true ) ) : ?>
						<div class="rr-referral-register__notice rr-referral-register__notice--error">
							<ul>
								<?php foreach ( $this->reset_errors as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<div class="rr-referral-register__panes">
						<div class="rr-referral-register__pane<?php echo 'register' === $active_tab ? ' is-active' : ''; ?>" data-rr-panel="register" <?php echo 'register' === $active_tab ? '' : 'hidden'; ?>>
							<form class="rr-referral-register__form" method="post" action="<?php echo esc_url( $action ); ?>">
								<input type="hidden" name="rr_partner_register_action" value="1">
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $action ); ?>">
								<?php wp_nonce_field( 'rr_partner_register', 'rr_partner_register_nonce' ); ?>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Имя', 'realtrigel-core' ); ?></span>
									<input type="text" name="rr_partner_name" value="<?php echo esc_attr( $this->values['name'] ?? '' ); ?>" required>
								</label>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Email', 'realtrigel-core' ); ?></span>
									<input type="email" name="rr_partner_email" value="<?php echo esc_attr( $this->values['email'] ?? '' ); ?>" required>
								</label>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Телефон', 'realtrigel-core' ); ?></span>
									<input type="text" name="rr_partner_phone" value="<?php echo esc_attr( $this->values['phone'] ?? '' ); ?>">
								</label>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Пароль', 'realtrigel-core' ); ?></span>
									<input type="password" name="rr_partner_password" required minlength="8">
								</label>

								<div class="rr-referral-register__trap" aria-hidden="true">
									<label>
										<span><?php esc_html_e( 'Оставьте это поле пустым', 'realtrigel-core' ); ?></span>
										<input type="text" name="rr_partner_company" value="" tabindex="-1" autocomplete="off">
									</label>
								</div>

								<label class="rr-referral-register__checkbox">
									<input type="checkbox" name="rr_partner_consent" value="1" required>
									<span><?php esc_html_e( 'Я согласен с правилами программы и обработкой моих персональных данных.', 'realtrigel-core' ); ?></span>
								</label>

								<button type="submit" class="rr-referral-register__submit"><?php esc_html_e( 'Стать партнером', 'realtrigel-core' ); ?></button>
							</form>
						</div>

						<div class="rr-referral-register__pane<?php echo 'login' === $active_tab ? ' is-active' : ''; ?>" data-rr-panel="login" <?php echo 'login' === $active_tab ? '' : 'hidden'; ?>>
							<form class="rr-referral-register__form" method="post" action="<?php echo esc_url( $action ); ?>">
								<input type="hidden" name="rr_partner_login_action" value="1">
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $action ); ?>">
								<?php wp_nonce_field( 'rr_partner_login', 'rr_partner_login_nonce' ); ?>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Email или логин', 'realtrigel-core' ); ?></span>
									<input type="text" name="log" value="<?php echo esc_attr( $this->login_values['login'] ?? '' ); ?>" required>
								</label>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Пароль', 'realtrigel-core' ); ?></span>
									<input type="password" name="pwd" required>
								</label>

								<button type="submit" class="rr-referral-register__submit"><?php esc_html_e( 'Войти', 'realtrigel-core' ); ?></button>
							</form>

							<p class="rr-referral-register__helper">
								<button type="button" class="rr-referral-register__link-button" data-rr-panel-trigger="forgot">
									<?php esc_html_e( 'Забыли пароль?', 'realtrigel-core' ); ?>
								</button>
							</p>

							<p class="rr-referral-register__helper">
								<?php esc_html_e( 'Используйте данные своего партнерского аккаунта, чтобы войти в кабинет.', 'realtrigel-core' ); ?>
							</p>
						</div>

						<div class="rr-referral-register__pane<?php echo 'forgot' === $active_tab ? ' is-active' : ''; ?>" data-rr-panel="forgot" <?php echo 'forgot' === $active_tab ? '' : 'hidden'; ?>>
							<form class="rr-referral-register__form" method="post" action="<?php echo esc_url( $action ); ?>">
								<input type="hidden" name="rr_partner_reset_request_action" value="1">
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $action ); ?>">
								<?php wp_nonce_field( 'rr_partner_reset_request', 'rr_partner_reset_request_nonce' ); ?>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'Email', 'realtrigel-core' ); ?></span>
									<input type="email" name="rr_partner_reset_email" value="<?php echo esc_attr( $this->reset_values['email'] ?? '' ); ?>" required>
								</label>

								<button type="submit" class="rr-referral-register__submit"><?php esc_html_e( 'Get New Password', 'default' ); ?></button>
							</form>

							<p class="rr-referral-register__helper">
								<button type="button" class="rr-referral-register__link-button" data-rr-panel-trigger="login">
									<?php esc_html_e( 'Вход', 'realtrigel-core' ); ?>
								</button>
							</p>
						</div>

						<div class="rr-referral-register__pane<?php echo 'reset' === $active_tab ? ' is-active' : ''; ?>" data-rr-panel="reset" <?php echo 'reset' === $active_tab ? '' : 'hidden'; ?>>
							<form class="rr-referral-register__form" method="post" action="<?php echo esc_url( $action ); ?>">
								<input type="hidden" name="rr_partner_reset_password_action" value="1">
								<input type="hidden" name="rr_partner_reset_key" value="<?php echo esc_attr( $reset_key ); ?>">
								<input type="hidden" name="rr_partner_reset_login" value="<?php echo esc_attr( $reset_login ); ?>">
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( $action ); ?>">
								<?php wp_nonce_field( 'rr_partner_reset_password', 'rr_partner_reset_password_nonce' ); ?>

								<label class="rr-referral-register__field">
									<span><?php esc_html_e( 'New password', 'default' ); ?></span>
									<input type="password" name="rr_partner_new_password" required minlength="8">
								</label>

								<button type="submit" class="rr-referral-register__submit"><?php esc_html_e( 'Reset Password', 'default' ); ?></button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				var root = document.querySelector('.rr-referral-register');

				if (!root) {
					return;
				}

				var triggers = root.querySelectorAll('[data-rr-panel-trigger]');
				var panels = root.querySelectorAll('[data-rr-panel]');

				var activate = function (name) {
					triggers.forEach(function (trigger) {
						var isActive = trigger.getAttribute('data-rr-panel-trigger') === name;
						trigger.classList.toggle('is-active', isActive);
						trigger.setAttribute('aria-pressed', isActive ? 'true' : 'false');
					});

					panels.forEach(function (panel) {
						var isActive = panel.getAttribute('data-rr-panel') === name;
						panel.classList.toggle('is-active', isActive);
						panel.hidden = !isActive;
					});
				};

				triggers.forEach(function (trigger) {
					trigger.addEventListener('click', function () {
						activate(trigger.getAttribute('data-rr-panel-trigger'));
					});
				});

				activate('<?php echo esc_js( $active_tab ); ?>');
			});
		</script>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Determine whether honeypot was filled.
	 *
	 * @return bool
	 */
	private function is_honeypot_filled(): bool {
		return isset( $_POST['rr_partner_company'] ) && '' !== trim( (string) wp_unslash( $_POST['rr_partner_company'] ) );
	}

	/**
	 * Determine whether current IP is rate limited.
	 *
	 * @return bool
	 */
	private function is_rate_limited(): bool {
		$key = $this->get_rate_limit_key();

		if ( '' === $key ) {
			return false;
		}

		$count = (int) get_transient( $key );

		return $count >= 5;
	}

	/**
	 * Increase registration attempt count for current IP.
	 *
	 * @return void
	 */
	private function bump_rate_limit(): void {
		$key = $this->get_rate_limit_key();

		if ( '' === $key ) {
			return;
		}

		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Determine whether current IP is rate limited for login.
	 *
	 * @return bool
	 */
	private function is_login_rate_limited(): bool {
		$key = $this->get_login_rate_limit_key();

		if ( '' === $key ) {
			return false;
		}

		return (int) get_transient( $key ) >= 8;
	}

	/**
	 * Increase login attempt count for current IP.
	 *
	 * @return void
	 */
	private function bump_login_rate_limit(): void {
		$key = $this->get_login_rate_limit_key();

		if ( '' === $key ) {
			return;
		}

		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Determine whether current IP is rate limited for password reset.
	 *
	 * @return bool
	 */
	private function is_reset_rate_limited(): bool {
		$key = $this->get_reset_rate_limit_key();

		if ( '' === $key ) {
			return false;
		}

		return (int) get_transient( $key ) >= 5;
	}

	/**
	 * Increase password reset request count for current IP.
	 *
	 * @return void
	 */
	private function bump_reset_rate_limit(): void {
		$key = $this->get_reset_rate_limit_key();

		if ( '' === $key ) {
			return;
		}

		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Build rate limit key for password reset requests.
	 *
	 * @return string
	 */
	private function get_reset_rate_limit_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' !== $ip ? self::RESET_RATE_LIMIT_PREFIX . md5( $ip ) : '';
	}

	/**
	 * Build rate limit key for current visitor.
	 *
	 * @return string
	 */
	private function get_rate_limit_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' !== $ip ? self::RATE_LIMIT_PREFIX . md5( $ip ) : '';
	}

	/**
	 * Build login rate limit key for current visitor.
	 *
	 * @return string
	 */
	private function get_login_rate_limit_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' !== $ip ? self::LOGIN_RATE_LIMIT_PREFIX . md5( $ip ) : '';
	}

	/**
	 * Send a password reset link to a partner.
	 *
	 * @param WP_User $user       Partner user.
	 * @param string  $return_url Current shortcode page URL.
	 * @return bool
	 */
	private function send_password_reset_email( WP_User $user, string $return_url ): bool {
		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			return false;
		}

		$reset_url = add_query_arg(
			array(
				'rr_partner_tab'       => 'reset',
				'rr_partner_reset_key' => $key,
				'rr_partner_login'     => $user->user_login,
			),
			$return_url
		);

		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$subject   = sprintf( __( '[%s] Password Reset', 'default' ), $site_name );
		$message   = __( 'Someone has requested a password reset for the following account:', 'default' ) . "\r\n\r\n";
		$message  .= network_home_url( '/' ) . "\r\n\r\n";
		$message  .= sprintf( __( 'Username: %s', 'default' ), $user->user_login ) . "\r\n\r\n";
		$message  .= __( 'If this was a mistake, ignore this email and nothing will happen.', 'default' ) . "\r\n\r\n";
		$message  .= __( 'To reset your password, visit the following address:', 'default' ) . "\r\n\r\n";
		$message  .= $reset_url . "\r\n";

		return wp_mail( $user->user_email, $subject, $message );
	}

	/**
	 * Resolve active panel tab.
	 *
	 * @return string
	 */
	private function get_active_tab(): string {
		if ( isset( $_GET['rr_partner_reset_key'], $_GET['rr_partner_login'] ) ) {
			return 'reset';
		}

		if ( ! empty( $this->reset_errors ) ) {
			return isset( $_POST['rr_partner_reset_password_action'] ) ? 'reset' : 'forgot';
		}

		if ( ! empty( $this->login_errors ) ) {
			return 'login';
		}

		if ( ! empty( $this->errors ) ) {
			return 'register';
		}

		if ( isset( $_GET['rr_partner_tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_GET['rr_partner_tab'] ) );

			if ( in_array( $tab, array( 'register', 'login', 'forgot', 'reset' ), true ) ) {
				return $tab;
			}
		}

		return 'register';
	}

	/**
	 * Resolve partner dashboard URL by shortcode.
	 *
	 * @return string
	 */
	private function get_dashboard_url(): string {
		$pages = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				's'                      => '[rr_partner_dashboard]',
			)
		);

		if ( empty( $pages ) || ! isset( $pages[0]->ID ) ) {
			return '';
		}

		return (string) get_permalink( (int) $pages[0]->ID );
	}
}
