<?php

namespace NoopStudios\FilamentEditProfile\Livewire;

use Filament\Auth\Notifications\NoticeOfEmailChangeRequest;
use Filament\Auth\Notifications\VerifyEmailChange;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use League\Uri\Components\Query;
use NoopStudios\FilamentEditProfile\Concerns\HasUser;
use Spatie\MediaLibrary\HasMedia;

class EditProfileForm extends BaseProfileForm
{
    use HasUser;

    protected string $view = 'filament-edit-profile::livewire.edit-profile-form';

    public ?array $data = [];

    public $userClass;

    protected static int $sort = 10;

    public function mount(): void
    {
        $plugin = filament('filament-edit-profile');

        $this->user = $this->getUser();
        $this->userClass = get_class($this->user);

        $fields = [config('filament-edit-profile.name_column', 'name'), 'email'];

        if ($plugin->getShouldShowLocaleForm()) {
            $fields[] = config('filament-edit-profile.locale_column', 'locale');
        }

        if ($plugin->getShouldShowThemeColorForm()) {
            $fields[] = config('filament-edit-profile.theme_color_column', 'theme_color');
        }

        $this->form->fill($this->user->only($fields));
    }

    public function form(Schema $schema): Schema
    {
        $plugin = filament('filament-edit-profile');
        $components = [];

        if ($this->canShowAvatarUpload()) {
            $components[] = SpatieMediaLibraryFileUpload::make('avatar')
                ->model($this->user)
                ->label(__('filament-edit-profile::default.avatar'))
                ->collection($plugin->getAvatarCollection())
                ->image()
                ->maxFiles(1)
                ->avatar()
                ->imageEditor()
                ->disk($plugin->getAvatarDisk())
                ->visibility($plugin->getAvatarVisibility())
                ->rules($plugin->getAvatarRules());
        }

        $components[] = TextInput::make(config('filament-edit-profile.name_column', 'name'))
            ->label(__('filament-edit-profile::default.name'))
            ->required();

        $components[] = TextInput::make('email')
            ->label(__('filament-edit-profile::default.email'))
            ->email()
            ->required()
            ->hidden(! $plugin->getShouldShowEmailForm())
            ->unique($this->userClass, ignorable: $this->user);

        $components[] = Select::make('locale')
            ->label(__('filament-edit-profile::default.locale'))
            ->options($plugin->getOptionsLocaleForm())
            ->rules($plugin->getLocaleRules())
            ->hidden(! $plugin->getShouldShowLocaleForm());

        $components[] = ColorPicker::make('theme_color')
            ->label(__('filament-edit-profile::default.theme_color'))
            ->rules($plugin->getThemeColorRules())
            ->hidden(! $plugin->getShouldShowThemeColorForm());

        return $schema
            ->components($components)
            ->statePath('data');
    }

    public function updateProfile(): void
    {
        $locale = null;
        $theme_color = null;
        if (filament('filament-edit-profile')->getShouldShowLocaleForm()) {
            $locale = $this->user->getAttributeValue('locale');
        }
        if (filament('filament-edit-profile')->getShouldShowThemeColorForm()) {
            $theme_color = $this->user->getAttributeValue('theme_color');
        }

        try {
            $data = $this->form->getState();
            unset($data['avatar']);

            if (Filament::hasEmailChangeVerification() && array_key_exists('email', $data)) {
                $this->sendEmailChangeVerification($this->user, $data['email']);

                unset($data['email']);

                // Refresh the model to clear any potentially dirty email attribute,
                // ensuring that only the fields in $data are persisted.
                // @see https://github.com/noopstudios/filament-edit-profile/issues/132
                $this->user->refresh();
            }

            $this->user->update($data);

            $this->dispatch('refresh-topbar');
        } catch (Halt $exception) {
            return;
        }

        FilamentNotification::make()
            ->success()
            ->title(__('filament-edit-profile::default.saved_successfully'))
            ->send();

        if (filament('filament-edit-profile')->getShouldShowLocaleForm()) {
            if ($locale !== $this->user->getAttributeValue('locale')) {
                redirect(request()->header('referer'));

                return;
            }
        }
        if (filament('filament-edit-profile')->getShouldShowThemeColorForm()) {
            if ($theme_color !== $this->user->getAttributeValue('theme_color')) {
                redirect(request()->header('referer'));
            }
        }
    }

    protected function canShowAvatarUpload(): bool
    {
        return filament('filament-edit-profile')->getShouldShowAvatarForm() && ($this->user instanceof HasMedia);
    }

    private function sendEmailChangeVerification(Authenticatable & Model $user, string $newEmail): void
    {
        if ($user->getAttributeValue('email') === $newEmail) {
            return;
        }

        $notification = app(VerifyEmailChange::class);
        $notification->url = Filament::getVerifyEmailChangeUrl($user, $newEmail);

        $verificationSignature = Query::new($notification->url)->get('signature');

        cache()->put($verificationSignature, true, ttl: now()->addHour());

        $user->notify(app(NoticeOfEmailChangeRequest::class, [/** @phpstan-ignore-line */
            'blockVerificationUrl' => Filament::getBlockEmailChangeVerificationUrl($user, $newEmail, $verificationSignature),
            'newEmail' => $newEmail,
        ]));

        Notification::route('mail', $newEmail)
            ->notify($notification);

        $this->getEmailChangeVerificationSentNotification($newEmail)?->send();

        $this->data['email'] = $user->getAttributeValue('email');
    }

    private function getEmailChangeVerificationSentNotification(string $newEmail): ?FilamentNotification
    {
        return FilamentNotification::make()
            ->success()
            ->title(__('filament-panels::auth/pages/edit-profile.notifications.email_change_verification_sent.title', ['email' => $newEmail]))
            ->body(__('filament-panels::auth/pages/edit-profile.notifications.email_change_verification_sent.body', ['email' => $newEmail]));
    }
}
