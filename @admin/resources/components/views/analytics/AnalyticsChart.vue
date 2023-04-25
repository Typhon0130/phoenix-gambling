<template>
  <div class="block chart">
    <template v-if="noData">
      <div class="title" v-if="config.title">{{ config.title }}</div>
      <div class="content">
        No data available.
      </div>
    </template>
    <template v-else-if="data">
      <div class="title" v-if="config.title">{{ config.title }}</div>
      <div class="subtitle" v-if="config.subtitle">{{ config.subtitle }}</div>
      <div class="content">
        <div ref="chart"></div>
        <div v-if="config.debugData">
          <pre>{{ data }}</pre>
        </div>
      </div>
    </template>
    <template v-else-if="error">
      <div class="title" v-if="config.title">{{ config.title }}</div>
      <div class="content">
        An error has occurred.
      </div>
    </template>
    <template v-else>
      <loader-animation></loader-animation>
    </template>
    <div v-if="isDemonstration" class="demo">
      <web-icon icon="far fa-exclamation-triangle"></web-icon>
      <div class="description">
        <div>This chart is set to demonstration mode.</div>
        <div>All values are random.</div>
      </div>
    </div>
  </div>
</template>

<script>
  import LoaderAnimation from "../../ui/LoaderAnimation.vue";
  import ApexCharts from 'apexcharts';
  import WebIcon from "../../ui/WebIcon.vue";

  export default {
    props: [ 'config' ],
    data() {
      return {
        data: null,
        error: false,
        noData: false,

        chartData: null,

        isDemonstration: window.$isDemo || window.$forceRandomAnalytics
      }
    },
    created() {
      this.chartData = {
        chart: {
          type: 'line',
          height: '300px',
          width: '300px',
          background: 'transparent',
          toolbar: {
            show: false
          },
          zoom: {
            enabled: false
          },
          sparkline: {
            enabled: false
          },
          animations: {
            enabled: false
          }
        },
        grid: {
          show: false
        },
        theme: {
          mode: 'dark',
        },
        colors: ["#ea5454", "#f4853f", "#f9af4d", "#9376bf", "#8e45ad", "#f27d7d", "#2f4566"],
        legend: {
          show: false
        },
        xaxis: {
          labels: {
            show: false
          },
          axisBorder: {
            show: false
          },
          axisTicks: {
            show: false
          }
        },
        yaxis: {
          labels: {
            show: false
          },
          axisBorder: {
            show: false
          },
          axisTicks: {
            show: false
          }
        },
        dataLabels: {
          enabled: false
        },
        plotOptions: {
          pie: {
            expandOnClick: false
          }
        }
      };

      if(this.config.chart) this.chartData = this.config.chart(this.chartData);
    },
    watch: {
      data() {
        let series = [], labels = [];

        this.data.forEach(entry => {
          let dimension = entry.dimensions[this.config.dimension];
          let metric = entry.metrics[this.config.metric];

          if(this.config.formatter && this.config.formatter.dimension)
            dimension = this.config.formatter.dimension(dimension);

          if(this.config.formatter && this.config.formatter.metric)
            metric = this.config.formatter.metric(metric);

          labels.push(dimension);
          series.push(metric);
        });

        if(this.chartData.chart.type === 'donut') {
          this.chartData.series = series;
          this.chartData.labels = labels;
        } else if(this.chartData.chart.type === 'line') {
          this.chartData.xaxis.categories = labels;
          this.chartData.series = [{
            name: this.config.title ?? (this.config.dimension + '-' + this.config.metric),
            data: series
          }]
        } else throw new Error('Unknown chart type, can\'t configure labels/series ' + this.chartData.chart.type);

        this.$nextTick(() => {
          new ApexCharts(this.$refs.chart, this.chartData).render();
        });

        this.noData = this.data.length === 0;
      }
    },
    mounted() {
      if(this.isDemonstration) {
        const data = [];

        for(let i = 0; i < 25; i++)
          data.push({
            dimensions: {
              [this.config.dimension]: this.config.dimension === 'date' ? '20220101' : '*Demonstration*'
            },
            metrics: {
              [this.config.metric]: Math.floor(Math.random() * 50)
            }
          });

        this.data = data;
      } else window.axios.post(this.config.endpoint).then(({ data }) => this.data = data).catch(() => this.error = true);
    },
    components: {WebIcon, LoaderAnimation }
  }
</script>
