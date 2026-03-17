<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use App\Services\Currency\CurrencySyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Валюты';

    protected static ?string $modelLabel = 'валюта';

    protected static ?string $pluralModelLabel = 'Валюты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Код')
                    ->required()
                    ->length(3)
                    ->unique(ignoreRecord: true)
                    ->alpha()
                    ->maxLength(3),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Доступна')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sync')
                    ->label('Синхронизировать курс')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Currency $record, CurrencySyncService $sync): void {
                        try {
                            $sync->syncForCodes([$record->code]);
                            Notification::make()
                                ->title('Курс обновлён')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Ошибка синхронизации')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('syncRates')
                        ->label('Синхронизировать курсы')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (\Illuminate\Support\Collection $records, CurrencySyncService $sync): void {
                            try {
                                $sync->syncForCodes($records->pluck('code')->all());
                                Notification::make()
                                    ->title('Курсы обновлены')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Ошибка синхронизации')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('syncAll')
                    ->label('Синхронизировать все курсы')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (CurrencySyncService $sync): void {
                        try {
                            $sync->syncAll();
                            Notification::make()
                                ->title('Все курсы обновлены')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Ошибка синхронизации')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
