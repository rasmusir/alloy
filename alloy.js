var Alloy = (function() {
    
    var module = {};
    var alloyViews = {};
    var alloyevents = {};
    
    window.addEventListener("popstate",function(e) {
        _request(e.state.location,e.state.args);
    });
    
    window.addEventListener("click", function(e) {
        if (e.target.tagName == "A")
        {
            request(e.target.href,null);
            e.preventDefault();
        }
        
    }.bind(this));
    
    window.addEventListener("load", function(e) {
        if (typeof(requirejs) !=="undefined")
        {
            parsemeta();
        }
        else
        {
            var tries = 50;
            var i = setInterval(function() {
                tries--;
                if (typeof(requirejs) !=="undefined" || tries <= 0)
                {
                    clearInterval(i);
                    parsemeta();
                }
            }, 100);
        }
    });
    
    function parsemeta()
    {
        var meta = JSON.parse(document.querySelector("#ALLOYMETADATA").innerHTML);
        
        if (typeof(requirejs)!=="undefined")
        loadModules(meta.modules, function() {
            fireEvents(meta.events);
        });
        else
            fireEvents(meta.events);
    }
    
    function request(location,args,callback)
    {
        _request(location,args,callback);
        window.history.pushState({location:location,args:args},document.querySelector("title").innerHTML,location);
    }
    
    function _request(location,args,callback)
    {
        callback = callback || (function() {return true;});
        
        var x = new XMLHttpRequest();
        x.open("GET",location);
        x.setRequestHeader("Content-Type","application/json");
        x.setRequestHeader("Cache-Control", "no-cache");
        x.onload = function() {
            try
            {
                var obj = JSON.parse(x.responseText);
                if (callback(obj))
                    prepareUpdate(obj);
            }
            catch (ev)
            {
                var e = {Message:"Could not parse server response.",serverResponse:x.responseText};
                console.error(e);
            }
        };
        x.send();
    };
    
    function downloadView(view,callback)
    {
        var x = new XMLHttpRequest()
        x.open("GET","/_view/"+view);
        x.onload = function() {
            callback(x.responseText);
        };
        x.send();
    }
    
    function prepareUpdate(obj)
    {
        var views = obj.views;
        var count = views.length;
        
        var checkDownload = function()
        {
            if (count <= 0)
            {
                update(obj.data,views);
                loadModules(obj.modules, function() {
                    fireEvents(obj.events);
                });
            }
        };
        
        views.forEach(function(v)
        {
            if (!alloyViews[v])
                downloadView(v, function(viewdata) {
                    alloyViews[v] = viewdata;
                    count--;
                    checkDownload();
                });
            else
                count--;
        });
        checkDownload();
    }
    
    function update(data,views,p)
    {
        p = p || document;
        data.forEach(function (obj)
        {
            var elementremoved = document.createEvent("HTMLEvents");
            elementremoved.initEvent("remove",true,false);
            
            var e = p.querySelector("*[name="+obj.t+"]");
                e.dispatchEvent(elementremoved);
            if (obj.a && e)
            {
                if (obj.a)
                    for (var attr in obj.a)
                    {
                        e.setAttribute(attr, obj.a[attr]);
                    }
            }
                
            if (obj.d && e)
            {
                var d = obj.d;
                var contentupdate = document.createEvent("HTMLEvents");
                contentupdate.initEvent("contentupdate",true,true);
                contentupdate.data = d;
                
                if (e.dispatchEvent(contentupdate))
                {
                    if (d.v)
                    {
                        e.dispatchEvent(elementremoved);
                        e.innerHTML = d.v;
                    }
                    
                    
                }
            }
            else if (obj.v && e && obj.v.v !==undefined)
            {
                var v = views[obj.v.v];
                var view = createView(v);
                e.dispatchEvent(elementremoved);
                e.innerHTML = "";
                update(obj.v.data,views,view)
                e.appendChild(view);
            }
            else if(obj.v && e && obj.v.t)
            {
                var t = p.querySelector("script[name="+obj.v.t+"]") || document.querySelector("script[name="+obj.v.t+"]");
                var template = createTemplate(t);
                e.dispatchEvent(elementremoved);
                e.innerHTML = "";
                update(obj.v.data,views,template)
                e.appendChild(template);
            }
            else if(obj.v && e && obj.v.data)
            {
                if (!obj.append)
                {
                    e.dispatchEvent(elementremoved);
                    e.innerHTML = "";
                }
                obj.v.data.forEach(function(data){
                    
                    var t = p.querySelector("script[name="+data.v.t+"]") || document.querySelector("script[name="+obj.v.t+"]");
                    var template = createTemplate(t);
                    update(data.v.data,views,template)
                    e.appendChild(template);
                });
            }
            runScripts(e);
        });
    }
    
    function loadModules(modules, callback)
    {
        require(modules, function() {
            for (var i = 0; i<arguments.length; i++)
            {
                var module = arguments[i];
                if (module && module.run)
                    module.run();
            }
            callback();
        });
        
    }
    
    function fireEvents(events)
    {
        events.forEach(function(event) {
            if (alloyevents[event.event])
            {
                alloyevents[event.event].forEach(function(listener) {
                    listener.callback.apply(listener,event.args);
                });
            }
        });
        
    }
    
    function addListener(event,callback)
    {
        var listener = {callback:callback};
        if (!alloyevents[event])
            alloyevents[event] = [];
        listener._id = alloyevents[event].count;
        alloyevents[event].push(listener)
        return listener;
    }
    
    function runScripts(element)
    {
        var result = element.querySelectorAll('script[type="javascript/module"]');
        console.log(result);
        for (var i = 0; i<result.length; i++)
        {
            var script = result[i];
            if (script.innerHTML != "")
            {
                
                var raw = script.innerHTML;
                var module = new clientmodule();
                
                var encapsuled = "(function(_MID_) {var window = {}; var setInterval = window.setInterval = function(c,t) {return _MID_.setInterval(c,t);};"
                                +                   "var setTimeout = window.setTimeout = function(c,t) {return _MID_.setTimeout(c,t);}; " + raw + "})";
                
                var compiled = eval(encapsuled);
                var _MID_ = new clientmodule();
                element._MID_ = _MID_;
                var obj = new compiled(_MID_);
            }
        }
    }
    
    function clientmodule()
    {
        this.intervals = [];
        this.timers = [];
    }
    
    clientmodule.prototype.setInterval = function(c,t)
    {
        var id = setInterval(c,t);
        this.intervals.push(id);
        return id;
    };
    
    clientmodule.prototype.setTimeout = function(c,t)
    {
        var id = setTimeout(c,t);
        this.timers.push(id);
        
        return id;
    };
    
    clientmodule.prototype.listener = function() {
        console.log("_MID_ element removed");
    };
    
    clientmodule.prototype.clearAll = function() {
        this.intervals.forEach(function(id) {
            clearInterval(id);
        });
        this.timers.forEach(function(id) {
            clearTimeout(id);
        });
    };
    
    function createView(view)
    {
        var frag = document.createDocumentFragment();
        var tmp = document.createElement("div");
        tmp.innerHTML = alloyViews[view];
        while (tmp.firstChild) frag.appendChild(tmp.firstChild);
        
        return frag;
    }
    
    function createTemplate(template)
    {
        var frag = document.createDocumentFragment();
        var tmp = document.createElement("div");
        tmp.innerHTML = template.innerHTML;
        while (tmp.firstChild) frag.appendChild(tmp.firstChild);
        
        return frag;
    }
    
    function htmlify(obj)
    {
        var s = "";
        console.log(obj);
        for (var p in obj)
        {
            s += p + "="+obj[p];
        }
        return s;
    }
    
    module.request = request;
    module.update = update;
    module.on = addListener;
    
    return module;
})();