/*! Fabrik */

define(["jquery","fab/list-plugin","fab/fabrik"],function(jQuery,FbListPlugin,Fabrik){var FbListInlineedit=new Class({Extends:FbListPlugin,initialize:function(a){if(this.parent(a),this.defaults={},this.editors={},this.inedit=!1,this.saving=!1,"null"===typeOf(this.getList().getForm()))return!1;this.listid=this.options.listid,this.setUp(),Fabrik.addEvent("fabrik.list.clearrows",function(){this.cancel()}.bind(this)),Fabrik.addEvent("fabrik.list.inlineedit.stopEditing",function(){this.stopEditing()}.bind(this)),Fabrik.addEvent("fabrik.list.updaterows",function(){this.watchCells()}.bind(this)),Fabrik.addEvent("fabrik.list.ini",function(){var a=this.getList(),b=a.form.toQueryString().toObject();b.format="raw",b.listref=this.options.ref;new Request.JSON({url:"",data:b,onComplete:function(){console.log("complete")},onSuccess:function(b){b=Json.evaluate(b.stripScripts()),a.options.data=b.data}.bind(this),onFailure:function(a){console.log("ajax inline edit failure",a)},onException:function(a,b){console.log("ajax inline edit exception",a,b)}}).send()}.bind(this)),Fabrik.addEvent("fabrik.element.click",function(){1===Object.getLength(this.options.elements)&&!1===this.options.showSave&&this.save(null,this.editing)}.bind(this)),Fabrik.addEvent("fabrik.list.inlineedit.setData",function(){"null"!==typeOf(this.editOpts)&&($H(this.editOpts.plugins).each(function(a){var b=Fabrik["inlineedit_"+this.editOpts.elid].elements[a];delete b.element,b.update(this.editData[a]),b.select()}.bind(this)),this.watchControls(this.editCell),this.setFocus(this.editCell))}.bind(this)),window.addEvent("click",function(a){!a.target.hasClass("fabrik_element")&&this.td&&(this.td.removeClass(this.options.focusClass),this.td=null)}.bind(this))},setUp:function(){"null"!==typeOf(this.getList().getForm())&&(this.scrollFx=new Fx.Scroll(window,{wait:!1}),this.watchCells(),document.addEvent("keydown",function(a){this.checkKey(a)}.bind(this)))},watchCells:function(){var a=!1;this.getList().getForm().getElements(".fabrik_element").each(function(b,c){if(this.canEdit(b)){if(!a&&this.options.loadFirst&&(a=this.edit(null,b))&&this.select(null,b),!this.isEditable(b))return;this.setCursor(b),b.removeEvents(),b.addEvent(this.options.editEvent,function(a){this.edit(a,b)}.bind(this)),b.addEvent("click",function(a){this.select(a,b)}.bind(this)),b.addEvent("mouseenter",function(a){this.isEditable(b)||b.setStyle("cursor","pointer")}.bind(this)),b.addEvent("mouseleave",function(a){b.setStyle("cursor","")})}}.bind(this))},checkKey:function(a){var b,c,d;if("element"===typeOf(this.td))switch(a.code){case 39:if(this.inedit)return;"element"===typeOf(this.td.getNext())&&(a.stop(),this.select(a,this.td.getNext()));break;case 9:if(this.inedit)return void(this.options.tabSave&&("element"===typeOf(this.editing)?this.save(a,this.editing):this.edit(a,this.td)));break;case 37:if(this.inedit)return;"element"===typeOf(this.td.getPrevious())&&(a.stop(),this.select(a,this.td.getPrevious()));break;case 40:if(this.inedit)return;if(c=this.td.getParent(),"null"===typeOf(c))return;d=c.getElements("td").indexOf(this.td),"element"===typeOf(c.getNext())&&(a.stop(),b=c.getNext().getElements("td"),this.select(a,b[d]));break;case 38:if(this.inedit)return;if(c=this.td.getParent(),"null"===typeOf(c))return;d=c.getElements("td").indexOf(this.td),"element"===typeOf(c.getPrevious())&&(a.stop(),b=c.getPrevious().getElements("td"),this.select(a,b[d]));break;case 27:a.stop(),this.inedit?(this.select(a,this.editing),this.cancel(a)):(this.td.removeClass(this.options.focusClass),this.td=null);break;case 13:if(this.inedit||"element"!==typeOf(this.td))return;if(a.stop(),"element"===typeOf(this.editing)){if(this.editors[this.activeElementId].contains("<textarea"))return;this.save(a,this.editing)}else this.edit(a,this.td)}},select:function(a,b){if(this.isEditable(b)){var c=this.getElementName(b),d=this.options.elements[c];if(!1!==typeOf(d)&&("element"===typeOf(this.td)&&this.td.removeClass(this.options.focusClass),this.td=b,"element"===typeOf(this.td)&&this.td.addClass(this.options.focusClass),"null"!==typeOf(this.td)&&a&&"click"!==a.type&&"mouseover"!==a.type)){var e=this.td.getPosition(),f=e.x-window.getSize().x/2-this.td.getSize().x/2,g=e.y-window.getSize().y/2+this.td.getSize().y/2;this.scrollFx.start(f,g)}}},getElementName:function(a){return a.className.trim().split(" ").filter(function(a,b){return"fabrik_element"!==a&&"fabrik_row"!==a&&!a.contains("hidden")})[0].replace("fabrik_row___","")},setCursor:function(a){var b=this.getElementName(a),c=this.options.elements[b];"null"!==typeOf(c)&&(a.addEvent("mouseover",function(a){this.isEditable(a.target)&&a.target.setStyle("cursor","pointer")}),a.addEvent("mouseleave",function(a){this.isEditable(a.target)&&a.target.setStyle("cursor","")}))},isEditable:function(a){var b;return!(a.hasClass("fabrik_uneditable")||a.hasClass("fabrik_ordercell")||a.hasClass("fabrik_select")||a.hasClass("fabrik_actions"))&&(b=this.getRowId(a.getParent(".fabrik_row")),this.getList().firePlugin("onCanEditRow",b))},getPreviousEditable:function(a){for(var b=!1,c=this.getList().getForm().getElements(".fabrik_element"),d=c.length;d>=0;d--){if(b&&this.canEdit(c[d]))return c[d];c[d]===a&&(b=!0)}return!1},getNextEditable:function(a){var b=!1;return this.getList().getForm().getElements(".fabrik_element").filter(function(c,d){return b&&this.canEdit(c)?(b=!1,!0):(c===a&&(b=!0),!1)}.bind(this)).getLast()},canEdit:function(a){if(!this.isEditable(a))return!1;var b=this.getElementName(a),c=this.options.elements[b];return"null"!==typeOf(c)},edit:function(e,td){if(!this.saving){if(Fabrik.fireEvent("fabrik.plugin.inlineedit.editing"),this.inedit){if("mouseover"!==this.options.editEvent)return;if(td===this.editing)return;this.select(e,this.editing),this.cancel()}if(!this.canEdit(td))return!1;"null"!==typeOf(e)&&e.stop();var element=this.getElementName(td),rowid=this.getRowId(td),opts=this.options.elements[element];if("null"!==typeOf(opts)){this.inedit=!0,this.editing=td,this.activeElementId=opts.elid,this.defaults[rowid+"."+opts.elid]=td.innerHTML;var data=this.getDataFromTable(td);if("null"===typeOf(this.editors[opts.elid])||"null"===typeOf(Fabrik["inlineedit_"+opts.elid])){Fabrik.loader.start(td.getParent());var inline=this.options.showSave?1:0,editRequest=new Request({evalScripts:function(a,b){this.javascript=a}.bind(this),evalResponse:!1,url:"",data:{element:element,elid:opts.elid,elementid:Object.values(opts.plugins),rowid:rowid,listref:this.options.ref,formid:this.options.formid,listid:this.options.listid,inlinesave:inline,inlinecancel:this.options.showCancel,option:"com_fabrik",task:"form.inlineedit",format:"raw"},onSuccess:function(a){Fabrik.loader.stop(td.getParent()),function(){window.Browser.exec(this.javascript),Fabrik.tips.attach(".fabrikTip")}.bind(this).delay(100),td.empty().set("html",a),this.clearSelection(),a=a+'<script type="text/javascript">'+this.javascript+"<\/script>",this.editors[opts.elid]=a,this.watchControls(td),this.setFocus(td)}.bind(this),onFailure:function(a){this.saving=!1,this.inedit=!1,Fabrik.loader.stop(td.getParent()),window.alert(editRequest.getHeader("Status"))}.bind(this),onException:function(a,b){this.saving=!1,this.inedit=!1,Fabrik.loader.stop(td.getParent()),window.alert("ajax inline edit exception "+a+":"+b)}.bind(this)}).send()}else{var html=this.editors[opts.elid].stripScripts(function(a){this.javascript=a}.bind(this));td.empty().set("html",html),eval(this.javascript),this.clearSelection(),Fabrik.tips.attach(".fabrikTip"),this.editOpts=opts,this.editData=data,this.editCell=td}return!0}}},clearSelection:function(){document.selection?document.selection.empty():window.getSelection().removeAllRanges()},getDataFromTable:function(a){var b=this.getList().options.data,c=this.getElementName(a),d=a.getParent(".fabrik_row").id,e={};this.vv=[],"object"===typeOf(b)&&(b=$H(b)),b.each(function(a){if("array"===typeOf(a))for(var b=0;b<a.length;b++)a[b].id===d&&this.vv.push(a[b]);else{a.filter(function(a){return a.id===d})}}.bind(this));var f=this.options.elements[c];return this.vv.length>0&&$H(f.plugins).each(function(a,b){e[a]=this.vv[0].data[b+"_raw"]}.bind(this)),e},setTableData:function(a,b,c){var d=a.id,e=this.getList().options.data;"object"===typeOf(e)&&(e=$H(e)),e.each(function(a,e){a.each(function(a,e){a.id===d&&(a.data[b+"_raw"]=c,this.currentRow=a)}.bind(this))}.bind(this))},setFocus:function(a){if(!window.Browser.ie){var b=a.getElement(".fabrikinput");if("null"!==typeOf(b)){(function(){"null"!==typeOf(b)&&b.focus()}).delay(1e3)}}},watchControls:function(a){"null"!==typeOf(a.getElement(".inline-save"))&&a.getElement(".inline-save").removeEvents("click").addEvent("click",function(b){this.save(b,a)}.bind(this)),"null"!==typeOf(a.getElement(".inline-cancel"))&&a.getElement(".inline-cancel").removeEvents("click").addEvent("click",function(b){this.cancel(b,a)}.bind(this))},save:function(a,b){var c=this.getElementName(b),d=this.options.elements[c],e=this.editing.getParent(".fabrik_row"),f=this.getRowId(e),g={},h={},i={};if(this.editing){if(this.saving=!0,this.inedit=!1,a&&a.stop(),h=Fabrik["inlineedit_"+d.elid],"null"===typeOf(h))return fconsole("issue saving from inline edit: eObj not defined"),this.cancel(a),!1;Fabrik.loader.start(b.getParent()),i={option:"com_fabrik",task:"form.process",format:"raw",packageId:1,fabrik_ajax:1,element:c,listref:this.options.ref,elid:d.elid,plugin:d.plugin,rowid:f,listid:this.options.listid,formid:this.options.formid,fabrik_ignorevalidation:1},i.fabrik_ignorevalidation=0,i.join={},$H(h.elements).each(function(a){a.getElement();var b=a.getValue(),c=a.options.joinId;this.setTableData(e,a.options.element,b),a.options.isJoin?("object"!==typeOf(i.join[c])&&(i.join[c]={}),i.join[c][a.options.elementName]=b):i[a.options.element]=b}.bind(this)),$H(this.currentRow.data).each(function(a,b){"_raw"===b.substr(b.length-4,4)&&(g[b.substr(0,b.length-4)]=a)}),i=Object.append(g,i),i[h.token]=1,i.toValidate=this.options.elements[i.element].plugins,this.saveRequest=new Request({url:"",data:i,evalScripts:!0,onSuccess:function(a){b.empty(),b.empty().set("html",a),Fabrik.loader.stop(b.getParent()),Fabrik.fireEvent("fabrik.list.updaterows"),this.stopEditing(),this.saving=!1}.bind(this),onFailure:function(a){var c=b.getElement(".inlineedit .fabrikMainError");"null"===typeOf(c)&&(c=new Element("div.fabrikMainError.fabrikError.alert.alert-error"),c.inject(b.getElement("form"),"top")),this.saving=!1,Fabrik.loader.stop(b.getParent());var d=a.statusText;"null"===typeOf(d)&&(d="uncaught error"),c.set("html",d)}.bind(this),onException:function(a,c){Fabrik.loader.stop(b.getParent()),this.saving=!1,window.alert("ajax inline edit exception "+a+":"+c)}.bind(this)}).send()}},stopEditing:function(a){this.editing;this.editing=null,this.inedit=!1},cancel:function(a){if(a&&a.stop(),"element"===typeOf(this.editing)){var b=this.editing.getParent(".fabrik_row");if(!1!==b){var c=this.getRowId(b),d=this.editing;if(!1!==d){var e=this.getElementName(d),f=this.options.elements[e],g=this.defaults[c+"."+f.elid];d.set("html",g)}this.stopEditing()}}}});return FbListInlineedit});