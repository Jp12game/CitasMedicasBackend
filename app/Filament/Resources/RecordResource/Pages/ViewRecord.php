<?php

namespace App\Filament\Resources\RecordResource\Pages;

use App\Filament\Resources\RecordResource;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord as ViewRecordPage;

class ViewRecord extends ViewRecordPage
{
    protected static string $resource = RecordResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Schemas\Components\Section::make('Registro Médico')
                    ->schema([
                        Infolists\Components\TextEntry::make('patient.name')
                            ->label('Paciente'),
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
            ]);
    }
}
