import { Notify } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";

const routes = [
  {
    path: "/login",
    component: () => import("pages/LoginPage.vue"),
    name: "login",
  },
  {
    path: "/",
    component: () => import("layouts/MainLayout.vue"),
    children: [
      {
        path: "",
        component: () => import("pages/IndexPage.vue"),
        name: "home",
        beforeEnter: async () => {
          const store = useStore();
          const response = await callApi({
            path: "/food",
            method: "get",
            useAuth: true,
          });

          if (!response.status == "success") {
            Notify.create({
              type: "negative",
              message: response.message,
            });
            return;
          }

          store.food = response.data;
        },
      },
      {
        path: "/edit/:id?",
        component: () => import("pages/EditEntry.vue"),
        name: "edit",
      },
    ],
    meta: { requireAuth: true },
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: "/:catchAll(.*)*",
    component: () => import("pages/ErrorNotFound.vue"),
  },
];

export default routes;
