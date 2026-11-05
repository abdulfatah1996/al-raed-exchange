<?php

namespace App\Filament\Tables;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;

/**
 * Compact table rows: a few key fields per row, and a click on the row
 * expands a panel with the full record underneath it.
 *
 * Uses Filament's content layout (Split + collapsible Panel). The row's
 * Alpine scope already holds `isCollapsed`; the summary toggles it on click
 * (except when the click lands on a link, button or form control), and the
 * built-in chevron button keeps it keyboard accessible.
 */
final class ExpandableRow
{
    /**
     * @param  array<Column|Stack>  $summary  4–5 key columns; give each a label with ExpandableRow::labelled()
     * @param  Closure(mixed $record): array<string, mixed>  $details  label => value pairs, see record-details view
     * @return array<int, Split|Panel>
     */
    public static function columns(array $summary, Closure $details): array
    {
        return [
            Split::make($summary)
                ->from('md')
                ->extraAttributes([
                    'class' => 'alraed-row-summary',
                    'x-on:click' => "if (! \$event.target.closest('a, button, input, select, textarea, label')) isCollapsed = ! isCollapsed",
                ]),
            Panel::make([
                ViewColumn::make('record_details')
                    ->view('filament.tables.record-details')
                    ->getStateUsing(fn ($record) => $details($record)),
            ])->collapsible()->collapsed()->extraAttributes(['class' => 'alraed-row-details']),
        ];
    }

    /** Yes/no as a coloured badge with an icon, so the state isn't shown by colour alone. */
    public static function yesNo(string $name): TextColumn
    {
        return TextColumn::make($name)->badge()
            ->formatStateUsing(fn ($state) => $state ? __('admin.common.yes') : __('admin.common.no'))
            ->color(fn ($state) => $state ? 'success' : 'danger')
            ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle');
    }

    /** Small caption above the value, since the content layout has no header row. */
    public static function labelled(TextColumn $column, ?string $label = null): TextColumn
    {
        return $column->description($label ?? $column->getLabel(), position: 'above');
    }

    /** A value shown in a monospace, left-to-right box (references, phones, IBANs). */
    public static function code(?string $value): array
    {
        return ['value' => $value, 'code' => true];
    }

    /** A link that opens in a new tab (e.g. a private receipt file served through files.show). */
    public static function link(?string $label, ?string $url): array
    {
        return ['value' => $url ? $label : null, 'url' => $url];
    }

    /** A value that takes the full width of the details panel (notes, long text). */
    public static function wide(mixed $value): array
    {
        return ['value' => $value, 'wide' => true];
    }

    public static function money(mixed $amount, ?string $code): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $number = rtrim(rtrim(number_format((float) $amount, 8, '.', ','), '0'), '.');

        // Isolate as left-to-right so "1,800 USDT" doesn't flip to "USDT 1,800" inside Arabic text.
        return self::ltr(trim($number . ' ' . ($code ?? '')));
    }

    /** Wraps text in a left-to-right isolate (dates, amounts, codes inside Arabic UI). */
    public static function ltr(?string $text): ?string
    {
        return $text === null || $text === '' ? $text : "\u{2066}{$text}\u{2069}";
    }

    /** Date column with the value kept left-to-right ("2026-11-05 14:06", not "14:06 2026-11-05"). */
    public static function dateTime(string $name, string $format = 'Y-m-d H:i'): TextColumn
    {
        return TextColumn::make($name)
            ->formatStateUsing(fn ($state) => $state ? self::ltr(\Illuminate\Support\Carbon::parse($state)->format($format)) : null)
            ->placeholder('—');
    }
}
