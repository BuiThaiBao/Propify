import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import Home from "../pages/Home.vue";

const routes = [
  {
    path: "/",
    name: "Home",
    component: Home,
  },
  {
    path: "/login",
    name: "Login",
    component: () => import("@/pages/Auth/Login.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/register",
    name: "Register",
    component: () => import("@/pages/Auth/Register.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/login-success",
    name: "LoginSuccess",
    component: () => import("@/pages/Auth/LoginSuccess.vue"),
  },
  {
    path: "/sales",
    name: "Sales",
    component: () => import("@/pages/Sale/index.vue"),
  },
  {
    path: "/rent",
    name: "Rent",
    component: () => import("@/pages/Rent/index.vue"),
  },
  {
    path: "/profile",
    name: "Profile",
    component: () => import("@/pages/Profile/index.vue"),
    meta: { requiresAuth: true },
  },
  {
    path: "/chat",
    name: "Chat",
    component: () => import("@/pages/Chat/index.vue"),
    meta: { requiresAuth: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

/**
 * Navigation guard — enforce auth/guest route restrictions.
 */
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  // Route cần auth mà chưa login → redirect login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: "Login", query: { redirect: to.fullPath } });
  }

  // Đã login mà vào trang login/register → redirect home
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return next({ name: "Home" });
  }

  next();
});

export default router;
