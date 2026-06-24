<?php
/**
 * Referral settings page.
 *
 * @package RealtRigelCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RR_Referral_Settings_Page {

	/**
	 * Settings page slug.
	 */
	private const PAGE_SLUG = 'rr-referral-settings';

	/**
	 * Settings service.
	 *
	 * @var RR_Referral_Settings_Service
	 */
	private RR_Referral_Settings_Service $settings_service;

	/**
	 * Constructor.
	 *
	 * @param RR_Referral_Settings_Service $settings_service Settings service.
	 */
	public function __construct( RR_Referral_Settings_Service $settings_service ) {
		$this->settings_service = $settings_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings submenu page.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_options_page(
			__( 'Referral Settings', 'realtrigel-core' ),
			__( 'Referral Settings', 'realtrigel-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_ATTRIBUTION_LIFETIME_MODE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_lifetime_mode' ),
				'default'           => RR_Referral_Settings_Service::MODE_DAYS,
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_ATTRIBUTION_LIFETIME_DAYS,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_lifetime_days' ),
				'default'           => 90,
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_GOOGLE_SHEETS_ENABLED,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => '0',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_GOOGLE_SHEETS_WEBHOOK_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_GOOGLE_SHEETS_WEBHOOK_SECRET,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_TELEGRAM_ENABLED,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => '0',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_TELEGRAM_BOT_TOKEN,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_TELEGRAM_CHAT_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_BITRIX_ENABLED,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => '0',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_BITRIX_WEBHOOK_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			'rr_referral_settings',
			RR_Referral_Settings_Service::OPTION_BITRIX_SOURCE_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'WEB',
			)
		);

		add_settings_section(
			'rr_referral_attribution_section',
			__( 'Attribution', 'realtrigel-core' ),
			array( $this, 'render_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'rr_referral_google_sheets_section',
			__( 'Google Таблицы', 'realtrigel-core' ),
			array( $this, 'render_google_sheets_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'rr_referral_telegram_section',
			__( 'Telegram', 'realtrigel-core' ),
			array( $this, 'render_telegram_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'rr_referral_bitrix_section',
			__( 'Bitrix24', 'realtrigel-core' ),
			array( $this, 'render_bitrix_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'rr_referral_attribution_lifetime_mode',
			__( 'Attribution lifetime', 'realtrigel-core' ),
			array( $this, 'render_lifetime_mode_field' ),
			self::PAGE_SLUG,
			'rr_referral_attribution_section'
		);

		add_settings_field(
			'rr_referral_attribution_lifetime_days',
			__( 'Attribution lifetime days', 'realtrigel-core' ),
			array( $this, 'render_lifetime_days_field' ),
			self::PAGE_SLUG,
			'rr_referral_attribution_section'
		);

		add_settings_field(
			'rr_referral_google_sheets_enabled',
			__( 'Включить экспорт', 'realtrigel-core' ),
			array( $this, 'render_google_sheets_enabled_field' ),
			self::PAGE_SLUG,
			'rr_referral_google_sheets_section'
		);

		add_settings_field(
			'rr_referral_google_sheets_webhook_url',
			__( 'URL вебхука', 'realtrigel-core' ),
			array( $this, 'render_google_sheets_webhook_url_field' ),
			self::PAGE_SLUG,
			'rr_referral_google_sheets_section'
		);

		add_settings_field(
			'rr_referral_google_sheets_webhook_secret',
			__( 'Секрет вебхука', 'realtrigel-core' ),
			array( $this, 'render_google_sheets_webhook_secret_field' ),
			self::PAGE_SLUG,
			'rr_referral_google_sheets_section'
		);

		add_settings_field(
			'rr_referral_telegram_enabled',
			__( 'Включить Telegram', 'realtrigel-core' ),
			array( $this, 'render_telegram_enabled_field' ),
			self::PAGE_SLUG,
			'rr_referral_telegram_section'
		);

		add_settings_field(
			'rr_referral_telegram_bot_token',
			__( 'Токен бота', 'realtrigel-core' ),
			array( $this, 'render_telegram_bot_token_field' ),
			self::PAGE_SLUG,
			'rr_referral_telegram_section'
		);

		add_settings_field(
			'rr_referral_telegram_chat_id',
			__( 'ID чата', 'realtrigel-core' ),
			array( $this, 'render_telegram_chat_id_field' ),
			self::PAGE_SLUG,
			'rr_referral_telegram_section'
		);

		add_settings_field(
			'rr_referral_bitrix_enabled',
			__( 'Включить Bitrix24', 'realtrigel-core' ),
			array( $this, 'render_bitrix_enabled_field' ),
			self::PAGE_SLUG,
			'rr_referral_bitrix_section'
		);

		add_settings_field(
			'rr_referral_bitrix_webhook_url',
			__( 'Webhook URL', 'realtrigel-core' ),
			array( $this, 'render_bitrix_webhook_url_field' ),
			self::PAGE_SLUG,
			'rr_referral_bitrix_section'
		);

		add_settings_field(
			'rr_referral_bitrix_source_id',
			__( 'SOURCE_ID', 'realtrigel-core' ),
			array( $this, 'render_bitrix_source_id_field' ),
			self::PAGE_SLUG,
			'rr_referral_bitrix_section'
		);
	}

	/**
	 * Sanitize lifetime mode.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_lifetime_mode( string $value ): string {
		$allowed = array(
			RR_Referral_Settings_Service::MODE_DAYS,
			RR_Referral_Settings_Service::MODE_UNLIMITED,
		);

		return in_array( $value, $allowed, true ) ? $value : RR_Referral_Settings_Service::MODE_DAYS;
	}

	/**
	 * Sanitize lifetime days.
	 *
	 * @param int $value Raw days value.
	 * @return int
	 */
	public function sanitize_lifetime_days( int $value ): int {
		return max( 1, $value );
	}

	/**
	 * Sanitize checkbox value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_checkbox( $value ): string {
		return '1' === (string) $value ? '1' : '0';
	}

	/**
	 * Render section description.
	 *
	 * @return void
	 */
	public function render_section_description(): void {
		echo '<p>' . esc_html__( 'Configure how long the first-click attribution remains active for new visitors.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Google Sheets section description.
	 *
	 * @return void
	 */
	public function render_google_sheets_section_description(): void {
		echo '<p>' . esc_html__( 'Отправляет проверенные заявки с формы объекта в вебхук Google Apps Script.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Telegram section description.
	 *
	 * @return void
	 */
	public function render_telegram_section_description(): void {
		echo '<p>' . esc_html__( 'Отправляет проверенные заявки с формы объекта в группу или чат Telegram.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Bitrix24 section description.
	 *
	 * @return void
	 */
	public function render_bitrix_section_description(): void {
		echo '<p>' . esc_html__( 'Отправляет сохраненные заявки с формы объекта в Bitrix24 как лиды через crm.lead.add.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render lifetime mode field.
	 *
	 * @return void
	 */
	public function render_lifetime_mode_field(): void {
		$current = $this->settings_service->get_attribution_lifetime_mode();
		$options = array(
			RR_Referral_Settings_Service::MODE_DAYS      => __( 'Number of days', 'realtrigel-core' ),
			RR_Referral_Settings_Service::MODE_UNLIMITED => __( 'Unlimited', 'realtrigel-core' ),
		);

		echo '<select name="' . esc_attr( RR_Referral_Settings_Service::OPTION_ATTRIBUTION_LIFETIME_MODE ) . '">';

		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Render lifetime days field.
	 *
	 * @return void
	 */
	public function render_lifetime_days_field(): void {
		$value = $this->settings_service->get_attribution_lifetime_days();

		echo '<input type="number" min="1" step="1" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_ATTRIBUTION_LIFETIME_DAYS ) . '" value="' . esc_attr( (string) $value ) . '" class="small-text" />';
		echo '<p class="description">' . esc_html__( 'Used only when "Number of days" is selected.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Google Sheets enabled field.
	 *
	 * @return void
	 */
	public function render_google_sheets_enabled_field(): void {
		echo '<label>';
		echo '<input type="checkbox" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_GOOGLE_SHEETS_ENABLED ) . '" value="1"' . checked( $this->settings_service->is_google_sheets_enabled(), true, false ) . ' /> ';
		echo esc_html__( 'Отправлять заявки в Google Таблицы', 'realtrigel-core' );
		echo '</label>';
	}

	/**
	 * Render Google Sheets webhook URL field.
	 *
	 * @return void
	 */
	public function render_google_sheets_webhook_url_field(): void {
		echo '<input type="url" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_GOOGLE_SHEETS_WEBHOOK_URL ) . '" value="' . esc_attr( $this->settings_service->get_google_sheets_webhook_url() ) . '" class="regular-text code" placeholder="https://script.google.com/macros/s/.../exec" />';
		echo '<p class="description">' . esc_html__( 'URL веб-приложения Google Apps Script.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Google Sheets webhook secret field.
	 *
	 * @return void
	 */
	public function render_google_sheets_webhook_secret_field(): void {
		echo '<input type="text" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_GOOGLE_SHEETS_WEBHOOK_SECRET ) . '" value="' . esc_attr( $this->settings_service->get_google_sheets_webhook_secret() ) . '" class="regular-text code" autocomplete="off" />';
		echo '<p class="description">' . esc_html__( 'Необязательный общий секрет, который отправляется с каждым запросом.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Telegram enabled field.
	 *
	 * @return void
	 */
	public function render_telegram_enabled_field(): void {
		echo '<label>';
		echo '<input type="checkbox" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_TELEGRAM_ENABLED ) . '" value="1"' . checked( $this->settings_service->is_telegram_enabled(), true, false ) . ' /> ';
		echo esc_html__( 'Отправлять заявки в Telegram', 'realtrigel-core' );
		echo '</label>';
	}

	/**
	 * Render Telegram bot token field.
	 *
	 * @return void
	 */
	public function render_telegram_bot_token_field(): void {
		echo '<input type="text" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_TELEGRAM_BOT_TOKEN ) . '" value="' . esc_attr( $this->settings_service->get_telegram_bot_token() ) . '" class="regular-text code" autocomplete="off" />';
		echo '<p class="description">' . esc_html__( 'Токен от BotFather.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Telegram chat ID field.
	 *
	 * @return void
	 */
	public function render_telegram_chat_id_field(): void {
		echo '<input type="text" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_TELEGRAM_CHAT_ID ) . '" value="' . esc_attr( $this->settings_service->get_telegram_chat_id() ) . '" class="regular-text code" placeholder="-1001234567890" />';
		echo '<p class="description">' . esc_html__( 'ID группы или чата, куда добавлен бот.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Bitrix24 enabled field.
	 *
	 * @return void
	 */
	public function render_bitrix_enabled_field(): void {
		echo '<label>';
		echo '<input type="checkbox" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_BITRIX_ENABLED ) . '" value="1"' . checked( $this->settings_service->is_bitrix_enabled(), true, false ) . ' /> ';
		echo esc_html__( 'Отправлять заявки в Bitrix24', 'realtrigel-core' );
		echo '</label>';
	}

	/**
	 * Render Bitrix24 webhook URL field.
	 *
	 * @return void
	 */
	public function render_bitrix_webhook_url_field(): void {
		echo '<input type="url" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_BITRIX_WEBHOOK_URL ) . '" value="' . esc_attr( $this->settings_service->get_bitrix_webhook_url() ) . '" class="regular-text code" placeholder="https://example.bitrix24.eu/rest/1/token/" />';
		echo '<p class="description">' . esc_html__( 'Можно указать базовый webhook URL или полный URL метода crm.lead.add.json.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render Bitrix24 source ID field.
	 *
	 * @return void
	 */
	public function render_bitrix_source_id_field(): void {
		echo '<input type="text" name="' . esc_attr( RR_Referral_Settings_Service::OPTION_BITRIX_SOURCE_ID ) . '" value="' . esc_attr( $this->settings_service->get_bitrix_source_id() ) . '" class="regular-text code" placeholder="WEB" />';
		echo '<p class="description">' . esc_html__( 'Стандартное значение для сайта: WEB.', 'realtrigel-core' ) . '</p>';
	}

	/**
	 * Render page markup.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Referral Settings', 'realtrigel-core' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'rr_referral_settings' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<?php $this->render_google_sheets_setup_guide(); ?>
		</div>
		<?php
	}

	/**
	 * Render Google Sheets setup guide.
	 *
	 * @return void
	 */
	private function render_google_sheets_setup_guide(): void {
		$columns = 'date, object_id, object_title, object_url, name, phone, email, telegram, message, partner_code, source, redirect_url, language, user_agent';
		$script  = <<<'JS'
const SHEET_NAME = 'Sheet1';
const WEBHOOK_SECRET = '';

function doPost(e) {
  try {
    const payload = JSON.parse(e.postData.contents || '{}');

    if (WEBHOOK_SECRET && payload.secret !== WEBHOOK_SECRET) {
      return jsonResponse({ ok: false, error: 'Invalid secret' }, 403);
    }

    const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    const sheet = spreadsheet.getSheetByName(SHEET_NAME) || spreadsheet.getSheets()[0];

    sheet.appendRow([
      payload.date || '',
      payload.object_id || '',
      payload.object_title || '',
      payload.object_url || '',
      payload.name || '',
      payload.phone || '',
      payload.email || '',
      payload.telegram || '',
      payload.message || '',
      payload.partner_code || '',
      payload.source || '',
      payload.redirect_url || '',
      payload.language || '',
      payload.user_agent || ''
    ]);

    return jsonResponse({ ok: true }, 200);
  } catch (error) {
    return jsonResponse({ ok: false, error: String(error) }, 500);
  }
}

function jsonResponse(data, statusCode) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}
JS;
		?>
		<hr>
		<h2><?php esc_html_e( 'Инструкция для Google Таблиц', 'realtrigel-core' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Создайте пустую Google Таблицу и добавьте первую строку с колонками:', 'realtrigel-core' ); ?><br><code><?php echo esc_html( $columns ); ?></code></li>
			<li><?php esc_html_e( 'Откройте в таблице: Расширения -> Apps Script.', 'realtrigel-core' ); ?></li>
			<li><?php esc_html_e( 'Вставьте скрипт ниже. Если используете секрет, укажите его в WEBHOOK_SECRET и в поле "Секрет вебхука" выше.', 'realtrigel-core' ); ?></li>
			<li><?php esc_html_e( 'Опубликуйте скрипт: Deploy -> New deployment -> Web app. Execute as: Me. Who has access: Anyone.', 'realtrigel-core' ); ?></li>
			<li><?php esc_html_e( 'Скопируйте Web App URL и вставьте его в поле "URL вебхука". Затем включите экспорт и сохраните настройки.', 'realtrigel-core' ); ?></li>
		</ol>
		<p>
			<button type="button" class="button" data-rr-copy-google-script><?php esc_html_e( 'Скопировать скрипт', 'realtrigel-core' ); ?></button>
			<span class="description" data-rr-copy-google-script-status></span>
		</p>
		<textarea class="large-text code" rows="36" readonly data-rr-google-script><?php echo esc_textarea( $script ); ?></textarea>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				var button = document.querySelector('[data-rr-copy-google-script]');
				var textarea = document.querySelector('[data-rr-google-script]');
				var status = document.querySelector('[data-rr-copy-google-script-status]');

				if (!button || !textarea) {
					return;
				}

				button.addEventListener('click', function () {
					var value = textarea.value || '';
					var done = function () {
						if (status) {
							status.textContent = '<?php echo esc_js( __( 'Скопировано.', 'realtrigel-core' ) ); ?>';
						}
					};

					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard.writeText(value).then(done).catch(function () {
							textarea.select();
							document.execCommand('copy');
							done();
						});
						return;
					}

					textarea.select();
					document.execCommand('copy');
					done();
				});
			});
		</script>
		<?php
	}
}
