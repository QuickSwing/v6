/*! Fabrik */

!function(p,v,b,e){function h(e){return v.getElementById(e)}b.runtimes.Html4=b.addRuntime("html4",{getFeatures:function(){return{multipart:!0,triggerDialog:b.ua.gecko&&p.FormData||b.ua.webkit}},init:function(f,e){f.bind("Init",function(d){var i,r,l,e,t,n,o,u=v.body,s="javascript",c=[],g=/MSIE/.test(navigator.userAgent),m=[],a=d.settings.filters;e:for(e=0;e<a.length;e++)for(t=a[e].extensions.split(/,/),o=0;o<t.length;o++){if("*"===t[o]){m=[];break e}(n=b.mimeTypes[t[o]])&&m.push(n)}m=m.join(","),d.settings.container&&(u=h(d.settings.container),"static"===b.getStyle(u,"position")&&(u.style.position="relative")),d.bind("UploadFile",function(e,t){var n;t.status!=b.DONE&&t.status!=b.FAILED&&e.state!=b.STOPPED&&(n=h("form_"+t.id),h("input_"+t.id).setAttribute("name",e.settings.file_data_name),n.setAttribute("action",e.settings.url),b.each(b.extend({name:t.target_name||t.name},e.settings.multipart_params),function(e,t){var i=v.createElement("input");b.extend(i,{type:"hidden",name:t,value:e}),n.insertBefore(i,n.firstChild)}),r=t,h("form_"+l).style.top="-1048575px",n.submit(),n.parentNode.removeChild(n))}),d.bind("FileUploaded",function(e){e.refresh()}),d.bind("StateChanged",function(e){var t;e.state==b.STARTED&&((t=v.createElement("div")).innerHTML='<iframe id="'+d.id+'_iframe" name="'+d.id+'_iframe" src="'+s+':&quot;&quot;" style="display:none"></iframe>',i=t.firstChild,u.appendChild(i),b.addEvent(i,"load",function(e){var t,i,n=e.target;if(r){try{t=n.contentWindow.document||n.contentDocument||p.frames[n.id].document}catch(e){return void d.trigger("Error",{code:b.SECURITY_ERROR,message:b.translate("Security error."),file:r})}(i=t.body.innerHTML)&&(r.status=b.DONE,r.loaded=1025,r.percent=100,d.trigger("UploadProgress",r),d.trigger("FileUploaded",r,{response:i}))}},d.id)),e.state==b.STOPPED&&p.setTimeout(function(){b.removeEvent(i,"load",e.id),i.parentNode&&i.parentNode.removeChild(i)},0)}),d.bind("Refresh",function(e){var t,i,n,r,o,s,a,d;(t=h(e.settings.browse_button))&&(o=b.getPos(t,h(e.settings.container)),s=b.getSize(t),a=h("form_"+l),h("input_"+l),b.extend(a.style,{top:o.y+"px",left:o.x+"px",width:s.w+"px",height:s.h+"px"}),e.features.triggerDialog&&("static"===b.getStyle(t,"position")&&b.extend(t.style,{position:"relative"}),d=parseInt(t.style.zIndex,10),isNaN(d)&&(d=0),b.extend(t.style,{zIndex:d}),b.extend(a.style,{zIndex:d-1})),n=e.settings.browse_button_hover,r=e.settings.browse_button_active,i=e.features.triggerDialog?t:a,n&&(b.addEvent(i,"mouseover",function(){b.addClass(t,n)},e.id),b.addEvent(i,"mouseout",function(){b.removeClass(t,n)},e.id)),r&&(b.addEvent(i,"mousedown",function(){b.addClass(t,r)},e.id),b.addEvent(v.body,"mouseup",function(){b.removeClass(t,r)},e.id)))}),f.bind("FilesRemoved",function(e,t){var i,n;for(i=0;i<t.length;i++)(n=h("form_"+t[i].id))&&n.parentNode.removeChild(n)}),f.bind("Destroy",function(e){var t,i,n,r={inputContainer:"form_"+l,inputFile:"input_"+l,browseButton:e.settings.browse_button};for(t in r)(i=h(r[t]))&&b.removeAllEvents(i,e.id);b.removeAllEvents(v.body,e.id),b.each(c,function(e,t){(n=h("form_"+e))&&u.removeChild(n)})}),function r(){var o,s,e,a;l=b.guid(),c.push(l),(o=v.createElement("form")).setAttribute("id","form_"+l),o.setAttribute("method","post"),o.setAttribute("enctype","multipart/form-data"),o.setAttribute("encoding","multipart/form-data"),o.setAttribute("target",d.id+"_iframe"),o.style.position="absolute",(s=v.createElement("input")).setAttribute("id","input_"+l),s.setAttribute("type","file"),s.setAttribute("accept",m),s.setAttribute("size",1),a=h(d.settings.browse_button),d.features.triggerDialog&&a&&b.addEvent(h(d.settings.browse_button),"click",function(e){s.click(),e.preventDefault()},d.id),b.extend(s.style,{width:"100%",height:"100%",opacity:0,fontSize:"999px"}),b.extend(o.style,{overflow:"hidden"}),(e=d.settings.shim_bgcolor)&&(o.style.background=e),g&&b.extend(s.style,{filter:"alpha(opacity=0)"}),b.addEvent(s,"change",function(e){var t,i=e.target,n=[];i.value&&(h("form_"+l).style.top="-1048575px",t=(t=i.value.replace(/\\/g,"/")).substring(t.length,t.lastIndexOf("/")+1),n.push(new b.File(l,t)),d.features.triggerDialog?b.removeEvent(a,"click",d.id):b.removeAllEvents(o,d.id),b.removeEvent(s,"change",d.id),r(),n.length&&f.trigger("FilesAdded",n))},d.id),o.appendChild(s),u.appendChild(o),d.refresh()}()}),e({success:!0})}})}(window,document,plupload);