/*! Fabrik */

var fabrikFusiongraph=new Class({Implements:[Options],options:{legend:!1,label:"",aChartKeys:{},axis_label:"",json:{},chartType:"Column3D",xticks:[]},initialize:function(t,s,i){this.el=t,this.setOptions(i),this.json=s,this.render()},render:function(){switch(this.options.chartType){case"Column3D":this.graph=new Plotr.BarChart(this.el,this.options);break;case"PieChart":this.graph=new Plotr.PieChart(this.el,this.options);break;case"LineChart":this.graph=new Plotr.LineChart(this.el,this.options)}this.graph.addDataset(this.json),this.graph.render(),"1"===this.options.legend&&this.graph.addLegend(this.el)}});