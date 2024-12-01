<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

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
                Forms\Components\TextInput::make('name')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(),
    
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('role')
                ->options([
                    'admin' => 'Admin',
                    'staff' => 'Staff',
                    'customer' => 'Customer',
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('name')
                ->searchable(),

                Tables\Columns\TextColumn::make('email')
                ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                ->searchable(),

                Tables\Columns\TextColumn::make('role')
                ->searchable(),
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
