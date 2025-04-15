<?php

namespace Joaopaulolndev\FilamentEditProfile\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Pages\Page;

class EditProfilePage extends Page
{
    protected static string $view = 'filament-edit-profile::filament.pages.edit-profile-page';

    protected static ?string $slug = 'edit-profile';

    public ?string $activeTab = 'profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->buildTabs(),
            ])
            ->statePath('data');
    }

    /**
     * Build the tabs component with all the appropriate forms
     */
    protected function buildTabs(): Tabs
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');
        $tabsConfig = [];

        $components = $plugin->getRegisteredCustomProfileComponents();

        dd($components);

        foreach($components as $component){
            Log::info($component);
        }

        // Profile tab
        if ($plugin->shouldShowEditProfileForm) {
            $tabsConfig[] = Tabs\Tab::make(__('filament-edit-profile::default.profile_information'))
                ->icon('heroicon-o-user')
                ->schema([
                    View::make('edit_profile_form')
                        ->view('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => 'edit_profile_form']),
                ]);
        }

        // Password tab
        if ($plugin->shouldShowEditPasswordForm) {
            $tabsConfig[] = Tabs\Tab::make(__('filament-edit-profile::default.password'))
                ->icon('heroicon-o-key')
                ->schema([
                    View::make('edit_password_form')
                        ->view('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => 'edit_password_form']),
                ]);
        }

        // Delete account tab
        if ($plugin->getShouldShowDeleteAccountForm()) {
            $tabsConfig[] = Tabs\Tab::make(__('filament-edit-profile::default.delete_account'))
                ->icon('heroicon-o-trash')
                ->schema([
                    View::make('delete_account_form')
                        ->view('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => 'delete_account_form']),
                ]);
        }

        // API tokens tab
        if ($plugin->getShouldShowSanctumTokens()) {
            $tabsConfig[] = Tabs\Tab::make(__('filament-edit-profile::default.api_tokens_title'))
                ->icon('heroicon-o-key')
                ->schema([
                    View::make('sanctum_tokens')
                        ->view('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => 'sanctum_tokens']),
                ]);
        }

        // Browser sessions tab
        if ($plugin->getShouldShowBrowserSessionsForm()) {
            $tabsConfig[] = Tabs\Tab::make(__('filament-edit-profile::default.browser_section_title'))
                ->icon('heroicon-o-computer-desktop')
                ->schema([
                    View::make('browser_sessions_form')
                        ->view('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => 'browser_sessions_form']),
                ]);
        }

        // Custom fields tab
        if (config('filament-edit-profile.show_custom_fields') && ! empty(config('filament-edit-profile.custom_fields'))) {
            $tabsConfig[] = Tabs\Tab::make(__('filament-edit-profile::default.custom_fields'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    View::make('custom_fields_form')
                        ->view('filament-edit-profile::livewire-component-view')
                        ->viewData(['component' => 'custom_fields_form']),
                ]);
        }

        return Tabs::make('profile_tabs')
            ->tabs($tabsConfig);
    }

    public static function getSlug(): string
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        $slug = $plugin->getSlug();

        $slug = $slug ? $slug : self::$slug;

        return $slug;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getShouldRegisterNavigation();
    }

    public static function getNavigationSort(): ?int
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getSort();
    }

    public static function getNavigationIcon(): ?string
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getIcon();
    }

    public static function getNavigationGroup(): ?string
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getNavigationGroup();
    }

    public function getTitle(): string
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getTitle() ?? __('filament-edit-profile::default.title');
    }

    public static function getNavigationLabel(): string
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getNavigationLabel() ?? __('filament-edit-profile::default.title');
    }

    public static function canAccess(): bool
    {
        $plugin = Filament::getCurrentPanel()?->getPlugin('filament-edit-profile');

        return $plugin->getCanAccess();
    }

    public function getRegisteredCustomProfileComponents(): array
    {
        return filament('filament-edit-profile')->getRegisteredCustomProfileComponents();
    }
}
