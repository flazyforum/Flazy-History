/**
 * ББ-коды.
 *@license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */


var form_name = 'post';
var text_name = 'req_message';
var clientVer = parseInt(navigator.appVersion); // Get browser version
var ua = navigator.userAgent.toLowerCase();
var is_ie = (ua.indexOf('msie') != -1 && ua.indexOf('opera') == -1);
var is_safari = ua.indexOf('safari') != -1;
var is_gecko = (ua.indexOf('gecko') != -1 && !is_safari);
var is_win = ((ua.indexOf('win') != -1) || (ua.indexOf('16bit') != -1));
var baseHeight;

// Apply bbcodes. Code from phpBB
function bbcode(bbopen, bbclose)
{
	theSelection = false;
	var textarea = document.forms[form_name].elements[text_name];
	textarea.focus();

	if ((clientVer >= 4) && is_ie && is_win)
	{
		theSelection = document.selection.createRange().text;
		if (theSelection)
		{
			// Add tags around selection
			document.selection.createRange().text = bbopen+theSelection+bbclose;
			document.forms[form_name].elements[text_name].focus();
			theSelection = '';
			return;
		}
	}
	else if (document.forms[form_name].elements[text_name].selectionEnd && (document.forms[form_name].elements[text_name].selectionEnd - document.forms[form_name].elements[text_name].selectionStart > 0))
	{
		mozWrap(document.forms[form_name].elements[text_name], bbopen, bbclose);
		document.forms[form_name].elements[text_name].focus();
		theSelection = '';
		return;
	}
	//The new position for the cursor after adding the bbcode
	var caret_pos = getCaretPosition(textarea).start;
	var new_pos = caret_pos+bbopen.length;
	// Open tag
	insert(bbopen+bbclose);
	// Center the cursor when we don't have a selection
	if (!isNaN(textarea.selectionStart))
	{
		textarea.selectionStart = new_pos;
		textarea.selectionEnd = new_pos;
	}
	else if (document.selection)
	{
		var range = textarea.createTextRange();
		range.move("character", new_pos);
		range.select();
		storeCaret(textarea);
	}
	textarea.focus();
	return;
}
// Insert text at position. Code from phpBB
function insert(text, spaces, popup)
{
	var textarea;
	
	if (!popup)
		textarea = document.forms[form_name].elements[text_name];
	else
		textarea = opener.document.forms[form_name].elements[text_name];
	if (spaces)
		text = ' '+text+' ';
	if (!isNaN(textarea.selectionStart))
	{
		var sel_start = textarea.selectionStart;
		var sel_end = textarea.selectionEnd;
		mozWrap(textarea, text, '')
		textarea.selectionStart = sel_start+text.length;
		textarea.selectionEnd = sel_end+text.length;
	}
	else if (textarea.createTextRange && textarea.caretPos)
	{
		if (baseHeight != textarea.caretPos.boundingHeight)
		{
			textarea.focus();
			storeCaret(textarea);
		}
		var caret_pos = textarea.caretPos;
		caret_pos.text = caret_pos.text.charAt(caret_pos.text.length - 1) == ' ' ? caret_pos.text+text+' ' : caret_pos.text+text;
	}
	else
		textarea.value = textarea.value+text;
	if (!popup)
		textarea.focus();
}
function mozWrap(txtarea, open, close)
{
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	var scrollTop = txtarea.scrollTop;

	if (selEnd == 1 || selEnd == 2)
		selEnd = selLength;

	var s1 = (txtarea.value).substring(0,selStart);
	var s2 = (txtarea.value).substring(selStart, selEnd)
	var s3 = (txtarea.value).substring(selEnd, selLength);

	txtarea.value = s1+open+s2+close+s3;
	txtarea.selectionStart = selEnd+open.length+close.length;
	txtarea.selectionEnd = txtarea.selectionStart;
	txtarea.focus();
	txtarea.scrollTop = scrollTop;
	return;
}
// Insert at Caret position.
function storeCaret(textEl)
{
	if (textEl.createTextRange)
		textEl.caretPos = document.selection.createRange().duplicate();
}
// Caret Position object.
function caretPosition()
{
	var start = null;
	var end = null;
}
// Get the caret position in an textarea.
function getCaretPosition(txtarea)
{
	var caretPos = new caretPosition();
	
	if(txtarea.selectionStart || txtarea.selectionStart == 0)
	{
		caretPos.start = txtarea.selectionStart;
		caretPos.end = txtarea.selectionEnd;
	}
	else if(document.selection)
	{
		var range = document.selection.createRange();
		var range_all = document.body.createTextRange();
		range_all.moveToElementText(txtarea);
		var sel_start;
		for (sel_start = 0; range_all.compareEndPoints('StartToStart', range) < 0; sel_start++)
			range_all.moveStart('character', 1);
	
		txtarea.sel_start = sel_start;
		caretPos.start = txtarea.sel_start;
		caretPos.end = txtarea.sel_start;
	}
	return caretPos;
}
function smile(code, popup)
{
	return insert(code, true, popup);
}
function smile_pop(desktopURL, alternateWidth, alternateHeight, noScrollbars)
{
	if ((alternateWidth && self.screen.availWidth * 0.8 < alternateWidth) || (alternateHeight && self.screen.availHeight * 0.8 < alternateHeight))
	{
		noScrollbars = false;
		alternateWidth = Math.min(alternateWidth, self.screen.availWidth * 0.8);
		alternateHeight = Math.min(alternateHeight, self.screen.availHeight * 0.8);
	}
	else
		noScrollbars = typeof(noScrollbars) != "undefined" && noScrollbars == true;

	window.open(desktopURL, 'requested_popup', 'toolbar=no,location=no,status=no,menubar=no,scrollbars='+(noScrollbars ? 'no' : 'yes')+',width='+(alternateWidth ? alternateWidth : 700)+',height='+(alternateHeight ? alternateHeight : 300)+',resizable=no');
	return false;
}
function changeVisibility(id)
{
	var obj = document.getElementById(id);
	
	if (obj == null || typeof(obj) == "undefined")
		return;
	
	var current = obj.style.display;
	var change = {
		"none":{"display": "block"},
		"block":{"display": "none"}
	}
	obj.style.display = change[current]["display"];
	return;
}
function SelectedText()
{
	var txt = '';
	var textarea = document.forms[form_name].elements[text_name];
	if (document.selection)
		txt = document.selection.createRange().text;
	else if (document.getSelection)
		txt = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
	else if (window.getSelection)
		txt = window.getSelection().toString();
	else
		return txt;
	return txt;
}
function tag(bbopen, bbclose, tag)
{
	var txt = SelectedText();
	if (txt != '')
		bbcode(bbopen, bbclose);
	else
		tag();
}
function tag_url()
{
	var enterURL = prompt("Поместите ссылку веб-страницы", "http://");
	if (!enterURL)
	{
		alert("Ошибка! Нет ссылки");
		return false;
	}
	var enterTITLE = prompt("Введите название ссылки", "Название сайта");
	if (!enterTITLE || enterTITLE == "Название сайта")
		insert('[url]'+enterURL+'[/url]');
	else
		insert('[url='+enterURL+']'+enterTITLE+'[/url]');	
}
function tag_email()
{
	var enter = prompt("Введите e-mail адрес", "");
	if (!enter)
	{
		alert("Нет E-mail'а");
		return false;
	}
	insert('[email]'+enter+'[/email]');
}
function tag_image()
{
	var image = prompt("Введите полный URL изображения", "http://");
	if (!image)
	{
		alert("Ошибка! Нет ссылки");
		return false;
	}
	var desc = prompt("Введите описание", "Описание");
	if (!desc || desc == "Описание")
		insert('[img]'+image+'[/img]');
	else
		insert('[img='+desc+']'+image+'[/img]');
}
function tag_video()
{
	var enter = prompt("Поместите ссылку на видео", "http://");
	if (!enter)
	{
		alert("Ошибка! Нет ссылки");
		return;
	}
	insert('[video]'+enter+'[/video]');
}
function tag_hide()
{
	var enter = prompt("Введите минимум сообщений для просмотра текста", "");
	if (!enter)
		bbcode('[hide]','[/hide]');
	else
		bbcode('[hide='+enter+']','[/hide]');
}
function tag_spoiler()
{
	var enter = prompt("Введите текст", "");
	if (!enter)
	{
		alert("Ошибка! Нет текста");
		return;
	}
	insert('[spoiler]'+enter+'[/spoiler]');
}
function add_handler(event, handler)
{
	if (document.addEventListener)
		document.addEventListener(event, handler, false);
	else if (document.attachEvent)
		document.attachEvent('on'+event, handler);
	else
		return false;

	return true;
}
function key_handler(e)
{
	e = e || window.event;
	var key = e.keyCode || e.which;

	if (e.ctrlKey && (is_gecko && key == 115 || !is_gecko && key == 83))
	{
		if (e.preventDefault)
			e.preventDefault();
		e.returnValue = false;
		document.post.preview.click()
		return false;
	}
	if (e.ctrlKey && (key == 13 || key == 10))
	{
		if (e.preventDefault)
			e.preventDefault();
		e.returnValue = false;
		document.post.submit.click()
		return false;
	}
}
var result = is_ie || is_safari ? add_handler("keydown", key_handler) : add_handler("keypress", key_handler);
if (result)
{
	setTimeout("document.forms.post.submit.title='Ctrl + Enter'", 500);
	setTimeout("document.forms.post.preview.title='Ctrl + S'", 500);
}