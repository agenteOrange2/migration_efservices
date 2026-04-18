<script setup lang="ts">
import { computed } from 'vue';
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import Button from '@/components/Base/Button';
import { FormCheck, FormInput, FormLabel } from '@/components/Base/Form';
import Lucide from '@/components/Base/Lucide';

defineProps<{
  canResetPassword?: boolean;
  status?: string;
}>();

type BrandingProps = {
  appName?: string;
  loginTitle?: string;
  loginSubtitle?: string | null;
  loginHeading?: string;
  loginDescription?: string | null;
  logoUrl?: string | null;
  faviconUrl?: string | null;
  loginBackgroundUrl?: string | null;
};

const page = usePage();
const branding = computed(() => (page.props.branding as BrandingProps | undefined) ?? {});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => {
      form.reset('password');
    },
  });
};
</script>

<template>
  <Head :title="`${branding.appName ?? 'EF Services'} Login`" />

  <div
    class="grid min-h-screen grid-cols-12 py-10 pl-5 pr-0 sm:py-14 sm:pl-10 sm:pr-0 md:pl-16 md:pr-0 lg:py-0 lg:pl-10 lg:pr-0 xl:pl-12 xl:pr-0 2xl:pl-14 2xl:pr-0"
  >
    <div
      :class="[
        'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:col-span-5 2xl:col-span-4 lg:py-0 lg:pl-16 lg:pr-10 xl:pl-24 xl:pr-20 2xl:pl-28 2xl:pr-24',
        'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
      ]"
    >
      <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-32">
        <div class="rounded-[0.8rem] w-[62px] h-[62px] border border-primary/20 flex items-center justify-center bg-white/80 shadow-sm">
          <img
            v-if="branding.logoUrl"
            :src="branding.logoUrl"
            :alt="branding.appName ?? 'Brand logo'"
            class="h-[44px] w-[44px] object-contain"
          />
          <div
            v-else
            class="relative flex items-center justify-center w-[52px] rounded-[0.6rem] h-[52px] bg-linear-to-b from-theme-1/90 to-theme-2/90"
          >
            <Lucide icon="Truck" class="w-8 h-8 text-white" />
          </div>
        </div>

        <div class="mt-10">
          <div class="text-2xl font-medium text-slate-800">
            {{ branding.loginTitle ?? `${branding.appName ?? 'EF Services'} Login` }}
          </div>
          <div class="mt-2.5 text-slate-600">
            {{ branding.loginSubtitle ?? 'Sign in with your email and password to continue.' }}
          </div>

          <div
            v-if="status"
            class="mt-4 rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
          >
            {{ status }}
          </div>

          <div
            v-if="form.errors.email || form.errors.password"
            class="mt-4 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger"
          >
            <p v-if="form.errors.email">{{ form.errors.email }}</p>
            <p v-if="form.errors.password">{{ form.errors.password }}</p>
          </div>

          <form class="mt-6" @submit.prevent="submit">
            <FormLabel>Email Address</FormLabel>
            <FormInput
              v-model="form.email"
              type="email"
              class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80"
              placeholder="email@company.com"
              required
            />

            <FormLabel class="mt-4">Password</FormLabel>
            <FormInput
              v-model="form.password"
              type="password"
              class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80"
              placeholder="Enter your password"
              required
            />

            <div class="flex items-center justify-between mt-4 text-xs text-slate-500 sm:text-sm">
              <label class="flex items-center cursor-pointer select-none">
                <FormCheck.Input
                  id="remember-me"
                  v-model="form.remember"
                  type="checkbox"
                  class="mr-2 border"
                />
                Remember me
              </label>

              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-primary font-medium"
              >
                Forgot password?
              </Link>
            </div>

            <div class="mt-5 text-center xl:mt-8 xl:text-left">
              <Button
                type="submit"
                variant="primary"
                rounded
                class="bg-linear-to-r from-theme-1/70 to-theme-2/70 w-full py-3.5"
                :disabled="form.processing"
              >
                <Lucide v-if="form.processing" icon="Loader" class="w-5 h-5 animate-spin mr-2" />
                {{ form.processing ? 'Signing in...' : 'Sign In' }}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="fixed inset-0 grid h-screen w-screen grid-cols-12 pl-5 pr-0 sm:pl-10 sm:pr-0 md:pl-16 md:pr-0 lg:pl-10 lg:pr-0 xl:pl-12 xl:pr-0 2xl:pl-14 2xl:pr-0">
    <div
      :class="[
        'relative h-screen col-span-12 lg:col-span-5 2xl:col-span-4 z-20',
        'after:bg-white after:hidden after:lg:block after:content-[\'\'] after:absolute after:right-0 after:inset-y-0 after:bg-linear-to-b after:from-white after:to-slate-100/80 after:w-[800%] after:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]',
        'before:content-[\'\'] before:hidden before:lg:block before:absolute before:right-0 before:inset-y-0 before:my-6 before:bg-linear-to-b before:from-white/10 before:to-slate-50/10 before:bg-white/50 before:w-[800%] before:-mr-4 before:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]',
      ]"
    />

    <div
      :class="[
        'h-full col-span-12 lg:col-span-7 2xl:col-span-8 lg:relative overflow-hidden rounded-r-[1.7rem]',
        'before:content-[\'\'] before:absolute before:lg:-ml-10 before:left-0 before:inset-y-0 before:bg-linear-to-b before:from-theme-1 before:to-theme-2 before:w-screen before:lg:w-[800%]',
        'after:content-[\'\'] after:absolute after:inset-y-0 after:left-0 after:w-screen after:lg:w-[800%] after:bg-texture-white after:bg-fixed after:bg-center after:lg:bg-[25rem_-25rem] after:bg-no-repeat',
      ]"
    >
      <div
        v-if="branding.loginBackgroundUrl"
        class="absolute inset-0 z-[1] bg-cover bg-center opacity-30"
        :style="{ backgroundImage: `url(${branding.loginBackgroundUrl})` }"
      />
      <div class="absolute inset-0 z-[2] bg-gradient-to-b from-theme-1/85 to-theme-2/90" />

      <div class="sticky top-0 z-10 hidden h-screen lg:flex lg:items-center">
        <div class="w-full px-12 lg:px-16 xl:px-24 2xl:px-32">
          <div class="max-w-3xl">
            <div class="leading-[1.15] text-[2.6rem] xl:text-5xl font-medium text-white">
          {{ branding.loginHeading ?? 'Transportation compliance in one place' }}
            </div>
            <div class="mt-5 max-w-2xl text-base leading-relaxed xl:text-lg text-white/80">
              {{ branding.loginDescription ?? 'Manage drivers, vehicles, documents, and trips from one operational workspace.' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
