<?php

namespace hanelyp\fancydice\migrations;

class acp extends \phpbb\db\migration\migration
{
	// needed to prevent an activation crash, even if it does nothing.
	public function effectively_installed()
	{
		global $config;
		return isset($config['fancyDiceMacro_1']);	//true;
	}
	
	public function update_data()
	{
		echo "fetching update data<br />\n";
		return array(
			//array('config.add', array('acme_demo_goodbye', 0)),
			array('config.add',
				array('fancyDiceMacro_1', json_encode(array('d'=>'@[1_>]')))
			),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_FANCYDICE_MACROS'
			)),
			array('module.add', array(
				'acp',
				'ACP_FANCYDICE_MACROS',
				array(
					'module_basename' => '\hanelyp\fancydice\acp\main_module',
					'modes' => array('settings'),
				),
			)),
		);
	}
}
