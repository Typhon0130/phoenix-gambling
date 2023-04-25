<template>
  <analytics-chart
    :config="{
      endpoint: '/admin/stats/city',
      title: 'City',
      dimension: 'city',
      metric: 'totalUsers',
      formatter: {
        metric: (e) => parseInt(e)
      },
      chart: (c) => {
        return Object.assign(c, {
          chart: Object.assign(c.chart, {
            type: 'donut'
          }),
          stroke: {
            show: false
          }
        });
      } }"></analytics-chart>
</template>

<script>
  import AnalyticsChart from "./AnalyticsChart.vue";
  export default {
    components: { AnalyticsChart }
  }
</script>
