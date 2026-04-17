<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\UserNotificationPreference;
use App\Services\Notification\NotificationPreferenceService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const PAGES = [
        'profile-info' => [
            'label' => 'Profile Information',
            'route' => 'admin.settings',
            'icon' => 'User',
        ],
        'email-settings' => [
            'label' => 'Email Settings',
            'route' => 'admin.settings-email-settings',
            'icon' => 'Mail',
        ],
        'security' => [
            'label' => 'Security',
            'route' => 'admin.settings-security',
            'icon' => 'Shield',
        ],
        'preferences' => [
            'label' => 'Preferences',
            'route' => 'admin.settings-preferences',
            'icon' => 'SlidersHorizontal',
        ],
        'two-factor-authentication' => [
            'label' => 'Two-Factor Authentication',
            'route' => 'admin.settings-two-factor-authentication',
            'icon' => 'ShieldCheck',
        ],
        'device-history' => [
            'label' => 'Device History',
            'route' => 'admin.settings-device-history',
            'icon' => 'Monitor',
        ],
        'notification-settings' => [
            'label' => 'Notification Settings',
            'route' => 'admin.settings-notification-settings',
            'icon' => 'Bell',
        ],
        'connected-services' => [
            'label' => 'Connected Services',
            'route' => 'admin.settings-connected-services',
            'icon' => 'PlugZap',
        ],
        'account-deactivation' => [
            'label' => 'Account Deactivation',
            'route' => 'admin.settings-account-deactivation',
            'icon' => 'Trash2',
        ],
    ];

    public function index(Request $request, NotificationPreferenceService $notificationPreferenceService, string $page = 'profile-info'): Response
    {
        $user = $request->user();
        $page = $this->normalizePage((string) $request->route('page', $page));

        if ($user->notificationPreferences()->count() === 0) {
            $notificationPreferenceService->createDefaultPreferences($user);
        }

        return Inertia::render('admin/settings/Index', [
            'title' => self::PAGES[$page]['label'],
            'currentPage' => $page,
            'pages' => collect(self::PAGES)
                ->map(fn (array $config, string $key) => [
                    'key' => $key,
                    'label' => $config['label'],
                    'route' => $config['route'],
                    'icon' => $config['icon'],
                ])
                ->values(),
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'emailVerifiedAt' => optional($user->email_verified_at)?->toIso8601String(),
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => \Laravel\Fortify\Features::optionEnabled(
                \Laravel\Fortify\Features::twoFactorAuthentication(),
                'confirm',
            ),
            'notificationPreferences' => $this->buildNotificationPreferences($user, $notificationPreferenceService),
            'deviceSessions' => $this->buildDeviceSessions($request),
            'connectedServices' => $this->buildConnectedServices($user),
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('admin.settings')->with('success', 'Profile information updated successfully.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ])->validate();

        $user = $request->user();
        $user->forceFill([
            'email' => $validated['email'],
            'email_verified_at' => $user->email !== $validated['email'] ? null : $user->email_verified_at,
        ])->save();

        return to_route('admin.settings-email-settings')->with('success', 'Email settings updated successfully.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        return to_route('admin.settings-security')->with('success', 'Password updated successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->hasMedia('profile_photos')) {
            $user->clearMediaCollection('profile_photos');
        }

        $user->addMediaFromRequest('photo')->toMediaCollection('profile_photos');

        return to_route('admin.settings')->with('success', 'Profile photo updated successfully.');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasMedia('profile_photos')) {
            $user->clearMediaCollection('profile_photos');
        }

        return to_route('admin.settings')->with('success', 'Profile photo deleted successfully.');
    }

    public function updateNotificationSettings(Request $request, NotificationPreferenceService $notificationPreferenceService): RedirectResponse
    {
        $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.in_app_enabled' => ['nullable', 'boolean'],
            'preferences.*.email_enabled' => ['nullable', 'boolean'],
        ]);

        $preferences = $request->input('preferences', []);

        foreach ($preferences as $category => $channels) {
            $isCritical = UserNotificationPreference::isCriticalCategory($category);
            $inAppEnabled = $isCritical ? true : (bool) data_get($channels, 'in_app_enabled', false);
            $emailEnabled = $isCritical ? true : (bool) data_get($channels, 'email_enabled', false);

            $notificationPreferenceService->updatePreference(
                $request->user(),
                $category,
                $inAppEnabled,
                $emailEnabled,
            );
        }

        return to_route('admin.settings-notification-settings')->with('success', 'Notification preferences updated successfully.');
    }

    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($validated['password']);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return to_route('admin.settings-device-history')->with('success', 'Logged out from all other devices successfully.');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your account has been deleted.');
    }

    private function normalizePage(string $page): string
    {
        return array_key_exists($page, self::PAGES) ? $page : 'profile-info';
    }

    private function buildNotificationPreferences($user, NotificationPreferenceService $notificationPreferenceService): array
    {
        $categories = $notificationPreferenceService->getCategoriesForRole($user->getRoleNames()->first() ?? 'admin');
        $stored = $user->notificationPreferences()
            ->get()
            ->keyBy('category');

        return collect($categories)
            ->map(function ($label, $category) use ($stored) {
                $preference = $stored->get($category);

                return [
                    'category' => $category,
                    'label' => $this->humanizeLabel(is_string($label) ? $label : $category),
                    'in_app_enabled' => $preference?->in_app_enabled ?? true,
                    'email_enabled' => $preference?->email_enabled ?? true,
                    'is_critical' => UserNotificationPreference::isCriticalCategory($category),
                ];
            })
            ->values()
            ->all();
    }

    private function buildDeviceSessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($request) {
                $device = $this->parseUserAgent((string) ($session->user_agent ?? ''));

                return [
                    'id' => $session->id,
                    'device_label' => $device['label'],
                    'device_type' => $device['type'],
                    'browser' => $device['browser'],
                    'platform' => $device['platform'],
                    'ip_address' => $session->ip_address ?: 'Unknown',
                    'user_agent' => $session->user_agent ?: 'Unknown device',
                    'last_active' => now()->setTimestamp((int) $session->last_activity)->toIso8601String(),
                    'last_active_human' => now()->setTimestamp((int) $session->last_activity)->diffForHumans(),
                    'is_current' => $session->id === $request->session()->getId(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildConnectedServices($user): array
    {
        $slackConfigured = filled(config('services.slack.notifications.bot_user_oauth_token'))
            || filled(data_get(config('logging.channels.slack', []), 'url'));

        return [
            [
                'name' => 'Email Delivery',
                'description' => 'Account communication and system email delivery.',
                'status' => filled(config('mail.default')) ? 'connected' : 'not_configured',
                'icon' => 'Mail',
            ],
            [
                'name' => 'Slack Alerts',
                'description' => 'Optional operational alerts for logs and system events.',
                'status' => $slackConfigured ? 'connected' : 'not_configured',
                'icon' => 'MessageSquare',
            ],
            [
                'name' => 'Browser Notifications',
                'description' => 'In-app notifications available from the top navigation bell.',
                'status' => 'available',
                'icon' => 'Bell',
            ],
            [
                'name' => 'Two-Factor Authentication',
                'description' => 'Authenticator app protection for your administrator account.',
                'status' => $user->hasEnabledTwoFactorAuthentication() ? 'connected' : 'available',
                'icon' => 'ShieldCheck',
            ],
        ];
    }

    private function parseUserAgent(string $userAgent): array
    {
        $browser = 'Browser';
        $platform = 'Unknown Platform';
        $type = 'desktop';

        if (str_contains($userAgent, 'Edg/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($userAgent, 'Chrome/')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($userAgent, 'Firefox/')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/')) {
            $browser = 'Safari';
        }

        if (str_contains($userAgent, 'iPhone')) {
            $platform = 'iPhone';
            $type = 'mobile';
        } elseif (str_contains($userAgent, 'iPad')) {
            $platform = 'iPad';
            $type = 'tablet';
        } elseif (str_contains($userAgent, 'Android')) {
            $platform = 'Android';
            $type = str_contains($userAgent, 'Mobile') ? 'mobile' : 'tablet';
        } elseif (str_contains($userAgent, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS X')) {
            $platform = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $platform = 'Linux';
        }

        return [
            'label' => "{$browser} on {$platform}",
            'browser' => $browser,
            'platform' => $platform,
            'type' => $type,
        ];
    }

    private function humanizeLabel(string $value): string
    {
        return str($value)
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }
}
