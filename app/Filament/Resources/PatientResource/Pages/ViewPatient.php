<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Infolists;
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
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Correo'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Teléfono'),
                        Infolists\Components\TextEntry::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->dateTime('d/m/Y'),
                        Infolists\Components\TextEntry::make('gender')
                            ->label('Género'),
                        Infolists\Components\TextEntry::make('address')
                            ->label('Dirección'),
                    ])
                    ->columns(2),
                Schemas\Components\Section::make('Expediente Médico')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('records')
                            ->label('Registros Médicos')
                            ->schema([
                                Infolists\Components\TextEntry::make('weight')
                                    ->label('Peso (kg)'),
                                Infolists\Components\TextEntry::make('height')
                                    ->label('Altura (cm)'),
                                Infolists\Components\TextEntry::make('last_checkup_date')
                                    ->label('Última Revisión')
                                    ->dateTime('d/m/Y H:i'),
                                Infolists\Components\TextEntry::make('last_checkup_notes')
                                    ->label('Notas'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}
