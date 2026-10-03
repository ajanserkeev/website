<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Team, partners and travelers. Partners need their operator; travelers sign in with Google and have no password.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $role = fn (Get $get) => $get('role') instanceof UserRole ? $get('role') : UserRole::tryFrom((string) $get('role'));

        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('name')->label('Имя')->required(),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')->label('Роль')->options(UserRole::class)->default(UserRole::Manager)->required()->live(),
                Select::make('operator_id')->label('Турфирма партнёра')->relationship('operator', 'name')->searchable()->preload()
                    ->visible(fn (Get $get) => $role($get) === UserRole::Partner)
                    ->required(fn (Get $get) => $role($get) === UserRole::Partner)
                    ->helperText('Партнёр видит в /partner только туры и брони этой фирмы.'),
                TextInput::make('telegram_chat_id')->label('Telegram chat id')
                    ->visible(fn (Get $get) => $role($get)?->isStaff())
                    ->helperText('Сюда приходят уведомления о заявках и оплатах (шаг 4.8).'),
                TextInput::make('password')->label('Пароль')->password()->revealable()
                    ->visible(fn (Get $get) => $role($get) !== UserRole::Tourist)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->minLength(10)
                    ->helperText('Для входа в /admin или /partner. При редактировании: оставьте пустым, чтобы не менять.'),
            ]),
        ]);
    }
}
