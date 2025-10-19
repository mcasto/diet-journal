import { defineStore } from "pinia";
import { ref, computed } from "vue";

const isDev = process.env.NODE_ENV == "development";

export const useStore = defineStore(
  "store",
  () => {
    const state = {
      user: ref({
        email: (isDev && process.env.VITE_ENV_EMAIL) || null,
        password: (isDev && process.env.VITE_ENV_PASSWORD) || null,
        token: null,
      }),
      food: ref(null),
    };
    const getters = {};
    const actions = {};

    return { ...state, ...getters, ...actions };
  },
  {
    persist: {
      key: "diet-journal",
    },
  }
);
