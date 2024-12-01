<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Filament\Resources\ReservationResource\RelationManagers;
use App\Filament\Resources\ReservationResource\RelationManagers\StaffTasksRelationManager;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canCreate(): bool
    {
        // Tampilkan tombol hanya untuk admin
        return auth()->user()->role === 'admin';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Room and Price')
                    ->completedIcon('heroicon-m-hand-thumb-up')
                    ->description('Choose User and Room')
                    ->schema([
                        Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->label('Select User'),
                            
                            Forms\Components\Select::make('room_id')
                            ->relationship('room', 'room_name', function ($query, callable $get) {
                                // Ambil room_id yang sedang dipilih
                                $currentRoomId = $get('room_id');
                        
                                // Filter kamar: tampilkan hanya yang '!= booked' atau kamar yang sudah dipilih
                                $query->where(function ($query) use ($currentRoomId) {
                                    $query->where('status', '!=', 'booked');
                                    
                                    // Jika ada room_id saat ini, tambahkan ke hasil query
                                    if ($currentRoomId) {
                                        $query->orWhere('id', $currentRoomId);
                                    }
                                });
                            })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->label('Select Room')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $room = Room::find($state);
                                    $set('total_price', $room ? $room->price_per_night : 0);
                                    $set('payment.amount', $room ? $room->price_per_night : 0);
                                })
                                ->rules(function (callable $get) {
                                    // Ambil room_id yang sedang dipilih
                                    $currentRoomId = $get('room_id');
                            
                                    return [
                                        // Validasi: pastikan kamar yang dipilih tidak 'booked', kecuali kamar saat ini
                                        function ($attribute, $value, $fail) use ($currentRoomId) {
                                            $room = Room::find($value);
                                            if ($room && $room->status === 'booked' && $room->id !== $currentRoomId) {
                                                $fail('The selected room is already booked.');
                                            }
                                        },
                                    ];
                                }),
                            ]),
                    ]),

                    Forms\Components\Wizard\Step::make('Check in Out Date')
                    ->completedIcon('heroicon-m-hand-thumb-up')
                    ->description('Lets Schedule')
                        ->schema([
                            Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('check_in_date')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $checkOutDate = $get('check_out_date');
                                        if ($checkOutDate && Carbon::parse($state)->greaterThan(Carbon::parse($checkOutDate))) {
                                            $set('check_in_date', null); // Reset nilai jika tidak valid
                                            Notification::make()
                                                ->title('Check-in date tidak boleh lebih besar dari Check-out date.')
                                                ->danger()
                                                ->send();
                                        }
                                    }),

                                Forms\Components\DatePicker::make('check_out_date')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $checkInDate = $get('check_in_date');
                                        if ($checkInDate && Carbon::parse($state)->lessThan(Carbon::parse($checkInDate))) {
                                            $set('check_out_date', null); // Reset nilai jika tidak valid
                                            Notification::make()
                                                ->title('Check-out date tidak boleh lebih kecil dari Check-in date.')
                                                ->danger()
                                                ->send();
                                        }
                                    }),
                            ])
                        ]),
                    
                        Forms\Components\Wizard\Step::make('Payment Information')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->description('Review your payment')
                            ->schema([
                                Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('reservation_code')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\Select::make('reservation_status')
                                        ->required()
                                        ->options([
                                            'pending' => 'Pending',
                                            'confirmed' => 'Confirmed',
                                            'checked-in' => 'Checked-in',
                                            'completed' => 'Completed',
                                            'canceled' => 'Canceled',
                                        ]),
                                ]),

                                Grid::make(3)
                                ->relationship('payment')
                                ->schema([
                                    Forms\Components\Select::make('payment_method')
                                        ->required()
                                        ->options([
                                            'manual' => 'Manual',
                                            'midtrans' => 'Midtrans - Coming soon',
                                        ]),

                                    ToggleButtons::make('payment_status')
                                        ->label('Apakah sudah membayar?')
                                        ->boolean()
                                        ->grouped()
                                        ->icons([
                                            true => 'heroicon-o-pencil',
                                            false => 'heroicon-o-clock'
                                        ])
                                        ->required(),
    
                                    Forms\Components\FileUpload::make('proof')
                                        ->label('Bukti Pembayaran')
                                        ->image()
                                        ->required(),

                                    Forms\Components\TextInput::make('amount')
                                        ->numeric()
                                        ->readOnly()
                                        ->label('Total Prices')
                                ]),
                            ]),
                ])
                ->columnSpan('full')
                ->columns(1)
                ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('reservation_code')
                ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                ->searchable(),

                Tables\Columns\TextColumn::make('room.room_name')
                ->label('Room Name')
                ->searchable(),

                Tables\Columns\ImageColumn::make('room.image_url')
                ->label('Room Image'),

                Tables\Columns\ImageColumn::make('payment.proof')
                ->label('Proof'),

                Tables\Columns\TextColumn::make('check_in_date'),

                Tables\Columns\TextColumn::make('check_out_date'),

                Tables\Columns\TextColumn::make('total_price'),

                Tables\Columns\TextColumn::make('reservation_status')
                ->searchable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\MultiSelectFilter::make('reservation_status')
                ->options(fn () => auth()->user()->role === "staff" 
                    ? [
                        'confirmed' => 'Confirmed',
                        'checked-in' => 'Checked-in',
                        'completed' => 'Completed',
                    ]
                    : [
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'checked-in' => 'Checked-in',
                        'completed' => 'Completed',
                        'canceled' => 'Canceled',
                ])
                ->default(fn () => auth()->user()->role === "staff" ? ['confirmed', 'checked-in', 'completed']  : null),

                Tables\Filters\SelectFilter::make('user')
                ->label('Assigned User')
                ->query(function (Builder $query) {
                    $userId = auth()->user()->id;
        
                    return $query->whereHas('staffTasks', function (Builder $subQuery) use ($userId) {
                        $subQuery->where('user_id', $userId);
                    });
                })
                ->placeholder(auth()->user()->name)
                ->visible(fn () => auth()->user()->role === "staff"), 
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                ->visible(fn () => auth()->user()->role === "admin"),

                Tables\Actions\Action::make('confirm')
                ->label('Confirm')
                ->action(action: function (Reservation $record) {
                    $record->reservation_status = "confirmed";
                    $record->save();

                    Notification::make()
                        ->title('Reservation Confirmed')
                        ->success()
                        ->body('The reservation has been successfully confirmed.')
                        ->send();
                })
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (Reservation $record) => $record->reservation_status === 'pending' && auth()->user()->role === 'admin'),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->action(function (Reservation $record) {
                        // Update status reservasi menjadi canceled
                        $record->reservation_status = "canceled";
                        $record->save();

                        $record->delete();

                        // Update status room menjadi available
                        if ($record->room) {
                            $record->room->update(['status' => 'available']);
                        }

                        // Kirim notifikasi
                        Notification::make()
                            ->title('Reservation Canceled')
                            ->success()
                            ->body('The reservation has been successfully canceled and the room is now available.')
                            ->send();
                    })
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Reservation $record) => $record->reservation_status === 'pending' && auth()->user()->role === 'admin'),

                    Tables\Actions\Action::make('checked-in')
                    ->label('Checked-in')
                    ->action(action: function (Reservation $record) {
                        $record->reservation_status = "checked-in";
                        $record->save();
    
                        Notification::make()
                            ->title('Reservation Checked-in')
                            ->success()
                            ->body('The reservation has been successfully checked-in.')
                            ->send();
                    })
                    ->color('warning')
                    ->requiresConfirmation() 
                    ->visible(function (Reservation $record) {
                        // Check if user is an admin or staff
                        $user = auth()->user();
                
                        if ($user && $user->role === 'admin') {
                            // Admin can see the button if reservation_status is 'confirmed' 
                            // and check_in_date is today
                            return $record->reservation_status === 'confirmed'
                                && $record->check_in_date === now()->format('Y-m-d');
                        }
                
                        if ($user && $user->role === 'staff') {
                            // Staff can see the button if reservation_status is 'confirmed' 
                            // and check_in_date is today
                            return $record->reservation_status === 'confirmed'
                                && $record->check_in_date === now()->format('Y-m-d')
                                && $record->staffTasks()->where('task_type', 'Checked-in')
                                ->where('user_id', $user->id) // Check for the current logged-in user
                                ->exists();
                        }
                
                        return false; // Default case, button will be hidden
                    }),

                    Tables\Actions\Action::make('completed')
                    ->label('Completed')
                    ->action(action: function (Reservation $record) {
                        $record->reservation_status = "completed";
                        $record->save();

                        if ($record->room) {
                            $record->room->update(['status' => 'available']);
                        }
    
                        Notification::make()
                            ->title('Reservation Completed')
                            ->success()
                            ->body('The reservation has been successfully Completed.')
                            ->send();
                    })
                    ->color('success')
                    ->requiresConfirmation() 
                    ->visible(function (Reservation $record) {
                        // Check if user is an admin or staff
                        $user = auth()->user();
                
                        if ($user && $user->role === 'admin') {
                            // Admin can see the button if reservation_status is 'checked-in'
                            return $record->reservation_status === 'checked-in';
                        }
                
                        if ($user && $user->role === 'staff') {
                            // Staff can see the button only if the reservation is 'checked-in' 
                            // and there is a 'Checked-out' task assigned
                            return $record->reservation_status === 'checked-in'
                                && $record->check_out_date === now()->format('Y-m-d')
                                && $record->staffTasks()->where('task_type', 'Checked-out')
                                ->where('user_id', $user->id) // Check for the current logged-in user
                                ->exists();
                        }
                
                        return false; // Default case, button will be hidden
                    }),
                    

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
            StaffTasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
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
