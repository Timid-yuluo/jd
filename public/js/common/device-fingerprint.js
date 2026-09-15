(function(){
    'use strict';

    function collectFingerprint(){
        var fp={
            canvas:(function(){
                try{
                    var c=document.createElement('canvas');
                    var ctx=c.getContext('2d');
                    ctx.textBaseline='top';
                    ctx.font='14px Arial';
                    ctx.fillText('FP_X',2,2);
                    return c.toDataURL().slice(-50);
                }catch(e){return'na';}
            })(),
            webgl:(function(){
                try{
                    var c=document.createElement('canvas');
                    var gl=c.getContext('webgl');
                    var dbg=gl.getExtension('WEBGL_debug_renderer_info');
                    return dbg?gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL):'na';
                }catch(e){return'na';}
            })(),
            screen:screen.width+'x'+screen.height+'@'+(window.devicePixelRatio||1),
            tz:Intl.DateTimeFormat().resolvedOptions().timeZone,
            lang:navigator.language,
            plat:navigator.platform,
            cores:navigator.hardwareConcurrency||0,
            mem:navigator.deviceMemory||0,
            touch:navigator.maxTouchPoints||0,
            fonts:(function(){
                try{
                    var fonts=['Arial','Helvetica','Times New Roman','Courier','Verdana','Georgia','Comic Sans MS','Impact','Tahoma','Trebuchet MS','Palatino','Lucida Console'];
                    var available=[];
                    var canvas=document.createElement('canvas');
                    var ctx=canvas.getContext('2d');
                    var testStr='mmmmmmmmmmlli';
                    var defaultWidth=ctx.measureText(testStr).width;
                    for(var i=0;i<fonts.length;i++){
                        ctx.font='72px "'+fonts[i]+'", monospace';
                        if(ctx.measureText(testStr).width!==defaultWidth){
                            available.push(fonts[i]);
                        }
                    }
                    return available.join(',');
                }catch(e){return'na';}
            })(),
            colorScheme:window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light',
            cookieEnabled:navigator.cookieEnabled
        };
        return fp;
    }

    function hashFingerprint(raw){
        var str=JSON.stringify(raw);
        var hash=0;
        for(var i=0;i<str.length;i++){
            hash=((hash<<5)-hash)+str.charCodeAt(i);
            hash=hash&hash;
        }
        var hex=Math.abs(hash).toString(16).padStart(8,'0');
        var fpStr=btoa(unescape(encodeURIComponent(str))).slice(0,32);
        return hex+'.'+fpStr;
    }

    function setCookie(name,value,hours){
        var expires='';
        if(hours){
            var d=new Date();
            d.setTime(d.getTime()+(hours*60*60*1000));
            expires='; expires='+d.toUTCString();
        }
        document.cookie=name+'='+encodeURIComponent(value)+expires+'; path=/; SameSite=Lax';
    }

    function sendFingerprint(payload){
        var csrfToken=document.querySelector('meta[name="csrf-token"]');
        var token=csrfToken?csrfToken.content:'';

        setCookie('_dfp',payload,1);

        var xhr=new XMLHttpRequest();
        xhr.open('POST',window.__deviceFpEndpoint||'/admin/device-fingerprint',true);
        xhr.setRequestHeader('Content-Type','application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN',token);
        xhr.setRequestHeader('X-Device-Fingerprint',payload);
        xhr.onreadystatechange=function(){
            if(xhr.readyState===4&&xhr.status===200){
                try{
                    var resp=JSON.parse(xhr.responseText);
                    if(resp.stored){
                        setCookie('_dfp_ok','1',1);
                    }
                }catch(e){}
            }
        };
        xhr.send(JSON.stringify({fp:payload}));
    }

    try{
        var fp=collectFingerprint();
        var payload=hashFingerprint(fp);
        sendFingerprint(payload);
    }catch(e){}
})();
