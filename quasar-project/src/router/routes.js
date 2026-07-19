import { formatISO9075, subMonths } from "date-fns";
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
      },
      {
        path: "/edit/:id?",
        component: () => import("pages/EditEntry.vue"),
        name: "edit",
      },
      {
        path: "/profile",
        component: () => import("pages/ProfilePage.vue"),
        name: "profile",
      },
      {
        path: "/calories",
        component: () => import("pages/CaloriesPage.vue"),
        name: "calories",
        beforeEnter: async () => {
          const store = useStore();
          const today = new Date();
          const response = await callApi({
            path: `/food?from=${formatISO9075(subMonths(today, 1), { representation: "date" })}&to=${formatISO9075(today, { representation: "date" })}`,
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
          store.scrappedDates = response.scrapped_dates;
        },
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
