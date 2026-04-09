<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers;
use App\Models\Patient;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'medico']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    protected static ?string $model = Patient::class;

    protected static ?string $navigationLabel = 'Pacientes';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Cuenta de acceso')
                    ->options(fn () => self::userOptions())
                    ->default(fn () => self::defaultUserId())
                    ->visible(fn () => Patient::usesUserLinkColumn())
                    ->disabled(fn () => self::locksLinkedUser())
                    ->dehydrated(fn () => Patient::usesUserLinkColumn())
                    ->searchable()
                    ->preload()
                    ->helperText(fn () => ! Patient::usesUserLinkColumn()
                        ? 'Corre la migración de pacientes para persistir el vínculo por usuario.'
                        : (self::locksLinkedUser()
                        ? 'Tu expediente queda vinculado a tu propia cuenta.'
                        : 'Vincula el expediente del paciente con el usuario que iniciará sesión.')),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Fecha de Nacimiento'),
                Forms\Components\Select::make('gender')
                    ->label('Género')
                    ->options([
                        'male' => 'Masculino',
                        'female' => 'Femenino',
                        'other' => 'Otro',
                    ]),
                Forms\Components\Textarea::make('address')
                    ->label('Dirección')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Usuario vinculado')
                    ->placeholder('Sin vincular')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de Nacimiento')
                    ->date(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\RecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }

    protected static function userOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('paciente')) {
            return [$user->id => $user->email];
        }

        return User::query()
            ->orderBy('email')
            ->pluck('email', 'id')
            ->all();
    }

    protected static function defaultUserId(): ?int
    {
        return auth()->user()?->hasRole('paciente')
            ? auth()->id()
            : null;
    }

    protected static function locksLinkedUser(): bool
    {
        return auth()->user()?->hasRole('paciente') ?? false;
    }
}
