<?php
/**
* @version $Id: bad.php,v 1.2 2006/02/26 13:20:44 harryf Exp $
* @package utf8
*/

/**
* @param string
* @return mixed integer byte index or FALSE if no bad found
* @package utf8
*/
function utf8_bad_find($str)
{
	$UTF8_BAD =
		'([\x00-\x7F]'.                          # ASCII (including control chars)
		'|[\xC2-\xDF][\x80-\xBF]'.               # non-overlong 2-byte
		'|\xE0[\xA0-\xBF][\x80-\xBF]'.           # excluding overlongs
		'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.    # straight 3-byte
		'|\xED[\x80-\x9F][\x80-\xBF]'.           # excluding surrogates
		'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.        # planes 1-3
		'|[\xF1-\xF3][\x80-\xBF]{3}'.            # planes 4-15
		'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.        # plane 16
		'|(.{1}))';                              # invalid byte
	$pos = 0;
	$badList = array();
	while (preg_match('/'.$UTF8_BAD.'/S', $str, $matches))
	{
		$bytes = strlen($matches[0]);
		if ( isset($matches[2]))
			return $pos;
		$pos += $bytes;
		$str = substr($str,$bytes);
	}
	return false;
}

/**
* @param string
* @return mixed array of integers or FALSE if no bad found
* @package utf8
*/
function utf8_bad_findall($str)
{
	$UTF8_BAD =
		'([\x00-\x7F]'.                          # ASCII (including control chars)
		'|[\xC2-\xDF][\x80-\xBF]'.               # non-overlong 2-byte
		'|\xE0[\xA0-\xBF][\x80-\xBF]'.           # excluding overlongs
		'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.    # straight 3-byte
		'|\xED[\x80-\x9F][\x80-\xBF]'.           # excluding surrogates
		'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.        # planes 1-3
		'|[\xF1-\xF3][\x80-\xBF]{3}'.            # planes 4-15
		'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.        # plane 16
		'|(.{1}))';                              # invalid byte
	$pos = 0;
	$badList = array();
	while (preg_match('/'.$UTF8_BAD.'/S', $str, $matches))
	{
		$bytes = strlen($matches[0]);
		if ( isset($matches[2]))
			$badList[] = $pos;
		$pos += $bytes;
		$str = substr($str, $bytes);
	}
	if (count($badList) > 0)
		return $badList;
	return FALSE;
}

/**
* @param string
* @return string
* @package utf8
*/
function utf8_bad_strip($str)
{
	$UTF8_BAD =
		'([\x00-\x7F]'.                          # ASCII (including control chars)
		'|[\xC2-\xDF][\x80-\xBF]'.               # non-overlong 2-byte
		'|\xE0[\xA0-\xBF][\x80-\xBF]'.           # excluding overlongs
		'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.    # straight 3-byte
		'|\xED[\x80-\x9F][\x80-\xBF]'.           # excluding surrogates
		'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.        # planes 1-3
		'|[\xF1-\xF3][\x80-\xBF]{3}'.            # planes 4-15
		'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.        # plane 16
		'|(.{1}))';                              # invalid byte
	ob_start();
	while (preg_match('/'.$UTF8_BAD.'/S', $str, $matches))
	{
		if (!isset($matches[2]))
			echo $matches[0];
		$str = substr($str,strlen($matches[0]));
	}
	$result = ob_get_contents();
	ob_end_clean();
	return $result;
}

/**
* @param string to search
* @param string to replace bad bytes with (defaults to '?') - use ASCII
* @return string
* @package utf8
*/
function utf8_bad_replace($str, $replace = '?')
{
	$UTF8_BAD =
	'([\x00-\x7F]'.                          # ASCII (including control chars)
	'|[\xC2-\xDF][\x80-\xBF]'.               # non-overlong 2-byte
	'|\xE0[\xA0-\xBF][\x80-\xBF]'.           # excluding overlongs
	'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.    # straight 3-byte
	'|\xED[\x80-\x9F][\x80-\xBF]'.           # excluding surrogates
	'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.        # planes 1-3
	'|[\xF1-\xF3][\x80-\xBF]{3}'.            # planes 4-15
	'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.        # plane 16
	'|(.{1}))';                              # invalid byte
	ob_start();
	while (preg_match('/'.$UTF8_BAD.'/S', $str, $matches))
	{
		if ( !isset($matches[2]))
			echo $matches[0];
		else
			echo $replace;
		$str = substr($str,strlen($matches[0]));
	}
	$result = ob_get_contents();
	ob_end_clean();
	return $result;
}

/**
* @package utf8
*/
define('UTF8_BAD_5OCTET',1);
define('UTF8_BAD_6OCTET',2);
define('UTF8_BAD_SEQID',3);
define('UTF8_BAD_NONSHORT',4);
define('UTF8_BAD_SURROGATE',5);
define('UTF8_BAD_UNIOUTRANGE',6);
define('UTF8_BAD_SEQINCOMPLETE',7);

//--------------------------------------------------------------------
/**
* Reports on the type of bad byte found in a UTF-8 string. Returns a
* status code on the first bad byte found
* @author <hsivonen@iki.fi>
* @param string UTF-8 encoded string
* @return mixed integer constant describing problem or FALSE if valid UTF-8
* @package utf8
*/
function utf8_bad_identify($str, &$i)
{
	$mState = 0;
	$mUcs4  = 0;
	$mBytes = 1;
	$len = strlen($str);
	for($i = 0; $i < $len; $i++)
	{
		$in = ord($str{$i});
		if ( $mState == 0)
		{
			if (0 == (0x80 & ($in)))
				$mBytes = 1;
			else if (0xC0 == (0xE0 & ($in)))
			{
				$mUcs4 = ($in);
				$mUcs4 = ($mUcs4 & 0x1F) << 6;
				$mState = 1;
				$mBytes = 2;
			}
			else if (0xE0 == (0xF0 & ($in)))
			{
				$mUcs4 = ($in);
				$mUcs4 = ($mUcs4 & 0x0F) << 12;
				$mState = 2;
				$mBytes = 3;
			}
			else if (0xF0 == (0xF8 & ($in)))
			{
				$mUcs4 = ($in);
				$mUcs4 = ($mUcs4 & 0x07) << 18;
				$mState = 3;
				$mBytes = 4;
			}
			else if (0xF8 == (0xFC & ($in)))
				return UTF8_BAD_5OCTET;
			else if (0xFC == (0xFE & ($in)))
				return UTF8_BAD_6OCTET;
			else
				return UTF8_BAD_SEQID;
		}
		else
		{
			if (0x80 == (0xC0 & ($in)))
			{
				$shift = ($mState - 1) * 6;
				$tmp = $in;
				$tmp = ($tmp & 0x0000003F) << $shift;
				$mUcs4 |= $tmp;
				if (0 == --$mState)
				{
					if (((2 == $mBytes) && ($mUcs4 < 0x0080)) || ((3 == $mBytes) && ($mUcs4 < 0x0800)) || ((4 == $mBytes) && ($mUcs4 < 0x10000)))
                        			return UTF8_BAD_NONSHORT;
					else if (($mUcs4 & 0xFFFFF800) == 0xD800)
						return UTF8_BAD_SURROGATE;
					else if ($mUcs4 > 0x10FFFF)
						return UTF8_BAD_UNIOUTRANGE;
					$mState = 0;
					$mUcs4  = 0;
					$mBytes = 1;
				}
			}
			else
			{
				$i--;
				return UTF8_BAD_SEQINCOMPLETE;
			}
		}
	}
	if ($mState != 0)
	{
		$i--;
		return UTF8_BAD_SEQINCOMPLETE;
	}
	$i = null;
	return false;
}

/**
* Takes a return code from utf8_bad_identify() are returns a message
* (in English) explaining what the problem is.
* @param int return code from utf8_bad_identify
* @return mixed string message or FALSE if return code unknown
* @package utf8
*/
function utf8_bad_explain($code)
{
	switch ($code)
	{
		case UTF8_BAD_5OCTET:
			return 'Five octet sequences are valid UTF-8 but are not supported by Unicode';
		break;
		case UTF8_BAD_6OCTET:
			return 'Six octet sequences are valid UTF-8 but are not supported by Unicode';
		break;
		case UTF8_BAD_SEQID:
			return 'Invalid octet for use as start of multi-byte UTF-8 sequence';
		break;
		case UTF8_BAD_NONSHORT:
			return 'From Unicode 3.1, non-shortest form is illegal';
		break;
		case UTF8_BAD_SURROGATE:
			return 'From Unicode 3.2, surrogate characters are illegal';
		break;
		case UTF8_BAD_UNIOUTRANGE:
			return 'Codepoints outside the Unicode range are illegal';
		break;
		case UTF8_BAD_SEQINCOMPLETE:
			return 'Incomplete multi-octet sequence';
		break;
	}
	trigger_error('Unknown error code: '.$code,E_USER_WARNING);
	return FALSE; 
}
