<?php
/**
 * Autoloader - Main class
 *
 * @author  Pablo dos S G Pacheco
 */

namespace Pablo_Pacheco\WP_Namespace_Autoloader;


if ( ! class_exists( '\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader' ) ) {
	class WP_Namespace_Autoloader {

		/**
		 * Autoloader constructor.
		 *
		 * Autoloads all your WordPress classes in a easy way
		 *
		 * @param array|string $args               {
		 *                                         Array of arguments.
		 *
		 * @type string        $directory          Current directory. Use __DIR__.
		 * @type string        $namespace_prefix   Main namespace of your project . Probably use __NAMESPACE__.
		 * @type string        $force_to_lowercase If you want to keep all your folders lowercased
		 * @type string        $classes_dir        Name of the directory containing all your classes (optional).
		 * }
		 */
		function __construct( $args = array() ) {
			$args = wp_parse_args( $args, array(
				'directory'          => null,
				'namespace_prefix'   => null,
				'force_to_lowercase' => false,
				'classes_dir'        => '',
			) );

			$this->set_args( $args );
		}

		/**
		 * Register autoloader
		 *
		 * @return string
		 */
		public function init() {
			spl_autoload_register( array( $this, 'autoload' ) );
		}

		public function need_to_autoload( $class ) {
			$args      = $this->get_args();
			$namespace = $args['namespace_prefix'];

			if ( false !== strpos( $class, $namespace ) ) {
				if ( ! class_exists( $class ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Autoloads classes
		 *
		 * @param $class
		 */
		public function autoload( $class ) {
			$file = $this->convert_class_to_file( $class );

			if ( $this->need_to_autoload( $class ) ) {
				if ( file_exists( $file ) ) {
					require_once $file;
				} else {
					error_log( 'WP Namespace Autoloader could not load file: ' . print_r( $file, true ) );
				}
			}
		}

		/**
		 * Gets full path of directory containing all classes
		 *
		 * @return string
		 */
		private function get_dir() {
			$args = $this->get_args();
			$dir  = $this->sanitize_file_path( $args['classes_dir'] );

			// Directory containing all classes
			$classes_dir = empty( $dir ) ? '' : rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

			return untrailingslashit( $args['directory'] ) . DIRECTORY_SEPARATOR . $classes_dir;
		}

		/**
		 * Gets only the path leading to final file based on namespace
		 *
		 * @param $class
		 * @return string
		 */
		private function get_namespace_file_path( $class ) {
			$args             = $this->get_args();
			$namespace_prefix = $args['namespace_prefix'];

			// Sanitized class and namespace prefix
			$sanitized_class            = $this->sanitize_namespace( $class, false );
			$sanitized_namespace_prefix = $this->sanitize_namespace( $namespace_prefix, true );

			// Removes prefix from class namespace
			$namespace_without_prefix = str_replace( $sanitized_namespace_prefix, '', $sanitized_class );

			// Gets namespace file path
			$namespaces_without_prefix_arr = explode( '\\', $namespace_without_prefix );
			array_pop( $namespaces_without_prefix_arr );
			$namespace_file_path = implode( DIRECTORY_SEPARATOR, $namespaces_without_prefix_arr ) . DIRECTORY_SEPARATOR;

			if ( $args['force_to_lowercase'] ) {
				$namespace_file_path = strtolower( $namespace_file_path );
			}

			return $namespace_file_path;
		}

		/**
		 * Gets final file to be loaded considering WordPress coding standards
		 *
		 * @param $class
		 * @return string
		 */
		private function get_file_applying_wp_standards( $class ) {
			$args = $this->get_args();

			// Sanitized class and namespace prefix
			$sanitized_class = $this->sanitize_namespace( $class, false );

			// Gets namespace file path
			$namespaces_arr = explode( '\\', $sanitized_class );

			// Final file name
			$final_file = strtolower( array_pop( $namespaces_arr ) );

			// Final file with underscores replaced
			$file_name_dash_replaced = str_replace( array( '_', "\0" ), array( '-', '', ), $final_file ) . '.php';

			// Final file with 'class' appended
			return 'class-' . $file_name_dash_replaced;
		}

		/**
		 * Sanitizes file path
		 *
		 * @param $file_path
		 * @return string
		 */
		private function sanitize_file_path( $file_path ) {
			return trim( $file_path, DIRECTORY_SEPARATOR );
		}


		/**
		 * Sanitizes namespace
		 *
		 * @param      $namespace
		 * @param bool $add_backslash
		 * @return string
		 */
		private function sanitize_namespace( $namespace, $add_backslash = false ) {
			if ( $add_backslash ) {
				return trim( $namespace, '\\' ) . DIRECTORY_SEPARATOR;
			} else {
				return trim( $namespace, '\\' );
			}
		}

		/**
		 * Converts a namespaced class in a file to be loaded
		 *
		 * @param      $class
		 * @param bool $check_loading_need
		 * @return bool|string
		 */
		public function convert_class_to_file( $class, $check_loading_need = false ) {
			if ( $check_loading_need ) {
				if ( ! $this->need_to_autoload( $class ) ) {
					return false;
				}
			}

			$dir                 = $this->get_dir();
			$namespace_file_path = $this->get_namespace_file_path( $class );
			$final_file          = $this->get_file_applying_wp_standards( $class );

			return $dir . $namespace_file_path . $final_file;
		}

		/**
		 * @return mixed
		 */
		public function get_args() {
			return $this->args;
		}

		/**
		 * @param mixed $args
		 */
		public function set_args( $args ) {
			$this->args = $args;
		}
	}
}