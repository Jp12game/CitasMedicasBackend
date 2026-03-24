<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Schemas\Components\Section::make('Información del Usuario')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Correo'),
                        Infolists\Components\TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->separator(','),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
