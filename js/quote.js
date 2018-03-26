/**
 * Быстрое цитирование.
 *@license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

document.onmouseup = SetSelected;

function getSelectedText()
{
	var result = '';
	if (document.selection)
		result = document.selection.createRange().text;
	else if (document.getSelection)
		result = document.getSelection();
	else if (window.getSelection)
		result = window.getSelection();
	else
		return result;
	return result;
}

function RemoveSymbols(string)
{
	string = string.replace(/\r*/gi,'');
	string = string.replace(/\n*/gi,'');
	string = string.replace(/\s*/gi,'');
	string = string.replace(/\u00A0/g,' ');
	string = string.replace(/&nbsp;/g,' ');
	string = string.replace(/&lt;/g,'<');
	string = string.replace(/&gt;/g,'>');
	string = string.replace(/<BR>/ig,'');
	return string;
}

function ChangePost(post)
{
	var reg = new RegExp('<DIV[\\s]*class[\\s]*=[\\s]*["]*[\\s]*entry\\-content[\\s]*["]*[\\s]*>[\\s\\S]*<DIV[\\s]*class[\\s]*=[\\s]*["]*[\\s]*postfoot[\\s]*["]*[\\s]*>','ig');
	var post = new String(reg.exec(post));
	var browse = navigator.userAgent.toLowerCase();

	post = post.replace(/((<BR>)(<\/P>))|((<BR\/>)(<\/P>))/ig,'$2$4');

	if(browse.indexOf('opera') == -1)
		post = post.replace(/((<BR>)(<P>))|((<BR\/>)(<P>))/ig,'$2$4');

	post = post.replace(/(:?<BR>)|(:?<BR\/>)/ig,'\n');

	//</p><p> = \n\n  - Opera FF
	//</p><p> = /n - IE 7.0
	if(browse.indexOf('opera') != -1 ||  browse.indexOf('gecko') != -1)
		post = post.replace(/(:?<\/p>)|(:?<p>)/ig,'\n');
	else
		post = post.replace(/<\/p>[\s]*<p>/ig,'\n');

	post = post.replace(/>[\s]*</,'><');

	//Make [quote="name"]...[/quote]
	post = post.replace(/<div[\s]*class[\s]*=[\s]*["]*[\s]*quotebox[\s]*["]*[\s]*>[\s]*<cite>[\s]*/ig,'[quote=');
	post = post.replace(/[\s]*wrote:/g,"]");
	post = post.replace(/<div[\s]*class[\s]*=[\s]*["]*[\s]*quotebox[\s]*["]*[\s]*>[\s]*/ig,'[quote]');
	post = post.replace(/[\s]*<\/blockquote>[\s]*/ig,"[/quote]");

	//IMG
	post = post.replace(/<img[\s]*src[\s]*=[\s]*["]*[\s]*(.*?)[\s]*["]*[\s]*alt[\s]*=[\s]*["]*[\s]*(.*?)[\s]*["]*[\s]*>[\s]*/ig,'[img]$1[/img]');
	//B
	post = post.replace(/<strong>[\s]*(.*?)[\s]*<\/strong>/ig,'[b]$1[/b]');
	//I
	post = post.replace(/<em>[\s]*(.*?)[\s]*<\/em>/ig,'[i]$1[/i]');
	//U
	post = post.replace(/<span[\s]*class[\s]*=[\s]*["]*[\s]*bbu[\s]*["]*[\s]*>[\s]*(.*?)[\s]*<\/span>/ig,'[u]$1[/u]');
	//LIST
	post = post.replace(/<ul>/ig,'[list]\n');
	post = post.replace(/<ol[\s]*class[\s]*=[\s]*["]*[\s]*decimal[\s]*["]*[\s]*>/ig,'[list=1]\n');
	post = post.replace(/<ol[\s]*class[\s]*=[\s]*["]*[\s]*alpha[\s]*["]*[\s]*>/ig,'[list=a]\n');
	post = post.replace(/<li>[\s]*(.*?)[\s]*<\/li>[\s]*/ig,'[*]$1[/*]\n');
	post = post.replace(/<\/ul>/ig,'[/list]\n');
	post = post.replace(/<\/ol>/ig,'[/list]\n');

	//URL
	post = post.replace(/<a[\s]*href[\s]*=[\s]*["]*[\s]*(.*?)[\s]*["]*[\s]*>(.*?)<\/a>/ig,'[url=$1]$2[/url]');
	//CODE
	post = post.replace(/<pre><code>[\s]*(.*?)[\s]*<\/code><\/pre>[\s]*/ig,'[code]\n$1\n[/code]\n');
	//COLOR
	post = post.replace(/<span[\s]*style[\s]*=[\s]*["]*[\s]*color:[\s]*(.*?)[\s]*;[\s]*["]*[\s]*>(.*?)<\/span>/ig,'[color=$1]$2[/color]');
	//Remove tags
	post = post.replace(/<(:?.*?)>/gi,'');
	//Replace quote = name name on quote = "name name"
	post = post.replace(/\[quote=(["][-a-zA-Z0-9]*)[\s]+([-"a-zA-Z0-9]*)\]/g,'[quote=\"$1 $2\"]');
	//Insert \n before [/quote]
	post = post.replace(/\[\/quote\]/g,'\n[/quote]');
	//exotic symbols =)
	post = post.replace(/\u00A0/g,' ');
	post = post.replace(/&nbsp;/g,' ');
	post = post.replace(/&lt;/g,'<');
	post = post.replace(/&gt;/g,'>');

	return post;
}

function TrimString(param)
{
	param = param.replace(/ /g,' ');
	return param.replace(/(^\s+)|(\s+$)/g, '');
}

function Reply(tid_param, qid_param)
{
	var element = document.getElementsByTagName('div');
	for (var i=0; i < element.length; i++)
	{
		if(element[i].className.match(/^post\s.*/ig))
		{

			var post = new String(element[i].innerHTML);

			if(post.search('Reply[(]' +tid_param+','+qid_param+'[)]') != -1)
			{
				post=ChangePost(post);
				var post_new = RemoveSymbols(post);
				var selected_text = (window.selected_text_first == '')?window.selected_text_second:window.selected_text_first;
				//getSelectedText();
				
				if((selected_text != undefined)&&(selected_text!=''))
				{
				   //this is for Chrome browser. Text, selected by user, has 'Range' type, not 'String'. And in some cases, when there is no text selected, Chrome returns one symbol of 'Caret' type
					if((selected_text.type=='Range')||(selected_text.type=='Caret'))
						selected_text=selected_text.toString();
					selected_text = RemoveSymbols(selected_text);
					
					post = TrimString(post);

					if((post_new.indexOf(selected_text) != -1) && (selected_text.charAt(0) != ''))
					{
						var form = document.getElementById('qq');
						form.action='post.php?tid=' + tid_param + '&qid=' + qid_param;
						element = document.getElementById('post_msg');
						element.value =(window.selected_text_first == '')?window.selected_text_second:window.selected_text_first; //getSelectedText();
						form.submit();
						break;
					}
				}
				location = 'post.php?tid='+tid_param+'&qid='+qid_param;
			}
		}
	}
}

function QuickQuote(tid_param, qid_param)
{
	var element = document.getElementsByTagName('div');

	for (var i=0; i < element.length; i++)
	{
	
		if(element[i].className.match(/^post\s.*/ig))
		{		
			var post = new String(element[i].innerHTML);
			post = post.replace(/[\s]*<p[\s]*class[\s]*=[\s]*["]*[\s]*lastedit[\s]*["]*[\s]*>[\s]*(.*?)[\s]*<\/p>[\s]*/ig,'');
			post = post.replace(/[\s]*<div[\s]*class[\s]*=[\s]*["]*[\s]*sig-content[\s]*["]*[\s]*>[\s]*(.*?)[\s]*<\/div>[\s]*/ig,'');
			if(post.search('QuickQuote[(]'+tid_param+','+qid_param+'[)]') != -1)
			{
				//get quoted author name from the post
				//var RegExp = /<cite>.*\sby\s(.*?):/ig;  old markup compatibility
				var RegExp =/<span class="*post-byline"*>(?:.*?)<a(?:.*?)>(.*?)<\/a>/ig;
				var result =  RegExp.exec(post);
				RegExp.lastIndex=0;
				var author_name;

				if(result!=null)
					author_name=result[1];
							
				post=ChangePost(post);
				var post_new = RemoveSymbols(post);
				var selected_text = (window.selected_text_first == '')?window.selected_text_second:window.selected_text_first;
				//getSelectedText();
				
				post = TrimString(post);

				if((selected_text != undefined)&&(selected_text!=''))
				{
				   //this is for Chrome browser. Text, selected by user, has 'Range' type, not 'String'. And in some cases, when there is no text selected, Chrome returns one symbol of 'Caret' type
					if((selected_text.type=='Range')||(selected_text.type=='Caret'))
						selected_text=selected_text.toString();	
					selected_text = RemoveSymbols(selected_text);

					if((post_new.indexOf(selected_text) != -1) && (selected_text.charAt(0) != ''))
					{
						element = document.getElementById('fld1');
						element.value +=(window.selected_text_first == '')?'[quote='+author_name+']\n'+window.selected_text_second+'\n[/quote]'+'\n':'[quote='+author_name+']\n'+window.selected_text_first+'\n[/quote]'+'\n';
						//getSelectedText();
						break;
					}
				}
				element = document.getElementById('fld1');
				element.value+='[quote='+author_name+']\n'+post+'\n[/quote]'+'\n';
			}
		}
	}

}

function SetSelected()
{
	switch(window.selected_text_pointer)
	{
		case 0:
			window.selected_text_pointer = 1;
			window.selected_text_first = getSelectedText();
			break;
		case 1:
			window.selected_text_pointer = 0;
			window.selected_text_second = getSelectedText();
			break;
		case undefined:
			window.selected_text_pointer = 0;
			window.selected_text_second = getSelectedText();
			break;
	}
}
