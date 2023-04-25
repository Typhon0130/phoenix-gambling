<template>
  <analytics-chart
    :config="{
      endpoint: '/admin/stats/game/' + game.id,
      title: game.name,
      subtitle: game.id,
      dimension: 'date',
      metric: 'totalUsers',
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
    props: ['game'],
    setup() {
      return {
        date
      }
    }
  }
</script>
