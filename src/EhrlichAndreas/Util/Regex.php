<?php 

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Regex
{
	
	public static function quote($str, $delimiter = '/')
	{
		$search = array
		(
			'/',
			'^',
			'.',
			'$',
			'|',
			'(',
			')',
			'[',
			']',
			'*',
			'+',
			'?',
			'{',
			'}',
			',',
		);
		
		$replace = array
		(
			'\/',
			'\^',
			'\.',
			'\$',
			'\|',
			'\(',
			'\)',
			'\[',
			'\]',
			'\*',
			'\+',
			'\?',
			'\{',
			'\}',
			'\,',
		);
		
		return str_replace($search, $replace, $str);
	}
}

