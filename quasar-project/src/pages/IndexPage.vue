<template>
  <div>
    <q-toolbar class="shadow-1 q-mb-md">
      <q-toolbar-title>
        Food Log
      </q-toolbar-title>

      <div class="row q-mr-xl">
        <q-input
          type="date"
          dense
          outlined
          label="View Date"
          clearable
          v-model="filterDate"
        ></q-input>

        <q-btn icon="add" class="q-ml-md" :to="{ name: 'edit' }"></q-btn>
      </div>
    </q-toolbar>
    <q-table :rows="rows" :columns="columns">
      <template #body-cell-tools="{row}">
        <q-td class="text-right">
          <q-btn icon="delete" @click="deleteEntry(row)"></q-btn>
          <q-btn
            icon="edit"
            :to="{ name: 'edit', params: { id: row.id } }"
          ></q-btn>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script setup>
import {
  format,
  formatDuration,
  formatISO9075,
  intervalToDuration,
  parseISO,
} from "date-fns";
import { remove } from "lodash-es";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const filterDate = ref(null);

const columns = [
  {
    label: "Consumed",
    name: "consumed",
    field: "consumed",
  },
  {
    label: "Logged",
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

const rows = computed(() => {
  if (!filterDate.value) {
    return store.food;
  }

  return store.food.filter((item) => {
    return (
      formatISO9075(parseISO(item.consumed_at), { representation: "date" }) ==
      filterDate.value
    );
  });
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

          remove(store.food, ({ id }) => id == row.id);
        },
      },
    ],
  });
};
</script>
