<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Filament\Resources\RoomResource\RelationManagers;
use App\Filament\Resources\RoomResource\RelationManagers\ReviewsRelationManager;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        if(auth()->user()->role === "admin"){
            return true;
        }
        
        return false;
    }

    public static function canViewAny(): bool
    {
        if(auth()->user()->role === "admin"){
            return true;
        }
        
        return false;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Fieldset::make('Details')
                ->schema([
                    Forms\Components\TextInput::make('room_name')
                    ->maxLength(255)
                    ->required(),

                    Forms\Components\TextInput::make('room_type')
                    ->maxLength(255)
                    ->required(),

                    Forms\Components\FileUpload::make('image_url')
                    ->required()
                    ->image(),

                    Forms\Components\TextInput::make('price_per_night')
                    ->required()
                    ->numeric()
                    ->prefix('IDR'),

                    Forms\Components\TextInput::make('capacity')
                    ->required()
                    ->numeric(),

                    Forms\Components\TextInput::make('description')
                    ->required(),

                    Forms\Components\Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'booked' => 'Booked',
                        'maintance' => 'Maintance',
                    ])
                    ]),

                Fieldset::make('Facility')
                ->schema([
                    Forms\Components\Repeater::make('roomFacilitys')
                    ->relationship('roomFacilitys')
                    ->schema([
                        Forms\Components\TextInput::make('facility_name')
                        ->required()
                    ]),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\ImageColumn::make('image_url'),
                
                Tables\Columns\TextColumn::make('room_name')
                ->searchable(),

                Tables\Columns\TextColumn::make('room_type')
                ->searchable(),

                Tables\Columns\TextColumn::make('price_per_night'),

                Tables\Columns\TextColumn::make('capacity'),
                
                Tables\Columns\TextColumn::make('description'),

                Tables\Columns\TextColumn::make('status')
                ->color('primary'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
