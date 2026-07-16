<template>
  <div v-if="profile">
    <q-toolbar class="shadow-1">
      <q-toolbar-title> Profile </q-toolbar-title>
    </q-toolbar>
    <q-form @submit.prevent="onSubmit">
      <div class="column q-gutter-y-md q-px-md q-pt-md" style="max-width: 400px;">
        <q-select
          dense
          outlined
          label="Sex"
          emit-value
          map-options
          :options="sexOptions"
          v-model="profile.sex"
        ></q-select>
        <q-input
          type="number"
          label="Height (in)"
          dense
          outlined
          v-model.number="profile.height"
        ></q-input>
        <q-input
          type="number"
          label="Weight (lbs)"
          dense
          outlined
          v-model.number="profile.weight"
        ></q-input>
        <q-input
          type="date"
          label="Birthdate"
          dense
          outlined
          v-model="profile.birthdate"
        ></q-input>
        <q-select
          dense
          outlined
          label="Exercise Level"
          emit-value
          map-options
          :options="exerciseOptions"
          v-model="profile.exercise"
        ></q-select>
        <q-option-group
          dense
          v-model="profile.target"
          type="radio"
          :options="targetOptions"
        ></q-option-group>
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

    <div class="q-px-md q-pt-lg" style="max-width: 640px;">
      <div class="text-subtitle1 q-mb-sm">Weight History</div>
      <WeightChart :data="weightHistory" />
    </div>
  </div>
</template>

<script setup>
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import WeightChart from "components/WeightChart.vue";
import { useStore } from "src/stores/store";
import { computed, onMounted, ref } from "vue";

const store = useStore();

const profile = ref(null);
const exerciseLevels = ref([]);
const targets = ref([]);
const weightHistory = ref([]);

const sexOptions = [
  { label: "Male", value: "m" },
  { label: "Female", value: "f" },
];

const exerciseLevelLabels = [
  "Sedentary (little or no exercise)",
  "Lightly active (light exercise 1-3 days/week)",
  "Moderately active (moderate exercise 3-5 days/week)",
  "Active (daily moderate exercise)",
  "Very active (hard exercise 6-7 days/week)",
  "Extra active (very hard exercise & physical job)",
];

const targetLabels = {
  loss: "Losing weight",
  maintenance: "Maintaining weight",
};

const exerciseOptions = computed(() =>
  exerciseLevels.value.map((level, i) => ({
    label: exerciseLevelLabels[i] || `Level ${i + 1}`,
    value: i,
  })),
);

const targetOptions = computed(() =>
  targets.value.map((target) => ({
    label:
      targetLabels[target] ||
      target.charAt(0).toUpperCase() + target.slice(1),
    value: target,
  })),
);

const onSubmit = async () => {
  const response = await callApi({
    path: "/profile",
    method: "put",
    payload: profile.value,
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to save profile.",
    });
    return;
  }

  store.router.push({ name: "home" });
};

onMounted(async () => {
  const response = await callApi({
    path: "/profile",
    method: "get",
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to load profile.",
    });
    store.router.push({ name: "home" });
    return;
  }

  exerciseLevels.value = response.exerciseLevels;
  targets.value = response.targets;

  profile.value = {
    sex: response.sex,
    height: response.height,
    weight: response.weight,
    birthdate: response.birthdate,
    exercise: response.exercise,
    target: response.target,
  };

  const weightsResponse = await callApi({
    path: "/weights",
    method: "get",
    useAuth: true,
  });

  if (weightsResponse.status == "success") {
    weightHistory.value = weightsResponse.data;
  }
});
</script>
