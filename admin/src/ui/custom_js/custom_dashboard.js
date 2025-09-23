$(document).ready(function () {
    let chart = null;

    // const chartOptions = {
    //     series: [
    //         { name: 'Earnings', data: [] },
    //         { name: 'GST', data: [] }
    //     ],
    //     chart: {
    //         type: 'bar',
    //         height: 365,
    //         stacked: true,
    //         toolbar: {
    //             show: true,
    //             tools: {
    //                 download: true,
    //                 selection: false,
    //                 zoom: false,
    //                 zoomin: false,
    //                 zoomout: false,
    //                 pan: false,
    //                 reset: false
    //             }
    //         }
    //     },
    //     plotOptions: {
    //         bar: {
    //             horizontal: false,
    //             columnWidth: '15%',
    //             endingShape: 'rounded'
    //         }
    //     },
    //     dataLabels: { enabled: false },
    //     stroke: {
    //         show: true,
    //         width: 2,
    //         colors: ['transparent']
    //     },
    //     xaxis: {
    //         categories: [],
    //         axisBorder: { show: false },
    //         axisTicks: { show: false },
    //         labels: {
    //             style: {
    //                 colors: '#787878',
    //                 fontSize: '13px',
    //                 fontFamily: 'Poppins',
    //                 fontWeight: 400
    //             }
    //         }
    //     },
    //     yaxis: {
    //         show: true,
    //         labels: {
    //             style: {
    //                 colors: '#787878',
    //                 fontSize: '13px',
    //                 fontFamily: 'Poppins',
    //                 fontWeight: 400
    //             },
    //             formatter: function (value) {
    //                 return '₹' + value.toFixed(2);
    //             }
    //         }
    //     },
    //     colors: ['#0B2A97', '#FF9900'],
    //     tooltip: {
    //         y: {
    //             formatter: function (value) {
    //                 return '₹' + value.toFixed(2);
    //             }
    //         }
    //     },
    //     fill: { opacity: 1 },
    //     legend: {
    //         position: 'top',
    //         horizontalAlign: 'left',
    //         show: true,
    //         fontSize: '12px',
    //         fontFamily: 'Poppins'
    //         // offsetY: removed to avoid legend being too close to top
    //     }
    // };

    const chartOptions = {
    series: [
        { name: 'Earnings', data: [] },
        { name: 'GST', data: [] }
    ],
    chart: {
        type: 'bar',
        height: 365,
        stacked: true,
        toolbar: {
            show: true,
            tools: {
                download: true,
                selection: false,
                zoom: false,
                zoomin: false,
                zoomout: false,
                pan: false,
                reset: false
            }
        }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '20%',  // adjusted for col-xl-6 layout
            endingShape: 'rounded'
        }
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: {
        categories: [],
        labels: {
            style: {
                colors: '#ffffff',  // white for dark bg
                fontSize: '13px',
                fontFamily: 'Poppins',
                fontWeight: 400
            }
        }
    },
    yaxis: {
        labels: {
            style: {
                colors: '#ffffff',  // white for dark bg
                fontSize: '13px',
                fontFamily: 'Poppins',
                fontWeight: 400
            },
            formatter: function(value) {
                return '₹' + value.toFixed(2);
            }
        }
    },
    colors: ['rgba(248,185,64,0.85)', 'rgba(34, 43, 64, 0.85)'], 
    tooltip: {
        theme: 'dark',
        y: {
            formatter: function(value) {
                return '₹' + value.toFixed(2);
            }
        }
    },
    fill: { opacity: 1 },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        labels: {
            colors: '#ffffff',  // white legends
        },
        fontSize: '12px',
        fontFamily: 'Poppins',
    }
};


    if ($("#revenueStackedBarChart").length > 0) {
        chart = new ApexCharts(document.querySelector("#revenueStackedBarChart"), chartOptions);
        chart.render();
    }

    $('#revenueDateRange').daterangepicker({
        opens: 'left',
        startDate: moment().subtract(29, 'days'),
        endDate: moment(),
        ranges: {
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 90 Days': [moment().subtract(89, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#revenueDateRange').on('apply.daterangepicker', function (ev, picker) {
        updateChart(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
    });

    function formatCurrency(amount) {
        return '₹' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function updateChart(startDate, endDate) {
        if (!chart) return;

        $.ajax({
            url: '../backend/get_revenue_stats.php',
            method: 'GET',
            data: { start_date: startDate, end_date: endDate },
            success: function (response) {
                if (response.status === 'success') {
                    const dates = [];
                    const earnings = [];
                    const gst = [];

                    response.data.forEach(item => {
                        dates.push(moment(item.date).format('MMM DD'));
                        earnings.push(parseFloat(item.earnings) || 0);
                        gst.push(parseFloat(item.gst) || 0);
                    });

                    chart.updateOptions({
                        xaxis: { categories: dates }
                    });

                    chart.updateSeries([
                        { name: 'Earnings', data: earnings },
                        { name: 'GST', data: gst }
                    ]);

                    if (response.summary) {
                        $('#totalRevenueLabel').text(formatCurrency(response.summary.total_revenue));
                        $('#totalEarningsLabel').text(formatCurrency(response.summary.total_earnings));
                        $('#totalGSTLabel').text(formatCurrency(response.summary.total_gst));
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching revenue data:', error);
            }
        });
    }

    // Initial load
    updateChart(
        moment().subtract(29, 'days').format('YYYY-MM-DD'),
        moment().format('YYYY-MM-DD')
    );
});
