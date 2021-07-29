<template>
        <div>
            <div class="grid grid-cols-3 gap-3 mb-5">

                <vue-highcharts v-for="(chartOptions, index) in [
                        companyStatusChartOptions, foreignStatusChartOptions, exemptStatusChartOptions, companyTypeChartOptions, companySubTypeChartOptions, businessSectorChartOptions, oldCompanyNumberChartOptions, dissolutionDateChartOptions, reRegistrationDateChartOptions
                    ]" 
                    :key="index" type="chart" :options="chartOptions" :redrawOnUpdate="true" :oneToOneUpdate="false" :animateOnUpdate="true" @rendered="onRender"/>

            </div>
            <div>

                <vue-highcharts v-for="(chartOptions, index) in [incorporationDateChartOptions]" 
                                :key="index" type="chart" :options="chartOptions" :redrawOnUpdate="true" :oneToOneUpdate="false" :animateOnUpdate="true" @rendered="onRender"/>

            </div>
        </div>

</template>

<script>

    import Dashboard from '@/Pages/Dashboard'
    import { ref, computed } from 'vue';
    import VueHighcharts from 'vue3-highcharts';
    import Drilldown from 'highcharts/modules/drilldown.js'
    import Highcharts from 'highcharts'
    Drilldown(Highcharts);

    export default {
        layout: Dashboard,
        name: 'SimpleChart',
        props: [
            'company_status_by_percentage', 'exempt_status_by_percentage', 'foreign_status_by_percentage',
            'company_type_by_percentage', 'company_sub_type_by_percentage', 'business_sector_by_percentage',
            'old_company_number_by_percentage', 'dissolution_date_by_percentage', 're_registration_date_by_percentage',
            'incorporation_dates'
        ],
        components: {
            VueHighcharts,
        },
        setup(props) {

            //  Company Statuses
            const companyStatusData = ref(props.company_status_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: (group.name == 'Registered'),
                    selected: (group.name == 'Registered'),
                }
            }));

            const companyStatusChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Company Statuses'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Status',
                    colorByPoint: true,
                    data: companyStatusData.value
                }]
            }));

            //  Foreign Statuses
            const foreignStatusData = ref(props.foreign_status_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: (group.name == 'Yes'),
                    selected: (group.name == 'Yes'),
                }
            }));

            const foreignStatusChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Foreign Status'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Status',
                    colorByPoint: true,
                    data: foreignStatusData.value
                }]
            }));

            //  Exempt Statuses
            const exemptStatusData = ref(props.exempt_status_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: (group.name == 'Yes'),
                    selected: (group.name == 'Yes'),
                }
            }));

            const exemptStatusChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Exempt Status'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Status',
                    colorByPoint: true,
                    data: exemptStatusData.value
                }]
            }));

            //  Company Type
            const companyTypeData = ref(props.company_type_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: false,
                    selected: false,
                }
            }));

            const companyTypeChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Company Type'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Company type',
                    colorByPoint: true,
                    data: companyTypeData.value
                }]
            }));

            //  Company Sub Type
            const companySubTypeData = ref(props.company_sub_type_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: false,
                    selected: false,
                }
            }));

            const companySubTypeChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Company Sub Type'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Company sub type',
                    colorByPoint: true,
                    data: companySubTypeData.value
                }]
            }));

            //  Business Sector 
            const businessSectorData = ref(props.business_sector_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: false,
                    selected: false,
                }
            }));

            const businessSectorChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Business Sector'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Business Sector',
                    colorByPoint: true,
                    data: businessSectorData.value
                }]
            }));

            //  Old Company Number
            const oldCompanyNumberData = ref(props.old_company_number_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: false,
                    selected: false,
                }
            }));

            const oldCompanyNumberChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Old Company Number'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Old Company Number',
                    colorByPoint: true,
                    data: oldCompanyNumberData.value
                }]
            }));






            //  Dissolution Date
            const dissolutionDateData = ref(props.dissolution_date_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: false,
                    selected: false,
                }
            }));

            const dissolutionDateChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Dissolution Date'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Dissolution Date',
                    colorByPoint: true,
                    data: dissolutionDateData.value
                }]
            }));

            //  Re-Registration Date
            const reRegistrationDateData = ref(props.re_registration_date_by_percentage.map((group) => { 
                return {
                    name: group.name,
                    count: group.total,
                    y: group.percentage,
                    sliced: false,
                    selected: false,
                }
            }));

            const reRegistrationDateChartOptions = computed(() => ({

                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Re-registration Date'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.3f}%</b><br>Total:<b>{point.count}</b>'
                },
                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Re-registration Date',
                    colorByPoint: true,
                    data: reRegistrationDateData.value
                }]
            }));

            //  Incorporation Dates
            const incorporationDateData = ref(props.incorporation_dates.map((group) => { 
                return {
                    name: group.name,
                    y: group.percentage,
                    drilldown: group.name
                }
            }));

            //  Incorporation Dates Drilldown
            const incorporationDateDrilldownData = ref(props.incorporation_dates.map((group) => { 
                return {
                    id: group.id,
                    name: group.name,
                    data: group.data,
                }
            }));

            const incorporationDateChartOptions = computed(() => ({
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Companies By Incorporation Date'
                },
                subtitle: {
                    text: 'Click the columns to view versions. Source: <a href="http://statcounter.com" target="_blank">statcounter.com</a>'
                },
                accessibility: {
                    announceNewData: {
                        enabled: true
                    }
                },
                xAxis: {
                    type: 'category'
                },
                yAxis: {
                    title: {
                        text: 'Total Companies'
                    }

                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        borderWidth: 0,
                        dataLabels: {
                            enabled: true,
                            format: '{point.y:.1f}%'
                        }
                    }
                },

                tooltip: {
                    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.2f}%</b> of total<br/>'
                },

                series: [
                    {
                        name: "Browsers",
                        colorByPoint: true,
                        data: incorporationDateData.value
                    }
                ],
                drilldown: {
                    series: incorporationDateDrilldownData.value
                }
            }));

            const onRender = () => {
                console.log('Chart rendered');
            };

            return {
                companyStatusChartOptions,
                foreignStatusChartOptions,
                exemptStatusChartOptions,
                companyTypeChartOptions,
                companySubTypeChartOptions,
                businessSectorChartOptions,
                oldCompanyNumberChartOptions,
                dissolutionDateChartOptions,
                reRegistrationDateChartOptions,
                incorporationDateChartOptions,
                onRender,
            };
        },
    };
</script>