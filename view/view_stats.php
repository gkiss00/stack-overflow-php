<!DOCTYPE html>

<?php
    require_once "lib/parsedown-1.7.3/Parsedown.php";
    require_once "util/Utils.php";
?>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Stats</title>
    <base href="<?= $web_root ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Stack overflow for stuck people">
    <meta name="author" content="Gautier Kiss | Guillaume Rigaux">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="icon" href="upload/favicon.ico" />
    <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    <script src="lib/jquery-3.4.1.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

    <script>
        let nbr_stats = <?= $nbr_stats ?>;
        let number = 3;
        let period = "month";
        let stats = <?= $stats_json ?>;
        var myChart;
        var labels;
        var data;
        var tblDetails;

        $(function (){
            $("#error_js").hide();  
            $("#show_js").show();
            $("#period_number").val(number);
            $("#period_option").val(period);

            tblDetails = $("#details_show");               

            displayChart();
        });

        function numberChanged() {
            number = $("#period_number").val();
            
            valuesChanged();
        }

        function periodChanged(){
            var period_option = $("#period_option").val();
            if (period_option === "day") {
                period = "day";
            }
            else if (period_option === "week") {
                period = "week";
            } 
            else if (period_option === "month") {
                period = "month";
            } 
            else if (period_option === "year") {
                period = "year";
            }
            valuesChanged();
        }

        function valuesChanged(){
            $.get("user/get_stats_service/"+period+"/"+number, function(data){
                    stats = data;
                    updateChartValues();
                }, "json").fail(function(){
                    tblDetails.html("<tr><td>Error encountered while retrieving the details!</td></tr>");
                    tblDetails.toggle();
                });
        }

        function getLabels() {
            let label = "{";
            let size = Object.keys(stats).length;
            let i = 0;
            for (let s of stats)
            {
                if (i < size - 1) {
                    label += "\"" + i + "\":\"" + s.username + "\",";
                } else {
                    label += "\"" + i + "\":\"" + s.username + "\"";
                }
                i++;
            }
            label += "}";
            label = JSON.parse(label);
            return label;
        }

        function getData() {
            let data = "{";
            let size = Object.keys(stats).length;
            let i = 0;
            for (let s of stats)
            {
                if (i < size - 1) {
                    data += "\"" + i + "\":\"" + s.totalactions + "\",";
                } else {
                    data += "\"" + i + "\":\"" + s.totalactions + "\"";
                }
                i++;
            }
            data += "}";
            data = JSON.parse(data);
            return data;
        }

        function updateChartValues() {
            labels = getLabels();
            data = getData();
            myChart.data.labels = Object.values(labels);
            myChart.data.datasets[0].data = Object.values(data);
            myChart.update();
        }

        function clickEvent(evt, item){
            tblDetails.show();
            let index = item[0]["_index"];
            let pseudo = labels[index];
            $.get("post/get_details_service/"+period+"/"+number+"/"+pseudo, function(data){
                    var details = data;
                    displayTable(pseudo, details);
                }, "json").fail(function(){
                    tblDetails.html("<tr><td>Error encountered while retrieving the details!</td></tr>");
                });
        }

        function displayTable(pseudo, details) {
            tblDetails.html("");
            let html = "<h2>Detailed acitivity for " + pseudo + "</h1>" +
                        "<table>" + 
                        "<thead><tr><th>Moment</th><th>Type</th><th>Question</th></tr></thead>";
            for (let d of details)
            {
                html += "<tr>";
                html += "<td>" + d.timestamp + "</td>";
                html += "<td>create/update " + d.type + "</td>";
                html += "<td><a href=\"post/show/"+ d.postId +"\">" + d.title + "</a></td>";
                html += "</tr>";
            }
            html += "</table>";
            tblDetails.html(html);
        }

        function displayChart() {
            labels = getLabels();
            data = getData();

            var ctx = document.getElementById('myChart').getContext('2d');
            myChart = new Chart(ctx, {
                height:260,
                type: 'bar',
                data: {
                    labels: Object.values(labels),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    title: {
                        display: true,
                        text: 'Most active members',
                    },
                    legend: {
                        display: false,
                    },
                    onClick: clickEvent,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        }


    </script>
</head>
<body>
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main>
        <div id="error_js">
            <h1>You must have Javascript activated to see this page!</h1>
        </div>
        <div id ="show_js" hidden>
            <div id="period_choice">
                <h2>Period : Last </h2>
                <input id="period_number" type="number" min="1" max="99" onChange="numberChanged();">
                <select id="period_option" onChange="periodChanged();">
                    <option value="day" id="period_day">Day(s)</option>
                    <option value="week" id="period_week">Week(s)</option>
                    <option value="month" id="period_month">Month(s)</option>
                    <option value="year" id="period_year">Year(s)</option>
                </select>
            </div>
            <div id ="chart">
                <canvas id="myChart"></canvas>
            </div>
            <div id="details_show" hidden>
            </div>
        </div>
    <main>
</body>