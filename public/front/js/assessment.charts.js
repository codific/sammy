const CHART_TYPE_BAR = "bar";
const CHART_TYPE_RADAR = "radar";
const CHART_TYPE_LINE = "line";
const CHART_COLORS = {
    red: "#dc3545",
    orange: "#fd7e14",
    yellow: "#ffbb07",
    green: "#28a745",
    blue: "#007bff",
    purple: "#6f42c1",
    grey: "#6c757d",
    black: "#000000",
    white: "#ffffff",
    pink: "#ff1493",
    warning: "#f7b926",
    info: "#16aaff",
    target_posture_yellow: "#ffe700",
    target_posture_yellow_bar: "#ffda00"

    //original SAMM palette
    // coral_blue: "#435b70",
    // tenne_tawny: "#ca5902",
    // fern_green: "#45712d",
    // eggplant: "#754858",
    // davys_grey: "#5b5b62"

    //lighter & brighter palette
    // coral_blue_light: "#2f6ea5",
    // tenne_tawny_light: "#eb6600",
    // fern_green_light: "#48b40d",
    // eggplant_light: "#9e2f57",
    // davys_grey: "#5b5b62"

    //similar scheme palette
    // blue: "#007bff",
    // orange: "#fd7e14",
    // green: "#28a745",
    // purple: "#6f42c1",
    // grey: "#6c757d"
};


const MIN_VALUE_BUSINESS_FUNCTION = 0;
let color = Chart.helpers.color;

let maxScore = $("#max-score").val();
let MAX_VALUE_SCORE = parseInt(maxScore);
const LINE_CHART_POINTS_COLOR = "#fff";

const LINE_CHART_DATASET_NAMES = {
    governance: 'Governance',
    design: 'Design',
    implementation: 'Implementation',
    verification: 'Verification',
    operations: 'Operations'
}

const TARGET_POSTURE_COLOR = CHART_COLORS.target_posture_yellow;
const TARGET_POSTURE_BORDER_COLOR = "black";
const TARGET_POSTURE_LABEL = "Target posture";
const TARGET_POSTURE_CHART_TYPE = "scatter";
const TARGET_POSTURE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART = 16;
const TARGET_POSTURE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART = 14;

const VERIFIED_SCORE_COLOR = CHART_COLORS.info;
const VERIFIED_SCORE_BORDER_COLOR = "black";
const VERIFIED_SCORE_LABEL = "Externally Verified Score";
const VERIFIED_SCORE_CHART_TYPE = "scatter";
const VERIFIED_SCORE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART = 18;
const VERIFIED_SCORE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART = 16;

const TARGET_POSTURE_ICON = new Image(TARGET_POSTURE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART,TARGET_POSTURE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART);
TARGET_POSTURE_ICON.src = './front/images/charts/bullseye-solid.png'

const TARGET_POSTURE_ICON_SMALL = new Image(TARGET_POSTURE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART,TARGET_POSTURE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART);
TARGET_POSTURE_ICON_SMALL.src = './front/images/charts/bullseye-solid.png'


const VERIFIED_SCORE_ICON = new Image(VERIFIED_SCORE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART,VERIFIED_SCORE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART);
VERIFIED_SCORE_ICON.src = './front/images/charts/star-solid.png'

const VERIFIED_SCORE_ICON_SMALL = new Image(VERIFIED_SCORE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART,VERIFIED_SCORE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART);
VERIFIED_SCORE_ICON_SMALL.src = './front/images/charts/star-solid.png'

let DATASET_NAMES = {first: 'Current', second: 'Phase 1'};


function stackDifference(dataset1, dataset2) {
    return dataset2.map(function (element, index) {
        return element - dataset1[index];
    });
}

function refreshMaxScore() {
    maxScore = $("#max-score").val();
    MAX_VALUE_SCORE = parseInt(maxScore);
}

function loadMultiDatasetCharts() {
    let chartWrapperExist = $(".charts-wrapper").length > 0;
    if (chartWrapperExist) {
        refreshMaxScore();
        $(".chart-line-bf").each(function () {
            let assessmentId = $(this).attr("data-assessment-id");

            let scores = [];
            let labels = [];
            $(".line-chart-score").each(function () {
                scores.push($(this).attr("value"));
                labels.push($(this).attr("date"));
            });

            let scores2 = [];
            scores.forEach(function(val, index, arr){
                scores2.push(index === arr.length - 1 ? val : null)
            });

            let backgroundColors = [
                color(CHART_COLORS.blue).alpha(0.75).rgbString(),
                color(CHART_COLORS.blue).alpha(0.85).rgbString(),
                color(CHART_COLORS.blue).rgbString(),
                color(CHART_COLORS.green).rgbString(),
                color(CHART_COLORS.grey).rgbString(),,
                ];
            let numericScores = scores.map(value => Number(value));
            let dataSets = [
                {
                    label: "Overall",
                    backgroundColor: backgroundColors,
                    data: numericScores.slice(0,numericScores.length - 1),
                    trendlineLinear: {
                        colorMin: "red",
                        colorMax: "red",
                        lineStyle: "solid",
                        width: 2,
                        projection: false
                    }
                },
                ];

            if (scores2.at(-1) !== 'null'){
                dataSets.push({
                    label: "Target",
                    backgroundColor: CHART_COLORS.target_posture_yellow_bar,
                    data: scores2,
                })
            }else {
                labels.pop()
            }
            let ticksOptions = {};
            if ($(this).attr("data-compact-view") === "true") {
                ticksOptions = {
                    font: {
                        size: 13
                    }
                }
            }

            let scales = getDefaultScales(100, ticksOptions, MAX_VALUE_SCORE);

            let historyChartConfig = getBarChartConfig(dataSets, labels, scales);

            let canvas = $(this).get(0);
            let chart = Chart.getChart(canvas.id);
            if (chart) {
                chart.destroy();
            }
            chart = new Chart(canvas, historyChartConfig);
        });

        $(".chart-bar-bf").each(function () {
            let assessmentId = $(this).attr("data-assessment-id");
            let ticksOptions = {};
            if ($(this).attr("data-compact-view") === "true") {
                ticksOptions = {
                    font: {
                        size: 13
                    }
                }
            }
            let colors = [
                CHART_COLORS.red,
                CHART_COLORS.orange,
                CHART_COLORS.yellow,
                CHART_COLORS.green,
                CHART_COLORS.blue,
                CHART_COLORS.grey,
            ];

            let colorsLight = [
                color(CHART_COLORS.red).alpha(0.5).rgbString(),
                color(CHART_COLORS.orange).alpha(0.5).rgbString(),
                color(CHART_COLORS.yellow).alpha(0.5).rgbString(),
                color(CHART_COLORS.green).alpha(0.5).rgbString(),
                color(CHART_COLORS.blue).alpha(0.5).rgbString(),
                color(CHART_COLORS.grey).alpha(0.5).rgbString(),
            ];

            let dataset1 = getBusinessFunctionValues(assessmentId, 1);
            let dataset2 = getBusinessFunctionValues(assessmentId, 2);
            let targetPostureDataset = getBusinessFunctionValues(assessmentId, 3);
            let verifiedDataset = getBusinessFunctionValues(assessmentId, 4);

            let dataSets = [
                {
                    label: DATASET_NAMES.first,
                    backgroundColor: colors,
                    data: dataset1,
                    order: 10
                },
                {
                    label: DATASET_NAMES.second,
                    backgroundColor: colorsLight,
                    data: stackDifference(dataset1, dataset2),
                    order: 11
                },
            ];

            if (targetPostureDataset.length !== 0){
                dataSets.push({
                    type: TARGET_POSTURE_CHART_TYPE,
                    label: TARGET_POSTURE_LABEL,
                    backgroundColor: [TARGET_POSTURE_COLOR],
                    borderColor: TARGET_POSTURE_BORDER_COLOR,
                    data: targetPostureDataset,
                    pointRadius: TARGET_POSTURE_DOT_SIZE_FOR_BUSINESS_FUNCTION_CHART,
                    pointStyle: TARGET_POSTURE_ICON,
                    stack: "targetPosture",
                    order: 0
                })
            }
            if (verifiedDataset.length !== 0){
                dataSets.push({
                    type: VERIFIED_SCORE_CHART_TYPE,
                    label: VERIFIED_SCORE_LABEL,
                    backgroundColor: [VERIFIED_SCORE_COLOR],
                    borderColor: VERIFIED_SCORE_BORDER_COLOR,
                    data: verifiedDataset.map(function (val, i) {
                        return val == 0 ? null : val;
                    }),
                    pointRadius: VERIFIED_SCORE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART,
                    pointStyle: VERIFIED_SCORE_ICON,
                    stack: "verifiedScore",
                    order: 1
                })
            }


            let scales = getDefaultScales(100, ticksOptions, MAX_VALUE_SCORE);

            let businessFunctionChartConfig = getBarChartConfig(
                dataSets,
                getBusinessFunctionNames(assessmentId),
                scales
            );

            let canvas = $(this).get(0);
            let chart = Chart.getChart(canvas.id);
            if (chart) {
                chart.destroy();
            }
            chart = new Chart(canvas, businessFunctionChartConfig);
        });

        $(".chart-bar-sp").each(function () {
            let assessmentId = $(this).attr("data-assessment-id");
            let showMinifiedView = $(this).attr("data-compact-view") === "true";
            let ticksOptions = {};
            if (showMinifiedView) {
                ticksOptions = {
                    font: {
                        size: 11
                    },
                    maxRotation: 90,
                    minRotation: 90
                }
            }

            let colors = [
                CHART_COLORS.red, CHART_COLORS.red, CHART_COLORS.red,
                CHART_COLORS.orange, CHART_COLORS.orange, CHART_COLORS.orange,
                CHART_COLORS.yellow, CHART_COLORS.yellow, CHART_COLORS.yellow,
                CHART_COLORS.green, CHART_COLORS.green, CHART_COLORS.green,
                CHART_COLORS.blue, CHART_COLORS.blue, CHART_COLORS.blue
            ];
            let colorsLight = [
                color(CHART_COLORS.red).alpha(0.5).rgbString(), color(CHART_COLORS.red).alpha(0.5).rgbString(), color(CHART_COLORS.red).alpha(0.5).rgbString(),
                color(CHART_COLORS.orange).alpha(0.5).rgbString(), color(CHART_COLORS.orange).alpha(0.5).rgbString(), color(CHART_COLORS.orange).alpha(0.5).rgbString(),
                color(CHART_COLORS.yellow).alpha(0.5).rgbString(), color(CHART_COLORS.yellow).alpha(0.5).rgbString(), color(CHART_COLORS.yellow).alpha(0.5).rgbString(),
                color(CHART_COLORS.green).alpha(0.5).rgbString(), color(CHART_COLORS.green).alpha(0.5).rgbString(), color(CHART_COLORS.green).alpha(0.5).rgbString(),
                color(CHART_COLORS.blue).alpha(0.5).rgbString(), color(CHART_COLORS.blue).alpha(0.5).rgbString(), color(CHART_COLORS.blue).alpha(0.5).rgbString()
            ];

            let dataset1 = getSecurityPracticeValues(assessmentId, 1);
            let dataset2 = getSecurityPracticeValues(assessmentId, 2);
            let targetPostureDataset = getSecurityPracticeValues(assessmentId, 3);
            let verifiedDataset = getSecurityPracticeValues(assessmentId, 4);

            let datasets = [
                {
                    label: DATASET_NAMES.first,
                    backgroundColor: colors,
                    data: dataset1,
                    order: 10
                },
                {
                    label: DATASET_NAMES.second,
                    backgroundColor: colorsLight,
                    data: stackDifference(dataset1, dataset2),
                    order: 10
                },
            ];

            if (targetPostureDataset.length !== 0){
                datasets.push({
                    type: TARGET_POSTURE_CHART_TYPE,
                    label: TARGET_POSTURE_LABEL,
                    backgroundColor: [TARGET_POSTURE_COLOR],
                    borderColor: TARGET_POSTURE_BORDER_COLOR,
                    data: targetPostureDataset,
                    pointRadius: TARGET_POSTURE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART,
                    pointStyle: TARGET_POSTURE_ICON_SMALL,
                    stack: "targetPosture",
                    order: 0
                })
            }
            if (verifiedDataset.length !== 0){
                datasets.push({
                    type: VERIFIED_SCORE_CHART_TYPE,
                    label: VERIFIED_SCORE_LABEL,
                    backgroundColor: [VERIFIED_SCORE_COLOR],
                    borderColor: VERIFIED_SCORE_BORDER_COLOR,
                    data: verifiedDataset.map(function (val, i) {
                        return val == 0 ? null : val;
                    }),
                    pointRadius: VERIFIED_SCORE_DOT_SIZE_FOR_SECURITY_PRACTICE_CHART,
                    pointStyle: VERIFIED_SCORE_ICON_SMALL,
                    stack: "verifiedScore",
                    order: 1
                })
            }

            let scales = getDefaultScales(100, ticksOptions, MAX_VALUE_SCORE);
            let securityPracticeChartConfig = getBarChartConfig(
                datasets,
                getSecurityPracticeNames(assessmentId),
                scales
            );
            let canvas = $(this).get(0);
            let chart = Chart.getChart(canvas.id);
            if (chart) {
                chart.destroy();
            }
            chart = new Chart(canvas, securityPracticeChartConfig);
        });
        loadScopeComparisonBarChart();
    }
}

function loadScopeComparisonBarChart() {
    $(".chart-bar-scope").each(function () {
        let assessmentId = $(this).attr("data-assessment-id");
        let showMinifiedView = $(this).attr("data-compact-view") === "true";
        let ticksOptions = {};
        if (showMinifiedView) {
            ticksOptions = {
                font: {
                    size: 11
                },
                maxRotation: 90,
                minRotation: 90
            }
        }
        let scopesChartDatasets = getScopesChartsData(parseInt(assessmentId))
        let scopesChartData = scopesChartDatasets[1]
        let scopesChartData2 = scopesChartDatasets[2];
        let dataset1 = scopesChartData['vals'];
        let colors = scopesChartData['colors'];
        let borders = scopesChartData['borders'];

        let colorHelper = Chart.helpers.color;
        let dataset2 = scopesChartData2['vals'];
        let colors2 = [];
        colors.forEach(function (color) {
            colors2.push(colorHelper(color).alpha(0.5).rgbString());
        });
        let datasets = [
            {
                label: DATASET_NAMES.first,
                data: dataset1,
                backgroundColor: colors,
                borderColor: CHART_COLORS.blue,
                borderWidth: borders,
                borderRadius: borders.map((num) => num*4)
            },
            {
                label: DATASET_NAMES.second,
                data: stackDifference(dataset1, dataset2),
                backgroundColor: colors2,
                borderColor: CHART_COLORS.blue,
                borderWidth: borders
            }
        ];

        let yTicksOptions = {
            callback: function (value) {
                return (value * 100).toFixed(0) + '%';
            },
        }
        let scale_max = 1;

        let scales = {
            x: {
                stacked: true,
                afterFit: (scale) => {
                    scale.height = 100
                },
                ticks: ticksOptions
            },
            y: {
                stacked: true,
                min: 0,
                max: scale_max,
                ticks: yTicksOptions
            }
        }
        let scopesChartConfig = {
            plugins: [ChartDataLabels],
            type: CHART_TYPE_BAR,
            data: {
                datasets: datasets,
                labels: scopesChartData['names'],
            },
            options: {
                responsive: true,
                scales: scales,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';

                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    value = context.parsed.y
                                    if (value > 1) {
                                        label += "> 100%";
                                    } else {
                                        label += Number(context.parsed.y * 100).toFixed(2) + "%";
                                    }
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        color: "black",
                        align: "top",
                        font: {
                            weight: 'bold'
                        },
                        formatter: function (value) {
                            if (value !== 0) {
                                value = Math.min(1, value)
                                return Number(value * 100).toFixed(2);
                            } else {
                                value = "";
                                return value;
                            }
                        },
                        opacity: function (context) {
                            if (context.dataset.type === TARGET_POSTURE_CHART_TYPE) {
                                return 0;
                            }
                            return 100;
                        },
                    },
                }
            },
        };
        let canvas = $(this).get(0);
        let chart = Chart.getChart(canvas.id);
        if (chart) {
            chart.destroy();
        }
        chart = new Chart(canvas, scopesChartConfig);
    });
}

function loadSingleDatasetCharts() {
    let chartWrapperExist = $(".charts-wrapper").length > 0;
    if (chartWrapperExist) {
        refreshMaxScore();
        $(".chart-bar-bf").each(function () {
            let assessmentId = $(this).attr("data-assessment-id");
            let ticksOptions = {};
            if ($(this).attr("data-compact-view") === "true") {
                ticksOptions = {
                    font: {
                        size: 13
                    }
                }
            }

            let colors = [
                CHART_COLORS.red,
                CHART_COLORS.orange,
                CHART_COLORS.yellow,
                CHART_COLORS.green,
                CHART_COLORS.blue,
            ];

            let colorsLight = [
                color(CHART_COLORS.red).alpha(0.5).rgbString(),
                color(CHART_COLORS.orange).alpha(0.5).rgbString(),
                color(CHART_COLORS.yellow).alpha(0.5).rgbString(),
                color(CHART_COLORS.green).alpha(0.5).rgbString(),
                color(CHART_COLORS.blue).alpha(0.5).rgbString(),
            ];

            let dataset1 = getBusinessFunctionValues(assessmentId, 1);

            let dataSets = [
                {
                    label: DATASET_NAMES.first,
                    backgroundColor: colors,
                    data: dataset1,
                }
            ];

            let scales = getDefaultScales(100, ticksOptions, MAX_VALUE_SCORE);

            let businessFunctionChartConfig = getBarChartConfig(
                dataSets,
                getBusinessFunctionNames(assessmentId),
                scales
            );

            let canvas = $(this).get(0);
            let chart = Chart.getChart(canvas.id);
            if (chart) {
                chart.destroy();
            }
            chart = new Chart(canvas, businessFunctionChartConfig);
        });

        $(".chart-bar-sp").each(function () {
            let assessmentId = $(this).attr("data-assessment-id");
            let showMinifiedView = $(this).attr("data-compact-view") === "true";
            let ticksOptions = {};
            if (showMinifiedView) {
                ticksOptions = {
                    font: {
                        size: 11
                    },
                    maxRotation: 90,
                    minRotation: 90
                }
            }

            let colors = [
                CHART_COLORS.red, CHART_COLORS.red, CHART_COLORS.red,
                CHART_COLORS.orange, CHART_COLORS.orange, CHART_COLORS.orange,
                CHART_COLORS.yellow, CHART_COLORS.yellow, CHART_COLORS.yellow,
                CHART_COLORS.green, CHART_COLORS.green, CHART_COLORS.green,
                CHART_COLORS.blue, CHART_COLORS.blue, CHART_COLORS.blue
            ];
            let colorsLight = [
                color(CHART_COLORS.red).alpha(0.5).rgbString(), color(CHART_COLORS.red).alpha(0.5).rgbString(), color(CHART_COLORS.red).alpha(0.5).rgbString(),
                color(CHART_COLORS.orange).alpha(0.5).rgbString(), color(CHART_COLORS.orange).alpha(0.5).rgbString(), color(CHART_COLORS.orange).alpha(0.5).rgbString(),
                color(CHART_COLORS.yellow).alpha(0.5).rgbString(), color(CHART_COLORS.yellow).alpha(0.5).rgbString(), color(CHART_COLORS.yellow).alpha(0.5).rgbString(),
                color(CHART_COLORS.green).alpha(0.5).rgbString(), color(CHART_COLORS.green).alpha(0.5).rgbString(), color(CHART_COLORS.green).alpha(0.5).rgbString(),
                color(CHART_COLORS.blue).alpha(0.5).rgbString(), color(CHART_COLORS.blue).alpha(0.5).rgbString(), color(CHART_COLORS.blue).alpha(0.5).rgbString()
            ];

            let dataset1 = getSecurityPracticeValues(assessmentId, 1);

            let datasets = [
                {
                    label: DATASET_NAMES.first,
                    backgroundColor: colors,
                    data: dataset1,
                }
            ];

            let scales = getDefaultScales(120, ticksOptions, MAX_VALUE_SCORE);
            let securityPracticeChartConfig = getBarChartConfig(datasets, getSecurityPracticeNames(assessmentId), scales);
            let canvas = $(this).get(0);
            let chart = Chart.getChart(canvas.id);
            if (chart) {
                chart.destroy();
            }
            chart = new Chart(canvas, securityPracticeChartConfig);
        });

    }
}

function getBusinessFunctionValues(assessmentId, dataset = 1) {
    let result = [];
    $(`[data-business-function-chart='true'][data-assessment-id=${assessmentId}][data-dataset=${dataset}]`).each(function () {
        if ($(this).val() !== "") {
            result.push(Number($(this).val()).toFixed(2));
        } else {
            result.push(null);
        }
    });
    return result;
}

function getBusinessFunctionNames(assessmentId, dataset = 1) {
    let result = [];
    $(`[data-business-function-chart='true'][data-assessment-id=${assessmentId}][data-dataset=${dataset}]`).each(function () {
        result.push($(this).attr("id"));
    });
    return result;
}

function getSecurityPracticeValues(assessmentId, dataset = 1) {
    let result = [];
    $(`[data-security-practice-chart='true'][data-assessment-id=${assessmentId}][data-dataset=${dataset}]`).each(function () {
        if ($(this).val() !== "") {
            result.push(Number($(this).val()).toFixed(2));
        } else {
            result.push(null);
        }
    });
    return result;
}

function getSecurityPracticeNames(assessmentId, dataset = 1) {
    let result = [];
    $(`[data-security-practice-chart='true'][data-assessment-id=${assessmentId}][data-dataset=${dataset}]`).each(function () {
        result.push($(this).attr("id"));
    });
    return result;
}

function getDefaultScales(scaleHeight, ticksOptions, maxValue, stacked = true) {
    return {
        x: {
            stacked: stacked,
            afterFit: (scale) => {
                scale.height = scaleHeight
            },
            ticks: ticksOptions
        },
        y: {
            stacked: stacked,
            min: 0,
            max: maxValue
        }
    }
}

function getChartConfig(datasets, labels, scales, type = CHART_TYPE_BAR) {
    return {
        type: type,
        data: {
            datasets: datasets,
            labels: labels,
        },
        options: {
            responsive: true,
            scales: scales,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';

                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.r) {
                                label += Number(context.parsed.r).toFixed(2);
                            } else {
                                label += "0.00"
                            }
                            return label;
                        }
                    }
                },
            },

        }
    }
}

function getBarChartConfig(datasets, labels, scales) {
    return {
        plugins: [ChartDataLabels],
        type: CHART_TYPE_BAR,
        data: {
            datasets: datasets,
            labels: labels,
        },
        options: {
            responsive: true,
            scales: scales,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';

                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += Number(context.parsed.y).toFixed(2);
                            }
                            return label;
                        }
                    }
                },
                datalabels: {
                    color: "black",
                    align: "top",
                    font: {
                        weight: 'bold'
                    },
                    formatter: function (value) {
                        if (value > 0 && value !== null) {
                            return Number(value).toFixed(2);
                        } else {
                            value = "";
                            return value;
                        }
                    },
                    opacity: function (context) {
                        if (context.dataset.type === TARGET_POSTURE_CHART_TYPE) {
                            return 0;
                        }
                        return 100;
                    },
                },
            }
        },
    };
}

function getLineChartConfig(datasets, labels, scaleHeight) {
    return {
        type: CHART_TYPE_LINE,
        data: {
            datasets: datasets,
            labels: labels,
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            pointBackgroundColor: LINE_CHART_POINTS_COLOR,
            scales: {
                x: {
                    afterFit: (scale) => {
                        scale.height = scaleHeight
                    }
                },
                y: {
                    min: MIN_VALUE_BUSINESS_FUNCTION,
                    max: MAX_VALUE_SCORE
                }
            },
        }
    };
}

function getLineChartDates(assessmentId) {
    let result = [];
    $(`.line-chart-date[data-assessment-id=${assessmentId}]`).each(function () {
        result.push($(this).val());
    });
    return result;
}

function getBusinessFunctionValueForLineChart(businessFunctionName, assessmentId) {
    let result = [];
    $(`.line-chart-date[data-assessment-id=${assessmentId}]`).each(function () {
        let date = $(this).val();
        result.push((Number($(`.line-chart-business-function[data-name='${businessFunctionName}'][data-assessment-id=${assessmentId}][data-date='${date}']`).val()).toFixed(2)));
    });
    return result;
}

function getScopesChartsData(assessmentId) {

    let colorScale = (value) => value > .8 ? CHART_COLORS.green : value >= .65 ? CHART_COLORS.yellow : CHART_COLORS.red;

    let scopes = {};
    $(`input[data-scope-compare-chart="true"][data-assessment-id=${assessmentId}]`).each(function () {
        scopes[$(this).attr("data-scope-name")] = scopes[$(this).attr("data-scope-name")] ?? {};
        scopes[$(this).attr("data-scope-name")][$(this).attr("data-dataset")] = scopes[$(this).attr("data-scope-name")][$(this).attr("data-dataset")] ?? {};
        scopes[$(this).attr("data-scope-name")][$(this).attr("data-dataset")]['name'] = $(this).attr("data-scope-name");
        scopes[$(this).attr("data-scope-name")][$(this).attr("data-dataset")]['value'] = $(this).val() !== '' ? Math.min(1.01, Number($(this).val())).toFixed(2) : "N/A";
        scopes[$(this).attr("data-scope-name")][$(this).attr("data-dataset")]['color'] = colorScale($(this).val());
        scopes[$(this).attr("data-scope-name")][$(this).attr("data-dataset")]['border'] = ($(this).attr("data-current-project")) ? 3 : 0;
    });

    let orderedScopeNames = [];
    for (const scopeName in scopes) {
        let arr = [];
        let name = null;
        let val = 0;
        if (scopes[scopeName][1]?.["name"]) {
            name = scopes[scopeName][1]["name"];
            val += Number(scopes[scopeName][1]["value"]);
        }

        if (scopes[scopeName][2]?.["name"]) {
            name = scopes[scopeName][2]["name"];
            val += Number(scopes[scopeName][2]["value"]) / 100;
        }
        arr = [name, String(val)]
        orderedScopeNames.push(arr)
    }
    orderedScopeNames = orderedScopeNames.sort(scopeNamesSort);

    let datasets = [];
    for (const scopeNameIndex in orderedScopeNames) {
        let scopeName = orderedScopeNames[scopeNameIndex][0];
        for (const datasetId of [1, 2]) {
            datasets[datasetId] = datasets[datasetId] ?? [];
            datasets[datasetId]["names"] = datasets[datasetId]["names"] ?? [];
            datasets[datasetId]["vals"] = datasets[datasetId]["vals"] ?? [];
            datasets[datasetId]["colors"] = datasets[datasetId]["colors"] ?? [];
            datasets[datasetId]["borders"] = datasets[datasetId]["borders"] ?? [];
            if (scopes[scopeName][datasetId]?.["value"]) {
                datasets[datasetId]["names"].push(scopes[scopeName][datasetId]?.["name"]);
                datasets[datasetId]["vals"].push(scopes[scopeName][datasetId]?.["value"]);
                datasets[datasetId]["colors"].push(scopes[scopeName][datasetId]?.["color"]);
                datasets[datasetId]["borders"].push(scopes[scopeName][datasetId]?.["border"]);
            }
        }
    }


    return datasets
}

function scopeNamesSort(x, y) {
    n1 = Number(x[1]);
    n2 = Number(y[1])

    if (isNaN(n1)) return 1;
    if (isNaN(n2)) return -1;

    return (n1 < n2 ? 1 : -1);
}