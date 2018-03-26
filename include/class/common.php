<?php
/**
 * Общие классы используемые на форуме.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */

$return = ($hook = get_hook('cls_fl_js_helper_start')) ? eval($hook) : null;
if ($return != null)
	return;

$js = array(
'jquery'		=> 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js',
'tooltip'		=> $base_url.'/js/jquery.tooltip.js',
'pstrength'		=> $base_url.'/js/jquery.pstrength.js',
'cookies'		=> $base_url.'/js/jquery.cookie.js',
);

($hook = get_hook('cls_fl_pre_class_js_helper')) ? eval($hook) : null;

// jsHelper by hcs
class jsHelper
{
	var $jsFile = array();
	var $jsCode = array();
	
	function jsHelper()
	{
	}

	function addFile($paths)
	{
		if (!is_array($paths))
		{
			if (!in_array($paths, $this->jsFile))
				$this->jsFile[] = $paths;
		}
		else
		{
			foreach ($paths as $path_num => $path)
				if (!in_array($paths, $this->jsFile))
					$this->jsFile[] = $path;
		}
	}
	function addCode($code)
	{
		if (!in_array($code, $this->jsCode))
			$this->jsCode[] = $code;
	}
	function headerOut()
	{
		$str = '';
		foreach ($this->jsFile as $file)
			$str .= '<script type="text/javascript" src="'.$file.'"></script>'."\n";
		foreach ($this->jsCode as $code)
			$str .= '<script type="text/javascript">'.$code.'</script>'."\n";
	
		return $str;
	}

}

($hook = get_hook('cls_fl_js_helper_end')) ? eval($hook) : null;

$forum_js = new jsHelper();
