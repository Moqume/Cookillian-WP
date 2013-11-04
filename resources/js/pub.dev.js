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
 *
 * Cleaned by Mike Green <myatus@gmail.com>
 */
(function($, global) {
    var canCall, logError, SCRIPT_TAGS, SCRIPT_2, SRC_REGEX, SRC_ATTR, TYPE_ATTR, LANG_ATTR, GLOBAL, DIV_PREFIX, TEMPLATE, callbacks, TEMPLATE_TAG, global_id, GLOBAL_Q, debug, logDebug, logString, name, self, doc = global.document;
    function doEvil(code) {
        var div = doc.createElement("div");
        doc.body.insertBefore(div, null);
        $.replaceWith(div, '<script type="text/javascript">' + code + "</script>");
    }
    $ = $ || (function(jQuery) {
        return {
            ajax: jQuery.ajax,
            $: function(s) {
                return jQuery(s)[0];
            },
            replaceWith: function(selector, content) {
                var el = jQuery(selector)[0], next = el.nextSibling, parent = el.parentNode;
                jQuery(el).remove();
                if (next) {
                    jQuery(next).before(content);
                } else {
                    jQuery(parent).append(content);
                }
            },
            onLoad: function(fn) {
                jQuery(fn);
            },
            copyAttrs: function(src, dest) {
                var i, len, el = jQuery(dest), attrs = src.attributes;
                for (i = 0, len = attrs.length; i < len; i++) {
                    if (attrs[i] && attrs[i].value) {
                        try {
                            el.attr(attrs[i].name, attrs[i].value);
                        } catch (e) {}
                    }
                }
            }
        };
    }(global.jQuery));

    $.copyAttrs = $.copyAttrs || function() {};
    $.onLoad = $.onLoad || function() {
        throw "error: autoAsync cannot be used without jQuery " + "or defining writeCaptureSupport.onLoad";
    };
    function each(array, fn) {
        var i, len;
        for (i = 0, len = array.length; i < len; i++) {
            if (fn(array[i]) === false) {
                return;
            }
        }
    }
    function isFunction(o) {
        return Object.prototype.toString.call(o) === "[object Function]";
    }
    function slice(array, start, end) {
        return Array.prototype.slice.call(array, start || 0, end || (array && array.length));
    }
    function any(array, fn) {
        var result = false;
        each(array, function(it) {
            return !(result = fn(it));
        });
        return result;
    }
    function SubQ(parent) {
        this._queue = [];
        this._children = [];
        this._parent = parent;
        if (parent) {
            parent._addChild(this);
        }
    }
    SubQ.prototype = {
        _addChild: function(q) {
            this._children.push(q);
        },
        push: function(task) {
            this._queue.push(task);
            this._bubble("_doRun");
        },
        pause: function() {
            this._bubble("_doPause");
        },
        resume: function() {
            this._bubble("_doResume");
        },
        _bubble: function(name) {
            var root = this;
            while (!root[name]) {
                root = root._parent;
            }
            return root[name]();
        },
        _next: function() {
            var task;
            if (any(this._children, function(c) { return c._next(); })) {
                return true;
            }

            task = this._queue.shift();
            if (task) {
                task();
            }
            return !!task;
        }
    };
    function Q(parent) {
        if (parent) {
            return new SubQ(parent);
        }
        SubQ.call(this);
        this.paused = 0;
    }
    Q.prototype = (function() {
        function F() {}
        F.prototype = SubQ.prototype;
        return new F();
    }());
    Q.prototype._doRun = function() {
        if (!this.running) {
            this.running = true;
            try {
                while (this.paused < 1 && this._next()) {}
            } finally {
                this.running = false;
            }
        }
    };
    Q.prototype._doPause = function() {
        this.paused++;
    };
    Q.prototype._doResume = function() {
        this.paused--;
        this._doRun();
    };
    function MockDocument() {}
    MockDocument.prototype = {
        _html: "",
        open: function() {
            this._opened = true;
            if (this._delegate) {
                this._delegate.open();
            }
        },
        write: function(s) {
            if (this._closed) {
                return;
            }
            this._written = true;
            if (this._delegate) {
                this._delegate.write(s);
            } else {
                this._html += s;
            }
        },
        writeln: function(s) {
            this.write(s + "\n");
        },
        close: function() {
            this._closed = true;
            if (this._delegate) {
                this._delegate.close();
            }
        },
        copyTo: function(d) {
            this._delegate = d;
            d.foobar = true;
            if (this._opened) {
                d.open();
            }
            if (this._written) {
                d.write(this._html);
            }
            if (this._closed) {
                d.close();
            }
        }
    };
    canCall = (function() {
        var f = {
            f: doc.getElementById
        };
        try {
            f.f.call(doc, "abc");
            return true;
        } catch (e) {
            return false;
        }
    }());
    function unProxy(elements) {
        each(elements, function(it) {
            var real = doc.getElementById(it.id);
            if (!real) {
                logError("<proxyGetElementById - finish>", "no element in writen markup with id " + it.id);
                return;
            }
            each(it.el.childNodes, function(it) {
                real.appendChild(it);
            });
            if (real.contentWindow) {
                global.setTimeout(function() {
                    it.el.contentWindow.document.copyTo(real.contentWindow.document);
                }, 1);
            }
            $.copyAttrs(it.el, real);
        });
    }
    function getOption(name, options) {
        if (options && options[name] === false) {
            return false;
        }
        return (options && options[name]) || self[name];
    }
    function capture(context, options) {
        var f, findEl, tempEls = [], proxy = getOption("proxyGetElementById", options), forceLast = getOption("forceLastScriptTag", options), writeOnGet = getOption("writeOnGetElementById", options), immediate = getOption("immediateWrites", options), state = {
            write: doc.write,
            writeln: doc.writeln,
            finish: function() {},
            out: ""
        };
        function replacementWrite(s) {
            state.out += s;
        }
        function replacementWriteln(s) {
            state.out += s + "\n";
        }
        function immediateWrite(s) {
            var target = $.$(context.target), div = doc.createElement("div");
            target.parentNode.insertBefore(div, target);
            $.replaceWith(div, sanitize(s));
        }
        function immediateWriteln(s) {
            var target = $.$(context.target), div = doc.createElement("div");
            target.parentNode.insertBefore(div, target);
            $.replaceWith(div, sanitize(s) + "\n");
        }
        function makeTemp(id) {
            var t = doc.createElement("div");
            tempEls.push({
                id: id,
                el: t
            });
            t.contentWindow = {
                document: new MockDocument()
            };
            return t;
        }
        function writeThenGet(id) {
            var target = $.$(context.target), div = doc.createElement("div");
            target.parentNode.insertBefore(div, target);
            $.replaceWith(div, state.out);
            state.out = "";
            return canCall ? state.getEl.call(doc, id) : state.getEl(id);
        }
        function getEl(id) {
            var result = canCall ? state.getEl.call(doc, id) : state.getEl(id);
            return result || findEl(id);
        }

        context.state = state;
        doc.write = immediate ? immediateWrite : replacementWrite;
        doc.writeln = immediate ? immediateWriteln : replacementWriteln;
        if (proxy || writeOnGet) {
            state.getEl = doc.getElementById;
            doc.getElementById = getEl;
            if (writeOnGet) {
                findEl = writeThenGet;
            } else {
                findEl = makeTemp;
                state.finish = function() {
                    unProxy(tempEls);
                };
            }
        }
        if (forceLast) {
            state.getByTag = doc.getElementsByTagName;
            doc.getElementsByTagName = function(name) {
                var result = slice(canCall ? state.getByTag.call(doc, name) : state.getByTag(name));
                if (name === "script") {
                    result.push($.$(context.target));
                }
                return result;
            };
            f = state.finish;
            state.finish = function() {
                f();
                doc.getElementsByTagName = state.getByTag;
            };
        }

        return state;
    }
    function uncapture(state) {
        doc.write = state.write;
        doc.writeln = state.writeln;
        if (state.getEl) {
            doc.getElementById = state.getEl;
        }
        return state.out;
    }
    function clean(code) {
        return code && code.replace(/^\s*<!(\[CDATA\[|--)/, "").replace(/(\]\]|--)>\s*$/, "");
    }
    function ignore() {}
    function doLog(code, error) {
        console.error("Error", error, "executing code:", code);
    }
    logError = isFunction(global.console && console.error) ? doLog : ignore;
    function captureWrite(code, context, options) {
        var state = capture(context, options);
        try {
            doEvil(clean(code));
        } catch (e) {
            logError(code, e);
        } finally {
            uncapture(state);
        }
        return state;
    }
    function isXDomain(src) {
        var parts = /^(\w+:)?\/\/([^\/?#]+)/.exec(src);
        return parts && ( (parts[1] && parts[1] !== location.protocol) || parts[2] !== location.host );
    }
    function attrPattern(name) {
        return new RegExp("[\\s\\r\\n]" + name + "[\\s\\r\\n]*=[\\s\\r\\n]*(?:([\"'])([\\s\\S]*?)\\1|([^\\s>]+))", "i");
    }
    function matchAttr(name) {
        var regex = attrPattern(name);
        return function(tag) {
            var match = regex.exec(tag) || [];
            return match[2] || match[3];
        };
    }
    SCRIPT_TAGS = /(<script[^>]*>)([\s\S]*?)<\/script>/ig;
    SCRIPT_2 = /<script[^>]*\/>/ig;
    SRC_REGEX = attrPattern("src");
    SRC_ATTR = matchAttr("src");
    TYPE_ATTR = matchAttr("type");
    LANG_ATTR = matchAttr("language");
    GLOBAL = "__document_write_ajax_callbacks__";
    DIV_PREFIX = "__document_write_ajax_div-";
    TEMPLATE = "window['" + GLOBAL + "']['%d']();";
    callbacks = global[GLOBAL] = {};
    TEMPLATE_TAG = '<script type="text/javascript">' + TEMPLATE + "</script>";
    global_id = 0;

    function nextId() {
        return (++global_id).toString();
    }
    function normalizeOptions(options, callback) {
        var done;
        if (isFunction(options)) {
            done = options;
            options = null;
        }
        options = options || {};
        done = done || (options && options.done);
        options.done = callback ? function() {
            callback(done);
        } : done;
        return options;
    }
    GLOBAL_Q = new Q();
    debug = [];
    logDebug = window._debugWriteCapture ? function() {} : function(type, src, data) {
        debug.push({
            type: type,
            src: src,
            data: data
        });
    };
    logString = window._debugWriteCapture ? function() {} : function() {
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
        return TEMPLATE_TAG.replace(/%d/, newCallback(fn));
    }
    function doXDomainLoad(target, url, success) {
        var done, parent, script = document.createElement("script");
        script.src = url;
        target = $.$(target);
        done = false;
        parent = target.parentNode;
        script.onload = script.onreadystatechange = function() {
            if (!done && (!this.readyState || this.readyState === "loaded" || this.readyState === "complete")) {
                done = true;
                success();
                script.onload = script.onreadystatechange = null;
                parent.removeChild(script);
            }
        };
        parent.insertBefore(script, target);
    }
    function sanitize(html, options, parentQ, parentContext) {
        var done, doneHtml, fixUrls, queue = (parentQ && new Q(parentQ)) || GLOBAL_Q;

        function proxyTag(element, openTag, code) {
            var id, divId, run, context, src = SRC_ATTR(openTag), type = TYPE_ATTR(openTag) || "", lang = LANG_ATTR(openTag) || "", isJs = (!type && !lang) || type.toLowerCase().indexOf("javascript") !== -1 || lang.toLowerCase().indexOf("javascript") !== -1;

            function queueScript() {
                queue.push(run);
            }
            function captureHtml(script, cb) {
                var state = captureWrite(script, context, options);
                cb = newCallbackTag(state.finish) + (cb || "");
                html(state.out, cb);
            }
            function runInline() {
                captureHtml(code);
            }
            function loadSync() {
                $.ajax({
                    url: src,
                    type: "GET",
                    dataType: "text",
                    async: false,
                    success: function(html) {
                        captureHtml(html);
                    }
                });
            }
            function logAjaxError(xhr, status, error) {
                logError("<XHR for " + src + ">", error);
                queue.resume();
            }
            function setupResume() {
                return newCallbackTag(function() {
                    queue.resume();
                });
            }
            function loadAsync() {
                var ready, scriptText;
                function captureAndResume(script) {
                    if (!ready) {
                        scriptText = script;
                        return;
                    }
                    try {
                        captureHtml(script, setupResume());
                    } catch (e) {
                        logError(script, e);
                    }
                }

                $.ajax({
                    url: src,
                    type: "GET",
                    dataType: "text",
                    async: true,
                    success: captureAndResume,
                    error: logAjaxError
                });

                return function() {
                    ready = true;
                    if (scriptText) {
                        captureHtml(scriptText);
                    } else {
                        queue.pause();
                    }
                };
            }
            function loadXDomain() {
                var state = capture(context, options);
                function captureAndResume() {
                    logDebug("out", src, state.out);
                    html(uncapture(state), newCallbackTag(state.finish) + setupResume());
                    logDebug("resume", src);
                }
                queue.pause();
                logDebug("pause", src);
                doXDomainLoad(context.target, src, captureAndResume);
            }
            function safeOpts(options) {
                var i, copy = {};
                for (i in options) {
                    if (options.hasOwnProperty(i)) {
                        copy[i] = options[i];
                    }
                }
                delete copy.done;
                return copy;
            }
            function html(markup, cb) {
                $.replaceWith(context.target, sanitize(markup, safeOpts(options), queue, context) + (cb || ""));
            }

            logDebug("replace", src, element);
            if (!isJs) {
                return element;
            }

            id = newCallback(queueScript);
            divId = DIV_PREFIX + id;
            context = {
                target: "#" + divId,
                parent: parentContext
            };

            if (src) {
                src = fixUrls(src);
                openTag = openTag.replace(SRC_REGEX, "");
                if (isXDomain(src)) {
                    run = loadXDomain;
                } else {
                    if (getOption("asyncAll", options)) {
                        run = loadAsync();
                    } else {
                        run = loadSync;
                    }
                }
            } else {
                run = runInline;
            }

            return '<div style="display: none" id="' + divId + '"></div>' + openTag + TEMPLATE.replace(/%d/, id) + "</script>";
        }
        function proxyBodyless(tag) {
            return proxyTag(tag, tag.substring(0, tag.length - 2) + ">", "");
        }

        options = normalizeOptions(options);
        done = getOption("done", options);
        doneHtml = "";
        fixUrls = getOption("fixUrls", options);
        if (!isFunction(fixUrls)) {
            fixUrls = function(src) {
                return src;
            };
        }
        if (isFunction(done)) {
            doneHtml = newCallbackTag(function() {
                queue.push(done);
            });
        }
        return html.replace(SCRIPT_TAGS, proxyTag).replace(SCRIPT_2, proxyBodyless) + doneHtml;
    }
    function sanitizeSerial(fragments, done) {
        var queue = GLOBAL_Q;
        each(fragments, function(f) {
            queue.push(function () {
                f.action(sanitize(f.html, f.options, queue), f);
            });
        });
        if (done) {
            queue.push(done);
        }
    }
    function findLastChild(el) {
        var n = el;
        while (n && n.nodeType === 1) {
            el = n;
            n = n.lastChild;
            while (n && n.nodeType !== 1) {
                n = n.previousSibling;
            }
        }
        return el;
    }
    function autoCapture(done) {
        var currentScript, state, write = doc.write, writeln = doc.writeln, autoQ = [];
        doc.writeln = function(s) {
            doc.write(s + "\n");
        };
        doc.write = function(s) {
            var scriptEl = findLastChild(doc.body);
            if (scriptEl !== currentScript) {
                currentScript = scriptEl;
                autoQ.push(state = {
                    el: scriptEl,
                    out: []
                });
            }
            state.out.push(s);
        };
        $.onLoad(function() {
            var el, div, out, safe, doneFn, i, len;
            done = normalizeOptions(done);
            doneFn = done.done;
            done.done = function() {
                doc.write = write;
                doc.writeln = writeln;
                if (doneFn) {
                    doneFn();
                }
            };
            for (i = 0, len = autoQ.length; i < len; i++) {
                el = autoQ[i].el;
                div = doc.createElement("div");
                el.parentNode.insertBefore(div, el.nextSibling);
                out = autoQ[i].out.join("");
                safe = len - i === 1 ? sanitize(out, done) : sanitize(out);
                $.replaceWith(div, safe);
            }
        });
    }
    function extsrc(cb) {
        var s, o, html, ext, async, i, len, exts = [], scripts = document.getElementsByTagName("script"), doneCount = 0, done = cb ? newCallbackTag(function() {
            if (++doneCount >= exts.length) {
                cb();
            }
        }) : "";
        for (i = 0, len = scripts.length; i < len; i++) {
            s = scripts[i];
            ext = s.getAttribute("extsrc");
            async = s.getAttribute("asyncsrc");
            if (ext || async) {
                exts.push({
                    ext: ext,
                    async: async,
                    s: s
                });
            }
        }
        for (i = 0, len = exts.length; i < len; i++) {
            o = exts[i];
            if (o.ext) {
                html = '<script type="text/javascript" src="' + o.ext + '"> </script>';
                $.replaceWith(o.s, sanitize(html) + done);
            } else if (o.async) {
                html = '<script type="text/javascript" src="' + o.async + '"> </script>';
                $.replaceWith(o.s, sanitize(html, {
                    asyncAll: true
                }, new Q()) + done);
            }
        }
    }
    name = "writeCapture";
    self = global[name] = {
        _original: global[name],
        support: $,
        fixUrls: function(src) {
            return src.replace(/&amp;/g, "&");
        },
        noConflict: function() {
            global[name] = this._original;
            return this;
        },
        debug: debug,
        proxyGetElementById: false,
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
        replaceWith: function(selector, content, options) {
            $.replaceWith(selector, sanitize(content, options));
        },
        html: function(selector, content, options) {
            var el = $.$(selector);
            el.innerHTML = "<span/>";
            $.replaceWith(el.firstChild, sanitize(content, options));
        },
        load: function(selector, url, options) {
            $.ajax({
                url: url,
                dataType: "text",
                type: "GET",
                success: function(content) {
                    self.html(selector, content, options);
                }
            });
        },
        extsrc: extsrc,
        autoAsync: autoCapture,
        sanitize: sanitize,
        sanitizeSerial: sanitizeSerial
    };
}(this.writeCaptureSupport, this));

/**
 * jquery.writeCapture.js
 * @author noah <noah.sloan@gmail.com>
 *
 * Cleaned by Mike Green <myatus@gmail.com>
 */
 (function($, wc) {
    var PROXIED = "__writeCaptureJsProxied-fghebd__", PLACEHOLDER = /jquery-writeCapture-script-placeholder-(\d+)-wc/g, methods;

    function html(safe) {
        $(this).html(safe);
    }
    function isString(s) {
        return Object.prototype.toString.call(s) === "[object String]";
    }
    function doEach(content, options, action) {
        var done, self = this;
        if (options && options.done) {
            done = options.done;
            delete options.done;
        } else if ($.isFunction(options)) {
            done = options;
            options = null;
        }
        wc.sanitizeSerial($.map(this, function(el) {
            return {
                html: content,
                options: options,
                action: function(text) {
                    action.call(el, text);
                }
            };
        }), (done && function() {
            done.call(self);
        }) || done);
        return this;
    }
    function makeMethod(method) {
        return function(safe) {
            $(this)[method](safe);
        };
    }
    function getText(text, selector) {
        var id, scripts;
        if (!selector || !text) {
            return text;
        }
        id = 0;
        scripts = {};
        return $("<div/>").append(text.replace(/<script(.|\s)*?\/script>/g, function(s) {
            scripts[id] = s;
            return "jquery-writeCapture-script-placeholder-" + id++ + "-wc";
        })).find(selector).html().replace(PLACEHOLDER, function(all, id) {
            return scripts[id];
        });
    }
    function loadCallback(self, options, selector) {
        return function(res, status) {
            var text;
            if (status === "success" || status === "notmodified") {
                text = getText(res.responseText, selector);
                doEach.call(self, text, options, html);
            }
        };
    }
    function load(url, options, callback) {
        var selector, self = this, off = url.indexOf(" ");
        if (off >= 0) {
            selector = url.slice(off, url.length);
            url = url.slice(0, off);
        }
        if ($.isFunction(callback)) {
            options = options || {};
            options.done = callback;
        }
        return $.ajax({
            url: url,
            type: (options && options.type) || "GET",
            dataType: "html",
            data: options && options.params,
            complete: loadCallback(self, options, selector)
        });
    }
    function error(method) {
        throw "invalid method parameter " + method;
    }
    function proxyMethods() {
        var jq;
        if (this[PROXIED]) {
            return this;
        }
        jq = this;
        function F() {
            var _this = this, sanitizing = false;
            this[PROXIED] = true;
            $.each(methods, function(method) {
                var _super = jq[method];
                if (!_super) {
                    return;
                }
                _this[method] = function(content, options, cb) {
                    if (!sanitizing && isString(content)) {
                        try {
                            sanitizing = true;
                            return executeMethod.call(_this, method, content, options, cb);
                        } finally {
                            sanitizing = false;
                        }
                    }
                    return _super.apply(_this, arguments);
                };
            });
            this.pushStack = function() {
                return proxyMethods.call(jq.pushStack.apply(_this, arguments));
            };
            this.endCapture = function() {
                return jq;
            };
        }
        F.prototype = jq;
        return new F();
    }
    function executeMethod(method, content, options, cb) {
        var m;
        if (arguments.length === 0) {
            return proxyMethods.call(this);
        }
        m = methods[method];
        if (method === "load") {
            return load.call(this, content, options, cb);
        }
        if (!m) {
            error(method);
        }
        return doEach.call(this, content, options, m);
    }


    $.fn.writeCapture = executeMethod;

    methods = { 'html': html };

    $.each([ "append", "prepend", "after", "before", "wrap", "wrapAll", "replaceWith", "wrapInner" ], function() {
        methods[this] = makeMethod(this);
    });

    $.writeCapture = wc;
}(jQuery, writeCapture.noConflict()));

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
                timeout  : 9000,
                async    : has_callback,
                data     : { "action": cookillian_ajax.action, "func": func, "data": data, "_ajax_nonce": cookillian_ajax.nonce },
                success  : function(ajax_resp) {
                    if (ajax_resp.stat === 'ok') {
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
                "deleted_cookies" : true,
                "implied_consent" : false,
                "opted_out"       : false,
                "opted_in"        : false,
                "is_manual"       : false,
                "has_nst"         : false
            });

            // Initialize scrubber
            cookillian.initScrubCookies();

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

        /**
         * Periodically scrubs cookies that may have been set by JavaScript
         */
        initScrubCookies: function() {
            if (!cookillian.scrub_cookies) {
                return;
            }

            $(document).on('cookillian_ready', function() {
                function cookieScrubber() {
                    // Check if cookies are supposed to be deleted, but some are found
                    if (cookillian.deleted_cookies && document.cookie) {
                        // If we haven't "seen" these cookies before, try delete them
                        if (typeof cookillian.last_seen_cookies === "undefined" ||
                            cookillian.last_seen_cookies !== document.cookie) {
                            cookillian.deleteCookies();
                        }

                        // Anything left is likely to be "required",
                        // and we save these for the next check
                        cookillian.last_seen_cookies = document.cookie;
                    }
                    setTimeout(cookieScrubber, 500);
                }

                cookieScrubber();
            });
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
}(jQuery));
