/*
 * @category  UI interaction
 * @package   
 * @author    Sarven Capadisli <sarven.capadisli@deri.org>
 * @copyright 2010 Digital Enterprise Research Institute
 * @license   
 * @link      http://deri.org/
 */


var LATC = { // 
    C: { // Config
        I: { // Init

        },

        S: { // Selector

        }
    },

    U: { // Utils
        Init: {

        },

        GoogleCharts: function() { //XXX: Not at all stable. Test only.
            var queryString = '';
            var dataUrl = '';

            function onLoadCallback() {
                if (dataUrl.length > 0) {
                    var query = new google.visualization.Query(dataUrl);
                    query.setQuery(queryString);
                    query.send(handleQueryResponse);
                } else {
                    var dataTable = new google.visualization.DataTable();
                    dataTable.addRows(18);

                    for (i=0; i <= 4; i++) {
                        dataTable.addColumn('number');
                    }

                    var a = [];
                    var c = [];
                    var i = 0;
                    $('#marital-status-age-population table tbody tr').each(function() {
                        c = [];
                        i = 0;
                        $('td', this).each(function() {
                            //XXX: Temporary hack until rdfs:label or 'total's are sorted out.
                            if (i != 4) {
                                if (this.textContent.length > 0) {
                                    c.push(this.textContent);
                //                        console.log(c);
                                }
                            }
                            i++;
                        });
                        a.push(c);
                    });

                    a.shift();
                    a.pop();
                    i = 0;
                    $(a).each(function() {
                        var j = 0;
                        $(this).each(function() {
                            dataTable.setValue(i, j, parseFloat(this));
                            j++;
                        });
                        i++;
                    });

                    draw(dataTable);
                }
            }
            /*
            var legend_maritalStatus = [];
            $('#marital-status-age-population table tbody tr:first th').each(function() {
                if (this.textContent.length > 0) {
                    legend_maritalStatus.push(this.textContent);
                }
            });
            legend_maritalStatus = legend_maritalStatus.join("|");
            */
            function draw(dataTable) {
                var vis = new google.visualization.ImageChart(document.getElementById('chart_msap'));
                var options = {
                    chxl: '0:|0|10|15|20|25|30|35|40|45|5|50|55|60|65|70|75|80|>85',
                    chxp: '',
                    chxr: '',
                    chxs: '0,676767,12,0,l,676767',
                    chxtc: '',
                    chxt: 'x',
                    chbh: '23,5,10',
                    chs: '1100x800',
                    cht: 'bvs',
                    chco: 'CE5C00,8F5902,4E9A06,204A87,A40000',

                    chdl: 'Divorced|Married|Separated|Single|Widowed',
                    chdlp: 'r',
                    chtt: 'Marital Status and Age population',
                    chts: '000000,12'
                };
                vis.draw(dataTable, options);
            }

            function handleQueryResponse(response) {
                if (response.isError()) {
                    alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
                    return;
                }
                draw(response.getDataTable());
            }

            google.load("visualization", "1", {packages:["imagechart"]});
            google.setOnLoadCallback(onLoadCallback);
        }
    }
};


$(document).ready(function(){

});
