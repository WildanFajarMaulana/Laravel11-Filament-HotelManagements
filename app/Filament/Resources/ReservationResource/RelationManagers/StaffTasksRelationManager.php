<?php

namespace App\Filament\Resources\ReservationResource\RelationManagers;

use App\Models\Reservation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'staffTasks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                ->relationship('user', 'name', function ($query) {
                    // Filter hanya untuk pengguna dengan role 'staff'
                    $query->where('role', 'staff');
                })
                ->searchable()
                ->preload()
                ->required()
                ->label('Select User'),
          

                Forms\Components\Select::make('task_type')
                ->required()
                ->options([
                    'Checked-in' => 'Checked-in',
                    'Checked-out' => 'Checked-out'
                ])
                ->rules(function () {
                    return [
                        function ($attribute, $value, $fail) {
                            $reservationId = request()->input('reservation_id'); // Get the reservation ID from the form data
                            $exists = \App\Models\StaffTask::where('reservation_id', $reservationId)
                                ->where('task_type', $value)
                                ->exists();
                            if ($exists) {
                                Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('The $value task is already assigned for this reservation.')
                                ->send();
                            }
                        },
                    ];
                }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user_id reservation_id task_type assigned_at')
            ->columns([
                Tables\Columns\TextColumn::make('user.name'),

                Tables\Columns\TextColumn::make('task_type'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
