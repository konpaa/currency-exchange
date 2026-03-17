<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyRateResource\Pages;
use App\Models\CurrencyRate;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CurrencyRateResource extends Resource
{
    protected static ?string $model = CurrencyRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Курсы валют';

    protected static ?string $modelLabel = 'курс';

    protected static ?string $pluralModelLabel = 'Курсы валют';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('base_currency')
                    ->label('База')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Валюта')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Курс')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('currency_code')
            ->filters([
                Tables\Filters\SelectFilter::make('base_currency')
                    ->label('Базовая валюта')
                    ->options(
                        fn () => CurrencyRate::query()
                            ->distinct()
                            ->pluck('base_currency', 'base_currency')
                            ->all()
                    ),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $base = strtoupper((string) config('currency.base', 'USD'));

        $maxGen = CurrencyRate::query()
            ->where('base_currency', $base)
            ->max('generation') ?? 0;

        return parent::getEloquentQuery()
            ->where('generation', $maxGen);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencyRates::route('/'),
        ];
    }
}
