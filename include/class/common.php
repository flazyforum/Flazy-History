<?php
/**
 * Общие классы используемые на форуме.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


// jsHelper by hcs
class jsHelper
{
	var $jsFile = array();
	var $jsCode = array();
	
	function jsHelper()
	{
	}
	function addFile($path)
	{
		if (!in_array($path, $this->jsFile))
			$this->jsFile[] = $path;
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

$forum_js = new jsHelper();
