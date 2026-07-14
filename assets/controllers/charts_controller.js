import { Controller } from '@hotwired/stimulus';

import ApexCharts from 'apexcharts';

export default class extends Controller {
    static values = {
        title: String,
        data: Array,
        type: String,
        series: Array,
        categories: Array,
        colors: Array,
        formatterUnit: String,
        labelUnit: String,
        decimals: Number,
        labelDecimals: Number,
        legend: Boolean,
        dataLabels: Boolean,
        stacked: Boolean,
    }

    connect() {
        var colors = this.colorsValue;

        if (colors.length == 0) {
            colors = [
                "var(--bs-secondary)",
                "var(--bs-primary)",
                "var(--bs-warning)",
                "var(--bs-info)",
                "var(--bs-light)",
                "var(--bs-success)",
                "var(--bs-danger)",
            ];
        }

        var formatterUnit = this.formatterUnitValue;
        var labelUnit = this.labelUnitValue || formatterUnit;
        var decimals = this.decimalsValue;
        var labelDecimals = this.labelDecimalsValue;

        var labelFormatter = function (val) {
          return val.toFixed(labelDecimals) + labelUnit;
        };

        var axisFormatter = function (val) {
          return val.toFixed(decimals) + formatterUnit;
        };

        var series = [
            {
                name: this.titleValue,
                data: this.dataValue,
            },
        ];

        if (this.typeValue == 'donut' || this.typeValue == 'semidonut') {
            series = this.dataValue;
        }

        if (this.seriesValue.length) {
            series = this.seriesValue;
        }

        var chartType = this.typeValue == 'semidonut' ? 'donut' : this.typeValue;

        var options = {
            series: series,
            chart: {
                toolbar: {
                    show: false,
                },
                height: 300,
                type: chartType,
                fontFamily: "inherit",
                foreColor: "#adb0bb",
                stacked: this.stackedValue,
            },
            colors: colors,
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: "59%",
                    distributed: series.length <= 1,
                    endingShape: "rounded",
                },
            },
            dataLabels: {
                enabled: this.dataLabelsValue == true,
                formatter: labelFormatter,
                background: {
                    enabled: "line" == this.typeValue,
                }
            },
            legend: {
                show: this.legendValue,
                position: "bottom"
            },
            grid: {
                show: true,
                strokeDashArray: 3,
                borderColor: "#90A4AE50",
            },
            xaxis: {
                categories: this.categoriesValue,
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
            },
            yaxis: {
                tickAmount: 3,
                labels: {
                    formatter: axisFormatter
                }
            },
            tooltip: {
                theme: localStorage.getItem("theme"),
                enabled: true,
                style: {
                    fontFamily: 'Inter, sans-serif',
                    fontSize: '14px'
                },
                y: {
                    title: {
                        formatter: (title) => {
                            return title + ': ';
                        }
                    }
                }
            },
            stroke: {
                width: 3,
                curve: "smooth",
            }
        };

        if (this.typeValue == 'donut') {
            options.labels = this.categoriesValue;
        }

        if (this.typeValue == 'semidonut') {
            options.labels = this.categoriesValue;
            options.plotOptions = {
                pie: {
                    startAngle: -90,
                    endAngle: 90,
                    offsetY: 10,
                    donut: {
                        size: "75%",
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: "15px",
                                color: undefined,
                                offsetY: 5,
                                color: "var(--bs-dark)",
                            },
                            value: {
                                show: true,
                                formatter: labelFormatter
                            },
                            total: {
                                show: false,
                                color: "var(--bs-gray-400)",
                                fontSize: "20px",
                                fontWeight: "600",
                                label: ""
                            },
                        },
                    },
                },
            };
            options.grid =  {
                padding: {
                    bottom: -80,
                },
            };
        }

        var chart = new ApexCharts(this.element, options);
        chart.render();
    }
}
