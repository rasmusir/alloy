var Alloy = (function() {
    
    var module = {};
    var alloyViews = {};
    var alloyevents = {};
    
    window.addEventListener("popstate",function(e) {
        _request(e.state.location,e.state.args);
    });
    
    window.addEventListener("click", function(e) {
        var target = e.target;
        while (target)
        {
            if (target.tagName == "A")
            {
                request(target.href,null);
                e.preventDefault();
                return;
            }
            else if (target.tagName == "INPUT" && target.type=="submit")
            {
                var elements = {};
                for (var i = 0; i<target.form.length; i++)
                {
                    var t = target.form[i];
                    if (!(t.type == "submit" && t != target) && t.name != "")
                        elements[t.name] = t.value;
                }
                var include = target.form.getAttribute("include");
                if (include)
                {
                    include = include.split(" ");
                    include.forEach(function(n) {
                        var t = target.form.querySelector("*[name="+n+"]");
                        elements[t.getAttribute("name")] = t.innerHTML;
                    });
                }
                
                request(target.form.target,{post:elements});
                e.preventDefault();
                return;
            }
            else
                target = target.parentElement;
        }
        
    }.bind(this));
    
    window.addEventListener("load", function(e) {
        
        var title = document.querySelector("title") ? document.querySelector("title").innerHTML : "unknown";
        window.history.pushState({location:window.location.href,args:null},title,window.location.href);
        
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
        var metadataelement = document.querySelector("#ALLOYMETADATA");
        if (metadataelement)
        {
            var meta = JSON.parse(metadataelement.innerHTML);
            
            if (typeof(requirejs)!=="undefined")
            loadModules(meta.modules, function() {
                fireEvents(meta.events);
            });
            else
                fireEvents(meta.events);
        }
    }
    
    function request(location,args,callback)
    {
        _request(location,args,callback);
        var title = document.querySelector("title") ? document.querySelector("title").innerHTML : "unknown";
        window.history.pushState({location:location,args:args},title,location);
    }
    
    function _request(location,args,callback)
    {
        callback = callback || (function() {return true;});
        
        var x = new XMLHttpRequest();
        var method = args ? "POST" : "GET";
        x.open(method,location);
        x.setRequestHeader("Content-Type","application/json");
        x.setRequestHeader("Cache-Control", "no-cache");
        x.onload = function() {
            try
            {
                var location = x.getResponseHeader("location");
                if (location)
                    return request(location,null,callback);
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
        x.send(args ? JSON.stringify(args) : null);
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
        var views = obj.views || [];
        var count = views.length;
        
        var checkDownload = function()
        {
            if (count <= 0)
            {
                if (obj.data)
                update(obj.data,views);
                if (obj.modules)
                loadModules(obj.modules, function() {
                    fireEvents(obj.events);
                });
                else
                    fireEvents(obj.events);
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
    
    function update(data,views,p,mainview)
    {
        p = p || document;
        mainview = mainview || p;
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
                var t = mainview.querySelector("script[name="+obj.v.t+"]") || document.querySelector("script[name="+obj.v.t+"]");
                var template = createTemplate(t);
                e.dispatchEvent(elementremoved);
                e.innerHTML = "";
                update(obj.v.data,views,template,mainview)
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
                    
                    var t = mainview.querySelector("script[name="+data.v.t+"]") || document.querySelector("script[name="+obj.v.t+"]");
                    var template = createTemplate(t);
                    update(data.v.data,views,template,mainview)
                    e.appendChild(template);
                });
            }
        });
    }
    
    function loadModules(modules, callback)
    {
        if (typeof(require) !== "undefined")
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
    
    function fireServerEvent(path,event,args,callback)
    {
        var x = new XMLHttpRequest();
        x.open("POST",path);
        x.setRequestHeader("Content-Type","application/json");
        x.onload = function() {
            var obj = JSON.parse(x.responseText);
            callback(obj);
        };
        x.send(JSON.stringify({event:event,args:args}));
    }
    
    module.request = request;
    module.update = update;
    module.on = addListener;
    module.fireServerEvent = fireServerEvent;
    
    return module;
})();