/*!
 * Copyright (c) 2012 Mike Green <myatus@gmail.com>
 * Portions Copyright Noah Sloan <noah.sloan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

if (typeof cookillian === "undefined") {
    var cookillian = {};
}

/**
 * writeCapture.js v1.0.5
 * @author noah <noah.sloan@gmail.com>
 */
(function($,global) {
	var doc = global.document;
	function doEvil(code) {
		var div = doc.createElement('div');
		doc.body.insertBefore(div,null);
		$.replaceWith(div,'<script type="text/javascript">'+code+'</script>');
	}
	// ensure we have our support functions
	$ = $ || (function(jQuery) {
		/**
		 * @name writeCaptureSupport
		 *
		 * The support functions writeCapture needs.
		 */
		return {
			/**
			 * Takes an options parameter that must support the following:
			 * {
			 * 	url: url,
			 * 	type: 'GET', // all requests are GET
			 * 	dataType: "script", // it this is set to script, script tag injection is expected, otherwise, treat as plain text
			 * 	async: true/false, // local scripts are loaded synchronously by default
			 * 	success: callback(text,status), // must not pass a truthy 3rd parameter
			 * 	error: callback(xhr,status,error) // must pass truthy 3rd parameter to indicate error
			 * }
			 */
			ajax: jQuery.ajax,
			/**
			 * @param {String Element} selector an Element or selector
			 * @return {Element} the first element matching selector
			 */
			$: function(s) { return jQuery(s)[0]; },
			/**
			 * @param {String jQuery Element} selector the element to replace.
			 * writeCapture only needs the first matched element to be replaced.
			 * @param {String} content the content to replace
			 * the matched element with. script tags must be evaluated/loaded
			 * and executed if present.
			 */
			replaceWith: function(selector,content) {
				// jQuery 1.4? has a bug in replaceWith so we can't use it directly
				var el = jQuery(selector)[0];
				var next = el.nextSibling, parent = el.parentNode;

				jQuery(el).remove();

				if ( next ) {
					jQuery(next).before( content );
				} else {
					jQuery(parent).append( content );
				}
			},

			onLoad: function(fn) {
				jQuery(fn);
			},

			copyAttrs: function(src,dest) {
				var el = jQuery(dest), attrs = src.attributes;
				for (var i = 0, len = attrs.length; i < len; i++) {
					if(attrs[i] && attrs[i].value) {
						try {
							el.attr(attrs[i].name,attrs[i].value);
						} catch(e) { }
					}
				}
			}
		};
	})(global.jQuery);

	$.copyAttrs = $.copyAttrs || function() {};
	$.onLoad = $.onLoad || function() {
		throw "error: autoAsync cannot be used without jQuery " +
			"or defining writeCaptureSupport.onLoad";
	};

	// utilities
	function each(array,fn) {
		for(var i =0, len = array.length; i < len; i++) {
			if( fn(array[i]) === false) return;
		}
	}
	function isFunction(o) {
		return Object.prototype.toString.call(o) === "[object Function]";
	}
	function isString(o) {
		return Object.prototype.toString.call(o) === "[object String]";
	}
	function slice(array,start,end) {
		return Array.prototype.slice.call(array,start || 0,end || array && array.length);
	}
	function any(array,fn) {
		var result = false;
		each(array,check);
		function check(it) {
			return !(result = fn(it));
		}
		return result;
	}

	function SubQ(parent) {
		this._queue = [];
		this._children = [];
		this._parent = parent;
		if(parent) parent._addChild(this);
	}

	SubQ.prototype = {
		_addChild: function(q) {
			this._children.push(q);
		},
		push: function (task) {
			this._queue.push(task);
			this._bubble('_doRun');
		},
		pause: function() {
			this._bubble('_doPause');
		},
		resume: function() {
			this._bubble('_doResume');
		},
		_bubble: function(name) {
			var root = this;
			while(!root[name]) {
				root = root._parent;
			}
			return root[name]();
		},
		_next: function() {
			if(any(this._children,runNext)) return true;
			function runNext(c) {
				return c._next();
			}
			var task = this._queue.shift();
			if(task) {
				task();
			}
			return !!task;
		}
	};

	/**
	 * Provides a task queue for ensuring that scripts are run in order.
	 *
	 * The only public methods are push, pause and resume.
	 */
	function Q(parent) {
		if(parent) {
			return new SubQ(parent);
		}
		SubQ.call(this);
		this.paused = 0;
	}

	Q.prototype = (function() {
		function f() {}
		f.prototype = SubQ.prototype;
		return new f();
	})();

	Q.prototype._doRun = function() {
		if(!this.running) {
			this.running = true;
			try {
				// just in case there is a bug, always resume
				// if paused is less than 1
				while(this.paused < 1 && this._next()){}
			} finally {
				this.running = false;
			}
		}
	};
	Q.prototype._doPause= function() {
		this.paused++;
	};
	Q.prototype._doResume = function() {
		this.paused--;
		this._doRun();
	};

	// TODO unit tests...
	function MockDocument() { }
	MockDocument.prototype = {
		_html: '',
		open: function( ) {
			this._opened = true;
			if(this._delegate) {
				this._delegate.open();
			}
		},
		write: function(s) {
			if(this._closed) return;
			this._written = true;
			if(this._delegate) {
				this._delegate.write(s);
			} else {
				this._html += s;
			}
		},
		writeln: function(s) {
			this.write(s + '\n');
		},
		close: function( ) {
			this._closed = true;
			if(this._delegate) {
				this._delegate.close();
			}
		},
		copyTo: function(d) {
			this._delegate = d;
			d.foobar = true;
			if(this._opened) {
				d.open();
			}
			if(this._written) {
				d.write(this._html);
			}
			if(this._closed) {
				d.close();
			}
		}
	};

	// test for IE 6/7 issue (issue 6) that prevents us from using call
	var canCall = (function() {
		var f = { f: doc.getElementById };
		try {
			f.f.call(doc,'abc');
			return true;
		} catch(e) {
			return false;
		}
	})();

	function unProxy(elements) {
		each(elements,function(it) {
			var real = doc.getElementById(it.id);
			if(!real) {
				logError('<proxyGetElementById - finish>',
					'no element in writen markup with id ' + it.id);
				return;
			}

			each(it.el.childNodes,function(it) {
				real.appendChild(it);
			});

			if(real.contentWindow) {
				// TODO why is the setTimeout necessary?
				global.setTimeout(function() {
					it.el.contentWindow.document.
						copyTo(real.contentWindow.document);
				},1);
			}
			$.copyAttrs(it.el,real);
		});
	}

	function getOption(name,options) {
		if(options && options[name] === false) {
			return false;
		}
		return options && options[name] || self[name];
	}

	function capture(context,options) {
		var tempEls = [],
			proxy = getOption('proxyGetElementById',options),
			forceLast = getOption('forceLastScriptTag',options),
			writeOnGet = getOption('writeOnGetElementById',options),
			immediate = getOption('immediateWrites', options),
			state = {
				write: doc.write,
				writeln: doc.writeln,
				finish: function() {},
				out: ''
			};
		context.state = state;
		doc.write = immediate ? immediateWrite : replacementWrite;
		doc.writeln = immediate ? immediateWriteln : replacementWriteln;
		if(proxy || writeOnGet) {
			state.getEl = doc.getElementById;
			doc.getElementById = getEl;
			if(writeOnGet) {
				findEl = writeThenGet;
			} else {
				findEl = makeTemp;
				state.finish = function() {
					unProxy(tempEls);
				};
			}
		}
		if(forceLast) {
			state.getByTag = doc.getElementsByTagName;
			doc.getElementsByTagName = function(name) {
				var result = slice(canCall ? state.getByTag.call(doc,name) :
					state.getByTag(name));
				if(name === 'script') {
					result.push( $.$(context.target) );
				}
				return result;
			};
			var f = state.finish;
			state.finish = function() {
				f();
				doc.getElementsByTagName = state.getByTag;
			};
		}
		function replacementWrite(s) {
			state.out +=  s;
		}
		function replacementWriteln(s) {
			state.out +=  s + '\n';
		}
		function immediateWrite(s) {
			var target = $.$(context.target);
			var div = doc.createElement('div');
			target.parentNode.insertBefore(div,target);
			$.replaceWith(div,sanitize(s));
		}
		function immediateWriteln(s) {
			var target = $.$(context.target);
			var div = doc.createElement('div');
			target.parentNode.insertBefore(div,target);
			$.replaceWith(div,sanitize(s) + '\n');
		}
		function makeTemp(id) {
			var t = doc.createElement('div');
			tempEls.push({id:id,el:t});
			// mock contentWindow in case it's supposed to be an iframe
			t.contentWindow = { document: new MockDocument() };
			return t;
		}
		function writeThenGet(id) {
			var target = $.$(context.target);
			var div = doc.createElement('div');
			target.parentNode.insertBefore(div,target);
			$.replaceWith(div,state.out);
			state.out = '';
			return canCall ? state.getEl.call(doc,id) :
				state.getEl(id);
		}
		function getEl(id) {
			var result = canCall ? state.getEl.call(doc,id) :
				state.getEl(id);
			return result || findEl(id);
		}
		return state;
	}
	function uncapture(state) {
		doc.write = state.write;
		doc.writeln = state.writeln;
		if(state.getEl) {
			doc.getElementById = state.getEl;
		}
		return state.out;
	}

	function clean(code) {
		// IE will execute inline scripts with <!-- (uncommented) on the first
		// line, but will not eval() them happily
		return code && code.replace(/^\s*<!(\[CDATA\[|--)/,'').replace(/(\]\]|--)>\s*$/,'');
	}

	function ignore() {}
	function doLog(code,error) {
		console.error("Error",error,"executing code:",code);
	}

	var logError = isFunction(global.console && console.error) ?
			doLog : ignore;

	function captureWrite(code,context,options) {
		var state = capture(context,options);
		try {
			doEvil(clean(code));
		} catch(e) {
			logError(code,e);
		} finally {
			uncapture(state);
		}
		return state;
	}

	// copied from jQuery
	function isXDomain(src) {
		var parts = /^(\w+:)?\/\/([^\/?#]+)/.exec(src);
		return parts && ( parts[1] && parts[1] != location.protocol || parts[2] != location.host );
	}

	function attrPattern(name) {
		return new RegExp('[\\s\\r\\n]'+name+'[\\s\\r\\n]*=[\\s\\r\\n]*(?:(["\'])([\\s\\S]*?)\\1|([^\\s>]+))','i');
	}

	function matchAttr(name) {
		var regex = attrPattern(name);
		return function(tag) {
			var match = regex.exec(tag) || [];
			return match[2] || match[3];
		};
	}

	var SCRIPT_TAGS = /(<script[^>]*>)([\s\S]*?)<\/script>/ig,
		SCRIPT_2 = /<script[^>]*\/>/ig,
		SRC_REGEX = attrPattern('src'),
		SRC_ATTR = matchAttr('src'),
		TYPE_ATTR = matchAttr('type'),
		LANG_ATTR = matchAttr('language'),
		GLOBAL = "__document_write_ajax_callbacks__",
		DIV_PREFIX = "__document_write_ajax_div-",
		TEMPLATE = "window['"+GLOBAL+"']['%d']();",
		callbacks = global[GLOBAL] = {},
		TEMPLATE_TAG = '<script type="text/javascript">' + TEMPLATE + '</script>',
		global_id = 0;
	function nextId() {
		return (++global_id).toString();
	}

	function normalizeOptions(options,callback) {
		var done;
		if(isFunction(options)) {
			done = options;
			options = null;
		}
		options = options || {};
		done = done || options && options.done;
		options.done = callback ? function() {
			callback(done);
		} : done;
		return options;
	}

	// The global Q synchronizes all sanitize operations.
	// The only time this synchronization is really necessary is when two or
	// more consecutive sanitize operations make async requests. e.g.,
	// sanitize call A requests foo, then sanitize B is called and bar is
	// requested. document.write was replaced by B, so if A returns first, the
	// content will be captured by B, then when B returns, document.write will
	// be the original document.write, probably messing up the page. At the
	// very least, A will get nothing and B will get the wrong content.
	var GLOBAL_Q = new Q();

	var debug = [];
	var logDebug = window._debugWriteCapture ? function() {} :
		function (type,src,data) {
		    debug.push({type:type,src:src,data:data});
		};

	var logString = window._debugWriteCapture ? function() {} :
		function () {
			debug.push(arguments);
		};

	function newCallback(fn) {
		var id = nextId();
		callbacks[id] = function() {
			fn();
			delete callbacks[id];
		};
		return id;
	}

	function newCallbackTag(fn) {
		return TEMPLATE_TAG.replace(/%d/,newCallback(fn));
	}

	/**
	 * Sanitize the given HTML so that the scripts will execute with a modified
	 * document.write that will capture the output and append it in the
	 * appropriate location.
	 *
	 * @param {String} html
	 * @param {Object Function} [options]
	 * @param {Function} [options.done] Called when all the scripts in the
	 * sanitized HTML have run.
	 * @param {boolean} [options.asyncAll] If true, scripts loaded from the
	 * same domain will be loaded asynchronously. This can improve UI
	 * responsiveness, but will delay completion of the scripts and may
	 * cause problems with some scripts, so it defaults to false.
	 */
	function sanitize(html,options,parentQ,parentContext) {
		// each HTML fragment has it's own queue
		var queue = parentQ && new Q(parentQ) || GLOBAL_Q;
		options = normalizeOptions(options);
		var done = getOption('done',options);
		var doneHtml = '';

		var fixUrls = getOption('fixUrls',options);
		if(!isFunction(fixUrls)) {
			fixUrls = function(src) { return src; };
		}

		// if a done callback is passed, append a script to call it
		if(isFunction(done)) {
			// no need to proxy the call to done, so we can append this to the
			// filtered HTML
			doneHtml = newCallbackTag(function() {
				queue.push(done);
			});
		}
		// for each tag, generate a function to load and eval the code and queue
		// themselves
		return html.replace(SCRIPT_TAGS,proxyTag).replace(SCRIPT_2,proxyBodyless) + doneHtml;
		function proxyBodyless(tag) {
			// hack in a bodyless tag...
			return proxyTag(tag,tag.substring(0,tag.length-2)+'>','');
		}
		function proxyTag(element,openTag,code) {
			var src = SRC_ATTR(openTag),
				type = TYPE_ATTR(openTag) || '',
				lang = LANG_ATTR(openTag) || '',
				isJs = (!type && !lang) || // no type or lang assumes JS
					type.toLowerCase().indexOf('javascript') !== -1 ||
					lang.toLowerCase().indexOf('javascript') !== -1;

			logDebug('replace',src,element);

			if(!isJs) {
			    return element;
			}

			var id = newCallback(queueScript), divId = DIV_PREFIX + id,
				run, context = { target: '#' + divId, parent: parentContext };

			function queueScript() {
				queue.push(run);
			}

			if(src) {
				// fix for the inline script that writes a script tag with encoded
				// ampersands hack (more comon than you'd think)
				src = fixUrls(src);

				openTag = openTag.replace(SRC_REGEX,'');
				if(isXDomain(src)) {
					// will load async via script tag injection (eval()'d on
					// it's own)
					run = loadXDomain;
				} else {
					// can be loaded then eval()d
					if(getOption('asyncAll',options)) {
						run = loadAsync();
					} else {
						run = loadSync;
					}
				}
			} else {
				// just eval code and be done
				run = runInline;

			}
			function runInline() {
				captureHtml(code);
			}
			function loadSync() {
				$.ajax({
					url: src,
					type: 'GET',
					dataType: 'text',
					async: false,
					success: function(html) {
						captureHtml(html);
					}
				});
			}
			function logAjaxError(xhr,status,error) {
				logError("<XHR for "+src+">",error);
				queue.resume();
			}
			function setupResume() {
				return newCallbackTag(function() {
					queue.resume();
				});
			}
			function loadAsync() {
				var ready, scriptText;
				function captureAndResume(script,status) {
					if(!ready) {
						// loaded before queue run, cache text
						scriptText = script;
						return;
					}
					try {
						captureHtml(script, setupResume());
					} catch(e) {
						logError(script,e);
					}
				}
				// start loading the text
				$.ajax({
					url: src,
					type: 'GET',
					dataType: 'text',
					async: true,
					success: captureAndResume,
					error: logAjaxError
				});
				return function() {
					ready = true;
					if(scriptText) {
						// already loaded, so don't pause the queue and don't resume!
						captureHtml(scriptText);
					} else {
						queue.pause();
					}
				};
			}
			function loadXDomain(cb) {
				var state = capture(context,options);
				queue.pause(); // pause the queue while the script loads
				logDebug('pause',src);

				doXDomainLoad(context.target,src,captureAndResume);

				function captureAndResume(xhr,st,error) {
					logDebug('out', src, state.out);
					html(uncapture(state),
						newCallbackTag(state.finish) + setupResume());
					logDebug('resume',src);
				}
			}
			function captureHtml(script, cb) {
				var state = captureWrite(script,context,options);
				cb = newCallbackTag(state.finish) + (cb || '');
				html(state.out,cb);
			}
			function safeOpts(options) {
				var copy = {};
				for(var i in options) {
					if(options.hasOwnProperty(i)) {
						copy[i] = options[i];
					}
				}
				delete copy.done;
				return copy;
			}
			function html(markup,cb) {
			 	$.replaceWith(context.target,sanitize(markup,safeOpts(options),queue,context) + (cb || ''));
			}
			return '<div style="display: none" id="'+divId+'"></div>' + openTag +
				TEMPLATE.replace(/%d/,id) + '</script>';
		}
	}

    function doXDomainLoad(target,url,success) {
    	// TODO what about scripts that fail to load? bad url, etc.?
		var script = document.createElement("script");
		script.src = url;

		target = $.$(target);

		var done = false, parent = target.parentNode;

		// Attach handlers for all browsers
		script.onload = script.onreadystatechange = function(){
			if ( !done && (!this.readyState ||
					this.readyState == "loaded" || this.readyState == "complete") ) {
				done = true;
				success();

				// Handle memory leak in IE
				script.onload = script.onreadystatechange = null;
				parent.removeChild( script );
			}
		};

		parent.insertBefore(script,target);
    }

	/**
	 * Sanitizes all the given fragments and calls action with the HTML.
	 * The next fragment is not started until the previous fragment
	 * has executed completely.
	 *
	 * @param {Array} fragments array of objects like this:
	 * {
	 *   html: '<p>My html with a <script...',
	 *   action: function(safeHtml,frag) { doSomethingToInject(safeHtml); },
	 *   options: {} // optional, see #sanitize
	 * }
	 * Where frag is the object.
	 *
	 * @param {Function} [done] Optional. Called when all fragments are done.
	 */
	function sanitizeSerial(fragments,done) {
		// create a queue for these fragments and make it the parent of each
		// sanitize call
		var queue = GLOBAL_Q;
		each(fragments, function (f) {
			queue.push(run);
			function run() {
				f.action(sanitize(f.html,f.options,queue),f);
			}
		});
		if(done) {
			queue.push(done);
		}
	}

	function findLastChild(el) {
		var n = el;
		while(n && n.nodeType === 1) {
			el = n;
			n = n.lastChild;
			// last child may not be an element
			while(n && n.nodeType !== 1) {
				n = n.previousSibling;
			}
		}
		return el;
	}

	/**
	  * Experimental - automatically captures document.write calls and
	  * defers them untill after page load.
	  * @param {Function} [done] optional callback for when all the
	  * captured content has been loaded.
	  */
	function autoCapture(done) {
		var write = doc.write,
			writeln = doc.writeln,
			currentScript,
			autoQ = [];
		doc.writeln = function(s) {
			doc.write(s+'\n');
		};
		var state;
		doc.write = function(s) {
			var scriptEl = findLastChild(doc.body);
			if(scriptEl !== currentScript) {
				currentScript = scriptEl;
				autoQ.push(state = {
					el: scriptEl,
					out: []
				});
			}
			state.out.push(s);
		};
		$.onLoad(function() {
			// for each script, append a div immediately after it,
			// then replace the div with the sanitized output
			var el, div, out, safe, doneFn;
			done = normalizeOptions(done);
			doneFn = done.done;
			done.done = function() {
				doc.write = write;
				doc.writeln = writeln;
				if(doneFn) doneFn();
			};
			for(var i = 0, len = autoQ.length; i < len; i++ ) {
				el = autoQ[i].el;
				div = doc.createElement('div');
				el.parentNode.insertBefore( div, el.nextSibling );
				out = autoQ[i].out.join('');
				// only the last snippet gets passed the callback
				safe = len - i === 1 ? sanitize(out,done) : sanitize(out);
				$.replaceWith(div,safe);
			}
		});
	}

	function extsrc(cb) {
		var scripts = document.getElementsByTagName('script'),
			s,o, html, q, ext, async, doneCount = 0,
			done = cb ? newCallbackTag(function() {
				if(++doneCount >= exts.length) {
					cb();
				}
			}) : '',
			exts = [];

		for(var i = 0, len = scripts.length; i < len; i++) {
			s = scripts[i];
			ext = s.getAttribute('extsrc');
			async = s.getAttribute('asyncsrc');
			if(ext || async) {
				exts.push({ext:ext,async:async,s:s});
			}
		}

		for(i = 0, len = exts.length; i < len; i++) {
			o = exts[i];
			if(o.ext) {
				html = '<script type="text/javascript" src="'+o.ext+'"> </script>';
				$.replaceWith(o.s,sanitize(html) + done);
			} else if(o.async) {
				html = '<script type="text/javascript" src="'+o.async+'"> </script>';
				$.replaceWith(o.s,sanitize(html,{asyncAll:true}, new Q()) + done);
			}
		}
	}

	var name = 'writeCapture';
	var self = global[name] = {
		_original: global[name],
		support: $,
		/**
		 */
		fixUrls: function(src) {
		    return src.replace(/&amp;/g,'&');
		},
		noConflict: function() {
			global[name] = this._original;
			return this;
		},
		debug: debug,
		/**
		 * Enables a fun little hack that replaces document.getElementById and
		 * creates temporary elements for the calling code to use.
		 */
		proxyGetElementById: false,
		// this is only for testing, please don't use these
		_forTest: {
			Q: Q,
			GLOBAL_Q: GLOBAL_Q,
			$: $,
			matchAttr: matchAttr,
			slice: slice,
			capture: capture,
			uncapture: uncapture,
			captureWrite: captureWrite
		},
		replaceWith: function(selector,content,options) {
			$.replaceWith(selector,sanitize(content,options));
		},
		html: function(selector,content,options) {
			var el = $.$(selector);
			el.innerHTML ='<span/>';
			$.replaceWith(el.firstChild,sanitize(content,options));
		},
		load: function(selector,url,options) {
			$.ajax({
				url: url,
				dataType: 'text',
				type: "GET",
				success: function(content) {
					self.html(selector,content,options);
				}
			});
		},
		extsrc: extsrc,
		autoAsync: autoCapture,
		sanitize: sanitize,
		sanitizeSerial: sanitizeSerial
	};

})(this.writeCaptureSupport,this);

/**
 * jquery.writeCapture.js
 * @author noah <noah.sloan@gmail.com>
 */
(function($,wc,noop) {
    // methods that take HTML content (according to API)
    var methods = {
        html: html
    };
    // TODO wrap domManip instead?
    $.each(['append', 'prepend', 'after', 'before', 'wrap', 'wrapAll', 'replaceWith',
        'wrapInner'],function() { methods[this] = makeMethod(this); });

    function isString(s) {
        return Object.prototype.toString.call(s) == "[object String]";
    }

    function executeMethod(method,content,options,cb) {
        if(arguments.length == 0) return proxyMethods.call(this);

        var m = methods[method];
        if(method == 'load') {
            return load.call(this,content,options,cb);
        }
        if(!m) error(method);
        return doEach.call(this,content,options,m);
    }

    $.fn.writeCapture = executeMethod;

    var PROXIED = '__writeCaptureJsProxied-fghebd__';
    // inherit from the jQuery instance, proxying the HTML injection methods
    // so that the HTML is sanitized
    function proxyMethods() {
        if(this[PROXIED]) return this;

        var jq = this;
        function F() {
            var _this = this, sanitizing = false;
            this[PROXIED] = true;
            $.each(methods,function(method) {
                var _super = jq[method];
                if(!_super) return;
                _this[method] = function(content,options,cb) {
                    // if it's unsanitized HTML, proxy it
                    if(!sanitizing && isString(content)) {
                        try {
                            sanitizing = true;
                            return executeMethod.call(_this,method,content,
                                options,cb);
                        } finally {
                            sanitizing = false;
                        }
                    }
                    return _super.apply(_this,arguments); // else delegate
                };
            });
            // wrap pushStack so that the new jQuery instance is also wrapped
            this.pushStack = function() {
                return proxyMethods.call(jq.pushStack.apply(_this,arguments));
            };
            this.endCapture = function() { return jq; };
        }
        F.prototype = jq;
        return new F();
    }

    function doEach(content,options,action) {
        var done, self = this;
        if(options && options.done) {
            done = options.done;
            delete options.done;
        } else if($.isFunction(options)) {
            done = options;
            options = null;
        }
        wc.sanitizeSerial($.map(this,function(el) {
            return {
                html: content,
                options: options,
                action: function(text) {
                    action.call(el,text);
                }
            };
        }),done && function() { done.call(self); } || done);
        return this;
    }


    function html(safe) {
        $(this).html(safe);
    }

    function makeMethod(method) {
        return function(safe) {
            $(this)[method](safe);
        };
    }

    function load(url,options,callback) {
        var self = this,  selector, off = url.indexOf(' ');
        if ( off >= 0 ) {
            selector = url.slice(off, url.length);
            url = url.slice(0, off);
        }
        if($.isFunction(callback)) {
            options = options || {};
            options.done = callback;
        }
        return $.ajax({
            url: url,
            type:  options && options.type || "GET",
            dataType: "html",
            data: options && options.params,
            complete: loadCallback(self,options,selector)
        });
    }

    function loadCallback(self,options,selector) {
        return function(res,status) {
            if ( status == "success" || status == "notmodified" ) {
                var text = getText(res.responseText,selector);
                doEach.call(self,text,options,html);
            }
        };
    }

    var PLACEHOLDER = /jquery-writeCapture-script-placeholder-(\d+)-wc/g;
    function getText(text,selector) {
        if(!selector || !text) return text;

        var id = 0, scripts = {};
        return $('<div/>').append(
            text.replace(/<script(.|\s)*?\/script>/g, function(s) {
                scripts[id] = s;
                return "jquery-writeCapture-script-placeholder-"+(id++)+'-wc';
            })
        ).find(selector).html().replace(PLACEHOLDER,function(all,id) {
            return scripts[id];
        });
    }

    function error(method) {
        throw "invalid method parameter "+method;
    }

    // expose core
    $.writeCapture = wc;
})(jQuery,writeCapture.noConflict());

/**
 * Cookillian Main
 * @author Mike Green <myatus@gmail.com>
 */
(function($){
    $.extend(cookillian, {
        /**
         * This is a simple wrapper for calling an Ajax function and obtaining its response
         *
         * @param string ajaxFunc The Ajax function to perform
         * @param mixed ajaxData the data to send along with the function
         * @param function callback A callback to trigger on an asynchronous call
         * @return mixed Returns the response from the Ajax function, or `false` if there was an error
         */
        getAjaxData : function(func, data, callback) {
            var has_callback = (typeof callback === "function"), resp = false;

            $.ajax({
                type     : 'POST',
                dataType : 'json',
                url      : cookillian_ajax.url,
                timeout  : 5000,
                async    : has_callback,
                data     : { "action": cookillian_ajax.action, "func": func, "data": data, "_ajax_nonce": cookillian_ajax.nonce },
                success  : function(ajax_resp) {
                    if (ajax_resp.nonce === cookillian_ajax.nonceresponse && ajax_resp.stat === 'ok') {
                        resp = ajax_resp.data;

                        if (has_callback) {
                            callback(resp);
                        }
                    }
                }
            });

            return resp;
        },

        /**
         * Initializes Cookillian
         */
        init : function() {
            var true_referrer = document.referrer || false
                , resp, default_handler;

            // Default handler for a valid response
            default_handler = function(r) {
                if (typeof r.debug !== "undefined" && typeof console === "object") {
                    // We have debug details, show it in the console if it's available
                    console.log(r);
                }

                if (typeof r.header_script !== "undefined") {
                    // "head" exists at this point (it's where we're called from), so add
                    // any extra header scripts now. Footer scripts will need to wait.
                    $("head").append(r.header_script);

                    delete r.header_script;
                }

                // Extend ourselves with the response
                $.extend(cookillian, r);
            };

            // Provide some defaults
            $.extend(this, {
                "blocked_cookies" : true,
                "implied_consent" : false,
                "opted_out"       : false,
                "opted_in"        : false,
                "is_manual"       : false,
                "has_nst"         : false
            });

            if (!cookillian.use_async_ajax) {
                // Synchronous AJAX call
                resp = cookillian.getAjaxData('init', {"true_referrer" : true_referrer});

                if (resp) {
                    default_handler(resp);
                }
            } else {
                // Asynchronous AJAX call
                cookillian.getAjaxData('init', {"true_referrer" : true_referrer}, function(r) {
                    default_handler(r);

                    // Perform post intialization now
                    cookillian.postInit();
                });
            }
        },

        /**
         * Performs post-Initialization
         */
        postInit : function() {
            // Perform when document is ready:
            $(document).ready(function($) {
                // Inject footer script, if we have any
                if (typeof cookillian.footer_script !== "undefined") {
                    $("body").append(cookillian.footer_script);

                    delete cookillian.footer_script;
                }

                // Display the alert (if needed)
                cookillian.displayAlert();

                $(document).trigger('cookillian_ready', cookillian);
            });

            // Perform now:
            $(document).trigger('cookillian_load', cookillian); // Event triggered immediately
        },

        /**
         * Displays the cookie alert to the visitor
         */
        displayAlert : function() {
            var cookillian_alert = $(".cookillian-alert")
                , do_show = (cookillian.blocked_cookies && !cookillian.opted_out);

            if (!cookillian_alert.length) {
                return; // Nothing to do!
            }

            // Bind a click event to the "X" (close) button
            $('.close', cookillian_alert).click(function(e) {
               cookillian_alert.fadeOut('slow');
               e.preventDefault();
            });

            // Bind a click event to the "ok" and "no" buttons, to allow AJAX functionality
            $('.btn-ok', cookillian_alert).click(function(e) {
                cookillian.optIn();

                cookillian_alert.fadeOut('slow');
                e.preventDefault();
            });

            $('.btn-no', cookillian_alert).click(function(e) {
                cookillian.optOut();

                cookillian_alert.fadeOut('slow');
                e.preventDefault();
            });

            // Show the alert if needed
            if (do_show) {
                if (!cookillian.is_manual) {
                    // We have added the alert automatically, so move it from where it was inserted
                    // to the top of the content
                    cookillian_alert.detach().prependTo("body").fadeIn("slow");
                } else {
                    // The plugin admin decided where to add the alert, so we just make sure it's shown now
                    cookillian_alert.show();
                }

                // Give some feedback to the plugin that we decided to display an alert (and force it as async)
                if ((typeof cookillian.debug === "undefined" || !cookillian.debug.logged_in) && !cookillian.has_nst) {
                    cookillian.getAjaxData('displayed', true, function() {});
                }
            }
        },

        // ----------- API ----------- //

        /**
         * Deletes the cookies (user func)
         *
         * @api
         */
        deleteCookies : function() {
            return cookillian.getAjaxData('delete_cookies', true);
        },

        /**
         * Opts a visitor in
         *
         * @api
         */
        optIn : function() {
            return cookillian.getAjaxData('opt_in', true);
        },

        /**
         * Opts a visitor out
         *
         * @api
         */
        optOut : function() {
            return cookillian.getAjaxData('opt_out', true);
        },

        /**
         * Resets the user's choice of opt in or out
         *
         * @api
         */
        resetOptinout : function() {
            return cookillian.getAjaxData('reset_optinout', true);
        },

        /**
         * Inserts an arbitrary string, depending on the value
         *
         * @api
         * @param string where Selector where to insert the string
         * @param string true_string String to write when tf_value is true
         * @param string false_string String to write when tf_value is false (optional)
         * @param string|bool tf_value If a string, compares against Cookillian variables ("blocked_cookes" by default), otherwise a simple true/false trigger
         */
        insertString : function(where, true_string, false_string, tf_value) {
            var selector = $(where)
                , tf_string;

            // Return if there's no valid selector
            if (!selector.length) {
                return;
            }

            // Set a default values
            false_string = false_string || "";
            tf_value     = tf_value || "blocked_cookies";

            $(document).on("cookillian_ready", function() {
                if (typeof tf_value === "string") {
                    tf_value = Boolean(cookillian[tf_value]);
                }

                tf_string = (tf_value === false) ? false_string : true_string;

                $(selector).writeCapture().append(tf_string);
            });
        }
    });

    // ! Initialize Cookillian ASAP !
    cookillian.init();

    if (!cookillian.use_async_ajax) {
        // Perform post initialization if we're not using asynchronous AJAX
        cookillian.postInit();
    }

    $(document).ready(function(){
        console.log('Ready!');
    });
}(jQuery));
