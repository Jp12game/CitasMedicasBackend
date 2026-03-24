<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorScheduleResource\Pages;
use App\Models\DoctorSchedule;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DoctorScheduleResource extends Resource
{
    protected static ?string $model = DoctorSchedule::class;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'doctor']) ?? false;
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

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('day_of_week')
                    ->label('Día de la Semana')
                    ->options([
                        0 => 'Domingo',
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                    ])
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Hora de Inicio')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de Fin')
                    ->seconds(false)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Día')
                    ->formatStateUsing(fn ($state) => [
                        0 => 'Domingo',
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                    ][$state] ?? $state),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Inicio'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Fin'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('doctor')
                    ->label('Doctor')
                    ->relationship('doctor', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDoctorSchedules::route('/'),
            'create' => Pages\CreateDoctorSchedule::route('/create'),
            'edit'   => Pages\EditDoctorSchedule::route('/{record}/edit'),
        ];
    }
}
