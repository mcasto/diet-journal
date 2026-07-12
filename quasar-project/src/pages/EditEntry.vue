<template>
  <div v-if="entry">
    <q-toolbar class="shadow-1">
      <q-toolbar-title>
        {{
          entry.id
            ? `Edit ${entry.consumed} :: ${format(entry.consumed_at, "PPpp")}`
            : `New Entry :: ${format(new Date(), "PPpp")}`
        }}
      </q-toolbar-title>
    </q-toolbar>
    <q-form @submit.prevent="onSubmit">
      <div class="column q-gutter-y-md q-px-md q-pt-md">
        <q-input
          type="text"
          label="Consumed"
          dense
          outlined
          v-model="entry.consumed"
          required
        ></q-input>
        <q-input
          type="date"
          label="Date"
          dense
          outlined
          v-model="entry.date"
        ></q-input>
        <q-input
          type="time"
          label="Time"
          dense
          outlined
          v-model="entry.time"
        ></q-input>
        <div class="flex justify-between">
          <q-btn
            label="Cancel"
            color="warning"
            class="text-black"
            :to="{ name: 'home' }"
          ></q-btn>
          <q-btn
            label="Submit"
            color="positive"
            type="submit"
            class="text-black"
          ></q-btn>
        </div>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { format, formatISO9075 } from "date-fns";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";

const store = useStore();

const route = useRoute();

const entry = ref(null);

const onSubmit = async () => {
  const payload = entry.value;
  const method = payload.id ? "put" : "post";
  const path = `/food/${payload.id || ""}`;

  const response = await callApi({ path, method, payload, useAuth: true });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unkonwn error",
    });
    return;
  }

  store.router.push({ name: "home" });
};

onMounted(async () => {
  if (!route.params.id) {
    entry.value = {
      consumed: null,
      date: formatISO9075(new Date(), { representation: "date" }),
      time: formatISO9075(new Date(), { representation: "time" }),
    };
    return;
  }

  const response = await callApi({
    path: `/food/${route.params.id}`,
    method: "get",
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to load entry.",
    });
    store.router.push({ name: "home" });
    return;
  }

  // JavaScript will automatically handle timezone conversion
  const localDate = new Date(response.rec.consumed_at);

  entry.value = {
    ...response.rec,
    date: formatISO9075(localDate, { representation: "date" }),
    time: formatISO9075(localDate, { representation: "time" }),
  };
});
</script>
