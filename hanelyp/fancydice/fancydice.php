<?php
//fancydice.php

namespace hanelyp\fancydice;

class fancydice
{
/*
=pod

=head1 NAME

fancydice - scriptable dice

=head1 SYNOPSIS

roll dice defined by a specialty scripting language

=head1 USAGE

	require ("fancydice.php");
	// macro 'd' to implement common dice notation
	$macros = array('d' => '@[1_>]' );
	$fancydice = new fancydice($macros);
	
	$roll = $fancydice->roll("3d6");
	// $roll contains 3 random #s in 1..6
	$total = $fancydice->sum($roll);
=cut

=head1 Etc

Derived from fancydice.pm
Extended syntax version

Copyright (c)2008-2015 Peter Hanely. All rights reserved.
This program is free software; you can redistribute it and/or
modify it under the terms of GPL2 or later.

=head1 LANGUAGE

dice spec examples
	3d6			Roll 3 6-sided dice
	1d20+12		Roll 1 20-sided die and add 12
The dice spec cannot have spaces in it.	You can roll multiple specs
at once by separating them with spaces.
The spec consists of the following tokens:

	{number}	A literal number
		Example:	13

	{string}	Text enclosed in quotes
		A string retains independent identity across adds, not
		supported for -, *, and /
		Example:	"**"

	{alpha}		A word containing only letters
		is a macro, which can be a full or partial
		dice spec.	Use #define to create macros.
		Example:		fire
		Resolves to:	1d4+1

	(spec)		A spec inside parenthesis will be evaluated to a single
		number before other tokens are evaluated.
		Example:		(1,2)
		Resolves to:	3

	_		This will evaluate to a list of numbers, from the
		preceding to the following, inclusive.
		Example:		1_5
		Resolves to:	1,2,3,4,5

	[spec]		A token will be chosen at random from within this spec.
		Example:		[1,2,3]
		Resolves to:	one of:	1, 2, 3

	@		Repeats the following token a number of times equal to
		the previous token.	following token should be {number}
		(spec) or [spec].
		Example:		3@5
		Resolves to:	5,5,5

	+ -		These only effect the sign of the following number.
		Examples1:		+3	Example2:	-5
		Resolves to:	3				-5

	* /		These perform multiplication and division.
		Example1:		6*2	Example2:	9/3
		Resolves to:	12				3

	>		This may only be used in macros, and lets the
		macro pull in the following token.
		Example:	The macro 'd' is defined as '@[1_>]'
			so 3d8 resolves to 3@[1_8], which resolves to
			[1_8][1_8][1_8] which might resolve to 3,7,4.

	Other	Any other symbol is discarded,	but serves to
		separate tokens
		Example:		3,2		The comma is discarded,
		Resolves as:	3,2		but prevents it from resolving as 32.
=cut
*/

	public static $VERSION = "2015.0311";
	public static $default_macros = array(
		'd' => '@[1_>]',
		'f' => '@[-10,2_(>-1),(>+10)]' // house rules, critical success/fail as -10, max+10
	);
	
	public $debug = 0;
	public $macros;
	public $randval;
	
	function __construct($_macros = false, $seed = 0)
	{
		if (!$seed)
		{
			$seed = time();
		}
		if (!$_macros)
		{
			$_macros = self::$default_macros;
		}
		$this->macros = $_macros;
		$this->randval = $seed;
	}

	// not the best random number generator, but insures repeatable results with a supplied seed
	// and allows seed to be reset or per generator instance without messing up anything else
	function rand($min = 0, $max = 0)
	{
		// based on ANSI C LCG algorithm
		$this->randval = ($this->randval*1103515245 + 12345)%(1<<31);
		$r = ($this->randval >> 16);
		return $min + $r%($max-$min+1);
	}
	
	function sum($vals)
	{
		$sum = '';
		$strings = array();
		while (count($vals))
		{
			$val = array_shift($vals);
			if (preg_match('/[^\d]/', $val))
			{
				array_push ($strings, $val);
			}
			else
			{
				$sum += $val;
			}
		}
		if (count($strings))
		{
			return $sum.' '.join(' ', $strings);
		}
		return 1*$sum;
	}

	function selection($list)
	{
		if ($this->debug)
		{
			echo ("select from ".join (',', $list)."\n");
		}
		return $list[$this->rand(0,count($list)-1)];
	}

	function parsetoken($roll)
	{
		if ($roll == '')
		{
			return array('', '', ']');
		}
		
		if (preg_match('/^(\d+)(.*)/', $roll, $matches)) # number
		{
			return array($matches[1], $matches[2], '#');
		}

		if (preg_match('/^"([^"]*)"(.*)/', $roll, $matches)) # string
		{
			return array($matches[1], $matches[2], '"');
		}
		 
		if (preg_match('/^([a-zA-Z]+)(.*)/', $roll, $matches)) # macro
		{
			return array($matches[1], $matches[2], 'm');
		}

		if (preg_match('/^([\_\@\-\*\/])(.*)/', $roll, $matches) ) # _, @, -, *, /
		{
			return array($matches[1], $matches[2], $matches[1]);
		}

		if (preg_match('/^(\()(.*)/', $roll, $matches) ) # sum of...
		{
			$stack = array();
			$t = $this->parse($matches[2], $stack);
			$list = $t[0];
			$roll = $t[1];
			$token = $this->sum($list);
			return array($token, $roll, '#');
		}

		if (preg_match('/^(\[)(.*)/', $roll, $matches) ) # selection of...
		{
			$stack = array();
			$t = $this->parse($matches[2], $stack);
			$list = $t[0];
			$roll = $t[1];
			$token = $this->selection($list);
			if (preg_match('/[^\d]/', $token))
			{
				$type = '"';
			}
			else
			{
				$type = '#';
			}
			return array($token, $roll, $type);
		}

		if (preg_match('/^([\]\)])(.*)/', $roll, $matches))# end tags
		{
			return array($matches[1], $matches[2], ']');
		}

	// else
		preg_match('/(.)(.*)/', $roll, $matches);
		return array($matches[1], $matches[2], $matches[1]);
	}

	function parse($roll, &$rolll)
	{
		$sign = 1;
		$endtoken = false;

		while ((isset($roll)) && (!$endtoken))
		{
			$t = $this->parsetoken($roll);
			$token = $t[0];
			$roll = $t[1];
			$type = $t[2];
			if ($this->debug)
			{
				echo (join (',', $rolll). ": $token, $roll, $type\n");
			}

			if (($type == '#') || ($type == '['))
			{
				array_push($rolll, $sign*$token);
				$sign = 1;
			}
			if ($type == '"')
			{
				array_push($rolll, $token);
				$sign = 1;
			}

			elseif ($type == 'm')
			{
				if (isset($this->macros[strtolower($token)]))
				{
					$macro = $this->macros[strtolower($token)];
					if ($this->debug)
					{
						echo "macro $token => $macro\n";
					}
					if (preg_match('/>/', $macro))
					{
						$t = $this->parsetoken($roll);
						$token = $t[0];
						$roll = $t[1];
						$macro = preg_replace('/>/', $token, $macro);
						if ($this->debug)
						{
							echo "macro $token => $macro\n";
						}
					}
					$l = $this->parse($macro, $rolll);
					$list = $l[0];
				}
				else
				{
					$list = $token;	
				}
				if ($this->debug)
				{
					echo (join (',', $rolll). "\n");
					print_r($list);
					echo ("result => ".join (',', $list). "\n");
				}
			}

			elseif ($type == '_')
			{
				$t = $this->parsetoken($roll);
				$token = $t[0];
				$roll = $t[1];
				$last = array_pop($rolll);
				if (!preg_match('/\d/', $token))
				{
					$token = 0;
				}
				if ($last > $token)
				{
					$i = $last; $last = $token; $token = $i;
				}
				for ($i = $last; $i <= $token; $i++)
				{
					array_push($rolll, $i);
				}
			}

			elseif ($type == '@')
			{
				$last = array_pop($rolll);
				$last = $last?$last:1;
				for ($i = 0; $i < $last; $i++)
				{
					if ($this->debug)
					{
						echo "running $roll\n";
					}
					$t = $this->parsetoken($roll);
					$token = $t[0];
					$next = $t[1];
					$ntype = $t[2];

					if ($this->debug)
					{
						echo "type $ntype\n";
					}
					if ($ntype == '#')
					{
						array_push($rolll, $token*$sign);
					}
					else
					{
						array_push($rolll, $token);
					}
					if ($this->debug)
					{
						echo "token $token\n";
						echo ("repeat $i ".join (',', $rolll). ": @\n");
					}
				}
				$sign = 1;
				$roll = $next;
			}

			elseif ($type == '-')
			{
				$sign = -1;
			}

			elseif ($type == '*')
			{
				$last = array_pop($rolll);
				$t = $this->parsetoken($roll);
				$token = $t[0];
				$next = $t[1];
				$ntype = $t[2];
				array_push($rolll, $last*$token);
				$roll = $next;
			}

			elseif ($type == '/')
			{
				$last = array_pop($rolll);
				$t = $this->parsetoken($roll);
				$token = $t[0];
				$next = $t[1];
				$ntype = $t[2];
				array_push($rolll, intval($last/$token));
				$roll = $next;
			}

			elseif ($type == ']')
			{
				$endtoken = true;
			}
		}
		return array($rolll, $roll);
	}

	function roll($roll)
	{
		$stack = array();
		//echo $roll, '<br />';
		$l = $this->parse($roll, $stack);
		return $l[0];
	}
}

