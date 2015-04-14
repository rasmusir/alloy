var Alloy = (function() {
    
    var module = {};
    var alloyViews = {};
    
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
                update(obj.data,views);
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
            var e = p.querySelector("*[name="+obj.t+"]");
            if (obj.d && e)
            {
                var d = obj.d;
                var contentupdate = document.createEvent("HTMLEvents");
                contentupdate.initEvent("contentupdate",true,true);
                contentupdate.data = d;
                
                if (e.dispatchEvent(contentupdate))
                {
                    if (d.v)
                        e.innerHTML = d.v;
                    if (d.a)
                        for (var attr in d.a)
                        {
                            e.setAttribute(attr, d.a[attr]);
                        }
                    
                }
            }
            else if (obj.v && e && obj.v.v !==undefined)
            {
                var v = views[obj.v.v];
                var view = createView(v);
                e.innerHTML = "";
                update(obj.v.data,views,view)
                e.appendChild(view);
            }
            else if(obj.v && e && obj.v.t)
            {
                var t = p.querySelector("script[name="+obj.v.t+"]") || document.querySelector("script[name="+obj.v.t+"]");
                var template = createTemplate(t);
                e.innerHTML = "";
                update(obj.v.data,views,template)
                e.appendChild(template);
            }
            else if(obj.v && e && obj.v.data)
            {
                if (!obj.append)
                e.innerHTML = "";
                obj.v.data.forEach(function(data){
                    
                    var t = p.querySelector("script[name="+data.v.t+"]") || document.querySelector("script[name="+obj.v.t+"]");
                    var template = createTemplate(t);
                    update(data.v.data,views,template)
                    e.appendChild(template);
                });
            }
        }.bind(this));
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
    
    module.request = request;
    module.update = update;
    
    return module;
})();