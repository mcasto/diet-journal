<template>
  <div class="weight-chart">
    <div v-if="!data || data.length === 0" class="text-caption text-grey q-pa-md">
      No weight history yet.
    </div>
    <template v-else>
      <svg
        ref="svgRef"
        :viewBox="`0 0 ${width} ${height}`"
        class="weight-chart__svg"
        @pointermove="onPointerMove"
        @pointerleave="onPointerLeave"
      >
        <line
          v-for="tick in yTicks"
          :key="`grid-${tick.value}`"
          :x1="padding.left"
          :x2="width - padding.right"
          :y1="tick.y"
          :y2="tick.y"
          class="weight-chart__gridline"
        ></line>
        <text
          v-for="tick in yTicks"
          :key="`ytick-${tick.value}`"
          :x="padding.left - 8"
          :y="tick.y"
          text-anchor="end"
          dominant-baseline="middle"
          class="weight-chart__axis-label"
        >{{ tick.value }}</text>

        <line
          :x1="padding.left"
          :x2="width - padding.right"
          :y1="height - padding.bottom"
          :y2="height - padding.bottom"
          class="weight-chart__baseline"
        ></line>
        <text
          v-for="tick in xTicks"
          :key="`xtick-${tick.label}`"
          :x="tick.x"
          :y="height - padding.bottom + 18"
          text-anchor="middle"
          class="weight-chart__axis-label"
        >{{ tick.label }}</text>

        <path v-if="points.length > 1" :d="areaPath" class="weight-chart__area"></path>
        <path v-if="points.length > 1" :d="linePath" class="weight-chart__line"></path>

        <circle
          v-for="(p, i) in points"
          :key="`pt-${i}`"
          :cx="p.x"
          :cy="p.y"
          :r="i === points.length - 1 ? 5 : 3"
          class="weight-chart__dot"
        ></circle>

        <text
          :x="points[points.length - 1].x"
          :y="points[points.length - 1].y - 12"
          text-anchor="end"
          class="weight-chart__end-label"
        >{{ data[data.length - 1].weight }} lbs</text>

        <g v-if="hover">
          <line
            :x1="hover.x"
            :x2="hover.x"
            :y1="padding.top"
            :y2="height - padding.bottom"
            class="weight-chart__crosshair"
          ></line>
          <circle :cx="hover.x" :cy="hover.y" r="5" class="weight-chart__hover-dot"></circle>
        </g>
      </svg>

      <div
        v-if="hover"
        class="weight-chart__tooltip"
        :style="{ left: `${(hover.x / width) * 100}%`, top: `${(hover.y / height) * 100}%` }"
      >
        <div class="weight-chart__tooltip-value">{{ hover.point.weight }} lbs</div>
        <div class="weight-chart__tooltip-date">{{ hover.label }}</div>
      </div>

      <details class="weight-chart__table q-mt-sm">
        <summary class="text-caption text-grey">View as table</summary>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Weight (lbs)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(d, i) in data" :key="i">
              <td>{{ format(parseISO(d.date), "PP") }}</td>
              <td>{{ d.weight }}</td>
            </tr>
          </tbody>
        </table>
      </details>
    </template>
  </div>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { computed, ref } from "vue";

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
});

const width = 640;
const height = 260;
const padding = { top: 16, right: 16, bottom: 32, left: 44 };

const svgRef = ref(null);
const hover = ref(null);

const dates = computed(() => props.data.map((d) => parseISO(d.date).getTime()));
const minDate = computed(() => Math.min(...dates.value));
const maxDate = computed(() => Math.max(...dates.value));

const weights = computed(() => props.data.map((d) => d.weight));

const niceBounds = (min, max) => {
  if (min === max) {
    return { min: min - 5, max: max + 5 };
  }
  const pad = (max - min) * 0.1 || 1;
  return {
    min: Math.floor((min - pad) / 5) * 5,
    max: Math.ceil((max + pad) / 5) * 5,
  };
};

const yBounds = computed(() =>
  niceBounds(Math.min(...weights.value), Math.max(...weights.value)),
);

const xScale = (t) => {
  if (maxDate.value === minDate.value) {
    return padding.left + (width - padding.left - padding.right) / 2;
  }
  const ratio = (t - minDate.value) / (maxDate.value - minDate.value);
  return padding.left + ratio * (width - padding.left - padding.right);
};

const yScale = (w) => {
  const { min, max } = yBounds.value;
  const ratio = (w - min) / (max - min || 1);
  return height - padding.bottom - ratio * (height - padding.top - padding.bottom);
};

const points = computed(() =>
  props.data.map((d) => ({
    x: xScale(parseISO(d.date).getTime()),
    y: yScale(d.weight),
  })),
);

const linePath = computed(() =>
  points.value.map((p, i) => `${i === 0 ? "M" : "L"} ${p.x} ${p.y}`).join(" "),
);

const areaPath = computed(() => {
  if (points.value.length < 2) return "";
  const baseline = height - padding.bottom;
  const first = points.value[0];
  const last = points.value[points.value.length - 1];
  return `${linePath.value} L ${last.x} ${baseline} L ${first.x} ${baseline} Z`;
});

const yTicks = computed(() => {
  const { min, max } = yBounds.value;
  const step = (max - min) / 4;
  return Array.from({ length: 5 }, (_, i) => {
    const value = Math.round(min + step * i);
    return { value, y: yScale(value) };
  });
});

const xTicks = computed(() => {
  const n = props.data.length;
  if (n === 0) return [];
  const idxs = n <= 4 ? props.data.map((_, i) => i) : [0, Math.floor((n - 1) / 2), n - 1];
  return [...new Set(idxs)].map((i) => ({
    x: points.value[i].x,
    label: format(parseISO(props.data[i].date), "MMM d"),
  }));
});

const onPointerMove = (e) => {
  if (!svgRef.value || props.data.length === 0) return;

  const rect = svgRef.value.getBoundingClientRect();
  const svgX = ((e.clientX - rect.left) / rect.width) * width;

  let nearest = 0;
  let nearestDist = Infinity;
  points.value.forEach((p, i) => {
    const dist = Math.abs(p.x - svgX);
    if (dist < nearestDist) {
      nearestDist = dist;
      nearest = i;
    }
  });

  const point = points.value[nearest];
  hover.value = {
    x: point.x,
    y: point.y,
    point: props.data[nearest],
    label: format(parseISO(props.data[nearest].date), "PPP"),
  };
};

const onPointerLeave = () => {
  hover.value = null;
};
</script>

<style scoped>
.weight-chart {
  position: relative;
}

.weight-chart__svg {
  width: 100%;
  height: auto;
  display: block;
  touch-action: none;
}

.weight-chart__gridline {
  stroke: #e1e0d9;
  stroke-width: 1;
}

.weight-chart__baseline {
  stroke: #c3c2b7;
  stroke-width: 1;
}

.weight-chart__axis-label {
  fill: #898781;
  font-size: 11px;
}

.weight-chart__area {
  fill: #1976d2;
  fill-opacity: 0.1;
  stroke: none;
}

.weight-chart__line {
  fill: none;
  stroke: #1976d2;
  stroke-width: 2;
  stroke-linejoin: round;
  stroke-linecap: round;
}

.weight-chart__dot {
  fill: #1976d2;
  stroke: #fff;
  stroke-width: 2;
}

.weight-chart__end-label {
  fill: #0b0b0b;
  font-size: 12px;
  font-weight: 600;
}

.weight-chart__crosshair {
  stroke: #c3c2b7;
  stroke-width: 1;
}

.weight-chart__hover-dot {
  fill: #1976d2;
  stroke: #fff;
  stroke-width: 2;
}

.weight-chart__tooltip {
  position: absolute;
  transform: translate(-50%, -120%);
  background: #fff;
  border: 1px solid rgba(11, 11, 11, 0.1);
  border-radius: 4px;
  padding: 4px 8px;
  pointer-events: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  white-space: nowrap;
}

.weight-chart__tooltip-value {
  font-weight: 600;
  font-size: 13px;
  color: #0b0b0b;
}

.weight-chart__tooltip-date {
  font-size: 11px;
  color: #52514e;
}

.weight-chart__table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.weight-chart__table th,
.weight-chart__table td {
  text-align: left;
  padding: 2px 8px;
  border-bottom: 1px solid #e1e0d9;
}
</style>
