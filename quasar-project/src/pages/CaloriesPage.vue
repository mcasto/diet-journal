<template>
  <div>
    <q-toolbar class="shadow-1 q-mb-md">
      <q-toolbar-title> Calories </q-toolbar-title>
    </q-toolbar>

    <div class="row items-start q-col-gutter-sm q-mb-md">
      <div class="col-12 col-md-6" style="max-width: 400px;">
        <q-input
          dense
          outlined
          clearable
          label="Search all food history"
          v-model="foodSearchQuery"
          @keyup.enter="searchFood"
          @clear="foodSearchResult = null"
        >
          <template #append>
            <q-icon name="search" class="cursor-pointer" @click="searchFood"></q-icon>
          </template>
        </q-input>
        <div v-if="foodSearchResult" class="text-caption q-mt-xs">
          Found "{{ foodSearchResult.consumed }}", last eaten
          {{ format(new Date(foodSearchResult.consumed_at), "PPp") }} —
          {{
            foodSearchResult.calories === null ||
            foodSearchResult.calories === undefined
              ? "no calories logged yet"
              : `${foodSearchResult.calories} cal`
          }}. Loaded into the calculator below.
        </div>
      </div>
    </div>

    <q-table
      :rows="rows"
      :columns="columns"
      row-key="consumed"
      dense
      class="cursor-pointer"
      @row-click="(evt, row) => loadRecipe(row.consumed)"
    >
      <template #body-cell-calories="{ row }">
        <q-td class="text-right" @click.stop>
          <q-input
            type="number"
            dense
            outlined
            min="0"
            v-model.number="row.calories"
            @keyup.enter="saveCalories(row)"
            @blur="saveCalories(row)"
          ></q-input>
        </q-td>
      </template>
    </q-table>

    <div class="row q-col-gutter-lg q-mt-lg">
      <div class="col-12 col-md-6" style="max-width: 600px;">
        <q-card class="q-pa-md">
          <div class="text-h6">Calorie Calculator</div>
          <div class="text-caption text-grey q-mb-md">
            Click a row in the table above to load and edit its recipe.
          </div>

          <q-select
            dense
            outlined
            label="Food"
            :options="foodOptions"
            v-model="selectedFood"
            class="q-mb-md"
          ></q-select>

          <div class="row q-col-gutter-sm items-start">
            <div class="col">
              <q-input
                dense
                outlined
                label="Ingredient"
                v-model="ingredientName"
                @keyup.enter="addIngredient"
              ></q-input>
            </div>
            <div class="col-4">
              <q-input
                type="number"
                dense
                outlined
                min="0"
                label="Calories"
                v-model.number="ingredientCalories"
                @keyup.enter="addIngredient"
              ></q-input>
            </div>
            <div class="col-auto">
              <q-btn
                icon="add"
                round
                dense
                color="primary"
                @click="addIngredient"
              ></q-btn>
            </div>
          </div>

          <q-list bordered separator class="q-mt-md" v-if="ingredients.length">
            <q-item v-for="(item, index) in ingredients" :key="index">
              <q-item-section>{{ item.name }}</q-item-section>
              <q-item-section side>{{ item.calories }} cal</q-item-section>
              <q-item-section side>
                <q-btn
                  icon="close"
                  flat
                  round
                  dense
                  @click="removeIngredient(index)"
                ></q-btn>
              </q-item-section>
            </q-item>
          </q-list>

          <div class="row items-center justify-between q-mt-md">
            <div class="text-subtitle2">Total: {{ totalCalories }} cal</div>
            <q-btn
              label="Calculate"
              color="primary"
              :disable="!selectedFood || !ingredients.length"
              @click="calculate"
            ></q-btn>
          </div>
        </q-card>
      </div>

      <div class="col-12 col-md-6" style="max-width: 600px;">
        <q-card class="q-pa-md">
          <div class="text-h6 q-mb-md">Summary</div>

          <div class="text-subtitle2">Average Daily Intake</div>
          <div class="text-h5 q-mb-md">{{ Math.round(averageDailyIntake) }} cal</div>

          <div class="text-subtitle2">Foods Missing Calories</div>
          <q-list
            v-if="foodsMissingCalories.length"
            bordered
            separator
            class="q-mt-sm scroll"
            style="max-height: 300px; overflow-y: auto;"
          >
            <q-item v-for="food in foodsMissingCalories" :key="food">
              <q-item-section>{{ food }}</q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-caption text-grey q-mt-sm">
            Every food has a calorie value.
          </div>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { format, formatISO9075, subMonths } from "date-fns";
import { groupBy } from "lodash-es";
import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { computed, ref } from "vue";

const store = useStore();

const columns = [
  {
    label: "Consumed",
    name: "consumed",
    field: "consumed",
  },
  {
    label: "Last Logged",
    name: "logged",
    field: (row) => format(new Date(row.consumed_at), "PPpp"),
    sortable: true,
  },
  {
    label: "Calories",
    name: "calories",
    field: "calories",
  },
];

const rows = computed(() => {
  const cutoff = subMonths(new Date(), 1);
  const recent = (store.food || []).filter(
    (item) => new Date(item.consumed_at) >= cutoff,
  );

  const groups = groupBy(recent, (item) => item.consumed.toLowerCase());

  return Object.values(groups)
    .map((entries) =>
      entries.reduce((latest, entry) =>
        new Date(entry.consumed_at) > new Date(latest.consumed_at)
          ? entry
          : latest,
      ),
    )
    .sort((a, b) => new Date(b.consumed_at) - new Date(a.consumed_at));
});

const saveCalories = async (row) => {
  if (
    row.calories === null ||
    row.calories === undefined ||
    row.calories === ""
  ) {
    return;
  }

  const response = await callApi({
    path: `/calories`,
    method: "put",
    payload: { consumed: row.consumed, calories: row.calories },
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to save calories.",
    });
    return;
  }

  // Every entry with the same `consumed` name (case-insensitively) shares
  // one calorie value.
  store.food = store.food.map((item) =>
    item.consumed.toLowerCase() === row.consumed.toLowerCase()
      ? { ...item, calories: row.calories }
      : item,
  );
};

const foodOptions = computed(() => rows.value.map((row) => row.consumed));

const foodsMissingCalories = computed(() =>
  rows.value
    .filter((row) => row.calories === null || row.calories === undefined)
    .map((row) => row.consumed),
);

const averageDailyIntake = computed(() => {
  const cutoff = subMonths(new Date(), 1);
  const logged = (store.food || []).filter(
    (item) =>
      new Date(item.consumed_at) >= cutoff &&
      item.calories !== null &&
      item.calories !== undefined &&
      item.consumed.toLowerCase() !== "check calories",
  );

  const byDay = groupBy(logged, (item) =>
    formatISO9075(new Date(item.consumed_at), { representation: "date" }),
  );

  const scrappedDates = new Set(store.scrappedDates || []);

  const dailyTotals = Object.entries(byDay)
    .filter(([date]) => !scrappedDates.has(date))
    .map(([, entries]) => entries.reduce((sum, entry) => sum + entry.calories, 0));

  if (!dailyTotals.length) {
    return 0;
  }

  return (
    dailyTotals.reduce((sum, total) => sum + total, 0) / dailyTotals.length
  );
});

const selectedFood = ref(null);
const ingredientName = ref(null);
const ingredientCalories = ref(null);
const ingredients = ref([]);

const totalCalories = computed(() =>
  ingredients.value.reduce((sum, item) => sum + item.calories, 0),
);

const addIngredient = () => {
  if (
    !ingredientName.value ||
    ingredientCalories.value === null ||
    ingredientCalories.value === ""
  ) {
    return;
  }

  ingredients.value.push({
    name: ingredientName.value,
    calories: ingredientCalories.value,
  });

  ingredientName.value = null;
  ingredientCalories.value = null;
};

const removeIngredient = (index) => {
  ingredients.value.splice(index, 1);
};

const foodSearchQuery = ref("");
const foodSearchResult = ref(null);

const searchFood = async () => {
  const query = foodSearchQuery.value?.trim();
  if (!query) {
    return;
  }

  const response = await callApi({
    path: `/food/search?q=${encodeURIComponent(query)}`,
    method: "get",
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to search for food.",
    });
    return;
  }

  if (!response.found) {
    foodSearchResult.value = null;
    Notify.create({
      type: "warning",
      message: `No food found matching "${query}".`,
    });
    return;
  }

  foodSearchResult.value = response.rec;
  await loadRecipe(response.rec.consumed);
};

const loadRecipe = async (consumed) => {
  selectedFood.value = consumed;

  const response = await callApi({
    path: "/recipes",
    method: "get",
    payload: { consumed: encodeURIComponent(consumed) },
    useAuth: true,
  });

  if (response.status != "success") {
    Notify.create({
      type: "negative",
      position: "center",
      message: response.message || "Unable to load recipe.",
    });
    return;
  }

  ingredients.value = response.data.map((item) => ({
    name: item.ingredient,
    calories: item.calories,
  }));
};

const saveRecipe = async (consumed) => {
  await callApi({
    path: "/recipes",
    method: "put",
    payload: {
      consumed,
      ingredients: ingredients.value.map((item) => ({
        ingredient: item.name,
        calories: item.calories,
      })),
    },
    useAuth: true,
  });
};

const calculate = async () => {
  if (!selectedFood.value) {
    return;
  }

  const consumed = selectedFood.value;
  await saveCalories({ consumed, calories: totalCalories.value });
  await saveRecipe(consumed);

  ingredients.value = [];
  selectedFood.value = null;
  foodSearchResult.value = null;
};
</script>
