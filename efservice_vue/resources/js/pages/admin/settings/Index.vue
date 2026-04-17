<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import Button from '@/components/Base/Button/Button.vue';
import Lucide from '@/components/Base/Lucide';
import { FormInput, FormLabel, FormSwitch } from '@/components/Base/Form';
import AlertError from '@/components/AlertError.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import RazeLayout from '@/layouts/RazeLayout.vue';
import { disable, enable } from '@/routes/two-factor';

type SettingsPage = {
  key: string;
  label: string;
  route: string;
  icon: string;
};

type NotificationPreference = {
  category: string;
  label: string;
  in_app_enabled: boolean;
  email_enabled: boolean;
  is_critical: boolean;
};

type DeviceSession = {
  id: string;
  device_label: string;
  device_type: 'desktop' | 'mobile' | 'tablet' | string;
  browser: string;
  platform: string;
  ip_address: string;
  user_agent: string;
  last_active: string;
  last_active_human: string;
  is_current: boolean;
};

type ConnectedService = {
  name: string;
  description: string;
  status: 'connected' | 'available' | 'not_configured' | string;
  icon: string;
};

type Props = {
  title: string;
  currentPage: string;
  pages: SettingsPage[];
  mustVerifyEmail: boolean;
  status?: string;
  emailVerifiedAt?: string | null;
  twoFactorEnabled: boolean;
  requiresConfirmation: boolean;
  notificationPreferences: NotificationPreference[];
  deviceSessions: DeviceSession[];
  connectedServices: ConnectedService[];
};

const props = defineProps<Props>();

const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user ?? {});
const userName = computed(() => authUser.value?.name ?? '');
const userEmail = computed(() => authUser.value?.email ?? '');
const userAvatar = computed(() => authUser.value?.avatar ?? '');

const profileForm = useForm({
  name: userName.value,
  email: userEmail.value,
});

const emailForm = useForm({
  email: userEmail.value,
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const photoForm = useForm<{
  photo: File | null;
}>({
  photo: null,
});

const logoutDevicesForm = useForm({
  password: '',
});

const deleteAccountForm = useForm({
  password: '',
});

const notificationForm = useForm({
  preferences: Object.fromEntries(
    props.notificationPreferences.map((preference) => [
      preference.category,
      {
        in_app_enabled: preference.in_app_enabled,
        email_enabled: preference.email_enabled,
      },
    ]),
  ) as Record<string, { in_app_enabled: boolean; email_enabled: boolean }>,
});

const { appearance, updateAppearance } = useAppearance();
const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref(false);

onUnmounted(() => {
  clearTwoFactorAuthData();
});

const settingsTabs = computed(() => props.pages);
const activeTab = computed(() => props.currentPage);
const activeTabLabel = computed(
  () => settingsTabs.value.find((tab) => tab.key === activeTab.value)?.label ?? 'Settings',
);

const otherDeviceCount = computed(() => props.deviceSessions.filter((session) => !session.is_current).length);

const submitProfile = () => {
  profileForm.patch(route('admin.settings.update-profile'));
};

const submitEmail = () => {
  emailForm.put(route('admin.settings.update-email'));
};

const submitPassword = () => {
  passwordForm.put(route('admin.settings.update-password'), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
    onError: () => passwordForm.reset('password', 'password_confirmation'),
  });
};

const submitNotificationSettings = () => {
  notificationForm.put(route('admin.settings.update-notification-settings'), {
    preserveScroll: true,
  });
};

const submitLogoutOtherDevices = () => {
  logoutDevicesForm.post(route('admin.settings.logout-other-devices'), {
    preserveScroll: true,
    onSuccess: () => logoutDevicesForm.reset(),
  });
};

const submitDeleteAccount = () => {
  deleteAccountForm.delete(route('admin.settings.destroy-account'), {
    onError: () => deleteAccountForm.reset('password'),
  });
};

const updateProfilePhoto = () => {
  photoForm.post(route('admin.settings.update-photo'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      photoForm.reset();
      const input = document.getElementById('admin-settings-photo') as HTMLInputElement | null;
      if (input) input.value = '';
    },
  });
};

const removeProfilePhoto = () => {
  photoForm.delete(route('admin.settings.delete-photo'), {
    preserveScroll: true,
  });
};

const onPhotoSelected = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  photoForm.photo = file;
};

const toggleCriticalPreference = (category: string) => {
  notificationForm.preferences[category].in_app_enabled = true;
  notificationForm.preferences[category].email_enabled = true;
};

const statusBadgeClass = (status: ConnectedService['status']) => {
  if (status === 'connected') return 'bg-success/10 text-success border-success/20';
  if (status === 'available') return 'bg-primary/10 text-primary border-primary/20';
  return 'bg-slate-100 text-slate-600 border-slate-200';
};

const deviceIcon = (type: DeviceSession['device_type']) => {
  if (type === 'mobile') return 'Smartphone';
  if (type === 'tablet') return 'Tablet';
  return 'Monitor';
};

const isCurrentPage = (pageKey: string) => activeTab.value === pageKey;
</script>

<template>
  <Head :title="title" />

  <RazeLayout>
    <div class="grid grid-cols-12 gap-y-6 gap-x-6">
      <div class="col-span-12">
        <div class="box box--stacked p-5">
          <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
              <div class="text-base font-medium">{{ activeTabLabel }}</div>
              <p class="mt-1 text-sm text-slate-500">
                Manage your administrator account settings from one place.
              </p>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
              <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white">
                <img v-if="userAvatar" :src="userAvatar" :alt="userName" class="h-full w-full object-cover" />
                <Lucide v-else icon="User" class="h-5 w-5 text-slate-500" />
              </div>
              <div>
                <div class="text-sm font-medium text-slate-800">{{ userName }}</div>
                <div class="text-xs text-slate-500">{{ userEmail }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-3">
        <div class="box box--stacked p-2">
          <nav class="flex flex-col gap-1">
            <Link
              v-for="tab in settingsTabs"
              :key="tab.key"
              :href="route(tab.route)"
              :class="[
                'flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-colors',
                isCurrentPage(tab.key)
                  ? 'bg-primary/10 text-primary'
                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800',
              ]"
            >
              <Lucide :icon="tab.icon" class="h-4 w-4" />
              <span>{{ tab.label }}</span>
            </Link>
          </nav>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-9">
        <div v-if="isCurrentPage('profile-info')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Profile Information
          </div>

          <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-5 md:flex-row md:items-start">
              <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-slate-200 bg-white">
                <img v-if="userAvatar" :src="userAvatar" :alt="userName" class="h-full w-full object-cover" />
                <Lucide v-else icon="User" class="h-10 w-10 text-slate-400" />
              </div>

              <div class="flex-1">
                <div class="text-base font-medium text-slate-800">Profile Photo</div>
                <p class="mt-1 text-sm text-slate-500">
                  Upload a clear profile image. JPG, PNG or GIF up to 2MB.
                </p>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                  <input
                    id="admin-settings-photo"
                    type="file"
                    accept="image/*"
                    class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:font-medium file:text-primary"
                    @change="onPhotoSelected"
                  />
                  <Button variant="primary" :disabled="!photoForm.photo || photoForm.processing" @click="updateProfilePhoto">
                    Upload
                  </Button>
                  <Button
                    v-if="userAvatar"
                    variant="outline-danger"
                    :disabled="photoForm.processing"
                    @click="removeProfilePhoto"
                  >
                    Delete
                  </Button>
                </div>
                <div v-if="photoForm.errors.photo" class="mt-2 text-sm text-danger">
                  {{ photoForm.errors.photo }}
                </div>
              </div>
            </div>
          </div>

          <form class="space-y-5" @submit.prevent="submitProfile">
            <div>
              <FormLabel for="admin-settings-name">Full Name</FormLabel>
              <FormInput
                id="admin-settings-name"
                v-model="profileForm.name"
                type="text"
                placeholder="Enter your full name"
                class="mt-2"
              />
              <div v-if="profileForm.errors.name" class="mt-2 text-sm text-danger">
                {{ profileForm.errors.name }}
              </div>
            </div>

            <div>
              <FormLabel for="admin-settings-profile-email">Email Address</FormLabel>
              <FormInput
                id="admin-settings-profile-email"
                v-model="profileForm.email"
                type="email"
                readonly
                class="mt-2 bg-slate-100"
              />
              <p class="mt-1 text-xs text-slate-500">
                Email changes live under the Email Settings tab to keep the flow cleaner.
              </p>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
              <Button variant="primary" type="submit" :disabled="profileForm.processing">
                <Lucide icon="Save" class="mr-2 h-4 w-4" />
                Save Changes
              </Button>
              <Button
                variant="outline-secondary"
                type="button"
                @click="profileForm.reset(); profileForm.name = userName; profileForm.email = userEmail"
              >
                Reset
              </Button>
            </div>
          </form>
        </div>

        <div v-else-if="isCurrentPage('email-settings')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Email Settings
          </div>

          <form class="space-y-5" @submit.prevent="submitEmail">
            <div>
              <FormLabel for="admin-settings-email">Primary Email Address</FormLabel>
              <FormInput
                id="admin-settings-email"
                v-model="emailForm.email"
                type="email"
                placeholder="Enter your email address"
                class="mt-2"
              />
              <p class="mt-1 text-xs text-slate-500">
                This address is used for account alerts, verification, and important communication.
              </p>
              <div v-if="emailForm.errors.email" class="mt-2 text-sm text-danger">
                {{ emailForm.errors.email }}
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                  <div class="font-medium text-slate-800">Verification status</div>
                  <p class="mt-1 text-sm text-slate-500">
                    Keep your address verified so system notifications do not get blocked.
                  </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium',
                      emailVerifiedAt
                        ? 'border-success/20 bg-success/10 text-success'
                        : 'border-warning/20 bg-warning/10 text-warning',
                    ]"
                  >
                    {{ emailVerifiedAt ? 'Verified' : 'Verification Pending' }}
                  </span>
                  <Link
                    v-if="mustVerifyEmail && !emailVerifiedAt"
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="text-sm font-medium text-primary underline underline-offset-4"
                  >
                    Resend verification
                  </Link>
                </div>
              </div>
              <div v-if="status === 'verification-link-sent'" class="mt-3 text-sm text-success">
                A new verification link was sent to your inbox.
              </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
              <Button variant="primary" type="submit" :disabled="emailForm.processing">
                <Lucide icon="Save" class="mr-2 h-4 w-4" />
                Save Changes
              </Button>
            </div>
          </form>
        </div>

        <div v-else-if="isCurrentPage('security')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Security Settings
          </div>

          <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr),320px]">
            <form class="space-y-5" @submit.prevent="submitPassword">
              <div>
                <FormLabel for="admin-settings-current-password">Current Password</FormLabel>
                <FormInput
                  id="admin-settings-current-password"
                  v-model="passwordForm.current_password"
                  type="password"
                  placeholder="Enter your current password"
                  class="mt-2"
                />
                <div v-if="passwordForm.errors.current_password" class="mt-2 text-sm text-danger">
                  {{ passwordForm.errors.current_password }}
                </div>
              </div>

              <div>
                <FormLabel for="admin-settings-password">New Password</FormLabel>
                <FormInput
                  id="admin-settings-password"
                  v-model="passwordForm.password"
                  type="password"
                  placeholder="Enter a strong new password"
                  class="mt-2"
                />
                <div v-if="passwordForm.errors.password" class="mt-2 text-sm text-danger">
                  {{ passwordForm.errors.password }}
                </div>
              </div>

              <div>
                <FormLabel for="admin-settings-password-confirmation">Confirm New Password</FormLabel>
                <FormInput
                  id="admin-settings-password-confirmation"
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  placeholder="Confirm your new password"
                  class="mt-2"
                />
              </div>

              <div class="flex flex-wrap gap-3 pt-2">
                <Button variant="primary" type="submit" :disabled="passwordForm.processing">
                  <Lucide icon="Lock" class="mr-2 h-4 w-4" />
                  Update Password
                </Button>
                <Button variant="outline-secondary" type="button" @click="passwordForm.reset()">
                  Cancel
                </Button>
              </div>
            </form>

            <div class="space-y-4">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                    <Lucide icon="CalendarClock" class="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <div class="font-medium text-slate-800">Account created</div>
                    <div class="mt-1 text-sm text-slate-500">
                      {{ authUser?.created_at ? new Date(authUser.created_at).toLocaleDateString() : 'Unavailable' }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-success/10">
                    <Lucide icon="ShieldCheck" class="h-5 w-5 text-success" />
                  </div>
                  <div>
                    <div class="font-medium text-slate-800">Two-factor authentication</div>
                    <div class="mt-1 text-sm text-slate-500">
                      {{ twoFactorEnabled ? 'Enabled and protecting sign-in.' : 'Currently disabled.' }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-warning/30 bg-warning/5 p-4">
                <div class="font-medium text-slate-800">Security tips</div>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                  <li class="flex items-start gap-2">
                    <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-warning" />
                    <span>Use a unique password for this administrator account.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-warning" />
                    <span>Enable 2FA before sharing admin access with anyone else.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-warning" />
                    <span>Review active devices regularly from Device History.</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="isCurrentPage('preferences')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Preferences
          </div>

          <div class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <div class="font-medium text-slate-800">Appearance</div>
              <p class="mt-1 text-sm text-slate-500">
                Choose how the administrator area should look on this device.
              </p>

              <div class="mt-5 grid gap-4 md:grid-cols-3">
                <button
                  type="button"
                  :class="[
                    'rounded-2xl border p-4 text-left transition',
                    appearance === 'light'
                      ? 'border-primary bg-primary/5'
                      : 'border-slate-200 bg-white hover:border-slate-300',
                  ]"
                  @click="updateAppearance('light')"
                >
                  <div class="flex items-center gap-2 font-medium text-slate-800">
                    <Lucide icon="Sun" class="h-4 w-4" />
                    Light
                  </div>
                  <p class="mt-2 text-sm text-slate-500">Always use the light theme.</p>
                </button>

                <button
                  type="button"
                  :class="[
                    'rounded-2xl border p-4 text-left transition',
                    appearance === 'dark'
                      ? 'border-primary bg-primary/5'
                      : 'border-slate-200 bg-white hover:border-slate-300',
                  ]"
                  @click="updateAppearance('dark')"
                >
                  <div class="flex items-center gap-2 font-medium text-slate-800">
                    <Lucide icon="Moon" class="h-4 w-4" />
                    Dark
                  </div>
                  <p class="mt-2 text-sm text-slate-500">Always use the dark theme.</p>
                </button>

                <button
                  type="button"
                  :class="[
                    'rounded-2xl border p-4 text-left transition',
                    appearance === 'system'
                      ? 'border-primary bg-primary/5'
                      : 'border-slate-200 bg-white hover:border-slate-300',
                  ]"
                  @click="updateAppearance('system')"
                >
                  <div class="flex items-center gap-2 font-medium text-slate-800">
                    <Lucide icon="MonitorSmartphone" class="h-4 w-4" />
                    System
                  </div>
                  <p class="mt-2 text-sm text-slate-500">Follow your device preference automatically.</p>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="isCurrentPage('two-factor-authentication')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Two-Factor Authentication
          </div>

          <div class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                  <div class="flex items-center gap-3">
                    <div class="font-medium text-slate-800">Authenticator protection</div>
                    <span
                      :class="[
                        'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium',
                        twoFactorEnabled
                          ? 'border-success/20 bg-success/10 text-success'
                          : 'border-slate-200 bg-slate-100 text-slate-600',
                      ]"
                    >
                      {{ twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                    </span>
                  </div>
                  <p class="mt-2 text-sm text-slate-500">
                    Add an authenticator app challenge to every administrator sign-in.
                  </p>
                </div>

                <div>
                  <Button
                    v-if="hasSetupData"
                    variant="primary"
                    @click="showSetupModal = true"
                  >
                    <Lucide icon="ShieldCheck" class="mr-2 h-4 w-4" />
                    Continue Setup
                  </Button>
                  <Form
                    v-else-if="!twoFactorEnabled"
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                  >
                    <Button type="submit" variant="primary" :disabled="processing">
                      <Lucide icon="ShieldCheck" class="mr-2 h-4 w-4" />
                      Enable 2FA
                    </Button>
                  </Form>
                  <Form v-else v-bind="disable.form()" #default="{ processing }">
                    <Button type="submit" variant="outline-danger" :disabled="processing">
                      <Lucide icon="ShieldOff" class="mr-2 h-4 w-4" />
                      Disable 2FA
                    </Button>
                  </Form>
                </div>
              </div>
            </div>

            <div v-if="twoFactorEnabled">
              <TwoFactorRecoveryCodes />
            </div>

            <div v-else class="rounded-2xl border border-warning/30 bg-warning/5 p-5">
              <div class="flex items-start gap-3">
                <Lucide icon="ShieldAlert" class="mt-0.5 h-5 w-5 text-warning" />
                <div>
                  <div class="font-medium text-slate-800">2FA is not active yet</div>
                  <p class="mt-1 text-sm text-slate-600">
                    We strongly recommend enabling it for any account with administrator access.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
          />
        </div>

        <div v-else-if="isCurrentPage('device-history')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Device History
          </div>

          <div class="space-y-5">
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5">
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                  <div class="font-medium text-slate-800">Active sessions</div>
                  <p class="mt-1 text-sm text-slate-600">
                    Review where your administrator account is currently open.
                  </p>
                </div>
                <div class="text-sm text-slate-600">
                  {{ props.deviceSessions.length }} total session<span v-if="props.deviceSessions.length !== 1">s</span>
                </div>
              </div>
            </div>

            <div v-if="props.deviceSessions.length" class="overflow-hidden rounded-2xl border border-slate-200">
              <div
                v-for="session in props.deviceSessions"
                :key="session.id"
                class="flex flex-col gap-4 border-b border-slate-200 p-5 last:border-b-0 md:flex-row md:items-center md:justify-between"
              >
                <div class="flex items-start gap-4">
                  <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100">
                    <Lucide :icon="deviceIcon(session.device_type)" class="h-5 w-5 text-slate-600" />
                  </div>
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <div class="font-medium text-slate-800">{{ session.device_label }}</div>
                      <span
                        v-if="session.is_current"
                        class="inline-flex items-center rounded-full border border-success/20 bg-success/10 px-3 py-1 text-xs font-medium text-success"
                      >
                        Current Session
                      </span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">
                      {{ session.ip_address }} • {{ session.last_active_human }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">
                      {{ session.user_agent }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
              Session history is unavailable with the current session driver.
            </div>

            <div class="rounded-2xl border border-danger/20 bg-danger/5 p-5">
              <div class="font-medium text-slate-800">Log out other devices</div>
              <p class="mt-1 text-sm text-slate-600">
                End all sessions except this one. This is helpful after changing your password or if you notice unusual access.
              </p>

              <form class="mt-4 space-y-4" @submit.prevent="submitLogoutOtherDevices">
                <div>
                  <FormLabel for="admin-settings-device-password">Current Password</FormLabel>
                  <FormInput
                    id="admin-settings-device-password"
                    v-model="logoutDevicesForm.password"
                    type="password"
                    placeholder="Enter your current password"
                    class="mt-2"
                  />
                  <div v-if="logoutDevicesForm.errors.password" class="mt-2 text-sm text-danger">
                    {{ logoutDevicesForm.errors.password }}
                  </div>
                </div>

                <Button
                  variant="outline-danger"
                  type="submit"
                  :disabled="logoutDevicesForm.processing || otherDeviceCount === 0"
                >
                  <Lucide icon="LogOut" class="mr-2 h-4 w-4" />
                  Log Out Other Devices
                </Button>
              </form>
            </div>
          </div>
        </div>

        <div v-else-if="isCurrentPage('notification-settings')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Notification Settings
          </div>

          <form @submit.prevent="submitNotificationSettings">
            <div class="overflow-hidden rounded-2xl border border-slate-200">
              <table class="w-full">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-5 py-4 text-left text-sm font-medium text-slate-500">Notification Type</th>
                    <th class="px-5 py-4 text-center text-sm font-medium text-slate-500">In-App</th>
                    <th class="px-5 py-4 text-center text-sm font-medium text-slate-500">Email</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="preference in props.notificationPreferences"
                    :key="preference.category"
                    class="border-t border-slate-200 hover:bg-slate-50/70"
                  >
                    <td class="px-5 py-4">
                      <div class="font-medium text-slate-800">{{ preference.label }}</div>
                      <div v-if="preference.is_critical" class="mt-1 text-xs text-warning">
                        Critical alerts stay enabled to protect compliance and security.
                      </div>
                    </td>
                    <td class="px-5 py-4 text-center">
                      <FormSwitch class="justify-center">
                        <FormSwitch.Input
                          type="checkbox"
                          v-model="notificationForm.preferences[preference.category].in_app_enabled"
                          :disabled="preference.is_critical"
                          @change="preference.is_critical && toggleCriticalPreference(preference.category)"
                        />
                      </FormSwitch>
                    </td>
                    <td class="px-5 py-4 text-center">
                      <FormSwitch class="justify-center">
                        <FormSwitch.Input
                          type="checkbox"
                          v-model="notificationForm.preferences[preference.category].email_enabled"
                          :disabled="preference.is_critical"
                          @change="preference.is_critical && toggleCriticalPreference(preference.category)"
                        />
                      </FormSwitch>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
              <Button variant="primary" type="submit" :disabled="notificationForm.processing">
                <Lucide icon="Save" class="mr-2 h-4 w-4" />
                Save Changes
              </Button>
            </div>
          </form>
        </div>

        <div v-else-if="isCurrentPage('connected-services')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Connected Services
          </div>

          <div class="grid gap-5 md:grid-cols-2">
            <div
              v-for="service in props.connectedServices"
              :key="service.name"
              class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white">
                    <Lucide :icon="service.icon" class="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <div class="font-medium text-slate-800">{{ service.name }}</div>
                    <p class="mt-1 text-sm text-slate-500">{{ service.description }}</p>
                  </div>
                </div>
                <span
                  :class="[
                    'inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium',
                    statusBadgeClass(service.status),
                  ]"
                >
                  {{ service.status === 'not_configured' ? 'Not Configured' : service.status.charAt(0).toUpperCase() + service.status.slice(1) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="isCurrentPage('account-deactivation')" class="box box--stacked p-5">
          <div class="mb-6 border-b border-dashed border-slate-300/70 pb-5 text-[0.94rem] font-medium">
            Account Deactivation
          </div>

          <div class="space-y-5">
            <div class="rounded-2xl border border-danger/20 bg-danger/5 p-5">
              <div class="flex items-start gap-3">
                <Lucide icon="AlertTriangle" class="mt-0.5 h-5 w-5 text-danger" />
                <div>
                  <div class="font-medium text-danger">Permanent action</div>
                  <p class="mt-1 text-sm text-slate-600">
                    This administrator account will be permanently deleted. All related access to the platform will stop immediately.
                  </p>
                </div>
              </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="font-medium text-slate-800">Before you continue</div>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                  <li class="flex items-start gap-2">
                    <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-slate-400" />
                    <span>Confirm another administrator still has access.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-slate-400" />
                    <span>Review integrations and notifications owned by this account.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-slate-400" />
                    <span>Download anything you may need before deletion.</span>
                  </li>
                </ul>
              </div>

              <div class="rounded-2xl border border-danger/20 bg-white p-5">
                <div class="font-medium text-slate-800">Delete this account</div>
                <p class="mt-1 text-sm text-slate-500">
                  Enter your password to permanently remove your account.
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submitDeleteAccount">
                  <div>
                    <FormLabel for="admin-settings-delete-password">Current Password</FormLabel>
                    <FormInput
                      id="admin-settings-delete-password"
                      v-model="deleteAccountForm.password"
                      type="password"
                      placeholder="Enter your current password"
                      class="mt-2"
                    />
                    <div v-if="deleteAccountForm.errors.password" class="mt-2 text-sm text-danger">
                      {{ deleteAccountForm.errors.password }}
                    </div>
                  </div>

                  <Button variant="danger" type="submit" :disabled="deleteAccountForm.processing">
                    <Lucide icon="Trash2" class="mr-2 h-4 w-4" />
                    Delete Account Permanently
                  </Button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="box box--stacked p-5">
          <AlertError :errors="['This settings section is not available yet.']" />
        </div>
      </div>
    </div>
  </RazeLayout>
</template>
