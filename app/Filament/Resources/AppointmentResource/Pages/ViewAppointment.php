<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Schemas\Components\Section::make('Detalles de la Cita')
                    ->schema([
                        Schemas\Components\TextEntry::make('patient.name')
                            ->label('Paciente'),
                        Schemas\Components\TextEntry::make('doctor.name')
                            ->label('Doctor'),
                        Schemas\Components\TextEntry::make('date_time_begin')
                            ->label('Inicio')
                            ->dateTime('d/m/Y H:i'),
                        Schemas\Components\TextEntry::make('date_time_end')
                            ->label('Fin')
                            ->dateTime('d/m/Y H:i'),
                        Schemas\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'scheduled' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
