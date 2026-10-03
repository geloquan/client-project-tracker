<script setup>
import {computed, onMounted, ref, watch} from 'vue';

const user = ref(null);
const authLoading = ref(true);
const loginForm = ref({email: '', password: ''});
const loginErrors = ref({});
const loginSaving = ref(false);
const activePage = ref('projects');
const profileForm = ref({name: ''});
const passwordForm = ref({current_password: '', password: '', password_confirmation: ''});
const profileErrors = ref({});
const profileSaving = ref(false);
const projects = ref([]);
const loading = ref(false);
const saving = ref(false);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const search = ref('');
const status = ref('');
const priority = ref('');
const sortBy = ref('created_at');
const sortDirection = ref('desc');
const showModal = ref(false);
const showDeleteModal = ref(false);
const deletingProject = ref(null);
const showColumns = ref(false);
const editingProject = ref(null);
const toast = ref(null);
const errors = ref({});
let toastTimer;

const emptyForm = () => ({
  client_name: '',
  project_name: '',
  description: '',
  planned_at: '',
  in_progress_at: '',
  status: 'planned',
  priority: 'medium',
});
const form = ref(emptyForm());
const columns = [
  {key: 'client_name', label: 'Client'},
  {key: 'project_name', label: 'Project'},
  {key: 'status', label: 'Status'},
  {key: 'priority', label: 'Priority'},
  {key: 'in_progress_at', label: 'Start date'},
  {key: 'planned_at', label: 'Due date'},
];
const visibleColumns = ref(columns.map((column) => column.key));

const displayedProjects = computed(() => [...projects.value].sort((a, b) => {
  const first = String(a[sortBy.value] ?? '').toLowerCase();
  const second = String(b[sortBy.value] ?? '').toLowerCase();
  return first.localeCompare(second) * (sortDirection.value === 'asc' ? 1 : -1);
}));
const pageLabel = computed(() => total.value ? `${(page.value - 1) * 15 + 1}–${Math.min(page.value * 15, total.value)} of ${total.value}` : '0 projects');
const isVisible = (key) => visibleColumns.value.includes(key);
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content;
const jsonHeaders = () => ({
  'Content-Type': 'application/json',
  Accept: 'application/json',
  'X-CSRF-TOKEN': csrfToken()
});

async function loadProfile() {
  const response = await fetch('/api/profile', {headers: {Accept: 'application/json'}});
  if (response.status === 401) return false;
  const body = await response.json();
  user.value = body.data;
  profileForm.value.name = user.value.name;
  return true;
}

async function login() {
  loginSaving.value = true;
  loginErrors.value = {};
  try {
    const response = await fetch('/api/login', {
      method: 'POST',
      headers: jsonHeaders(),
      body: JSON.stringify(loginForm.value)
    });
    const body = await response.json();
    if (!response.ok) {
      loginErrors.value = body.errors ?? {};
      throw new Error(body.message || 'Unable to sign in.');
    }
    user.value = body.data;
    profileForm.value.name = user.value.name;
    await loadProjects();
  } catch (error) {
    notify(error.message, 'error');
  } finally {
    loginSaving.value = false;
  }
}

async function logout() {
  await fetch('/api/logout', {method: 'POST', headers: jsonHeaders()});
  user.value = null;
  activePage.value = 'projects';
}

async function updateName() {
  profileSaving.value = true;
  profileErrors.value = {};
  try {
    const response = await fetch('/api/profile', {
      method: 'PATCH',
      headers: jsonHeaders(),
      body: JSON.stringify(profileForm.value)
    });
    const body = await response.json();
    if (!response.ok) {
      profileErrors.value = body.errors ?? {};
      throw new Error(body.message || 'Unable to update your name.');
    }
    user.value = body.data;
    notify('Name updated successfully.');
  } catch (error) {
    notify(error.message, 'error');
  } finally {
    profileSaving.value = false;
  }
}

async function changePassword() {
  profileSaving.value = true;
  profileErrors.value = {};
  try {
    const response = await fetch('/api/profile/password', {
      method: 'PUT',
      headers: jsonHeaders(),
      body: JSON.stringify(passwordForm.value)
    });
    const body = await response.json();
    if (!response.ok) {
      profileErrors.value = body.errors ?? {};
      throw new Error(body.message || 'Unable to change your password.');
    }
    passwordForm.value = {current_password: '', password: '', password_confirmation: ''};
    notify('Password changed successfully.');
  } catch (error) {
    notify(error.message, 'error');
  } finally {
    profileSaving.value = false;
  }
}

async function loadProjects() {
  loading.value = true;
  const params = new URLSearchParams({page: page.value, per_page: 15});
  if (search.value) params.set('search', search.value);
  if (status.value) params.set('status', status.value);
  if (priority.value) params.set('priority', priority.value);
  try {
    const response = await fetch(`/api/projects?${params}`);
    const body = await response.json();
    if (!response.ok) throw new Error(body.message || 'Unable to load projects.');
    projects.value = body.data ?? [];
    page.value = body.meta?.current_page ?? 1;
    lastPage.value = body.meta?.last_page ?? 1;
    total.value = body.meta?.total ?? projects.value.length;
  } catch (error) {
    notify(error.message, 'error');
  } finally {
    loading.value = false;
  }
}

function notify(message, type = 'success') {
  toast.value = {message, type};
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.value = null;
  }, 4000);
}

function openCreate() {
  editingProject.value = null;
  form.value = emptyForm();
  errors.value = {};
  showModal.value = true;
}

function openEdit(project) {
  editingProject.value = project;
  form.value = {
    client_name: project.client_name ?? '',
    project_name: project.project_name ?? '',
    description: project.description ?? '',
    planned_at: toInputDate(project.planned_at),
    in_progress_at: toInputDate(project.in_progress_at),
    status: project.status ?? 'planned',
    priority: project.priority ?? 'medium',
  };
  errors.value = {};
  showModal.value = true;
}

function toInputDate(value) {
  return value ? new Date(value).toISOString().slice(0, 10) : '';
}

function apiDate(value) {
  return value ? `${value}T00:00:00` : null;
}

async function saveProject() {
  saving.value = true;
  errors.value = {};
  const payload = {
    ...form.value,
    planned_at: apiDate(form.value.planned_at),
    in_progress_at: form.value.status === 'planned' ? null : apiDate(form.value.in_progress_at),
  };
  const url = editingProject.value ? `/api/projects/${editingProject.value.id}` : '/api/projects';
  try {
    const response = await fetch(url, {
      method: editingProject.value ? 'PUT' : 'POST',
      headers: jsonHeaders(),
      body: JSON.stringify(payload),
    });
    const body = await response.json();
    if (!response.ok) {
      errors.value = body.errors ?? {};
      throw new Error(body.message || 'Please check the highlighted fields.');
    }
    showModal.value = false;
    notify(body.message || 'Project saved.');
    await loadProjects();
  } catch (error) {
    notify(error.message, 'error');
  } finally {
    saving.value = false;
  }
}

async function removeProject(project) {
  deletingProject.value = project;
  showDeleteModal.value = true;
}

async function confirmDelete() {
  if (!deletingProject.value) return;

  const response = await fetch(`/api/projects/${deletingProject.value.id}`, {method: 'DELETE', headers: jsonHeaders()});
  const body = await response.json();
  if (!response.ok) {
    showDeleteModal.value = false;
    return notify(body.message || 'Unable to delete project.', 'error');
  }

  showDeleteModal.value = false;
  deletingProject.value = null;
  notify('Project removed.');
  await loadProjects();
}

function changePage(nextPage) {
  page.value = Math.min(Math.max(Number(nextPage) || 1, 1), lastPage.value);
  loadProjects();
}

function toggleSort(key) {
  if (sortBy.value === key) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  else {
    sortBy.value = key;
    sortDirection.value = 'asc';
  }
}

function statusClass(value) {
  return {
    planned: 'bg-[#f4e8c9] text-[#80652f]',
    in_progress: 'bg-[#dcefe4] text-[#397258]',
    on_hold: 'bg-[#f7d7ce] text-[#9a5142]',
    completed: 'bg-[#ddd8ee] text-[#5e5686]',
  }[value] ?? 'bg-stone-100 text-stone-600';
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat('en', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  }).format(new Date(value)) : '—';
}

watch([search, status, priority], () => {
  if (user.value) {
    page.value = 1;
    loadProjects();
  }
});
onMounted(async () => {
  try {
    if (await loadProfile()) await loadProjects();
  } finally {
    authLoading.value = false;
  }
});
</script>

<template>
  <div v-if="authLoading" class="grid min-h-screen place-items-center text-caramel">Warming up the pantry…</div>
  <div v-else-if="!user" class="grid min-h-screen place-items-center px-5">
    <form
      class="w-full max-w-md rounded-3xl border border-[#eadfce] bg-white/90 p-8 shadow-[0_10px_40px_rgba(155,115,77,0.1)]"
      @submit.prevent="login">
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-caramel text-2xl text-white">✦</div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-caramel">Project pantry</p>
        <h1 class="mt-2 font-serif text-4xl font-bold">Welcome back</h1>
        <p class="mt-2 text-sm text-stone-500">Sign in to keep your projects moving.</p></div>
      <div class="space-y-4"><label class="field">Email<input v-model="loginForm.email" type="email" required
                                                              autocomplete="email" placeholder="you@example.com"/><small
        v-if="loginErrors.email">{{ loginErrors.email[0] }}</small></label><label class="field">Password<input
        v-model="loginForm.password" type="password" required autocomplete="current-password"
        placeholder="Your password"/><small v-if="loginErrors.password">{{ loginErrors.password[0] }}</small></label>
      </div>
      <button :disabled="loginSaving"
              class="mt-6 w-full rounded-full bg-caramel px-5 py-3 font-semibold text-white disabled:opacity-50">
        {{ loginSaving ? 'Opening pantry…' : 'Sign in' }}
      </button>
    </form>
  </div>
  <div v-else class="min-h-screen">
    <header class="border-b border-[#eadfce] bg-[#fffaf0]/90 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 lg:px-8">
        <div class="flex items-center gap-3">
          <div class="grid h-11 w-11 place-items-center rounded-2xl bg-caramel text-xl text-white shadow-sm">✦</div>
          <div><p class="font-serif text-2xl font-bold tracking-tight">Sundae</p>
            <p class="text-xs uppercase tracking-[0.25em] text-caramel">Project pantry</p></div>
        </div>
        <div class="flex items-center gap-3">
          <button class="rounded-full border border-[#eadfce] px-4 py-2.5 text-sm font-semibold text-stone-600 hover:bg-white"
                  @click="activePage = 'profile'">
            Profile
          </button>
          <button
            class="rounded-full border border-[#eadfce] px-4 py-2.5 text-sm font-semibold text-stone-600 hover:bg-white"
            @click="logout">Log out
          </button>
          <button v-if="activePage === 'projects'"
                  class="rounded-full bg-caramel px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b57244]"
                  @click="openCreate">+ New project
          </button>
        </div>
      </div>
    </header>

    <main v-if="activePage === 'projects'" class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
      <div class="mb-8"><p class="mb-2 text-sm font-semibold uppercase tracking-[0.25em] text-caramel">Today’s scoop</p>
        <h1 class="font-serif text-4xl font-bold tracking-tight md:text-5xl">Projects</h1>
        <p class="mt-2 max-w-xl text-stone-500">A sweet, simple place to keep every project moving.</p></div>
      <div class="mb-5 rounded-3xl border border-[#eadfce] bg-white/80 p-3 shadow-[0_10px_40px_rgba(155,115,77,0.08)]">
        <div class="flex flex-col gap-3 lg:flex-row">
          <label class="relative flex-1"><span class="sr-only">Search projects</span><span
            class="absolute left-4 top-2.5 text-lg text-stone-400">⌕</span><input v-model="search"
                                                                                  class="w-full rounded-2xl border-0 bg-[#fffaf0] py-3 pl-10 pr-4 text-sm outline-none ring-caramel/30 focus:ring-2"
                                                                                  placeholder="Search projects, clients, descriptions..."/></label>
          <select v-model="status"
                  class="rounded-2xl border-0 bg-[#fffaf0] px-4 py-3 text-sm text-stone-600 outline-none ring-caramel/30 focus:ring-2">
            <option value="">All statuses</option>
            <option value="planned">Planned</option>
            <option value="in_progress">In progress</option>
            <option value="on_hold">On hold</option>
            <option value="completed">Completed</option>
          </select>
          <select v-model="priority"
                  class="rounded-2xl border-0 bg-[#fffaf0] px-4 py-3 text-sm text-stone-600 outline-none ring-caramel/30 focus:ring-2">
            <option value="">All priorities</option>
            <option value="high">High priority</option>
            <option value="medium">Medium priority</option>
            <option value="low">Low priority</option>
          </select>
          <div class="relative">
            <button
              class="h-full rounded-2xl border border-[#eadfce] px-4 py-3 text-sm font-semibold text-stone-600 hover:bg-[#fffaf0]"
              @click="showColumns = !showColumns">Columns ▾
            </button>
            <div v-if="showColumns"
                 class="absolute right-0 z-10 mt-2 w-48 rounded-2xl border border-[#eadfce] bg-white p-3 shadow-xl">
              <label v-for="column in columns" :key="column.key"
                     class="flex items-center gap-2 px-2 py-2 text-sm"><input v-model="visibleColumns" type="checkbox"
                                                                              :value="column.key"
                                                                              class="accent-caramel"/>{{ column.label }}</label>
            </div>
          </div>
        </div>
      </div>
      <div
        class="overflow-hidden rounded-3xl border border-[#eadfce] bg-white/90 shadow-[0_10px_40px_rgba(155,115,77,0.08)]">
        <div class="flex items-center justify-between border-b border-[#f0e7da] px-5 py-4"><p
          class="text-sm font-semibold text-stone-600">{{ total }} projects in the pantry</p>
          <div class="flex items-center gap-2 text-sm text-stone-500"><span>Sort:</span><select v-model="sortBy"
                                                                                                class="border-0 bg-transparent font-semibold text-caramel outline-none">
            <option value="created_at">Newest added</option>
            <option value="client_name">Client A-Z</option>
            <option value="project_name">Project A-Z</option>
            <option value="priority">Priority</option>
            <option value="planned_at">Due date</option>
          </select>
            <button class="rounded-lg px-2 py-1 hover:bg-[#fffaf0]"
                    @click="sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'">
              {{ sortDirection === 'asc' ? '↑' : '↓' }}
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-[#fffaf0] text-xs uppercase tracking-wider text-stone-400">
            <tr>
              <th v-for="column in columns" v-show="isVisible(column.key)" :key="column.key"
                  class="cursor-pointer px-5 py-4 font-semibold" @click="toggleSort(column.key)">{{ column.label }}
                <span v-if="sortBy === column.key">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span></th>
              <th class="px-5 py-4"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-[#f0e7da]">
            <tr v-if="loading">
              <td colspan="7" class="px-5 py-14 text-center text-stone-400">Scooping up projects…</td>
            </tr>
            <tr v-else-if="!displayedProjects.length">
              <td colspan="7" class="px-5 py-14 text-center">
                <div class="mb-2 text-4xl">🍨</div>
                <p class="font-semibold">No projects found</p>
                <p class="mt-1 text-sm text-stone-400">Try a different filter or add a new scoop.</p></td>
            </tr>
            <tr v-for="project in displayedProjects" v-else :key="project.id"
                class="group transition hover:bg-[#fffaf0]/70">
              <td v-if="isVisible('client_name')" class="px-5 py-4 font-semibold">{{ project.client_name }}</td>
              <td v-if="isVisible('project_name')" class="px-5 py-4 text-stone-600">{{ project.project_name }}</td>
              <td v-if="isVisible('status')" class="px-5 py-4"><span
                class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                :class="statusClass(project.status)">{{ project.status.replace('_', ' ') }}</span></td>
              <td v-if="isVisible('priority')" class="px-5 py-4 capitalize text-stone-600">{{ project.priority }}</td>
              <td v-if="isVisible('in_progress_at')" class="px-5 py-4 text-stone-500">
                {{ formatDate(project.in_progress_at) }}
              </td>
              <td v-if="isVisible('planned_at')" class="px-5 py-4 text-stone-500">{{
                  formatDate(project.planned_at)
                }}
              </td>
              <td class="px-5 py-4 text-right">
                <button class="mr-3 text-xs font-semibold text-caramel opacity-0 transition group-hover:opacity-100"
                        @click="openEdit(project)">Edit
                </button>
                <button class="text-xs font-semibold text-[#b85e50] opacity-0 transition group-hover:opacity-100"
                        @click="removeProject(project)">Delete
                </button>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        <div class="flex items-center justify-between border-t border-[#f0e7da] px-5 py-4 text-sm text-stone-500"><span>{{
            pageLabel
          }}</span>
          <div class="flex items-center gap-2"><label for="page-number">Page</label><input id="page-number"
                                                                                           :value="page" type="number"
                                                                                           min="1" :max="lastPage"
                                                                                           class="w-16 rounded-lg border border-[#eadfce] bg-[#fffaf0] px-2 py-1.5 text-center outline-none focus:ring-2 focus:ring-caramel/30"
                                                                                           @change="changePage($event.target.value)"/><span>of {{
              lastPage
            }}</span>
            <button class="rounded-lg border border-[#eadfce] px-3 py-1.5 disabled:opacity-40" :disabled="page <= 1"
                    @click="changePage(page - 1)">‹
            </button>
            <button class="rounded-lg border border-[#eadfce] px-3 py-1.5 disabled:opacity-40"
                    :disabled="page >= lastPage" @click="changePage(page + 1)">›
            </button>
          </div>
        </div>
      </div>
    </main>

    <main v-else class="mx-auto max-w-3xl px-5 py-8 lg:px-8">
      <button class="mb-6 text-sm font-semibold text-caramel" @click="activePage = 'projects'">← Back to projects
      </button>
      <div class="mb-8"><p class="mb-2 text-sm font-semibold uppercase tracking-[0.25em] text-caramel">Your account</p>
        <h1 class="font-serif text-4xl font-bold">Profile</h1>
        <p class="mt-2 text-stone-500">Keep your account details fresh.</p></div>
      <div class="grid gap-6 md:grid-cols-2">
        <form class="rounded-3xl border border-[#eadfce] bg-white/90 p-6 shadow-[0_10px_40px_rgba(155,115,77,0.08)]"
              @submit.prevent="updateName"><h2 class="mb-5 font-serif text-2xl font-bold">Personal details</h2><label
          class="field">Name<input v-model="profileForm.name" required/><small
          v-if="profileErrors.name">{{ profileErrors.name[0] }}</small></label>
          <p class="mt-4 text-sm text-stone-500">{{ user.email }}</p>
          <button :disabled="profileSaving"
                  class="mt-6 rounded-full bg-caramel px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            Save name
          </button>
        </form>
        <form class="rounded-3xl border border-[#eadfce] bg-white/90 p-6 shadow-[0_10px_40px_rgba(155,115,77,0.08)]"
              @submit.prevent="changePassword"><h2 class="mb-5 font-serif text-2xl font-bold">Change password</h2>
          <div class="space-y-4"><label class="field">Current password<input v-model="passwordForm.current_password"
                                                                             type="password" required/><small
            v-if="profileErrors.current_password">{{ profileErrors.current_password[0] }}</small></label><label
            class="field">New password<input v-model="passwordForm.password" type="password" required
                                             minlength="8"/><small
            v-if="profileErrors.password">{{ profileErrors.password[0] }}</small></label><label class="field">Confirm
            new password<input v-model="passwordForm.password_confirmation" type="password" required
                               minlength="8"/></label></div>
          <button :disabled="profileSaving"
                  class="mt-6 rounded-full bg-caramel px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            Change password
          </button>
        </form>
      </div>
    </main>

    <div v-if="showModal" class="fixed inset-0 z-20 grid place-items-center bg-cocoa/30 p-4 backdrop-blur-sm"
         @click.self="showModal = false">
      <form class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl"
            @submit.prevent="saveProject">
        <div class="mb-6 flex items-start justify-between">
          <div><p class="text-sm font-semibold uppercase tracking-widest text-caramel">
            {{ editingProject ? 'Refine the scoop' : 'A fresh scoop' }}</p>
            <h2 class="mt-1 font-serif text-3xl font-bold">{{ editingProject ? 'Edit project' : 'New project' }}</h2>
          </div>
          <button type="button" class="rounded-full bg-[#fffaf0] px-3 py-1 text-xl text-stone-500"
                  @click="showModal = false">×
          </button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2"><label class="field">Client name<input v-model="form.client_name"
                                                                                      required
                                                                                      placeholder="e.g. Acme Co."/><small
          v-if="errors.client_name">{{ errors.client_name[0] }}</small></label><label class="field">Project name<input
          v-model="form.project_name" required placeholder="e.g. Summer website"/><small
          v-if="errors.project_name">{{ errors.project_name[0] }}</small></label><label class="field">Status<select
          v-model="form.status">
          <option value="planned">Planned</option>
          <option value="in_progress">In progress</option>
          <option value="on_hold">On hold</option>
          <option value="completed">Completed</option>
        </select></label><label class="field">Priority<select v-model="form.priority" required>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select><small v-if="errors.priority">{{ errors.priority[0] }}</small></label><label class="field">Start
          date<input v-model="form.in_progress_at" type="date" :required="form.status !== 'planned'"/><small
            v-if="errors.in_progress_at">{{ errors.in_progress_at[0] }}</small></label><label class="field">Due
          date<input v-model="form.planned_at" type="date" required/><small
            v-if="errors.planned_at">{{ errors.planned_at[0] }}</small></label><label class="field sm:col-span-2">Description<textarea
          v-model="form.description" rows="3" placeholder="What are we making?"></textarea></label></div>
        <div class="mt-7 flex justify-end gap-3">
          <button type="button" class="rounded-full px-5 py-2.5 text-sm font-semibold text-stone-500 hover:bg-[#fffaf0]"
                  @click="showModal = false">Cancel
          </button>
          <button :disabled="saving"
                  class="rounded-full bg-caramel px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            {{ saving ? 'Saving…' : editingProject ? 'Save changes' : 'Create project' }}
          </button>
        </div>
      </form>
    </div>
    <div v-if="showDeleteModal" class="fixed inset-0 z-30 grid place-items-center bg-cocoa/30 p-4 backdrop-blur-sm"
         @click.self="showDeleteModal = false">
      <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between">
          <div><p class="text-sm font-semibold uppercase tracking-widest text-[#b85e50]">A bittersweet goodbye</p>
            <h2 class="mt-1 font-serif text-3xl font-bold">Delete project?</h2>
          </div>
          <button type="button" class="rounded-full bg-[#fffaf0] px-3 py-1 text-xl text-stone-500"
                  @click="showDeleteModal = false">×</button>
        </div>
        <p class="text-sm leading-6 text-stone-600">You are about to remove
          <strong>{{ deletingProject?.project_name }}</strong>. This action cannot be undone.</p>
        <div class="mt-7 flex justify-end gap-3">
          <button type="button" class="rounded-full px-5 py-2.5 text-sm font-semibold text-stone-500 hover:bg-[#fffaf0]"
                  @click="showDeleteModal = false">Keep project</button>
          <button type="button" class="rounded-full bg-[#b85e50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#9f4d42]"
                  @click="confirmDelete">Delete project</button>
        </div>
      </div>
    </div>
    <Transition name="toast">
      <div v-if="toast"
           class="fixed bottom-6 right-6 z-30 flex max-w-sm items-center gap-3 rounded-2xl border border-[#eadfce] bg-white px-5 py-4 text-sm font-semibold shadow-xl"
           :class="toast.type === 'error' ? 'text-[#b85e50]' : 'text-[#397258]'"><span
        class="text-lg">{{ toast.type === 'error' ? '!' : '✓' }}</span>{{ toast.message }}
      </div>
    </Transition>
  </div>
</template>
