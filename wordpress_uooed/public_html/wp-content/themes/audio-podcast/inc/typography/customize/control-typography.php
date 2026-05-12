<?php
/**
 * Typography control class.
 *
 * @since  1.0.0
 * @access public
 */

class Audio_Podcast_Control_Typography extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'audio-podcast-typography';

	/**
	 * Array 
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $l10n = array();

	/**
	 * Set up our control.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @param  string  $id
	 * @param  array   $args
	 * @return void
	 */
	public function __construct( $manager, $id, $args = array() ) {

		// Let the parent class do its thing.
		parent::__construct( $manager, $id, $args );

		// Make sure we have labels.
		$this->l10n = wp_parse_args(
			$this->l10n,
			array(
				'color'       => esc_html__( 'Font Color', 'audio-podcast' ),
				'family'      => esc_html__( 'Font Family', 'audio-podcast' ),
				'size'        => esc_html__( 'Font Size',   'audio-podcast' ),
				'weight'      => esc_html__( 'Font Weight', 'audio-podcast' ),
				'style'       => esc_html__( 'Font Style',  'audio-podcast' ),
				'line_height' => esc_html__( 'Line Height', 'audio-podcast' ),
				'letter_spacing' => esc_html__( 'Letter Spacing', 'audio-podcast' ),
			)
		);
	}

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script( 'audio-podcast-ctypo-customize-controls' );
		wp_enqueue_style(  'audio-podcast-ctypo-customize-controls' );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function to_json() {
		parent::to_json();

		// Loop through each of the settings and set up the data for it.
		foreach ( $this->settings as $setting_key => $setting_id ) {

			$this->json[ $setting_key ] = array(
				'link'  => $this->get_link( $setting_key ),
				'value' => $this->value( $setting_key ),
				'label' => isset( $this->l10n[ $setting_key ] ) ? $this->l10n[ $setting_key ] : ''
			);

			if ( 'family' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_families();

			elseif ( 'weight' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_weight_choices();

			elseif ( 'style' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_style_choices();
		}
	}

	/**
	 * Underscore JS template to handle the control's output.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function content_template() { ?>

		<# if ( data.label ) { #>
			<span class="customize-control-title">{{ data.label }}</span>
		<# } #>

		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>

		<ul>

		<# if ( data.family && data.family.choices ) { #>

			<li class="typography-font-family">

				<# if ( data.family.label ) { #>
					<span class="customize-control-title">{{ data.family.label }}</span>
				<# } #>

				<select {{{ data.family.link }}}>

					<# _.each( data.family.choices, function( label, choice ) { #>
						<option value="{{ choice }}" <# if ( choice === data.family.value ) { #> selected="selected" <# } #>>{{ label }}</option>
					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.weight && data.weight.choices ) { #>

			<li class="typography-font-weight">

				<# if ( data.weight.label ) { #>
					<span class="customize-control-title">{{ data.weight.label }}</span>
				<# } #>

				<select {{{ data.weight.link }}}>

					<# _.each( data.weight.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.weight.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.style && data.style.choices ) { #>

			<li class="typography-font-style">

				<# if ( data.style.label ) { #>
					<span class="customize-control-title">{{ data.style.label }}</span>
				<# } #>

				<select {{{ data.style.link }}}>

					<# _.each( data.style.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.style.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.size ) { #>

			<li class="typography-font-size">

				<# if ( data.size.label ) { #>
					<span class="customize-control-title">{{ data.size.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.size.link }}} value="{{ data.size.value }}" />

			</li>
		<# } #>

		<# if ( data.line_height ) { #>

			<li class="typography-line-height">

				<# if ( data.line_height.label ) { #>
					<span class="customize-control-title">{{ data.line_height.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.line_height.link }}} value="{{ data.line_height.value }}" />

			</li>
		<# } #>

		<# if ( data.letter_spacing ) { #>

			<li class="typography-letter-spacing">

				<# if ( data.letter_spacing.label ) { #>
					<span class="customize-control-title">{{ data.letter_spacing.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.letter_spacing.link }}} value="{{ data.letter_spacing.value }}" />

			</li>
		<# } #>

		</ul>
	<?php }

	/**
	 * Returns the available fonts.  Fonts should have available weights, styles, and subsets.
	 *
	 * @todo Integrate with Google fonts.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_fonts() { return array(); }

	/**
	 * Returns the available font families.
	 *
	 * @todo Pull families from `get_fonts()`.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	function get_font_families() {

		return array(
			'' => __( 'No Fonts', 'audio-podcast' ),
        'Abril Fatface' => __( 'Abril Fatface', 'audio-podcast' ),
        'Acme' => __( 'Acme', 'audio-podcast' ),
        'Anton' => __( 'Anton', 'audio-podcast' ),
        'Architects Daughter' => __( 'Architects Daughter', 'audio-podcast' ),
        'Arimo' => __( 'Arimo', 'audio-podcast' ),
        'Arsenal' => __( 'Arsenal', 'audio-podcast' ),
        'Arvo' => __( 'Arvo', 'audio-podcast' ),
        'Alegreya' => __( 'Alegreya', 'audio-podcast' ),
        'Alfa Slab One' => __( 'Alfa Slab One', 'audio-podcast' ),
        'Averia Serif Libre' => __( 'Averia Serif Libre', 'audio-podcast' ),
        'Bangers' => __( 'Bangers', 'audio-podcast' ),
        'Boogaloo' => __( 'Boogaloo', 'audio-podcast' ),
        'Bad Script' => __( 'Bad Script', 'audio-podcast' ),
        'Bitter' => __( 'Bitter', 'audio-podcast' ),
        'Bree Serif' => __( 'Bree Serif', 'audio-podcast' ),
        'BenchNine' => __( 'BenchNine', 'audio-podcast' ),
        'Cabin' => __( 'Cabin', 'audio-podcast' ),
        'Cardo' => __( 'Cardo', 'audio-podcast' ),
        'Courgette' => __( 'Courgette', 'audio-podcast' ),
        'Cherry Swash' => __( 'Cherry Swash', 'audio-podcast' ),
        'Cormorant Garamond' => __( 'Cormorant Garamond', 'audio-podcast' ),
        'Crimson Text' => __( 'Crimson Text', 'audio-podcast' ),
        'Cuprum' => __( 'Cuprum', 'audio-podcast' ),
        'Cookie' => __( 'Cookie', 'audio-podcast' ),
        'Chewy' => __( 'Chewy', 'audio-podcast' ),
        'Days One' => __( 'Days One', 'audio-podcast' ),
        'Dosis' => __( 'Dosis', 'audio-podcast' ),
        'Droid Sans' => __( 'Droid Sans', 'audio-podcast' ),
        'Economica' => __( 'Economica', 'audio-podcast' ),
        'Fredoka One' => __( 'Fredoka One', 'audio-podcast' ),
        'Fjalla One' => __( 'Fjalla One', 'audio-podcast' ),
        'Francois One' => __( 'Francois One', 'audio-podcast' ),
        'Frank Ruhl Libre' => __( 'Frank Ruhl Libre', 'audio-podcast' ),
        'Gloria Hallelujah' => __( 'Gloria Hallelujah', 'audio-podcast' ),
        'Great Vibes' => __( 'Great Vibes', 'audio-podcast' ),
        'Handlee' => __( 'Handlee', 'audio-podcast' ),
        'Hammersmith One' => __( 'Hammersmith One', 'audio-podcast' ),
        'Inconsolata' => __( 'Inconsolata', 'audio-podcast' ),
        'Indie Flower' => __( 'Indie Flower', 'audio-podcast' ),
        'IM Fell English SC' => __( 'IM Fell English SC', 'audio-podcast' ),
        'Julius Sans One' => __( 'Julius Sans One', 'audio-podcast' ),
        'Josefin Slab' => __( 'Josefin Slab', 'audio-podcast' ),
        'Josefin Sans' => __( 'Josefin Sans', 'audio-podcast' ),
        'Kanit' => __( 'Kanit', 'audio-podcast' ),
        'Lobster' => __( 'Lobster', 'audio-podcast' ),
        'Lato' => __( 'Lato', 'audio-podcast' ),
        'Lora' => __( 'Lora', 'audio-podcast' ),
        'Libre Baskerville' => __( 'Libre Baskerville', 'audio-podcast' ),
        'Lobster Two' => __( 'Lobster Two', 'audio-podcast' ),
        'Merriweather' => __( 'Merriweather', 'audio-podcast' ),
        'Monda' => __( 'Monda', 'audio-podcast' ),
        'Montserrat' => __( 'Montserrat', 'audio-podcast' ),
        'Muli' => __( 'Muli', 'audio-podcast' ),
        'Marck Script' => __( 'Marck Script', 'audio-podcast' ),
        'Noto Serif' => __( 'Noto Serif', 'audio-podcast' ),
        'Open Sans' => __( 'Open Sans', 'audio-podcast' ),
        'Overpass' => __( 'Overpass', 'audio-podcast' ),
        'Overpass Mono' => __( 'Overpass Mono', 'audio-podcast' ),
        'Oxygen' => __( 'Oxygen', 'audio-podcast' ),
        'Orbitron' => __( 'Orbitron', 'audio-podcast' ),
        'Patua One' => __( 'Patua One', 'audio-podcast' ),
        'Pacifico' => __( 'Pacifico', 'audio-podcast' ),
        'Padauk' => __( 'Padauk', 'audio-podcast' ),
        'Playball' => __( 'Playball', 'audio-podcast' ),
        'Playfair Display' => __( 'Playfair Display', 'audio-podcast' ),
        'PT Sans' => __( 'PT Sans', 'audio-podcast' ),
        'Philosopher' => __( 'Philosopher', 'audio-podcast' ),
        'Permanent Marker' => __( 'Permanent Marker', 'audio-podcast' ),
        'Poiret One' => __( 'Poiret One', 'audio-podcast' ),
        'Quicksand' => __( 'Quicksand', 'audio-podcast' ),
        'Quattrocento Sans' => __( 'Quattrocento Sans', 'audio-podcast' ),
        'Raleway' => __( 'Raleway', 'audio-podcast' ),
        'Rubik' => __( 'Rubik', 'audio-podcast' ),
        'Rokkitt' => __( 'Rokkitt', 'audio-podcast' ),
        'Russo One' => __( 'Russo One', 'audio-podcast' ),
        'Righteous' => __( 'Righteous', 'audio-podcast' ),
        'Slabo' => __( 'Slabo', 'audio-podcast' ),
        'Source Sans Pro' => __( 'Source Sans Pro', 'audio-podcast' ),
        'Shadows Into Light Two' => __( 'Shadows Into Light Two', 'audio-podcast'),
        'Shadows Into Light' => __( 'Shadows Into Light', 'audio-podcast' ),
        'Sacramento' => __( 'Sacramento', 'audio-podcast' ),
        'Shrikhand' => __( 'Shrikhand', 'audio-podcast' ),
        'Tangerine' => __( 'Tangerine', 'audio-podcast' ),
        'Ubuntu' => __( 'Ubuntu', 'audio-podcast' ),
        'VT323' => __( 'VT323', 'audio-podcast' ),
        'Varela Round' => __( 'Varela Round', 'audio-podcast' ),
        'Vampiro One' => __( 'Vampiro One', 'audio-podcast' ),
        'Vollkorn' => __( 'Vollkorn', 'audio-podcast' ),
        'Volkhov' => __( 'Volkhov', 'audio-podcast' ),
        'Yanone Kaffeesatz' => __( 'Yanone Kaffeesatz', 'audio-podcast' )
		);
	}

	/**
	 * Returns the available font weights.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_weight_choices() {

		return array(
			'' => esc_html__( 'No Fonts weight', 'audio-podcast' ),
			'100' => esc_html__( 'Thin',       'audio-podcast' ),
			'300' => esc_html__( 'Light',      'audio-podcast' ),
			'400' => esc_html__( 'Normal',     'audio-podcast' ),
			'500' => esc_html__( 'Medium',     'audio-podcast' ),
			'700' => esc_html__( 'Bold',       'audio-podcast' ),
			'900' => esc_html__( 'Ultra Bold', 'audio-podcast' ),
		);
	}

	/**
	 * Returns the available font styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_style_choices() {

		return array(
			'' => esc_html__( 'No Fonts Style', 'audio-podcast' ),
			'normal'  => esc_html__( 'Normal', 'audio-podcast' ),
			'italic'  => esc_html__( 'Italic', 'audio-podcast' ),
			'oblique' => esc_html__( 'Oblique', 'audio-podcast' )
		);
	}
}
