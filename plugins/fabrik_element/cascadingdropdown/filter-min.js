/*! Fabrik */

var CascadeFilter=new Class({initialize:function(a,b){if(this.options=b,this.observer=document.id(a),this.observer||(this.observer=document.getElements("."+a),this.observer&&(this.observer=this.observer[0])),this.observer){new Element("img",{id:this.options.filterid+"_loading",src:Fabrik.liveSite+"media/com_fabrik/images/ajax-loader.gif",alt:"loading...",styles:{opacity:"0"}}).inject(this.observer,"after");var c=this.observer.get("value");this.myAjax=new Request({url:"",method:"post",data:{option:"com_fabrik",format:"raw",task:"plugin.pluginAjax",plugin:"cascadingdropdown",method:"ajax_getOptions",element_id:this.options.elid,v:c,formid:this.options.formid,fabrik_cascade_ajax_update:1,filterview:"table"},onComplete:function(a){this.ajaxComplete(a)}.bind(this)}),this.observer.addEvent("change",function(){this.update()}.bind(this)),this.update()}else fconsole("observer not found ",a)},update:function(){this.observer&&(document.id(this.options.filterid+"_loading").setStyle("opacity","1"),this.myAjax.options.data.v=this.observer.get("value"),$filterData=eval(this.options.filterobj).getFilterData(),Object.append(this.myAjax.options.data,$filterData),this.myAjax.send())},ajaxComplete:function(a){if(a=JSON.decode(a),"null"===typeOf(document.id(this.options.filterid)))return fconsole("filterid not found: ",this.options.filterid),void this.endAjax();document.id(this.options.filterid).empty(),a.each(function(a){new Element("option",{value:a.value}).appendText(a.text).inject(document.id(this.options.filterid))}.bind(this)),this.endAjax()},endAjax:function(){document.id(this.options.filterid+"_loading").setStyle("opacity","0"),document.id(this.options.filterid).value=this.options.def,this.options.advanced&&jQuery("#"+this.options.filterid).trigger("liszt:updated")}});