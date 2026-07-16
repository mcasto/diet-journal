<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-toolbar-title>Diet Journal</q-toolbar-title>

        <q-tabs shrink>
          <q-route-tab :to="{ name: 'home' }" label="Food Log" />
          <q-route-tab :to="{ name: 'calories' }" label="Calories" />
          <q-route-tab :to="{ name: 'profile' }" label="Profile" />
        </q-tabs>

        <q-btn icon="logout" flat round class="q-ml-md" @click="logout"></q-btn>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <q-page>
        <router-view />
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useStore } from "src/stores/store";

const store = useStore();

const logout = () => {
  store.user = { email: null, password: null, token: null };
  store.router.push({ name: "login" });
};
</script>
