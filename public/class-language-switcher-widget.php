<?php
/**
 * Language Switcher Widget
 *
 * Widget for displaying language switcher in sidebars.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Language Switcher Widget class
 */
class Language_Switcher_Widget extends \WP_Widget {

	/**
	 * Language Switcher instance
	 *
	 * @var Language_Switcher
	 */
	protected $switcher;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'wpste_language_switcher',
			__( 'Language Switcher', 'smart-translation-engine' ),
			array(
				'description' => __( 'Display a language switcher for multilingual content', 'smart-translation-engine' ),
			)
		);

		$this->switcher = new Language_Switcher();
	}

	/**
	 * Widget output
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		$title      = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$style      = ! empty( $instance['style'] ) ? $instance['style'] : 'dropdown';
		$show_flags = isset( $instance['show_flags'] ) ? (bool) $instance['show_flags'] : true;
		$show_names = isset( $instance['show_names'] ) ? (bool) $instance['show_names'] : true;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core widget args
		echo $args['before_widget'];

		if ( $title ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core widget args
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns escaped HTML
		echo $this->switcher->render(
			array(
				'style'      => $style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internal parameter, sanitized in update()
				'show_flags' => $show_flags, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Boolean value, sanitized in update()
				'show_names' => $show_names, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Boolean value, sanitized in update()
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core widget args
		echo $args['after_widget'];
	}

	/**
	 * Widget form
	 *
	 * @param array $instance Widget instance.
	 * @return string
	 */
	public function form( $instance ) {
		$title      = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Languages', 'smart-translation-engine' );
		$style      = ! empty( $instance['style'] ) ? $instance['style'] : 'dropdown';
		$show_flags = isset( $instance['show_flags'] ) ? (bool) $instance['show_flags'] : true;
		$show_names = isset( $instance['show_names'] ) ? (bool) $instance['show_names'] : true;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'smart-translation-engine' ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
			>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>">
				<?php esc_html_e( 'Display Style:', 'smart-translation-engine' ); ?>
			</label>
			<select
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>"
			>
				<option value="dropdown" <?php selected( $style, 'dropdown' ); ?>>
					<?php esc_html_e( 'Dropdown', 'smart-translation-engine' ); ?>
				</option>
				<option value="flags" <?php selected( $style, 'flags' ); ?>>
					<?php esc_html_e( 'Flags/Links', 'smart-translation-engine' ); ?>
				</option>
			</select>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_flags' ) ); ?>"
				<?php checked( $show_flags ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>">
				<?php esc_html_e( 'Show flags', 'smart-translation-engine' ); ?>
			</label>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_names' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_names' ) ); ?>"
				<?php checked( $show_names ); ?>
			>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_names' ) ); ?>">
				<?php esc_html_e( 'Show language names', 'smart-translation-engine' ); ?>
			</label>
		</p>
		<?php
		return '';
	}

	/**
	 * Update widget
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']      = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['style']      = ! empty( $new_instance['style'] ) ? sanitize_text_field( $new_instance['style'] ) : 'dropdown';
		$instance['show_flags'] = isset( $new_instance['show_flags'] ) ? 1 : 0;
		$instance['show_names'] = isset( $new_instance['show_names'] ) ? 1 : 0;

		return $instance;
	}
}
