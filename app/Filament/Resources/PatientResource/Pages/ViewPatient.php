<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Schemas\Components\Section::make('Información Personal')
                    ->schema([
                        Schemas\Components\TextEntry::make('name')
                            ->label('Nombre'),
                        Schemas\Components\TextEntry::make('email')
                            ->label('Correo'),
                        Schemas\Components\TextEntry::make('phone')
                            ->label('Teléfono'),
                        Schemas\Components\TextEntry::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->dateTime('d/m/Y'),
                        Schemas\Components\TextEntry::make('gender')
                            ->label('Género'),
                        Schemas\Components\TextEntry::make('address')
                            ->label('Dirección'),
                    ])
                    ->columns(2),
                Schemas\Components\Section::make('Expediente Médico')
                    ->schema([
                        Schemas\Components\RepeatableEntry::make('records')
                            ->label('Registros Médicos')
                            ->schema([
                                Schemas\Components\TextEntry::make('weight')
                                    ->label('Peso (kg)'),
                                Schemas\Components\TextEntry::make('height')
                                    ->label('Altura (cm)'),
                                Schemas\Components\TextEntry::make('last_checkup_date')
                                    ->label('Última Revisión')
                                    ->dateTime('d/m/Y H:i'),
                                Schemas\Components\TextEntry::make('last_checkup_notes')
                                    ->label('Notas'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}
