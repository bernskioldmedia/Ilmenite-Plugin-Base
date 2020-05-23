<?php
/**
 * Abstract Data
 *
 * The abstract data functions sets extends our
 * data interface, which ensures we always have a base
 * set of functions ready.
 *
 * These functions are available whenever we create classes
 * that interface with data, be it Custom Post Types, full
 * custom database tables or taxonomies.
 *
 * We create these "helper classes" to make accessing
 * data very predicable and easy.
 *
 * @author  Bernskiold Media <info@bernskioldmedia.com>
 * @package BernskioldMedia\WP\PluginScaffold
 * @since   1.0.0
 */

namespace BernskioldMedia\WP\PluginScaffold\Abstracts;

use BernskioldMedia\WP\PluginScaffold\Interfaces\Data_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Class Data
 *
 * @package BernskioldMedia\WP\PluginScaffold
 */
abstract class Data implements Data_Interface {

	/**
	 * ID for this object.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Object Type
	 *
	 * @var string
	 */
	protected static $object_type;

	/**
	 * Reference to the data store.
	 *
	 * @var Data_Store_WP
	 */
	protected $data_store;

	/**
	 * Contains all the metadata for the object.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var array
	 */
	protected $changes = [];

	/**
	 * Set to _data on construct so we can track and reset data if needed.
	 *
	 * @var array
	 */
	protected $default_data = [];

	/**
	 * This is false until the object is read from the DB.
	 *
	 * @var bool
	 */
	protected $is_read = false;

	/**
	 * Data constructor.
	 *
	 * @param  int|object|array  $read
	 */
	public function __construct( $read = 0 ) {
		$this->default_data = $this->data;
	}

	/**
	 * Only store the object ID to avoid serializing the data object instance.
	 *
	 * @return array
	 */
	public function __sleep(): array {
		return [ 'id' ];
	}

	/**
	 * Re-run the constructor with the object ID.
	 *
	 * If the object no longer exists, remove the ID.
	 */
	public function __wakeup() {
		try {
			$this->__construct( absint( $this->id ) );
		} catch ( \Exception $e ) {
			$this->set_id( 0 );
			$this->set_is_read( true );
		}
	}

	/**
	 * Change data to JSON format.
	 *
	 * @return string Data in JSON format.
	 */
	public function __toString() {
		return wp_json_encode( $this->get_data() );
	}

	/**
	 * Returns the unique ID for this object.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get Object Key
	 *
	 * @return string
	 */
	public static function get_object_type(): string {
		return self::$object_type;
	}

	/**
	 * Get data store.
	 *
	 * @return Data_Store_WP
	 */
	public function get_data_store(): Data_Store_WP {
		return $this->data_store;
	}

	/**
	 * Set ID.
	 *
	 * @param  int  $id
	 */
	public function set_id( $id ): void {
		$this->id = absint( $id );
	}

	/**
	 * Returns all data for this object.
	 *
	 * @return array
	 */
	public function get_data(): array {
		return array_merge( [
			'id' => $this->get_id(),
		], $this->data );
	}

	/**
	 * Get all the data keys for this object.
	 *
	 * @return array
	 */
	public function get_data_keys(): array {
		return array_keys( $this->data );
	}

	/**
	 * Get Property
	 *
	 * @param  string  $field_key
	 *
	 * @return mixed|null
	 */
	protected function get_prop( string $field_key ) {
		return get_field( $field_key, $this->get_id() );
	}

	/**
	 * Check if user can work with the object in question.
	 * As type, this function takes: "edit", "delete" and "view".
	 *
	 * Defaults to current user if no user is given.
	 *
	 * @param  string    $type
	 * @param  null|int  $user_id
	 *
	 * @return bool
	 */
	public function can_user( string $type, ?int $user_id = null ): bool {

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( 0 === $user_id ) {
			return false;
		}

		switch ( $type ) {

			case 'edit':
			case 'update':
				$capability = 'edit_' . $this->get_data_store()->get_key();
				break;

			case 'delete':
				$capability = 'delete_' . $this->get_data_store()->get_key();
				break;

			case 'view':
			case 'access':
			case 'read':
			default:
				$capability = 'read_' . $this->get_data_store()->get_key();
				break;

		}

		$is_allowed = user_can( $user_id, $capability, $this->get_id() );

		return $is_allowed;

	}

	/**
	 * Set property
	 *
	 * @param  string  $field_key
	 * @param  mixed   $new_value
	 *
	 * @return bool|int|mixed
	 */
	protected function set_prop( string $field_key, $new_value ) {
		return update_field( $field_key, $new_value, $this->get_id() );
	}

	/**
	 * Set object read property.
	 *
	 * @param  boolean  $read  Should read?.
	 */
	public function set_is_read( $read = true ): void {
		$this->is_read = (bool) $read;
	}

}
