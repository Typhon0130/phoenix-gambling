<template>
  <analytics-chart
    :config="{
      endpoint: '/admin/stats/newUsers',
      title: 'New Visits',
      dimension: 'date',
      metric: 'newUsers',
      formatter: {
        dimension: (e) => date(e),
        metric: (e) => parseInt(e)
      },
      chart: (c) => {
        return Object.assign(c, {
          chart: Object.assign(c.chart, {
            width: '350px'
          })
        });
      }
    }"></analytics-chart>
</template>

<script>
  import AnalyticsChart from "./AnalyticsChart.vue";
  import { date } from "./utils/dimensionFormatters.js";

  export default {
    components: { AnalyticsChart },
    setup() {
      return {
        date
      }
    }
  }
</script>
