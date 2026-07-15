<template>
  <div>
    <q-toolbar class="shadow-1 q-mb-md">
      <q-toolbar-title>
        Food Log
      </q-toolbar-title>

      <div class="row q-mr-xl">
        <q-btn
          icon="mdi-chevron-left"
          flat
          round
          @click="shiftFilterDate(-1)"
        ></q-btn>
        <q-input
          type="date"
          dense
          outlined
          label="View Date"
          v-model="filterDate"
          @update:model-value="onFilterDateChange"
        ></q-input>
        <q-btn
          icon="mdi-chevron-right"
          flat
          round
          :disable="isCurrentDate"
          @click="shiftFilterDate(1)"
        ></q-btn>

        <q-btn icon="add" class="q-ml-md" :to="{ name: 'edit' }"></q-btn>
      </div>
    </q-toolbar>
    <q-table
      :rows="rows"
      :columns="columns"
      :loading="loading"
      v-model:pagination="pagination"
      row-key="id"
      @request="onRequest"
    >
      <template #body="props">
        <q-tr
          :props="props"
          :class="{
            'bg-warning':
              props.row.calories === null || props.row.calories === undefined,
          }"
        >
          <q-td v-for="col in props.cols" :key="col.name" :props="props">
            <template v-if="col.name === 'tools'">
              <q-btn icon="delete" @click="deleteEntry(props.row)"></q-btn>
              <q-btn
                icon="edit"
                :to="{ name: 'edit', params: { id: props.row.id } }"
              ></q-btn>
            </template>
            <template v-else>{{ col.value }}</template>
          </q-td>
        </q-tr>
      </template>
    </q-table>

    <div class="text-h6 q-mt-md flex justify-between q-mx-lg">
      <div>Daily Calories: {{ dailyCalories }} cal</div>
      <div class="row items-center">
        Current Weight: {{ weight }} lbs
        <q-btn icon="edit" flat round dense size="sm" class="q-ml-xs">
          <q-menu
            ref="weightMenu"
            anchor="bottom middle"
            self="top middle"
            @show="
              weightInput = weight;
              weightTarget = target;
            "
          >
            <div class="q-pa-md" style="min-width: 220px;">
              <q-input
                type="number"
                dense
                outlined
                label="Weight (lbs)"
                v-model.number="weightInput"
                @keyup.enter="saveWeight"
              ></q-input>
              <q-option-group
                dense
                v-model="weightTarget"
                type="radio"
                class="q-mt-sm"
                :options="[
                  { label: 'Losing weight', value: 'loss' },
                  { label: 'Maintaining weight', value: 'maintenance' },
                ]"
              ></q-option-group>
              <div class="row justify-end q-mt-sm">
                <q-btn
                  label="Save"
                  color="primary"
                  dense
                  flat
                  @click="saveWeight"
                ></q-btn>
              </div>
            </div>
          </q-menu>
        </q-btn>
      </div>
      <div>Remaining Calories: {{ remaining }} cal</div>
    </div>
  </div>
</template>

<script setup>
import {
  addDays,
  format,
  formatDuration,
  formatISO9075,
  intervalToDuration,
  isToday,
  parseISO,
} from "date-fns";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { computed, onMounted, ref } from "vue";

const filterDate = ref(formatISO9075(new Date(), { representation: "date" }));
const isCurrentDate = computed(() => isToday(parseISO(filterDate.value)));
const rows = ref([]);
const loading = ref(false);
const dailyCalories = ref(0);
const remaining = ref(0);
const weight = ref(null);
const target = ref(null);
const weightInput = ref(null);
const weightTarget = ref(null);
const weightMenu = ref(null);
const pagination = ref({
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0,
});

const columns = [
  {
    label: "Consumed",
    name: "consumed",
    field: "consumed",
  },
  {
    label: "Logged",
    name: "logged",
    field: (row) => {
      return format(new Date(row.consumed_at), "PPpp");
    },
    sortable: true,
  },
  {
    label: "Elapsed",
    name: "elapsed",
    field: (row) => {
      const start = parseISO(row.consumed_at);
      const end = new Date();
      const interval = intervalToDuration({ start, end });
      return formatDuration(interval, { format: ["hours", "minutes"] });
    },
  },
  {
    name: "tools",
  },
];

const onRequest = async ({ pagination: requested }) => {
  loading.value = true;

  const response = await callApi({
    path: `/food?date=${filterDate.value}&page=${requested.page}&per_page=${requested.rowsPerPage}`,
    method: "get",
    useAuth: true,
  });

  loading.value = false;

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      message: response.message,
    });
    return;
  }

  rows.value = response.data;
  dailyCalories.value = response.daily_calories;
  pagination.value = {
    page: response.current_page,
    rowsPerPage: response.per_page,
    rowsNumber: response.total,
  };
};

const fetchRemaining = async () => {
  remaining.value = await callApi({
    path: "/calories/remaining",
    method: "get",
    useAuth: true,
  });
};

const fetchWeight = async () => {
  const response = await callApi({
    path: "/weight",
    method: "get",
    useAuth: true,
  });

  weight.value = response.weight;
  target.value = response.target;
};

const saveWeight = async () => {
  if (weightInput.value === null || weightInput.value === "") {
    return;
  }

  let response;
  try {
    response = await callApi({
      path: "/weight",
      method: "post",
      payload: { weight: weightInput.value, target: weightTarget.value },
      useAuth: true,
    });
  } catch {
    Notify.create({
      type: "negative",
      position: "center",
      message: "Unable to save weight.",
    });
    return;
  }

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to save weight.",
    });
    return;
  }

  weight.value = weightInput.value;
  target.value = weightTarget.value;
  weightMenu.value?.hide();
  fetchRemaining();
};

const onFilterDateChange = () => {
  onRequest({ pagination: { ...pagination.value, page: 1 } });
};

const shiftFilterDate = (days) => {
  filterDate.value = formatISO9075(addDays(parseISO(filterDate.value), days), {
    representation: "date",
  });
  onFilterDateChange();
};

onMounted(() => {
  onRequest({ pagination: pagination.value });
  fetchRemaining();
  fetchWeight();
});

const deleteEntry = async (row) => {
  Notify.create({
    type: "warning",
    position: "center",
    message: "Are you sure you want to delete the entry?",
    actions: [
      {
        label: "No",
      },
      {
        label: "Yes",
        handler: async () => {
          const response = await callApi({
            path: `/food/${row.id}`,
            method: "delete",
            useAuth: true,
          });

          if (response.status != "success") {
            Notify.create({
              position: "center",
              type: "negative",
              message: response.message,
            });
            return;
          }

          onRequest({ pagination: pagination.value });
          fetchRemaining();
        },
      },
    ],
  });
};
</script>
