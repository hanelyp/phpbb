<?php
/**
*
* captcha_jigsaw [English]
*
* @package language
* @version $Id$
* @copyright (c) 2009 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'CAPTCHA_JIGSAW'		=> 'Jigsaw Puzzle',
	'CONFIRM_CODE_EXPLAIN_JIGSAW'	=> 'Unscramble jigsaw. Left-click to pick up or drop pieces',
	'CONFIRM_QUESTION_WRONG'	=> 'You have provided an invalid answer to the question.',
	'CONFIRM_EXPLAIN_JIGSAW'	=> 'Please demonstrate that you are a human.',
	'CONFIRM_ENABLE_JAVASCRIPT'	=> 'Please enable javascript to operate captcha puzzle',
	'CAPTCHA_PUZZLE_IMG_PATH'	=> 'Path to images for puzzles.',
	'CAPTCHA_PUZZLE_IMG_PATH_EXPLAIN' => 'A folder containing .png images.  This must be readable from php, but need not be in the phpbb folder structure.',
	'CAPTCHA_PUZZLE_X_GRID'		=> 'Width of puzzle.',
	'CAPTCHA_PUZZLE_X_EXPLAIN'	=> 'Size of puzzle in tiles.',
	'CAPTCHA_PUZZLE_Y_GRID'		=> 'Height of puzzle.',
	'CAPTCHA_PUZZLE_Y_EXPLAIN'	=> 'Size of puzzle in tiles.',
	'CAPTCHA_PUZZLE_IMG_X'		=> 'Height of a puzzle tile.',
	'CAPTCHA_PUZZLE_IMG_X_EXPLAIN'	=> 'in pixels.',
	'CAPTCHA_PUZZLE_IMG_Y'		=> 'Width of a puzzle tile.',
	'CAPTCHA_PUZZLE_IMG_Y_EXPLAIN'	=> 'in pixels.',
	'CAPTCHA_PUZZLE_RESIZE'		=> 'Resize images',
	'CAPTCHA_PUZZLE_RESIZE_EXPLAIN'	=> 'When generating puzzles, first resize images.  Requires php >= 5.5.0'
));
//echo "captcha_jigsaw language processed";
?>
