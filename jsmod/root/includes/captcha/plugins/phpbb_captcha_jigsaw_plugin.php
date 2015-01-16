<?php
/**
*
* @package VC
* @version $Id: phpbb_captcha_jigsaw_plugin.php $
* @copyright (c) 2015 Peter Hanely
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2 or later
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
if (!class_exists('phpbb_more_abstract_captcha'))
{
	require($phpbb_root_path . 'includes/captcha/plugins/captcha_more_abstract.' . $phpEx);
}

/**
* @package VC
*/
class phpbb_captcha_jigsaw extends phpbb_more_abstract_captcha
{
	var $captcha_vars = array(
		'captcha_puzzle_x'	=>	'CAPTCHA_PUZZLE_X',
		'captcha_puzzle_y'	=>	'CAPTCHA_PUZZLE_Y',
		'captcha_puzzle_img_x'	=>	'CAPTCHA_PUZZLE_IMG_X',
		'captcha_puzzle_img_y'	=>	'CAPTCHA_PUZZLE_IMG_Y',
		'captcha_puzzle_img_path' =>	'CAPTCHA_PUZZLE_IMG_PATH',
		'captcha_puzzle_resize'	=>	'CAPTCHA_PUZZLE_RESIZE',
	);

	var $defaults = array(
		'captcha_puzzle_x'	=>	3,
		'captcha_puzzle_y'	=>	3,
		'captcha_puzzle_img_x'	=>	100,
		'captcha_puzzle_img_y'	=>	75,
		'captcha_puzzle_img_path' =>	'images/',
		'captcha_puzzle_resize'	=>	0
	);
	
	var $template = 'captcha_jigsaw.html';
	
	function init($type)
	{
		global $config, $db, $user;
		parent::init($type);
		$user->add_lang('captcha_jigsaw');
	}
	
	// Generate a key suitable for a sum of jigsaw.
	function genKey()
	{
		//echo 'generating key ...<br>\n';
		//debug_print_backtrace ();
		global $config;
		$places = array();
		$count = $config['captcha_puzzle_x']*$config['captcha_puzzle_y'];
		//echo "$count : ";
		for ($i = 0; $i < $count; $i++)
		{	$places[] = $i;	}
		for ($i = 0; $i < $count; $i++)
		{
			$j = rand($i, $count-1);
			$a = $places[$i];	$places[$i] = $places[$j];	$places[$j] = $a;
		}

		return join(',', $places);
	}
	
	// render the image
	function renderImage($code, $seed)
	{
		global $config, $phpbb_root_path;
		
		$solution = explode(',', $code);
		$img = imagecreatetruecolor(
			$config['captcha_puzzle_x'] * $config['captcha_puzzle_img_x'],
			$config['captcha_puzzle_y'] * $config['captcha_puzzle_img_y']);

		// select an image at random from the configured folder
		if (!file_exists($config['captcha_puzzle_img_path']))	// should never happen unless config is invalid.
		{
			return false;
		}
		$filenames = array_values(preg_grep('/.*\.png$/', scandir($config['captcha_puzzle_img_path'])));
		$name = $filenames[ $seed % sizeof($filenames) ];

		list($width, $height, $type, $attr) = getimagesize($config['captcha_puzzle_img_path'].$name);
		$source_image = imagecreatefrompng($config['captcha_puzzle_img_path'].$name);
		if (!$source_image)
		{
			return false;
		}		

		// rescale to configured size
	// php 5 code, enabled by config
		if ($config['captcha_puzzle_resize'])
		{
			$scale_x = $width / ($config['captcha_puzzle_x'] * $config['captcha_puzzle_img_x']);
			$scale_y = $height / ($config['captcha_puzzle_y'] * $config['captcha_puzzle_img_y']);
			if (($scale_x == 1) && ($scale_y >= 1) || ($scale_x >= 1) && ($scale_y == 1))
			{
				// do nothing, image is already as close as possible without changing aspect ratio
			}
			else if ($scale_x < $scale_y)
			{
				$width = $config['captcha_puzzle_x'] * $config['captcha_puzzle_img_x'];
				$height = $config['captcha_puzzle_y'] * $config['captcha_puzzle_img_y'] * $scale_y / $scale_x;
				$temp = imagescale($source_image, $width, $height);
				imagedestroy($source_image);
				$source_image = $temp;
				
			}
			else
			{
				$width = $config['captcha_puzzle_x'] * $config['captcha_puzzle_img_x'] * $scale_x / $scale_y;
				$height = $config['captcha_puzzle_y'] * $config['captcha_puzzle_img_y'];
				$temp = imagescale($source_image, $width, $height);
				imagedestroy($source_image);
				$source_image = $temp;
			}
		}
// */		
		// offset to center puzzle in image
		$xoff = ($width - $config['captcha_puzzle_x'] * $config['captcha_puzzle_img_x']) / 2;
		$yoff = ($height - $config['captcha_puzzle_y'] * $config['captcha_puzzle_img_y']) / 2;

		for ($i = 0; $i < $config['captcha_puzzle_x']; $i++)
		{
			for ($j = 0; $j < $config['captcha_puzzle_y']; $j++)
			{
				// where to place this tile?
				$source_index = $i + $j * $config['captcha_puzzle_x'];

				$dest_x = intval($solution[$source_index] % $config['captcha_puzzle_x']);
				$dest_y = intval($solution[$source_index] / $config['captcha_puzzle_x']);

				// image copied with 1 pixel margin border
				imagecopy($img, $source_image,
					$dest_x * $config['captcha_puzzle_img_x'] + 1, $dest_y * $config['captcha_puzzle_img_y'] + 1,
					$xoff + $i * $config['captcha_puzzle_img_x'], $yoff + $j * $config['captcha_puzzle_img_y'],
						$config['captcha_puzzle_img_x'] - 2,	$config['captcha_puzzle_img_y'] - 2);
			}
		}

		// Send image
		header('Content-Type: image/png');
		header('Cache-control: no-cache, no-store');
		imagepng($img);
		imagedestroy($img);
		imagedestroy($source_image);
	}
	
	function &get_instance()
	{
		//$instance =& new phpbb_captcha_jigsaw();
		$instance = new phpbb_captcha_jigsaw();
		return $instance;
	}
	
	function get_name()
	{
		return 'CAPTCHA_JIGSAW';
	}

	function get_class_name()
	{
		return 'phpbb_captcha_jigsaw';
	}
	
	function is_available()
	{
		global $config;
		// validate that image path is reasonable
		if (!file_exists($config['captcha_puzzle_img_path']))
		{
			return false;
		}
		$filenames = array_values(preg_grep('/.*\.png$/', scandir($config['captcha_puzzle_img_path'])));
		return (count($filenames)>0);
	}
	
	function get_template()
	{
		if ($this->is_solved())
		{
			return false;
		}
		global $template, $config, $phpEx, $phpbb_root_path;
		
		// format URL for image without coding that garbles with CSS
		$link = append_sid($phpbb_root_path . 'ucp.' . $phpEx, 'mode=confirm&confirm_id=' . $this->confirm_id . '&type=' . $this->type);
		$template->assign_var('CONFIRM_IMAGE_LINK_NOCODE',	$link);
		
		// pass in configured size info
		$template->assign_var('CAPTCHA_PUZZLE_WIDTH', (int) $config['captcha_puzzle_x'] * $config['captcha_puzzle_img_x']);
		$template->assign_var('CAPTCHA_PUZZLE_HEIGHT', (int) $config['captcha_puzzle_y'] * $config['captcha_puzzle_img_y']);

		foreach ($this->captcha_vars as $captcha_var => $template_var)
		{
			$var = $config[$captcha_var];
			$template->assign_var($template_var, $var);
		}
		parent::get_template();
		
		return $this->template;
	}
/*
	function get_demo_template($id)
	{
		global $config, $user, $template, $phpbb_admin_path, $phpEx;
		parent::get_demo_template($id);
		return 'captcha_jigsaw_acp_demo.html';
	}
// */	
	function has_config()
	{
		return true;
	}

	// edited from ancestor class to fix handling which is badly botched with this classes settings
	function get_demo_template($id)
	{
		global $config, $user, $template, $phpbb_admin_path, $phpEx;

		$variables = '';

		if (is_array($this->captcha_vars))
		{
			foreach ($this->captcha_vars as $captcha_var => $template_var)
			{
				$variables .= '&amp;' . rawurlencode($captcha_var) . '=' . rawurlencode(request_var($captcha_var, $config[$captcha_var]));
			}
		}

		// acp_captcha has a delivery function; let's use it
		$template->assign_vars(array(
			'CONFIRM_IMAGE'		=> append_sid($phpbb_admin_path . 'index.' . $phpEx, 'captcha_demo=1&amp;mode=visual&amp;i=' . $id . '&amp;select_captcha=' . $this->get_class_name()) . $variables,
			'CONFIRM_ID'		=> $this->confirm_id,
		));

		return 'captcha_jigsaw_acp_demo.html';
	}

	function acp_page($id, &$module)
	{
		global $db, $user, $auth, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		
		if (!$this->has_config())
		{
			trigger_error($user->lang['CAPTCHA_NO_OPTIONS'] . adm_back_link($module->u_action));
		}

		//$user->add_lang('acp/board');
		$user->add_lang('captcha_jigsaw');
/*
		$config_vars = array(
			'enable_confirm'	=> 'REG_ENABLE',
			'enable_post_confirm'	=> 'POST_ENABLE',
			'confirm_refresh'	=> 'CONFIRM_REFRESH',
			'captcha_jigsaw'	=> 'CAPTCHA_JIGSAW',
		);
// */
		$module->tpl_name = 'captcha_jigsaw_acp';
		$module->page_title = 'ACP_VC_SETTINGS';
		$form_key = 'acp_captcha';
		add_form_key($form_key);

		$submit = request_var('submit', '');

		if ($submit && check_form_key($form_key))
		{
			//print_r($_REQUEST);
			$captcha_vars = array_keys($this->captcha_vars);
			foreach ($captcha_vars as $captcha_var)
			{
				$value = request_var($captcha_var, $this->defaults[$captcha_var]);
				if ($value >= 0)
				{
					set_config($captcha_var, $value);
					//echo ("$captcha_var, $value<br>\n");
				}
			}
			$path = request_var('captcha_puzzle_img_path', '');
			if (strlen($path) > 0)
			{
				set_config('captcha_puzzle_img_path', $path);
			}

			add_log('admin', 'LOG_CONFIG_VISUAL');
			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($module->u_action));
		}
		else if ( $submit )
		{
			trigger_error($user->lang['FORM_INVALID'] . adm_back_link($module->u_action));
		}
		else
		{
			foreach ( $this->captcha_vars as $captcha_var => $template_var )
			{
				if ( isset($_REQUEST[$captcha_var]))
				{
					$var = request_var($captcha_var, $this->defaults[$captcha_var]);
				}
				else
				{
					$var = $config[$captcha_var];
				}
				if ( !$var )
				{
					$var = $this->defaults[$captcha_var];
				}
				$template->assign_var($template_var, $var);
			}

			$template->assign_vars ( array(
				'CAPTCHA_PREVIEW'	=> $this->get_demo_template($id),
				'CAPTCHA_NAME'		=> $this->get_class_name(),
				'U_ACTION'		=> $module->u_action,
			));
		}
	}
}

