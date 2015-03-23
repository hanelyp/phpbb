<?php
/**
*
* @package phpBB Extension - Acme Demo
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace acme\demo\acp;

class main_module
{
	var $u_action;

	// read config into handy array
	function get_macros()
	{
		global $config;
		$macros = false;
		if (isset($config['fancyDiceMacro_1']))
		{
			$macros = array();
			$i = 1;
			while (isset($config['fancyDiceMacro_'.$i]))
			{
				$macro = json_decode($config['fancyDiceMacro_'.$i]);
				foreach ($macro as $key=>$value)
				{
					$macros[] = array('index'=>$i, 'name'=>$key, 'def'=>$value);
				}
				$i++;
			}
		}
		return $macros;
	}

	function set_macros()
	{
		global $config, $request;
		$i = 1;
		while ($name = $request->variable('macroName_'.$i)
		{
			$def = $request->variable('macroDef_'.$i);
			$dice = new \hanelyp\fancydice\fancydice(false, $def);
			if (!$dice)
			{
				trigger_error($user->lang('ACP_FANCYDICE_DEF_ERROR').$name.'=>'.$def . adm_back_link($this->u_action));
			}
			$config->set('fancyDiceMacro_'.$i, json_encode(array($name=>$def)));
			$i++;
		}
	}

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$user->add_lang('acp/common');
		$this->tpl_name = 'fancydice_body';
		$this->page_title = $user->lang('ACP_FANCYDICE_TITLE');
		add_form_key('hanelyp/fancydice');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('hanelyp/fancydice'))
			{
				trigger_error('FORM_INVALID');
			}

			$this->set_macros();

			trigger_error($user->lang('ACP_FANCYDICE_SETTING_SAVED') . adm_back_link($this->u_action));
		}

		$macros = $this->get_macros();
		
		$i = 1;
		foreach ($macros as $macro)
		{
			//$macro['index'] = $i; 
			$template->assign_block_vars('macro', $macro );
			$i++;
		}
		$template->assign_vars(array(
			'U_ACTION'				=> $this->u_action,
			'U_NEXTINDEX'			=> $i,
		));
	}
}
