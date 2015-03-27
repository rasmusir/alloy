var Alloy = (function() {
    
    var module = {};
    
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
                    update(obj);
            }
            catch (ev)
            {
                var e = {Message:"Could not parse server response.",serverResponse:x.responseText};
                console.error(e);
            }
        };
        x.send();
    };
    
    function update(data)
    {
        console.log(data);
        
        for (var obj in data.data)
        {
            var e = document.querySelector("#"+obj);
            
            var contentupdate = document.createEvent("HTMLEvents");
            contentupdate.initEvent("contentupdate",true,true);
            contentupdate.data = data.data[obj];
            
            if (e.dispatchEvent(contentupdate))
                e.innerHTML = data.data[obj];
        }
        
        for (var obj in data.attr)
        {
            var e = document.querySelector("#"+obj);
            var a = data.attr[obj];
            var attributeupdate = document.createEvent("HTMLEvents");
            attributeupdate.initEvent("attributeupdate",true,true);
            
            attributeupdate.attributes = a;
            
            if (e.dispatchEvent(attributeupdate))
            for (var attr in a)
            {
                e.setAttribute(attr, a[attr]);
            }
        }
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