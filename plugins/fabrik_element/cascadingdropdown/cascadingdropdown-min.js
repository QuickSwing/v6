/*! fabrik 2015-03-23 */
var FbCascadingdropdown=new Class({Extends:FbDatabasejoin,initialize:function(a,b){this.ignoreAjax=!1,this.parent(a,b),this.plugin="cascadingdropdown",document.id(this.options.watch)&&(this.doChangeEvent=this.doChange.bind(this),document.id(this.options.watch).addEvent(this.options.watchChangeEvent,this.doChangeEvent)),this.options.showDesc===!0&&this.element.addEvent("change",function(a){this.showDesc(a)}.bind(this)),"null"!==typeOf(this.element)&&(this.spinner=new Spinner(this.element.getParent(".fabrikElementContainer")))},attachedToForm:function(){if(this.ignoreAjax||this.options.editable&&!this.options.editing){var a=this.form.formElements.get(this.options.watch).getValue();this.change(a,document.id(this.options.watch).id)}},dowatch:function(a){var b=Fabrik.blocks[this.form.form.id].formElements[this.options.watch].getValue();this.change(b,a.target.id)},doChange:function(a){"auto-complete"===this.options.displayType&&(this.element.value="",this.getAutoCompleteLabelField().value=""),this.dowatch(a)},change:function(a,b){if(window.ie&&0===this.options.repeatCounter.toInt()){var c=b.substr(b.length-2,1),d=b.substr(b.length-1,1);if("_"===c&&"number"===typeOf(parseInt(d,10))&&"0"!==d)return}this.spinner.show();var e=this.form.getFormElementData(),f={option:"com_fabrik",format:"raw",task:"plugin.pluginAjax",plugin:"cascadingdropdown",method:"ajax_getOptions",element_id:this.options.id,v:a,formid:this.form.id,fabrik_cascade_ajax_update:1,lang:this.options.lang};f=Object.append(e,f),this.myAjax&&this.myAjax.cancel(),this.myAjax=new Request({url:"",method:"post",data:f,onComplete:function(){this.spinner.hide()}.bind(this),onSuccess:function(a){{var b,c;this.options.def}this.spinner.hide(),this.setValue(this.getValue()),a=JSON.decode(a),this.options.editable?this.destroyElement():this.element.getElements("div").destroy(),this.options.showDesc===!0&&(c=this.getContainer().getElement(".dbjoin-description"),c.empty()),this.myAjax=null;var d=1===a.length;if(this.ignoreAjax){if(this.options.showPleaseSelect&&a.length>0){var e=a.shift();this.options.editable===!1?new Element("div").set("text",e.text).inject(this.element):(b=""!==e.value&&e.value===this.getValue()||d,this.addOption(e.value,e.text,b),new Element("option",{value:e.value,selected:"selected"}).set("text",e.text).inject(this.element))}}else a.each(function(a){if(this.options.editable===!1?(a.text=a.text.replace(/\n/g,"<br />"),new Element("div").set("html",a.text).inject(this.element)):(b=""!==a.value&&a.value===this.getValue()||d,this.addOption(a.value,a.text,b)),this.options.showDesc===!0&&a.description){var e=this.options.showPleaseSelect?"notice description-"+k:"notice description-"+(k-1);new Element("div",{styles:{display:"none"},"class":e}).set("html",a.description).inject(c)}}.bind(this));this.ignoreAjax=!1,this.options.editable&&"dropdown"===this.options.displayType&&(1===this.element.options.length?this.element.addClass("readonly"):this.element.removeClass("readonly")),this.renewEvents(),this.ignoreAjax||(this.ingoreShowDesc=!0,this.element.fireEvent("change",new Event.Mock(this.element,"change")),this.ingoreShowDesc=!1),this.ignoreAjax=!1,Fabrik.fireEvent("fabrik.cdd.update",this)}.bind(this),onFailure:function(){console.log(this.myAjax.getHeader("Status"))}.bind(this)}).send()},destroyElement:function(){switch(this.options.displayType){case"radio":case"checkbox":this.getContainer().getElements(".fabrik_subelement").destroy();break;case"dropdown":default:this.element.empty()}},cloned:function(a){this.myAjax=null,this.parent(a),this.spinner=new Spinner(this.element.getParent(".fabrikElementContainer")),document.id(this.options.watch)&&(this.options.watchInSameGroup===!0&&(this.options.watch=this.options.watch.test(/_(\d+)$/)?this.options.watch.replace(/_(\d+)$/,"_"+a):this.options.watch+"_"+a),document.id(this.options.watch)&&(this.options.watchInSameGroup&&document.id(this.options.watch).removeEvent(this.options.watchChangeEvent,this.doChangeEvent),this.doChangeEvent=this.doChange.bind(this),document.id(this.options.watch).addEvent(this.options.watchChangeEvent,this.doChangeEvent))),this.options.watchInSameGroup===!0&&(this.element.empty(),this.ignoreAjax=!0),this.options.showDesc===!0&&this.element.addEvent("change",function(){this.showDesc()}.bind(this)),Fabrik.fireEvent("fabrik.cdd.update",this)},cloneAutoComplete:function(){var a=this.getAutoCompleteLabelField();a.id=this.element.id+"-auto-complete",a.name=this.element.name.replace("[]","")+"-auto-complete",document.id(a.id).value="",new FabCddAutocomplete(this.element.id,this.options.autoCompleteOpts)},showDesc:function(a){if(this.ingoreShowDesc!==!0){var b=document.id(a.target).selectedIndex,c=this.getContainer().getElement(".dbjoin-description"),d=c.getElement(".description-"+b);c.getElements(".notice").each(function(a){if(a===d){var b=new Fx.Style(d,"opacity",{duration:400,transition:Fx.Transitions.linear});b.set(0),a.show(),b.start(0,1)}else a.hide()}.bind(this))}}});