<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
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

class AppointmentResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'medico', 'paciente']) ?? false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('paciente') && $user->patient !== null;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('paciente')
            && $record instanceof Appointment
            && $record->patient?->belongsToUser($user);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('medico')) {
            $query->where('doctor_id', auth()->id());
        } elseif (auth()->user()?->hasRole('paciente')) {
            $query->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->ownedByUser(auth()->user()));
        }

        return $query;
    }

    protected static ?string $model = Appointment::class;

    protected static ?string $navigationLabel = 'Citas';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('Paciente')
                    ->options(fn () => self::patientOptions())
                    ->default(fn () => self::defaultPatientId())
                    ->disabled(fn () => self::locksPatientSelection())
                    ->dehydrated()
                    ->searchable()
                    ->preload()
                    ->helperText(fn () => self::locksPatientSelection()
                        ? 'Tu perfil de paciente queda seleccionado automáticamente.'
                        : 'Selecciona el expediente del paciente para la cita.')
                    ->required(),
                Forms\Components\Select::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name', fn ($query) => $query->role('medico'))
                    ->searchable()
                    ->required(),
                Forms\Components\DateTimePicker::make('date_time_begin')
                    ->label('Inicio')
                    ->required(),
                Forms\Components\DateTimePicker::make('date_time_end')
                    ->label('Fin')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Programada',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ])
                    ->default('scheduled')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_time_begin')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_time_end')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Programada',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ]),
                Tables\Filters\SelectFilter::make('doctor')
                    ->label('Doctor')
                    ->relationship('doctor', 'name', fn ($query) => $query->role('medico')),
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
            //
        ];
    }

    protected static function patientOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('paciente')) {
            $patient = $user->patient;

            return $patient ? [$patient->id => $patient->name] : [];
        }

        return Patient::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected static function defaultPatientId(): ?int
    {
        return auth()->user()?->hasRole('paciente')
            ? auth()->user()?->patient?->id
            : null;
    }

    protected static function locksPatientSelection(): bool
    {
        return auth()->user()?->hasRole('paciente') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
