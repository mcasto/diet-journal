import { defineRouter } from "#q-app/wrappers";
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from "vue-router";
import routes from "./routes";
import { useStore } from "src/stores/store";
import callApi from "src/assets/call-api";
import { Notify } from "quasar";

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default defineRouter(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === "history"
    ? createWebHistory
    : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_BASE),
  });

  Router.beforeEach(async (to, from, next) => {
    const store = useStore();

    if (to.meta.requireAuth) {
      if (!store.user.token) {
        next({ name: "login" });
        return;
      }

      const response = await callApi({
        path: "/user",
        method: "get",
        useAuth: true,
      });
      if (response.status == 401) {
        next({ name: "login" });
        return;
      } else {
        if (response.status != "success") {
          Notify.create({
            type: "negative",
            message: response.message,
          });

          next({ name: "login" });
          return;
        }
      }
    }

    next();
  });

  return Router;
});
