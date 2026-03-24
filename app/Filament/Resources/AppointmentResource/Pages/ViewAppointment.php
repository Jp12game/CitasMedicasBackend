<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Infolists;
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
                        Infolists\Components\TextEntry::make('patient.name')
                            ->label('Paciente'),
                        Infolists\Components\TextEntry::make('doctor.name')
                            ->label('Doctor'),
                        Infolists\Components\TextEntry::make('date_time_begin')
                            ->label('Inicio')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('date_time_end')
                            ->label('Fin')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('status')
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
