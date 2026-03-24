<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $recordTitleAttribute = 'last_checkup_date';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('weight')
                    ->label('Peso (kg)')
                    ->numeric(),
                Forms\Components\TextInput::make('height')
                    ->label('Altura (cm)')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('last_checkup_date')
                    ->label('Última Revisión'),
                Forms\Components\Textarea::make('last_checkup_notes')
                    ->label('Notas de la Revisión')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('last_checkup_date')
            ->columns([
                Tables\Columns\TextColumn::make('weight')
                    ->label('Peso (kg)'),
                Tables\Columns\TextColumn::make('height')
                    ->label('Altura (cm)'),
                Tables\Columns\TextColumn::make('last_checkup_date')
                    ->label('Última Revisión')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_checkup_notes')
                    ->label('Notas')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
