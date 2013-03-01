<?php
require_once "include.php";
require_once "include_rpc.php";

site_header("User");
?>
<script type="text/javascript" src="sha256.js"></script>
<script type="text/javascript">
<!--
window.group		 = -1
window.first_question    = null
window.first_book        = null
window.first_chapter     = null
window.editing		 = null
window.createdContribute = false
window.abbreviations     = <?php echo json_encode(rpc("lb"))."\n"; ?>

function loggedIn() {
	var url = "rpc_user.php?t=l"
	var replace = get("replace")

	replace.innerHTML = "Loading..."

	http(url, function(text) {
		var loggedin = JSON.parse(text)

		replace.style.display = "none"
		replace.innerHTML = ""

		if (loggedin[0] == true) {
			showContribute(loggedin[1])
		} else {
			get("login").style.display = "inline"
			focus("user")
		}
	})
}

function abbreviation(book) {
	var a = window.abbreviations

	for (i = 0; i < a.length; ++i) {
		if (a[i][0] == book)
			return a[i][1]
	}

	return false
}

// http://th.atguy.com/mycode/xor_js_encryption/
function encrypt(key, text) {
	var result = new Array()

	for (i=0; i < text.length; ++i) {
		result.push(key^text.charCodeAt(i))
	}

	return "[" + result.join(",") + "]"
}

/*
 * Mild security without SSL:
 *   1. Get random key from server
 *   2. Encrypt SHA256 hash of password with key
 *   3. Send username and encrypted hash
*/
function login() {
	var replace  = get("replace")
	var username = get("user").value
	var password = Sha256.hash(get("password").value)

	get("login").style.display   = "none"
	get("invalid").style.display = "none"
	replace.style.display        = "inline"
	replace.innerHTML = "Signing in..."
	get("password").value = ""

	var url = "rpc_user.php?t=o"

	http(url, function(text1) {
		var key  = JSON.parse(text1)
		password = encrypt(key, password)

		url += "&u="+encodeURIComponent(username)
		      +"&p="+password
		
		http(url, function(text2) {
			var loggedin = JSON.parse(text2)

			replace.style.display="none"
			replace.innerHTML=""

			if (loggedin[0] == true) {
				showContribute(loggedin[1])
			} else {
				get("invalid").style.display = "inline"
				get("login").style.display   = "inline"
				focus("user")
			}
		})
	})
}

function logout(elem) {
	var replace = get("replace")
	get("title").innerHTML = "Login"
	get("contribute").style.display = "none"

	var url = "rpc_user.php?t=x"

	http(url, function(text) {
		replace.innerHTML          = ""
		replace.style.display      = "none"
		get("invalid").display     = "none"
		get("login").style.display = "inline"
	})
}

function forgot() {
	get("login").style.display   = "none"
	get("invalid").style.display = "none"
	get("title").innerHTML = "Reset Password"
	get("forgot").style.display  = "inline"
	focus("forgot_user")
}

function create() {
	get("login").style.display   = "none"
	get("invalid").style.display = "none"
	get("title").innerHTML = "Create Account"
	get("create").style.display  = "inline"
	focus("create_user")
}

function createSend() {
	var username  = get("create_user").value
	var email     = get("create_email").value
	var password  = Sha256.hash(get("create_password").value)
	var password2 = Sha256.hash(get("create_password_check").value)

	var b = get("invalid_blank")
	var e = get("invalid_email")
	var p = get("invalid_passwords")
	var t = get("invalid_taken")
	var u = get("invalid_user")
	
	b.style.display = "none"
	e.style.display = "none"
	p.style.display = "none"
	t.style.display = "none"

	if (username == "" || email == "" || get("create_password").value == "") {
		b.style.display = "inline"
		focus("create_user")
		return
	}

	if (!validEmail(email)) {
		e.style.display = "inline"
		focus("create_email")
		return
	}

	if (!validUser(username)) {
		u.style.display = "inline"
		focus("create_user")
		return
	}

	if (password != password2) {
		p.style.display = "inline"
		focus("create_password")
		return
	}

	var url = "rpc_user.php?t=c"
		+"&u="+encodeURIComponent(username)
		+"&p="+password
		+"&e="+encodeURIComponent(email)

	http(url, function(text) {
		var valid = JSON.parse(text)

		if (valid[0] == false) {
			replace.style.display = "none"
			t.style.display = "inline"
			focus("create_user")
		} else if (valid[1] == false) {
			replace.style.display = "none"
			e.style.display = "inline"
			focus("create_email")
		} else if (valid[2] == true) {
			get("create_password").value       = ""
			get("create_password_check").value = ""

			get("create").style.display  = "none"
			get("replace").style.display = "inline"
			replace.innerHTML = "Your account has been created. "
				+"Check your email for further instructions."
		}
	})
}

function forgotSend() {
	var replace = get("replace")
	get("login").style.display   = "none"
	get("forgot").style.display  = "none"
	get("forgot_invalid").style.display = "none"
	replace.innerHTML     = "Sending instructions..."
	replace.style.display = "inline"

	var url = "rpc_user.php?t=r&u="
		+encodeURIComponent(get("forgot_user").value)

	http(url, function(text) {
		var userexists = JSON.parse(text)

		if (userexists[0] == true) {
			get("forgot").style.display = "none"
			replace.innerHTML = "Instructions have been sent to your email."
		} else {
			replace.style.display = "none"
			replace.innerHTML = ""
			get("forgot").style.display         = "inline"
			get("forgot_invalid").style.display = "inline"
		}
	})
}

function link(elem, title, func) {
	var l = document.createElement("a")
	var f = function() {
		if (window.needToConfirm == false) {
			clearClass()
			get("right").innerHTML=""
			get("account").style.display="none"
			get("form").onsubmit = function () {
				return false
			}
			l.className="current"

			func()
		}

		return false
	}

	l.setAttribute("href", "javascript:void(0)")
	l.onclick=f
	l.innerHTML=title
	elem.appendChild(l)

	return l
}

function clearClass() {
	var items = document.getElementsByClassName("current")
	
	for (var i = 0; i < items.length; ++i) {
		items[i].className = ""
	}
}

function setTitle(title) {
	var t = get("title")
	t.innerHTML = "Contribute - " + title
}

function showContribute(group) {
	var l = get("left")
	var r = get("right")
	get("title").innerHTML = "Contribute"
	get("contribute").style.display = "inline"

	if (window.createdContribute == true) {
		window.link_recent.onclick()
	} else {
		window.createdContribute = true

		window.link_recent = link(l, "Recent", showRecent)
		window.link_yours = link(l, "Yours", showYours)

		if (group > 0)
			window.link_deleted = link(l, "Deleted", showDeleted)

		window.link_account = link(l, "Account", showAccount)
		link(l, "Logout", logout)

		l.appendChild(document.createElement("br"))
		window.link_commentary = link(l, "Commentary", showCommentary)

		var url = "rpc_user.php?t=list"

		http(url, function(text) {
			var loaded = false

			var books = JSON.parse(text)
			for (var book in books) {
				for (var i = 0; i < books[book].length; ++i) {
					var abbr = abbreviation(book)
					var page = link(l, abbr + " " + books[book][i],
						(function(book, chapter) {
							return function() {
								showPage(book, chapter)
							}
						})(book, books[book][i]))

					if (window.location.hash.substr(1) ==
						encodeURIComponent(book)+"-"+books[book][i]) {
						loaded = true
						page.onclick()
					}
				}
			}

			if (loaded == false) {
				var hash = window.location.hash.substr(1)

				switch(hash) {
					case "Commentary":
						window.link_commentary.onclick()
						break
					case "Account":
						window.link_account.onclick()
						break
					case "Yours":
						window.link_yours.onclick()
						break
					case "Deleted":
						if (group > 0)
							window.link_deleted.onclick()
						else
							window.link_recent.onclick()
						break
					default:
						window.link_recent.onclick()
				}
			}
		})
		
	}
}

function getId(row) {
	return row.id.split("_")[1]
}

function save(row, justAdded) {
	var loc      = row.children[0].value
	var question = row.children[1].value
	var answer   = row.children[2].value

	if (justAdded) {
		var url = "rpc_user.php?t=a"
			+"&l="+encodeURIComponent(loc)
			+"&q="+encodeURIComponent(question)
			+"&a="+encodeURIComponent(answer)
	} else {
		var url = "rpc_user.php?t=m"
			+"&i="+getId(row)
			+"&l="+encodeURIComponent(loc)
			+"&q="+encodeURIComponent(question)
			+"&a="+encodeURIComponent(answer)
	}
	
	http(url, function(text) {
		var result = JSON.parse(text)

		var valid_location = result[0]
		var valid_question = result[1]
		var valid_answer   = result[2]
		var qid            = result[3]
		
		var l = row.getElementsByTagName("input")[0]
		var q = row.getElementsByTagName("textarea")[0]
		var a = row.getElementsByTagName("textarea")[1]

		if (qid !== false) {
			var chapter = result[5]
			var verse   = result[6]
			
			if (verse == 0) {
				var book  = "Page"
				var verse = ""
			} else {
				var book = result[4]
			}
			
			row.id = "row_" + qid

			if (justAdded) {
				window.first_question = row
				get("add_row").style.display="inline"
			}

			cancel(row, book, chapter, verse, question.replace("&", "&amp;"), answer.replace("&", "&amp;"))
		} else {
			enableButtons(row, true)

			var inputs = new Array(l, q, a)
			for (var i = 0; i < inputs.length; ++i) {
				inputs[i].style.background="#FFFFFF"
				inputs[i].style.borderColor="#000000"
			}

			l.style.borderWidth="1px 0px 1px 1px"
			l.style.left="1px"
			q.style.borderWidth="1px 0px 1px 1px"
			a.style.borderWidth="1px 1px 1px 0px"

			if (!valid_location) {
				l.style.background="#FFF7F7"
				l.style.borderColor="#FF0000"
				l.style.borderWidth="1px"
			}
			
			if (!valid_question) {
				l.style.left="0px"
				q.style.background="#FFF7F7"
				q.style.borderColor="#FF0000"
				q.style.borderWidth="1px"
			}
			
			if (!valid_answer) {
				a.style.background="#FFF7F7"
				a.style.borderColor="#FF0000"
				a.style.borderWidth="1px"
			}
		}
	})
}

function remove(row, book, chapter, verse, question, answer) {
	row.innerHTML = ""
	var permanent = (window.link_deleted.className == "current")
	
	var span = document.createElement("span")
	span.innerHTML = "Are you sure you want to"
		+((permanent)?" permanently":"")+" delete the question for "
		+book+" "+chapter
		
	if (verse == "")
		span.innerHTML += "?"
	else
		span.innerHTML += ":"+verse+"?"

	var yes = document.createElement("input")
	yes.setAttribute("type", "submit")
	yes.value = "Yes"
	yes.onclick = function () {
		if (permanent) {
			var url="rpc_user.php?t=dd"
				+"&i="+getId(row)
		} else {
			var url="rpc_user.php?t=d"
				+"&i="+getId(row)
		}

		http(url, function(text) {
			var deleted = JSON.parse(text)

			if (deleted[0] == true) {
				window.needToConfirm = false
				row.parentNode.removeChild(row)
			} else {
				cancel(row, book, chapter, verse, question, answer)
			}
		})

		return false
	}

	var no = document.createElement("input")
	no.setAttribute("type", "submit")
	no.value = "No"
	no.onclick = function() {
		cancel(row, book, chapter, verse, question, answer)
		return false
	}

	row.appendChild(span)
	row.appendChild(document.createElement("br"))
	row.appendChild(yes)
	row.appendChild(no)
}

function cancel(elem, book, chapter, verse, question, answer) {
	window.needToConfirm = false
	window.editing       = null
	
	row(elem, new Array(
		book,
		chapter,
		verse
	), question, answer, true)
}

function enableButtons(row, enabled) {
	var inputs    = row.getElementsByTagName("input")
	var textareas = row.getElementsByTagName("textarea")

	for (var i = 0; i < inputs.length; ++i) {
		inputs[i].disabled    = !enabled
	}
	
	for (var i = 0; i < textareas.length; ++i) {
		textareas[i].disabled = !enabled
	}
	
}

function edit(row, justAdded) {
	if (window.editing != null) {
		window.editing.onclick()
	}
	
	window.needToConfirm = true
	
	var loc      = row.children[0]

	var book     = loc.children[0].innerHTML
	var chapter  = loc.children[1].innerHTML
	var verse    = loc.children[2].innerHTML
	var question = row.children[1].innerHTML
	var answer   = row.children[2].innerHTML

	row.innerHTML = ""
	row.className = "row_edit"
	row.onclick   = null
	row.onmouseup = null

	var l1 = document.createElement("input")
	l1.className = "edit_book"
	l1.setAttribute("type", "text")

	if (verse == "")
		l1.setAttribute("value", book+" "+chapter)
	else
		l1.setAttribute("value", book+" "+chapter+":"+verse)

	var buttons   = document.createElement("div")
	var container = document.createElement("div")
	var cancelB   = document.createElement("input")
	
	cancelB.setAttribute("type",  "submit")
	cancelB.setAttribute("value", "Cancel")
	cancelB.className = "buttons"

	if (justAdded === true) {
		var addB = document.createElement("input")
		addB.setAttribute("type", "submit")
		addB.setAttribute("value", "Add")
		addB.className = "buttons"
		addB.onclick=function() {
			enableButtons(row, false)
			save(row, true)
			return false
		}
		cancelB.onclick=function() {
			enableButtons(row, false)
			row.parentNode.removeChild(row)
			get("add_row").style.display="inline"
			window.needToConfirm = false
			window.editing       = null
			return false
		}
		buttons.appendChild(addB)
		buttons.appendChild(document.createElement("br"))
		buttons.appendChild(cancelB)
	} else {
		var saveB   = document.createElement("input")
		var removeB = document.createElement("input")
		var infoB   = document.createElement("input")
		saveB.setAttribute("type",  "submit")
		saveB.setAttribute("value", "Save")
		removeB.setAttribute("type",  "submit")
		removeB.setAttribute("value", "Delete")
		infoB.setAttribute("type",  "submit")
		infoB.setAttribute("value", "Info")
		saveB.className   = "buttons"
		removeB.className = "buttons"
		infoB.className = "buttons info_button"
		saveB.onclick=function() {
			enableButtons(row, false)
			save(row)
			return false
		}
		removeB.onclick=function() {
			enableButtons(row, false)
			remove(row, book, chapter, verse, question, answer)
			return false
		}
		cancelB.onclick=function() {
			enableButtons(row, false)
			cancel(row, book, chapter, verse, question, answer)
			return false
		}
		infoB.onclick=function() {
			info(row)
			return false
		}
		buttons.appendChild(saveB)
		buttons.appendChild(document.createElement("br"))
		buttons.appendChild(removeB)
		buttons.appendChild(document.createElement("br"))
		buttons.appendChild(cancelB)
		buttons.appendChild(document.createElement("br"))
		buttons.appendChild(infoB)
	}

	buttons.className = "buttons"
	container.className = "button_container"
	container.appendChild(buttons)

	var l2 = document.createElement("textarea")
	l2.className = "edit_question"
	l2.innerHTML = question
	
	var l3 = document.createElement("textarea")
	l3.className = "edit_answer"
	l3.innerHTML = answer

	row.appendChild(l1)
	row.appendChild(l2)
	row.appendChild(l3)
	row.appendChild(container)

	window.editing = cancelB
	
	focus(l2)
}

function hideInfo(previous_title, previous_x, previous_y) {
	get("history").innerHTML = ""
	setTitle(previous_title)
	get("info").style.display = "none"
	get("contribute").style.display = "inline"

	window.scrollTo(previous_x, previous_y)
}

function info(row) {
	var previous_x = window.scrollX
	var previous_y = window.scrollY
	
	var previous_title = get("title").innerHTML.split(" - ")[1]
	setTitle("Info")

	get("info").style.display = "inline"
	get("contribute").style.display = "none"

	var e = get("history")
	e.innerHTML = ""

	get("back").onclick = function() {
		hideInfo(previous_title, previous_x, previous_y)
		return false
	}

	var url = "rpc_user.php?t=i"
		+"&i="+getId(row)
	
	http(url, function(text) {
		var results = JSON.parse(text)

		for (var i = 0; i < results.length; ++i) {
			var r  = document.createElement("div")
			var h1 = document.createElement("div")
			var h2 = document.createElement("div")
			var h3 = document.createElement("div")
			var h4 = document.createElement("div")
			var h5 = document.createElement("div")
			r.className  = "revision"
			h1.className = "h1"
			h2.className = "h2"
			h3.className = "h3"
			h4.className = "h4"
			h5.className = "h5"
			
			if (results[i][7] == 1) {
				var q = document.createElement("i")
				q.innerHTML = "Deleted..."
				h2.innerHTML = results[i][3]
				h3.innerHTML = results[i][4]
				h4.innerHTML = "&nbsp;"
				h5.innerHTML = "&nbsp;"
				
				h1.appendChild(q)
				r.appendChild(h1)
			} else {
				var loc = results[i][0]+" "+results[i][1]
				var question = results[i][5].replace("&", "&amp;")
				var answer   = results[i][6].replace("&", "&amp;")

				if (results[i][2] != 0)
					loc += ":"+results[i][2]

				h1.innerHTML = loc
				h2.innerHTML = results[i][3]
				h3.innerHTML = results[i][4]
				h4.innerHTML = question
				h5.innerHTML = answer

				r.onclick=function() {
					row.getElementsByTagName("input")[0].value    = loc
					row.getElementsByTagName("textarea")[0].value = question
					row.getElementsByTagName("textarea")[1].value = answer
					hideInfo(previous_title, previous_x, previous_y)
				}
			}

			r.appendChild(h1)
			r.appendChild(h2)
			r.appendChild(h3)
			r.appendChild(h4)
			r.appendChild(h5)

			e.appendChild(r)
		}
	})
}

function addQuestion(loc) {
	var container = get("right")

	if (loc === true) {
		var parts = new Array(
			window.first_book,
			window.first_chapter,
			""
		)
	} else {
		var parts = loc
	}

	var r = row("new", parts, "", "")
	get("add_row").style.display = "none"
	
	if (window.first_question == null) {
		container.appendChild(r)
	} else {
		container.insertBefore(r, window.first_question)
	}

	edit(r, true)
}

function row(e, parts, col2, col3, onmouseup, replaceAmp) {
	if (typeof(e) == 'object') {
		var row = e
		row.innerHTML = ""
	} else {
		var row = document.createElement("div")

		if (e !== false)
			row.id = "row_" + e
	}

	var l1  = document.createElement("div")
	var s1  = document.createElement("span")
	var s2  = document.createElement("span")
	var s3  = document.createElement("span")
	var l2  = document.createElement("div")
	var l3  = document.createElement("div")
	row.className = "row"
	l1.className  = "l1"
	l2.className  = "l2"
	l3.className  = "l3"
	l2.innerHTML  = (replaceAmp)?col2.replace("&", "&amp;"):col2
	l3.innerHTML  = (replaceAmp)?col3.replace("&", "&amp;"):col3

	if (parts === false) {
		l1.innerHTML = "&nbsp;"
		row.className = "header"
	} else {
		s1.innerHTML = parts[0]
		s2.innerHTML = parts[1]

		l1.appendChild(s1)
		l1.innerHTML += " "
		l1.appendChild(s2)
		
		//commentary doesn't need this
		if (parts[2] != 0) {
			l1.innerHTML += ":"
			s3.innerHTML = parts[2]
		}

		l1.appendChild(s3)
		
		if (onmouseup === true) {
			row.onmouseup=function() {
				row.onclick=function() {
					edit(row)
					return false
				}
			}
		} else {
			row.onclick=function() {
				edit(row)
				return false
			}
		}
	}

	row.appendChild(l1)
	row.appendChild(l2)
	row.appendChild(l3)

	return row
}

function loadQuestions(url, add_row) {
	var e = get("right")

	e.appendChild(row(false, false, "Question", "Answer"))

	if (add_row !== false) {
		var n = document.createElement("div")
		n.className = "row"
		n.id = "add_row"
		n.innerHTML = "Add question..."
		n.onclick = function() {
			addQuestion(add_row)
			return false
		}
		e.appendChild(n)
	}

	window.first_question = null
	window.first_book     = null
	window.first_chapter  = null

	http(url, function(text) {
		var questions = JSON.parse(text)

		for (var i = 0; i < questions.length; ++i) {
			var qid        = questions[i][0]
			var commentary = (questions[i][1]=="Commentary")
			var book       = ((commentary)?"Page":questions[i][1])
			var parts      = new Array(
				book,
				questions[i][2],
				((commentary)?0:questions[i][3])
			)
			var col2 = questions[i][4]
			var col3 = questions[i][5]

			var r    = row(qid, parts, col2, col3, false, true)
			e.appendChild(r)

			if (i == 0) {
				window.first_question = r
				window.first_book     = book
				window.first_chapter  = questions[i][2]
			}
		}
	})
}

function showCommentary() {
	var e   = get("right")
	var url = "rpc_user.php?t=q&b=Commentary"

	setTitle("Commentary")
	window.location.hash="#Commentary"

	e.appendChild(row(false, false, "Question", "Answer"))

	var n = document.createElement("div")
	n.className = "row"
	n.id = "add_row"
	n.innerHTML = "Add question..."
	n.onclick = function() {
		addQuestion(true)
		return false
	}
	e.appendChild(n)

	window.first_question = null
	window.first_book     = "Page"
	window.first_chapter  = null

	//different indexes than in loadQuestions
	http(url, function(text) {
		var questions = JSON.parse(text)
		
		for (var i = 0; i < questions.length; ++i) {
			var qid   = questions[i][0]
			var parts = new Array(
				"Page",
				questions[i][1],
				0
			)
			var col2  = questions[i][2]
			var col3  = questions[i][3]

			var r = row(qid, parts, col2, col3, false, true)
			e.appendChild(r)
			
			if (i == 0) {
				window.first_question = r
				window.first_chapter  = questions[i][1]
			}
		}
	})
}

function showRecent() {
	var url = "rpc_user.php?t=qr"

	window.location.hash="#"
	setTitle("Recent Questions")
	loadQuestions(url, false)
}

function showDeleted() {
	var url = "rpc_user.php?t=qd"

	window.location.hash="#Deleted"
	setTitle("Deleted Questions")
	loadQuestions(url, false)
}

function showYours() {
	var url = "rpc_user.php?t=qy"

	window.location.hash="#Yours"
	setTitle("Your Questions")
	loadQuestions(url, true) //use window.first_{book,chapter}
}

function showPage(book, chapter) {
	var url = "rpc_user.php?t=q"
		+"&b="+encodeURIComponent(book)
		+"&c="+chapter
	
	window.location.hash="#"+encodeURIComponent(book)+"-"+chapter
	setTitle(book+" "+chapter)
	loadQuestions(url, new Array(book, chapter, ""))
}

function showAccount() {
	var div = document.createElement("div")

	window.location.hash="#Account"
	setTitle("Account")

	get("account").style.display="inline"
	get("form").onsubmit=function() {
		sendAccount()
		return false
	}

	var url = "rpc_user.php?t=u"

	http(url, function(text) {
		var results = JSON.parse(text)

		window.user  = results[0]
		window.email = results[1]
		get("account_user").value  = results[0]
		get("account_email").value = results[1]
	})
}

function sendAccount() {
	get("saved").style.display = "none"

	var send      = false
	var user      = get("account_user").value
	var email     = get("account_email").value
	var password  = Sha256.hash(get("account_password").value)
	var password2 = Sha256.hash(get("account_password_check").value)
	var current   = Sha256.hash(get("account_current_password").value)

	get("account_invaliduser").style.display  = "none"
	get("account_invalidemail").style.display = "none"
	get("account_taken").style.display        = "none"
	get("account_passwords").style.display    = "none"
	get("account_badpassword").style.display  = "none"

	if (get("account_password").value != "") {
		if (password != password2) {
			get("account_passwords").style.display = "inline"
			return
		}

		send = true
	} else {
		password = ""
	}

	if (user != window.user) {
		if (!validUser(user)) {
			get("account_invaliduser").style.display = "inline"
			return
		}

		send = true
	}

	if (email != window.email) {
		if (!validEmail(email)) {
			get("account_invalidemail").style.display = "inline"
			return
		}

		send = true
	}

	if (get("account_current_password").value == "") {
		get("account_badpassword").style.display = "inline"
		return
	}

	if (send) {
		var url = "rpc_user.php?t=uu"
			+"&u="+user
			+"&e="+email
			+"&p="+current
			+"&n="+password

		get("account_password").value         = ""
		get("account_password_check").value   = ""
		get("account_current_password").value = ""

		http(url, function(text) {
			var updated = JSON.parse(text)

			if (updated[2] == false) {
				get("account_badpassword").style.display = "inline"
				focus("account_current_password")
			} else if (updated[0] == false) {
				get("account_taken").style.display = "inline"
				focus("account_user")
			} else if (updated[1] == false) {
				get("account_invalidemail").style.display = "inline"
				focus("account_email")
			} else if (updated[4] == true) {
				get("saved_email").style.display = "inline"
				focus("account_user")

				window.email = email
				window.user  = user
			} else if (updated[3] == true) {
				get("saved").style.display = "inline"
				focus("account_user")

				window.user = user
			}
		})
	} else {
		get("saved").style.display = "inline"
	}
}

window.onload = loggedIn
// -->
</script>
<div class="title" id="title">Login</div>
<div id="replace"></div>

<noscript><p>You need Javascript to use this part of the site.</p></noscript>

<div id="login">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="login(); return false;">
<span class="warning" id="invalid">Incorrect username or password.</span>
<table>
<tr>
<td>Username&nbsp;</td>
<td><input type="text" id="user" /></td>
</tr><tr>
<td>Password </td>
<td><input type="password" id="password" /></td>
</tr>
<tr>
<td><input type="submit" value="Login" /></td>
<td>
<a href="javascript:void(0)" onclick="forgot(); return false;">Forgot Password?</a><br />
<a href="javascript:void(0)" onclick="create(); return false;">Create an Account</a></td>
</tr>
</table>
</form>
</div>

<div id="forgot">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="forgotSend(); return false;">
<span class="warning" id="forgot_invalid">User not found.</span>
<table>
<tr>
<td>Username&nbsp;</td>
<td><input type="text" id="forgot_user" /></td>
</tr>
<tr>
<td colspan="2">
<input type="submit" value="Email" />
</td>
</tr>
</table>
</form>
</div>

<div id="create">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="createSend(); return false;">
<span class="warning" id="invalid_blank">Please fill in the form.</span>
<span class="warning" id="invalid_email">Invalid email.</span>
<span class="warning" id="invalid_taken">Username not available.</span>
<span class="warning" id="invalid_user">Not a valid username.</span>
<span class="warning" id="invalid_passwords">Passwords don't match.</span>
<table>
<tr>
<td>Username&nbsp;</td>
<td><input type="text" id="create_user" /></td>
</tr>
<tr>
<td>Email&nbsp;</td>
<td><input type="text" id="create_email" /></td>
</tr>
<tr>
<td>Password&nbsp;</td>
<td><input type="password" id="create_password" /></td>
</tr>
<tr>
<td><i>(again)</i></td>
<td><input type="password" id="create_password_check" /></td>
</tr>
<tr>
<td colspan="2">
<input type="submit" value="Create" />
</td>
</tr>
</table>
</form>
</div>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="form" onsubmit="return false;">
<div id="info">
<p>Click on a row to restore to that revision.</p>
<div class="history_header">
<div class="h1">&nbsp;</div>
<div class="h2">User</div>
<div class="h3">Date</div>
<div class="h4">Question</div>
<div class="h5">Answer</div>
</div>
<div id="history">
</div>
<div class="blank">&nbsp;</div>
<div class="history_footer">
<input type="submit" value="Back" id="back" />
</div>
</div>

<div id="contribute">
<div class="left" id="left"></div>
<div class="right" id="right"></div>
<div id="account">
<span class="warning" id="saved">Saved.</span>
<span class="warning" id="saved_email">Please check for a verification email.</span>
<span class="warning" id="account_invalidemail">Invalid email.</span>
<span class="warning" id="account_invaliduser">Not a valid username.</span>
<span class="warning" id="account_taken">Username not available.</span>
<span class="warning" id="account_passwords">Passwords don't match.</span>
<span class="warning" id="account_badpassword">Password is incorrect.</span>
<table>
<tr>
<td>Username&nbsp;</td>
<td><input type="text" id="account_user" /></td>
</tr>
<tr>
<td>Email&nbsp;</td>
<td><input type="text" id="account_email" /></td>
</tr>
<tr>
<td>Password&nbsp;</td>
<td><input type="password" id="account_password" /></td>
</tr>
<tr>
<td><i>(again)</i></td>
<td><input type="password" id="account_password_check" /></td>
</tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr>
<td>Current password&nbsp;</td>
<td><input type="password" id="account_current_password" /></td>
</tr>
<tr>
<td colspan="2">
<input type="submit" value="Save" />
</td>
</tr>
</table>
</div>
<div class="blank">&nbsp;</div>
</div>
</form>
<?php site_footer(); ?>
