/* save on exit */
window.needToConfirm = false
window.onbeforeunload = confirmExit

function confirmExit()
{
	if (window.needToConfirm) return "Are you sure you want to leave this page? You will lose data if you do."
}

/* download data */
function http(url, func) {
	var con
	try       { con=new XMLHttpRequest(); }
	catch (e) { try { con=new ActiveXObject("Msxml2.XMLHTTP"); }
	catch (e) { try { con=new ActiveXObject("Microsoft.XMLHTTP"); }
	catch (e) { alert("Your browser does not support AJAX!"); return false; } } }

	con.open("GET",url,true)
	con.send(null)

	con.onreadystatechange=function() {
		if (con.readyState == 4) {
			func(con.responseText)
		}
	}
}

/* smaller function name */
var get = function(id) {
	return document.getElementById(id)
}

/* fill in the blanks */
function intoWords(text) {
	var words     = new Array()
	var html      = false
	var last_word = 0

	for (var i = 0; i < text.length; ++i) {
		var character = text.substr(i, 1)

		if (html == true && character != ">")
			continue

		switch (character) {
			case ".":
			case ",":
			case ";":
			case ":":
			case "?":
			case "!":
			case ")":
			case "]":
			case " ":
				words.push(text.substring(last_word, i + 1))
				last_word = i + 1
				break
			case "<":
				if (last_word != i) {
					words.push(text.substring(last_word, i))
					last_word = i
				}

				html=true
				break
			case ">":
				words.push(text.substring(last_word, i + 1))
				last_word = i + 1

				html=false
				break
		}
	}

	return words
}

function goodWord(word) {
	var check    = word.toLowerCase().replace(/[^a-z]/g, "")
	var badWords = "a,an,are,and,am,as,at,be,by,co,can,but,because,since,did,for,has,have,from,etc,do,he,her,she,his,hers,i,is,if,it,in,no,ok,of,off,oh,how,on,or,so,to,too,the,then,that,this,will".split(",")

	for (var i = 0; i < badWords.length; ++i)
		if (check == badWords[i])
			return false

	return true
}

/* sometimes it doesn't focus first try */
function focus(t) {
	var e = (typeof(t) == 'string')?get(t):t

	e.focus()
	setTimeout(function() {
		e.focus()
	}, 200)
}

/* distance between strings, the sift3 algorithm */
function distance(str1, str2) {
	var maxOffset = 5

	if (str1 == "")
		return (str2 =="")?0:str2.length
	if (str2 == "")
		return str1.length

	var c = 0
	var offset1 = 0
	var offset2 = 0
	var lcs = 0

	while ((c + offset1 < str1.length)
		&& (c + offset2 < str2.length)) {
		if (str1[c + offset1] == str2[c + offset2])
			lcs++
		else {
			offset1 = 0
			offset2 = 0

			for (var i = 0; i < maxOffset; ++i) {
				if ((c + i < str1.length) && str1[c + i] == str2[c]) {
					offset1 = i
					break
				}
				if ((c + i < str2.length) && str1[c] == str2[c+i]) {
					offset2 = i
					break
				}
			}
		}

		++c
	}

	return (str1.length + str2.length)/2 - lcs
}

/* based on http://www.linuxjournal.com/article/9585?page=0,3 */
function validEmail(email) {
	atIndex = email.indexOf("@")

	if (atIndex == -1)
		return false
	
	domain    = email.substr(atIndex+1)
	local     = email.substr(0, atIndex)
	localLen  = local.length
	domainLen = domain.length

	if (localLen < 1 || localLen > 64)
		return false
	if (domainLen < 1 || domainLen > 255)
		return false
	if (local[0] == '.' || local[localLen-1] == '.')
		return false
	if (local.match(/\.\./))
		return false
	if (!domain.match(/^[A-Za-z0-9\-\.]+$/))
		return false
	if (domain.match(/\.\./))
		return false
	if (!local.replace("\\\\", "").match(
		/^(\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/))
	{
		if (!localreplace("\\\\", "").match(/^"(\\"|[^"])+"$/))
			return false
	}

	//tld
	if (domain.indexOf(".") == -1)
		return false
	if (domain[0] == '.' || domain[domainLen-1] == '.')
		return false

	return true
}

function validUser(username) {
	if (username.length < 4 || username.length > 30)
		return false
	
	if (!username.match(/^[A-Za-z0-9\-_\.]+$/))
		return false
	
	return true
}

//http://forums.devshed.com/javascript-development-115/javascript-get-all-elements-of-class-abc-24349.html
if (document.getElementsByClassName == undefined) {
	document.getElementsByClassName = function(className) {
		var hasClassName = new RegExp("(?:^|\\s)" + className + "(?:$|\\s)");
		var allElements = document.getElementsByTagName("*");
		var results = [];

		var element;
		for (var i = 0; (element = allElements[i]) != null; i++) {
			var elementClass = element.className;
			if (elementClass && elementClass.indexOf(className) != -1 && hasClassName.test(elementClass))
				results.push(element);
		}

		return results;
	}
}
