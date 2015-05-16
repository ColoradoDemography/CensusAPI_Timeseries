var chart;
var table = "population";
var timeseriesmeta = {};
var jsondata = [];
var added_elements=[];

function chartGenerate() {

    var label = {};
    var categories = [];
    var format = "";

    //generate categories array
    for (i = 0; i < timeseriesmeta.length; i = i + 1) {

        if (timeseriesmeta[i].table === table) {
            //console.log('we have a match');
            for (j = 0; j < timeseriesmeta[i].columnmeta.length; j = j + 1) {
                categories.push(timeseriesmeta[i].columnmeta[j].cdesc);
            }

            label.text = timeseriesmeta[i].description;
            label.position = 'outer-center';
            format = timeseriesmeta[i].type;
        }

    }

    //console.log(jdata.data);
    chart = c3.generate({
        padding: {
            bottom: 60
        },
        data: {
            columns: [],
            onclick: function(d, element) { /*console.log("onclick", d, element);*/ },
            onmouseover: function(d) { /*console.log("onmouseover", d);*/ },
            onmouseout: function(d) { /*console.log("onmouseout", d);*/ },
        },
        axis: {
            x: {
                type: 'category',
                padding: {
                    left: -0.4,
                    right: -0.4
                },
                categories: categories,
                label: label,
                tick: {
                    centered: true
                }
            },
            y: {
                min: 0,
                padding: {
                    top: 50,
                    bottom: 0
                },
                tick: {
                    format: d3.format(format)
                }
            }
        }
    });
  
  
    //loop through id's in 'Current Chart' box, add them to chart.
    var myOpts = document.getElementById('removebox').options;
    var res;

    for (i = 0; i < myOpts.length; i = i + 1) {

        res = myOpts[i].value.split('|');

        $.ajax({
            url: "series.php?table=" + table + "&geonum=" + res[0],
            dataType: 'json',
            success: ldata
        });

    }


}


function changechart(update) {
    table = update;
    chart = chart.destroy();
    added_elements=[]; //these will be rebuilt
    chartGenerate();
}


//unload item from Current Chart
function unhandle(tval) {
    if (tval !== "") {
        //console.log(tval);
        var res = tval.split('|');
        var toremove="";
      
      //Use geonum to find correct name of county/place to remove  
      for(i=0;i<added_elements.length;i=i+1){
        if(added_elements[i].geonum==res[0]){
          toremove=added_elements[i].data[0];
        }
      }
      
        chart.unload({
            ids: toremove
        });
        //console.log(res[0].length);

        if (res[0].length == 6) {

            $("option[value='" + tval + "']").each(function() {
                $(this).appendTo('#addbox');
                sortlist('addbox');
            });
        } else {
            $("option[value='" + tval + "']").each(function() {
                $(this).appendTo('#addplace');
                sortlist('addplace');
            });

        }

    }
}



//add data point
function ldata(loadd) {

    var adddata = [];
    var evalstring = "adddata[0]=[loadd.data[0].geoname ";

    for (i = 0; i < timeseriesmeta.length; i = i + 1) {
        if (timeseriesmeta[i].table === table) {
            for (j = 0; j < timeseriesmeta[i].columnmeta.length; j = j + 1) {
                evalstring = evalstring + ",loadd.data[0]." + timeseriesmeta[i].columnmeta[j].colname;
            }
        }
    }

    evalstring = evalstring + "];"
    eval(evalstring);

    chart.load({
        columns: adddata
    });

  //keep track of added places and counties
  var newobj={};
  newobj.geonum=loadd.data[0].geonum;
  newobj.data=adddata[0];
  added_elements.push(newobj);
  //console.log(added_elements);
  
}


//add item from 'Add County' or 'Add Place' Select Box to 'Current Chart' by double clicking
function handle_geo_add(tval) {
    if (tval !== "") {
        var res = tval.split('|');
        $("option[value='" + tval + "']").each(function() {
            $(this).appendTo('#removebox');
            sortlist('removebox');
        });

        $.ajax({
            url: "series.php?table=" + table + "&geonum=" + res[0],
            dataType: 'json',
            success: ldata
        });

    }

}



function sortlist(elem) {

    var cl = document.getElementById(elem);
    var clTexts = new Array();

    for (i = 0; i < cl.length; i++) {
        clTexts[i] =
            cl.options[i].text.toUpperCase() + "," +
            cl.options[i].text + "," +
            cl.options[i].value + "," +
            cl.options[i].selected;
    }

    clTexts.sort();

    for (i = 0; i < cl.length; i++) {
        var parts = clTexts[i].split(',');

        cl.options[i].text = parts[1];
        cl.options[i].value = parts[2];
        if (parts[3] == "true") {
            cl.options[i].selected = true;
        } else {
            cl.options[i].selected = false;
        }
    }
}



//initialize
$(document).ready(function() {

    $.getJSON("js/timeseriesmeta.js", function(json) {
        timeseriesmeta = json.data;

        //once all metadata is loaded, we can create a blank chart
        chartGenerate();

    });


});