<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <q-page class="flex flex-center">
        <q-form @submit.prevent="onSubmit">
          <q-card style="width: 60vw;">
            <q-toolbar>
              <q-toolbar-title>
                Diet Journal
              </q-toolbar-title>
            </q-toolbar>
            <q-card-section>
              <q-input
                type="email"
                required
                v-model="form.email"
                label="Email"
                dense
                outlined
              ></q-input>
              <q-input
                :type="showPass ? 'text' : 'password'"
                required
                v-model="form.password"
                label="Password"
                dense
                outlined
              >
                <template #append>
                  <q-btn
                    :icon="showPass ? 'visibility_off' : 'visibility'"
                    @click="showPass = !showPass"
                    flat
                    round
                  ></q-btn>
                </template>
              </q-input>
            </q-card-section>
            <q-card-actions class="justify-end">
              <q-btn label="Sign In" type="submit" color="primary"></q-btn>
            </q-card-actions>
          </q-card>
        </q-form>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { Loading } from "quasar";
import callApi from "src/assets/call-api";
import { useStore } from "src/stores/store";
import { ref } from "vue";

const store = useStore();

const form = ref({
  email: store.user.email,
  password: store.user.password,
});

const showPass = ref(false);

const onSubmit = async () => {
  Loading.show({ delay: 100 });

  const response = await callApi({
    path: "/login",
    method: "post",
    payload: form.value,
  });

  Loading.hide();

  store.user = { ...store.user, ...response.user };

  store.router.push({ name: "home" });
};
</script>
