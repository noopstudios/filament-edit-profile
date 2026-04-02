<?php

namespace NoopStudios\FilamentEditProfile\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use NoopStudios\FilamentEditProfile\FilamentEditProfilePlugin;
use NoopStudios\FilamentEditProfile\Livewire\BrowserSessionsForm;
use NoopStudios\FilamentEditProfile\Livewire\CustomFieldsForm;
use NoopStudios\FilamentEditProfile\Livewire\DeleteAccountForm;
use NoopStudios\FilamentEditProfile\Livewire\EditPasswordForm;
use NoopStudios\FilamentEditProfile\Livewire\EditProfileForm;
use NoopStudios\FilamentEditProfile\Livewire\MultiFactorAuthentication;
use NoopStudios\FilamentEditProfile\Livewire\SanctumTokens;

class EditProfilePage extends Page
{
    use InteractsWithForms;

    protected string $view = 'filament-edit-profile::filament.pages.edit-profile-page';

    protected static ?string $slug = 'edit-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->buildTabs(),
            ])
            ->statePath('data');
    }

    protected function buildTabs(): Tabs
    {
        $definitions = [
            EditProfileForm::class => [
                'label' => __('filament-edit-profile::default.profile_information'),
                'icon' => 'heroicon-o-user',
            ],
            EditPasswordForm::class => [
                'label' => __('filament-edit-profile::default.password'),
                'icon' => 'heroicon-o-key',
            ],
            CustomFieldsForm::class => [
                'label' => __('filament-edit-profile::default.custom_fields'),
                'icon' => 'heroicon-o-document-text',
            ],
            BrowserSessionsForm::class => [
                'label' => __('filament-edit-profile::default.browser_section_title'),
                'icon' => 'heroicon-o-computer-desktop',
            ],
            MultiFactorAuthentication::class => [
                'label' => __('filament-edit-profile::default.mfa_section_title'),
                'icon' => 'heroicon-o-shield-check',
            ],
            SanctumTokens::class => [
                'label' => __('filament-edit-profile::default.api_tokens_title'),
                'icon' => 'heroicon-o-key',
            ],
            DeleteAccountForm::class => [
                'label' => __('filament-edit-profile::default.delete_account'),
                'icon' => 'heroicon-o-trash',
            ],
        ];

        $tabs = [];

        foreach ($this->getRegisteredCustomProfileComponents() as $alias => $componentClass) {
            $definition = $definitions[$componentClass] ?? [
                'label' => Str::of($alias)->replace('_', ' ')->headline()->toString(),
                'icon' => null,
            ];

            $tabs[] = Tab::make($definition['label'])
                ->icon($definition['icon'])
                ->schema([
                    View::make('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => $alias]),
                ]);
        }

        return Tabs::make('profile_tabs')
            ->tabs($tabs);
    }

    protected static function getPlugin(?Panel $panel = null): ?FilamentEditProfilePlugin
    {
        if ($panel === null) {
            $panel = Filament::getCurrentOrDefaultPanel();
        }

        return $panel?->getPlugin('filament-edit-profile');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        $slug = static::getPlugin($panel)?->getSlug();

        return $slug ? $slug : self::$slug;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::getPlugin()?->getShouldRegisterNavigation() ?? true;
    }

    public static function getNavigationSort(): ?int
    {
        return static::getPlugin()?->getSort();
    }

    public static function getNavigationIcon(): ?string
    {
        return static::getPlugin()?->getIcon();
    }

    public static function getNavigationGroup(): ?string
    {
        return static::getPlugin()?->getNavigationGroup();
    }

    public function getTitle(): string
    {
        return static::getPlugin()?->getTitle() ?? __('filament-edit-profile::default.title');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPlugin()?->getNavigationLabel() ?? __('filament-edit-profile::default.title');
    }

    public static function canAccess(): bool
    {
        return static::getPlugin()?->getCanAccess() ?? true;
    }

    public function getRegisteredCustomProfileComponents(): array
    {
        return static::getPlugin()?->getRegisteredCustomProfileComponents() ?? [];
    }
}
