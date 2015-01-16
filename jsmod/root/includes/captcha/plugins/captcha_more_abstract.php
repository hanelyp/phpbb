<?php
/**
*
* @package VC
* @version $Id: phpbb_captcha_more_abstract.php $
* @copyright (c) 2011 Peter Hanely
* expands on captcha/plugins/captcha_abstract.php and phpbb_captcha_gd_plugin.php @copyright (c) 2006, 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Placeholder for autoload
*/
if (!class_exists('phpbb_default_captcha'))
{
	include($phpbb_root_path . 'includes/captcha/plugins/captcha_abstract.' . $phpEx);
}

/**
* @package VC
*/
class phpbb_more_abstract_captcha extends phpbb_default_captcha
{
	var $captcha_vars = array();
	var $width = 360;
	var $height = 96;
	var $template = 'captcha_default.html';
	
	// for simple custom image captchas, subclass and redefine the following methods.

	// Generate a key suitable for your captcha.
	function genKey()
	{
		return gen_rand_string_friendly(mt_rand(CAPTCHA_MIN_CHARS, CAPTCHA_MAX_CHARS));
	}
	
	// render the image
	function renderImage($code, $seed)
	{
		$captcha = new captcha();
		define('IMAGE_OUTPUT', 1);
		$captcha->execute($code, $seed);
	}
	
	/**
	* API function
	*/
	function has_config()
	{
		return false;
	}

	function get_name()
	{
		return 'CAPTCHA_MORE_ABSTRACT';
	}

	function get_class_name()
	{
		return 'phpbb_more_abstract';
	}

/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
// parent class methods modified to use abstractions above.

	function execute_demo($k = false)
	{
		//echo 'execute demo ...<br>\n';
		global $user;

		if ($k == false)
		{
			$this->code = $this->genKey();
		}
		else
		{
			$this->code = $k;
		}
		$this->seed = hexdec(substr(unique_id(), 4, 10));

		// compute $seed % 0x7fffffff
		$this->seed -= 0x7fffffff * floor($this->seed / 0x7fffffff);
		//echo "$this->code";	die();

		$this->renderImage($this->code, $this->seed);
	}

	function execute()
	{
		if (empty($this->code))
		{
			if (!$this->load_code())
			{
				// invalid request, bail out
				return false;
			}
		}
		$this->renderImage($this->code, $this->seed);
	}

	/**
	* The old way to generate code, suitable for GD and non-GD. Resets the internal state.
	*/
	function generate_code()
	{
		//echo 'generate code ...<br>\n';
		global $db, $user;

		$this->code = $this->genKey();
		//$this->confirm_id = md5(unique_id($user->ip));
		$this->confirm_id = hash('md5', unique_id($user->ip));
		$this->seed = hexdec(substr(unique_id(), 4, 10));
		$this->solved = 0;
		// compute $seed % 0x7fffffff
		$this->seed -= 0x7fffffff * floor($this->seed / 0x7fffffff);

		$sql = 'INSERT INTO ' . CONFIRM_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'confirm_id'	=> (string) $this->confirm_id,
				'session_id'	=> (string) $user->session_id,
				'confirm_type'	=> (int) $this->type,
				'code'			=> (string) $this->code,
				'seed'			=> (int) $this->seed)
		);
		$db->sql_query($sql);
	}

	/**
	* New Question, if desired.
	*/
	function regenerate_code()
	{
		//echo 'regenerate code ...<br>\n';
		global $db, $user;

		$this->code = $this->genKey();
		$this->seed = hexdec(substr(unique_id(), 4, 10));
		$this->solved = 0;
		// compute $seed % 0x7fffffff
		$this->seed -= 0x7fffffff * floor($this->seed / 0x7fffffff);

		$sql = 'UPDATE ' . CONFIRM_TABLE . ' SET ' . $db->sql_build_array('UPDATE', array(
				'code'			=> (string) $this->code,
				'seed'			=> (int) $this->seed)) . '
				WHERE
				confirm_id = \'' . $db->sql_escape($this->confirm_id) . '\'
					AND session_id = \'' . $db->sql_escape($user->session_id) . '\'';
		$db->sql_query($sql);
	}

	/**
	* New Question, if desired.
	*/
	function new_attempt()
	{
		//echo 'new attempt ...<br>\n';
		global $db, $user;

		$this->code = $this->genKey();
		$this->seed = hexdec(substr(unique_id(), 4, 10));
		$this->solved = 0;
		// compute $seed % 0x7fffffff
		$this->seed -= 0x7fffffff * floor($this->seed / 0x7fffffff);

		$sql = 'UPDATE ' . CONFIRM_TABLE . ' SET ' . $db->sql_build_array('UPDATE', array(
				'code'			=> (string) $this->code,
				'seed'			=> (int) $this->seed)) . '
				, attempts = attempts + 1
				WHERE
					confirm_id = \'' . $db->sql_escape($this->confirm_id) . '\'
					AND session_id = \'' . $db->sql_escape($user->session_id) . '\'';
		$db->sql_query($sql);
	}

	function get_template()
	{
		if ($this->is_solved())
		{
			return false;
		}
		parent::get_template();
		//echo $this->template;
		//exit;	// test if we get here
		return $this->template;
	}

}
