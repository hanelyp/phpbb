<?php
/**
*
* @copyright (c) Derky <http://www.derky.nl>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace hanelyp\jigsawcaptcha\migrations;

class tables extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		// can the confirm.code field store the full jigsaw code?
		global $db, $user;
		$ok = false;
		// generate a dummy code of max suggested size
		$code = '';
		for ($i = 0; $i < 25; $i++)
		{
			$code .= $i.',';
		}
		$confirm_id = hash('md5', unique_id($user->ip));
		$seed = hexdec(substr(unique_id(), 4, 10));
		$solved = 0;
		// compute $seed % 0x7fffffff
		$seed -= 0x7fffffff * floor($this->seed / 0x7fffffff);

		$sql = 'INSERT INTO ' . CONFIRM_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'confirm_id'	=> (string) $this->confirm_id,
				'session_id'	=> (string) $user->session_id,
				'confirm_type'	=> (int) $this->type,
				'code'			=> (string) $this->code,
				'seed'			=> (int) $this->seed)
		);
		$db->sql_query($sql);
		
		$sql = 'SELECT *
			FROM ' . CONFIRM_TABLE . '
			WHERE 
				confirm_id = \'' . $db->sql_escape($this->confirm_id) . '\'
				AND session_id = \'' . $db->sql_escape($user->session_id) . '\'';
		$result = $db->sql_query($sql);

		if ($row = $db->sql_fetchrow($result))
		{
			if ($code == $row['code'])
			{
				$ok = true;
			}
			$sql = 'DELETE FROM ' . CONFIRM_TABLE . '
				WHERE session_id = \'' . $db->sql_escape($user->session_id) . '\'';
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);
		
		return $ok;
	}

	public function update_schema()
	{
		return array(
			'change_columns'	=> array(
				CONFIRM_TABLE => array(
					'code'	=> array('VCHAR:75', '')
				)
			)
		);
	}
/*	
	public function revert_schema()
	{
		return array(
			'drop_tables'		=> array(
				$this->table_prefix . 'sortables_questions',
				$this->table_prefix . 'sortables_answers',
				$this->table_prefix . 'sortables_confirm',
			),
		);
	}
*/
}
