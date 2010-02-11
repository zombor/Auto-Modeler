<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Auto_Modeler Auth driver.
 *
 * @package     Auth
 * @package     Auto_Modeler
 * @author      Jeremy Bush
 * @copyright   (c) 2008 Jeremy Bush
 * @license     http://www.opensource.org/licenses/isc-license.txt
 */
class Auth_AutoModelerORM_Driver extends Auth_Driver {

	protected $config;

	// Session library
	protected $session;

	/**
	 * Constructor. Loads the Session instance.
	 *
	 * @return  void
	 */
	public function __construct(array $config)
	{
		// Load config
		$this->config = $config;

		// Load libraries
		$this->session = Session::instance();
	}

	public function logged_in($role)
	{
		$status = FALSE;

		// Checks if a user is logged in and valid
		if ( ! empty($_SESSION['auth_user']) AND is_object($_SESSION['auth_user'])
			AND ($_SESSION['auth_user'] instanceof User_Model) AND $_SESSION['auth_user']->id > 0)
		{
			// Everything is okay so far
			$status = TRUE;

			if ( ! empty($role))
			{
				// Check that the user has the given role
				$status = $_SESSION['auth_user']->has('role', $role);
			}
		}

		return $status;
	}

	public function login($user, $password, $remember)
	{
		if ( ! is_object($user))
		{
			// Load the user
			$user = Auto_Modeler_ORM::factory('user', $user);
		}

		// If the passwords match, perform a login
		if ($user->has('role', 'login') AND $user->password === $password)
		{
			if ($remember)
			{
				// Create a new autologin token
				$token = Auto_Modeler_ORM::factory('user_token');

				// Set token data
				$token->user_id = $user->id;
				$token->expires = time() + $this->config['lifetime'];
				$token->save();

				// Set the autologin cookie
				cookie::set('authautologin', $token->token, $this->config['lifetime']);
			}

			// Finish the login
			$this->complete_login($user);

			return TRUE;
		}

		// Login failed
		return FALSE;
	}

	public function force_login($user)
	{
		if ( ! is_object($user))
		{
			// Load the user
			$user = Auto_Modeler_ORM::factory('user', $user);
		}

		// Mark the session as forced, to prevent users from changing account information
		$_SESSION['auth_forced'] = TRUE;

		// Run the standard completion
		$this->complete_login($user);
	}

	public function auto_login()
	{
		if ($token = cookie::get('authautologin'))
		{
			// Load the token and user
			$token = Auto_Modeler_ORM::factory('user_token', $token);

			if ($token->id AND $token->user->id)
			{
				if ($token->user_agent === sha1(Kohana::$user_agent))
				{
					// Save the token to create a new unique token
					$token->save();

					// Set the new token
					cookie::set('authautologin', $token->token, $token->expires - time());

					// Complete the login with the found data
					$this->complete_login($token->user);

					// Automatic login was successful
					return TRUE;
				}

				// Token is invalid
				$token->delete();
			}
		}

		return FALSE;
	}

	public function logout($destroy)
	{
		// Delete the autologin cookie if it exists
		cookie::get('authautologin') and cookie::delete('authautologin');

		if ($destroy === TRUE)
		{
			// Destroy the session completely
			Session::instance()->destroy();
		}
		else
		{
			// Remove the user object from the session
			unset($_SESSION['auth_user']);

			// Regenerate session_id
			$this->session->regenerate();
		}

		// Double check
		return ! isset($_SESSION['auth_user']);
	}

	public function password($user)
	{
		if ( ! is_object($user))
		{
			// Load the user
			$user = Auto_Modeler_ORM::factory('user', $user);
		}

		return $user->password;
	}

	/**
	 * Complete the login for a user by incrementing the logins and setting
	 * session data: user_id, username, roles
	 *
	 * @param   object   user model object
	 * @return  void
	 */
	protected function complete_login(User_Model $user)
	{
		// Update the number of logins
		$user->logins += 1;

		// Set the last login date
		$user->last_login = time();

		// Save the user
		$user->save();

		// Regenerate session_id
		$this->session->regenerate();

		// Store session data
		$_SESSION['auth_user'] = $user;
	}

} // End Auth_Auto_Modeler_ORM_Driver Class