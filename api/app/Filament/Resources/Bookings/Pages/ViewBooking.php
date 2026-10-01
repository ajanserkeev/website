<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingWorkflow;
use App\Services\Booking\InvalidBookingAction;
use App\Services\Booking\OperatorMessages;
use App\Services\Booking\RefundPolicy;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Booking page with the actions from the admin sketch (launch document, section 06):
 * take into work, operator confirmed → payment link, operator declined, deposit received, cancellations.
 */
class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        $is = fn (BookingStatus ...$statuses) => fn (Booking $record) => in_array($record->status, $statuses, true);
        $money = fn (int $cents) => '$'.number_format($cents / 100, $cents % 100 ? 2 : 0);

        return [
            Action::make('whatsapp_operator')->label('Написать фирме')->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)->color('gray')
                ->url(fn (Booking $record) => OperatorMessages::whatsappUrl(
                    $record->operator->whatsapp,
                    $record->paid_at ? OperatorMessages::bookingSheet($record) : OperatorMessages::availabilityRequest($record),
                ), shouldOpenInNewTab: true)
                ->visible(fn (Booking $record) => filled($record->operator->whatsapp)),

            Action::make('start_checking')->label('Взять в работу')->icon(Heroicon::OutlinedPhone)
                ->visible($is(BookingStatus::New))
                ->action($this->run(fn (BookingWorkflow $w, Booking $b) => $w->startChecking($b, auth()->user()), 'Статус: уточняем у фирмы')),

            Action::make('confirm')->label('Фирма подтвердила')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                ->visible($is(BookingStatus::New, BookingStatus::Checking))
                ->modalHeading('Отправить туристу ссылку на оплату')
                ->modalDescription(fn (Booking $record) => "Создайте в кабинете эквайера платёж на {$money($record->deposit_cents)} и вставьте ссылку. Турист получит письмо, места будут держаться 48 часов.")
                ->schema([
                    TextInput::make('payment_link_url')->label('Ссылка на оплату предоплаты')->url()->required(),
                ])
                ->action($this->run(fn (BookingWorkflow $w, Booking $b, array $data) => $w->confirm($b, $data['payment_link_url'], auth()->user()), 'Письмо со ссылкой на оплату отправлено')),

            Action::make('mark_paid')->label('Оплата получена')->icon(Heroicon::OutlinedBanknotes)->color('success')
                ->visible($is(BookingStatus::AwaitingPayment))
                ->modalHeading('Предоплата получена?')
                ->modalDescription(fn (Booking $record) => "Проверьте поступление {$money($record->deposit_cents)} в кабинете эквайера. Турист получит ваучер с контактами фирмы.")
                ->schema([
                    TextInput::make('provider_ref')->label('Номер платежа у эквайера')->placeholder('необязательно'),
                ])
                ->action($this->run(fn (BookingWorkflow $w, Booking $b, array $data) => $w->markPaid($b, $data['provider_ref'] ?? null, auth()->user()), 'Ваучер отправлен туристу')),

            ActionGroup::make([
                Action::make('decline')->label('Фирма отказала')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible($is(BookingStatus::New, BookingStatus::Checking))
                    ->schema([Textarea::make('note')->label('Причина / что предложили взамен')])
                    ->action($this->run(fn (BookingWorkflow $w, Booking $b, array $data) => $w->decline($b, $data['note'] ?? null, auth()->user()), 'Туристу отправлено письмо об отказе')),

                Action::make('cancel_tourist')->label('Отменить по просьбе туриста')->icon(Heroicon::OutlinedArrowUturnLeft)->color('danger')
                    ->visible($is(BookingStatus::New, BookingStatus::Checking, BookingStatus::AwaitingPayment, BookingStatus::DepositPaid, BookingStatus::VoucherSent))
                    ->modalDescription(fn (Booking $record) => 'Возврат по правилам: '.$money(app(RefundPolicy::class)->touristCancellationCents($record)).'. Вернуть деньги нужно в кабинете эквайера.')
                    ->schema([Textarea::make('reason')->label('Причина')])
                    ->action($this->run(fn (BookingWorkflow $w, Booking $b, array $data) => $w->cancelByTourist($b, $data['reason'] ?? null, auth()->user()), 'Бронь отменена')),

                Action::make('cancel_operator')->label('Фирма отменила')->icon(Heroicon::OutlinedExclamationTriangle)->color('danger')
                    ->visible($is(BookingStatus::DepositPaid, BookingStatus::VoucherSent))
                    ->modalDescription(fn (Booking $record) => 'Туристу вернём 100% предоплаты: '.$money($record->deposit_cents).'. Предложите замену.')
                    ->schema([Textarea::make('reason')->label('Причина')->required()])
                    ->action($this->run(fn (BookingWorkflow $w, Booking $b, array $data) => $w->cancelByOperator($b, $data['reason'], auth()->user()), 'Бронь отменена, турист уведомлён')),
            ])->label('Ещё')->icon(Heroicon::OutlinedEllipsisVertical)->button()->color('gray'),
        ];
    }

    /** Runs a workflow step; rule violations (no seats, wrong state) become a notification, not an error page. */
    private function run(Closure $step, string $success): Closure
    {
        return function (array $data = []) use ($step, $success) {
            try {
                $step(app(BookingWorkflow::class), $this->getRecord(), $data);
                Notification::make()->title($success)->success()->send();
            } catch (InvalidBookingAction $e) {
                Notification::make()->title('Нельзя выполнить')->body($e->getMessage())->danger()->send();
            }
            $this->getRecord()->refresh();
        };
    }
}
