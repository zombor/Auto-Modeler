<?php defined('SYSPATH') or die('No direct script access.');
/**
* User Token Model
*
* @package        Auto_Modeler
* @author         Jeremy Bush
* @copyright     (c) 2008 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class User_Token_Model extends Auto_Modeler_ORM {

	protected $table_name = 'user_tokens';

	protected $data = array('id' => '',
	                        'user_id' => '',
	                        'expires' => '',
	                        'created' => '',
	                        'user_agent' => '',
	                        'token' => '');

	// Relationships
	protected $belongs_to = array('user');

	// Current timestamp
	protected $now;

	/**
	 * Handles garbage collection and deleting of expired objects.
	 */
	public function __construct($id = FALSE)
	{
		parent::__construct();

		if ($id != NULL AND (ctype_digit($id) OR is_int($id)))
		{
			// try and get a row with this ID
			$data = $this->db->getwhere($this->table_name, array('id' => $id))->result(FALSE);

			// try and assign the data
			if (count($data) == 1 AND $data = $data->current())
			{
				foreach ($data as $key => $value)
					$this->data[$key] = $value;
			}
		}
		else if ($id != NULL AND is_string($id))
		{
			// try and get a row with this username/email
			$data = $this->db->where(array('token' => $id))->get($this->table_name)->result(FALSE);

			// try and assign the data
			if (count($data) == 1 AND $data = $data->current())
			{
				foreach ($data as $key => $value)
					$this->data[$key] = $value;
			}
		}

		// Set the now, we use this a lot
		$this->now = time();

		if (mt_rand(1, 100) === 1)
		{
			// Do garbage collection
			$this->delete_expired();
		}

		if ($this->data['id'] != 0 AND $this->data['expires'] < $this->now)
		{
			// This object has expired
			$this->delete();
		}
	}

	/**
	 * Overload saving to set the created time and to create a new token
	 * when the object is saved.
	 */
	public function save()
	{
		if ($this->data['id'] == 0)
		{
			// Set the created time, token, and hash of the user agent
			$this->data['created'] = $this->now;
			$this->data['user_agent'] = sha1(Kohana::$user_agent);
		}

		// Create a new token each time the token is saved
		$this->data['token'] = $this->create_token();

		return parent::save();
	}

	/**
	 * Deletes all expired tokens.
	 *
	 * @return  void
	 */
	public function delete_expired()
	{
		// Delete all expired tokens
		$this->db->where('expires <', $this->now)->delete($this->table);
	}

	/**
	 * Finds a new unique token, using a loop to make sure that the token does
	 * not already exist in the database. This could potentially become an
	 * infinite loop, but the chances of that happening are very unlikely.
	 *
	 * @return  string
	 */
	protected function create_token()
	{
		while (TRUE)
		{
			// Create a random token
			$token = text::random('alnum', 32);

			// Make sure the token does not already exist
			if (count($this->db->select('id')->from($this->table_name)->where('token', $token)->get($this->table)) === 0)
			{
				// A unique token has been found
				return $token;
			}
		}
	}

} // End User Token