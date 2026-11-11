<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', '30d');
        $today = now()->startOfDay();
        $end = $today->copy();

        switch ($period) {
            case '7d':
                $start = $end->copy()->subDays(6);
                break;
            case '90d':
                $start = $end->copy()->subDays(89);
                break;
            case 'month':
                $start = $end->copy()->startOfMonth();
                break;
            case 'custom':
                $validated = $request->validate([
                    'start' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                    'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start', 'before_or_equal:today'],
                ]);
                $start = Carbon::parse($validated['start'])->startOfDay();
                $end = Carbon::parse($validated['end'])->startOfDay();
                if ($start->diffInDays($end) > 179) {
                    throw ValidationException::withMessages(['start' => 'O período personalizado deve ter no máximo 180 dias.']);
                }
                break;
            case '30d':
                $start = $end->copy()->subDays(29);
                break;
            default:
                throw ValidationException::withMessages(['period' => 'Selecione um período válido.']);
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays((int) $start->diffInDays($end));
        $dayCount = (int) $start->diffInDays($end) + 1;
        $canFinance = $request->user()->can('manage-finance');
        $canReservations = $request->user()->can('manage-reservations');
        $canHousekeeping = $request->user()->can('manage-housekeeping');

        $reservations = Reservation::query()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get(['id', 'created_at', 'status', 'total']);
        $previousReservations = Reservation::query()
            ->whereBetween('created_at', [$prevStart->copy()->startOfDay(), $prevEnd->copy()->endOfDay()])
            ->count();

        $statusLabels = [
            'confirmada' => 'Confirmadas',
            'checkin' => 'Check-in realizado',
            'checkout' => 'Check-out realizado',
            'pendente' => 'Pendentes',
            'aguardando_pagamento' => 'Aguardando pagamento',
            'cancelada' => 'Canceladas',
            'nao_compareceu' => 'Não compareceu',
        ];
        $statusCounts = [];
        foreach ($statusLabels as $key => $label) {
            $statusCounts[] = [
                'key' => $key,
                'label' => $label,
                'count' => $reservations->where('status', $key)->count(),
            ];
        }
        $statusCounts = array_values(array_filter($statusCounts, fn ($item) => $item['count'] > 0));

        $totalRooms = Room::where('active', true)->count();
        $occupiedIntervals = DB::table('reservation_rooms as rr')
            ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
            ->join('rooms as rm', 'rm.id', '=', 'rr.room_id')
            ->where('rm.active', true)
            ->where('r.check_in', '<=', $endDate)
            ->where('r.check_out', '>', $startDate)
            ->whereIn('r.status', ['confirmada', 'checkin', 'checkout'])
            ->get(['rr.room_id', 'r.check_in', 'r.check_out']);

        $days = [];
        $dailyOccupancy = [];
        $roomIdsByDay = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $days[] = $date;
            $roomIdsByDay[$date] = [];
        }

        foreach ($occupiedIntervals as $interval) {
            $from = max($startDate, substr((string) $interval->check_in, 0, 10));
            $until = min($end->copy()->addDay()->toDateString(), substr((string) $interval->check_out, 0, 10));
            for ($cursor = Carbon::parse($from); $cursor->toDateString() < $until; $cursor->addDay()) {
                $roomIdsByDay[$cursor->toDateString()][(int) $interval->room_id] = true;
            }
        }
        foreach ($days as $date) {
            $count = count($roomIdsByDay[$date]);
            $dailyOccupancy[] = $totalRooms ? round($count * 100 / $totalRooms, 1) : 0;
        }

        // Disponibilidade de hoje inclui bloqueios cadastrados e manutenção.
        // É calculada separadamente do histórico de ocupação do período.
        $todayDate = $today->toDateString();
        $occupiedToday = DB::table('reservation_rooms as rr')
            ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
            ->join('rooms as rm', 'rm.id', '=', 'rr.room_id')
            ->where('rm.active', true)
            ->where('r.check_in', '<=', $todayDate)
            ->where('r.check_out', '>', $todayDate)
            ->whereIn('r.status', ['confirmada', 'checkin'])
            ->distinct()->pluck('rr.room_id')->all();
        $blockedToday = DB::table('room_blocks as rb')
            ->join('rooms as rm', 'rm.id', '=', 'rb.room_id')
            ->where('rm.active', true)
            ->where('rb.start_date', '<=', $todayDate)
            ->where('rb.end_date', '>', $todayDate)
            ->distinct()->pluck('rb.room_id')->all();
        $maintenanceToday = Room::query()->where('active', true)->where('housekeeping_status', 'manutencao')->pluck('id')->all();
        $unavailableToday = array_unique(array_merge($occupiedToday, $blockedToday, $maintenanceToday));
        $availableToday = max(0, $totalRooms - count($unavailableToday));

        $bookingsByDay = $reservations->groupBy(fn ($res) => $res->created_at->toDateString())->map->count();
        $newReservations = [];
        foreach ($days as $date) {
            $newReservations[] = (int) ($bookingsByDay[$date] ?? 0);
        }

        $arrivalToday = Reservation::whereDate('check_in', $todayDate)->where('status', 'confirmada')->count();
        $departureToday = Reservation::whereDate('check_out', $todayDate)->where('status', 'checkin')->count();
        $pending = Reservation::whereIn('status', ['pendente', 'aguardando_pagamento'])
            ->where('expires_at', '>', now())->count();
        $cleaningPending = HousekeepingTask::where('status', 'pendente')->count();

        $topRoomTypes = DB::table('reservation_rooms as rr')
            ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
            ->join('rooms as rm', 'rm.id', '=', 'rr.room_id')
            ->join('room_types as rt', 'rt.id', '=', 'rm.room_type_id')
            ->whereBetween('r.check_in', [$startDate, $endDate])
            ->whereIn('r.status', ['confirmada', 'checkin', 'checkout'])
            ->select('rt.name', DB::raw('COUNT(rr.id) as total'))
            ->groupBy('rt.id', 'rt.name')
            ->orderByDesc('total')->limit(5)->get();

        $received = 0.0;
        $spent = 0.0;
        $previousReceived = 0.0;
        $receivedByDay = [];
        $spentByDay = [];
        if ($canFinance) {
            $received = (float) Payment::where('status', 'aprovado')
                ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])->sum('amount');
            $previousReceived = (float) Payment::where('status', 'aprovado')
                ->whereBetween('paid_at', [$prevStart->copy()->startOfDay(), $prevEnd->copy()->endOfDay()])->sum('amount');
            $spent = (float) Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
            $receivedByDate = Payment::query()->where('status', 'aprovado')
                ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->selectRaw('DATE(paid_at) as dia, SUM(amount) as total')->groupBy('dia')->pluck('total', 'dia');
            $spentByDate = Expense::query()->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('date as dia, SUM(amount) as total')->groupBy('dia')->pluck('total', 'dia');
            foreach ($days as $date) {
                $receivedByDay[] = (float) ($receivedByDate[$date] ?? 0);
                $spentByDay[] = (float) ($spentByDate[$date] ?? 0);
            }
        }

        $recent = $canReservations
            ? Reservation::with('guest')->latest()->limit(7)->get()
            : collect();
        $upcoming = $canReservations
            ? Reservation::with('guest', 'rooms')->whereDate('check_in', '>=', $todayDate)
                ->whereIn('status', ['confirmada', 'checkin'])
                ->orderBy('check_in')->limit(5)->get()
            : collect();

        // Cards e paineis da interface V4: somente dados calculados do MySQL.
        $totalGuests = Guest::count();
        $newGuests = Guest::whereBetween('created_at', [
            $start->copy()->startOfDay(), $end->copy()->endOfDay(),
        ])->count();

        $statusGroups = [
            ['name' => 'Confirmadas', 'key' => 'confirmadas', 'color' => '#0b8c64', 'count' => $reservations->where('status', 'confirmada')->count()],
            ['name' => 'Em hospedagem', 'key' => 'hospedagem', 'color' => '#3388cf', 'count' => $reservations->where('status', 'checkin')->count()],
            ['name' => 'Pendentes', 'key' => 'pendentes', 'color' => '#f2ae2c', 'count' => $reservations->whereIn('status', ['pendente', 'aguardando_pagamento'])->count()],
            ['name' => 'Concluídas', 'key' => 'concluidas', 'color' => '#8360d5', 'count' => $reservations->where('status', 'checkout')->count()],
            ['name' => 'Canceladas / ausência', 'key' => 'canceladas', 'color' => '#e45b4e', 'count' => $reservations->whereIn('status', ['cancelada', 'nao_compareceu'])->count()],
        ];
        $statusGroups = $this->withPercentages($statusGroups, $reservations->count());

        $paymentGroups = [];
        if ($canFinance) {
            $methodLabels = [
                'pix' => 'Pix', 'cartao' => 'Cartão', 'credito' => 'Cartão de crédito',
                'presencial' => 'Presencial', 'transferencia' => 'Transferência',
            ];
            $palette = ['#0b8c64', '#3388cf', '#f2ae2c', '#8360d5', '#e45b4e'];
            $paymentRows = Payment::query()->where('status', 'aprovado')
                ->whereBetween('paid_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->select('method', DB::raw('SUM(amount) as amount'))
                ->groupBy('method')->orderByDesc('amount')->get();
            foreach ($paymentRows as $index => $paymentRow) {
                $methodKey = strtolower((string) $paymentRow->method);
                $paymentGroups[] = [
                    'key' => $methodKey,
                    'name' => $methodLabels[$methodKey] ?? ucfirst(str_replace('_', ' ', $methodKey)),
                    'color' => $palette[$index % count($palette)],
                    'amount' => (float) $paymentRow->amount,
                    'count' => (float) $paymentRow->amount,
                ];
            }
            $paymentGroups = $this->withPercentages($paymentGroups, $received);
        }

        // Ocupacao por categoria: diarias ocupadas / diarias disponiveis de quartos ativos.
        $roomOccupancy = [];
        $roomGroups = Room::query()->with('type')->where('active', true)->get(['id', 'room_type_id'])->groupBy('room_type_id');
        foreach ($roomGroups as $roomsOfType) {
            $roomIds = $roomsOfType->pluck('id')->map(fn ($id) => (int) $id)->all();
            $occupiedNights = 0;
            foreach ($days as $day) {
                foreach ($roomIds as $roomId) {
                    if (isset($roomIdsByDay[$day][$roomId])) {
                        $occupiedNights++;
                    }
                }
            }
            $type = $roomsOfType->first()->type;
            $roomOccupancy[] = [
                'name' => $type?->name ?? 'Categoria',
                'rooms' => count($roomIds),
                'percentage' => count($roomIds) && $dayCount
                    ? round($occupiedNights * 100 / (count($roomIds) * $dayCount), 1) : 0,
            ];
        }
        usort($roomOccupancy, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);
        $roomOccupancy = array_slice($roomOccupancy, 0, 5);

        $monthLabels = [];
        $monthValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->startOfMonth()->subMonths($i);
            $monthLabels[] = $month->translatedFormat('M');
            if ($canFinance) {
                $monthValues[] = (float) Payment::query()->where('status', 'aprovado')
                    ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount');
            } else {
                $monthValues[] = Reservation::query()
                    ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->count();
            }
        }
        $monthlyChartTitle = $canFinance ? 'Receita (últimos 6 meses)' : 'Reservas (últimos 6 meses)';
        $blockedExclusive = count(array_diff($unavailableToday, $occupiedToday));

        return view('admin.dashboard', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dayCount' => $dayCount,
            'canFinance' => $canFinance,
            'canReservations' => $canReservations,
            'canHousekeeping' => $canHousekeeping,
            'totalGuests' => $totalGuests,
            'newGuests' => $newGuests,
            'statusGroups' => $statusGroups,
            'paymentGroups' => $paymentGroups,
            'roomOccupancy' => $roomOccupancy,
            'monthlyChartTitle' => $monthlyChartTitle,
            'totalRooms' => $totalRooms,
            'occupiedToday' => count($occupiedToday),
            'availableToday' => $availableToday,
            'blockedToday' => $blockedExclusive,
            'occupancyAverage' => $dayCount ? round(array_sum($dailyOccupancy) / $dayCount, 1) : 0,
            'reservationsCount' => $reservations->count(),
            'previousReservations' => $previousReservations,
            'arrivalToday' => $arrivalToday,
            'departureToday' => $departureToday,
            'pending' => $pending,
            'cleaningPending' => $cleaningPending,
            'received' => $received,
            'spent' => $spent,
            'previousReceived' => $previousReceived,
            'statusCounts' => $statusCounts,
            'topRoomTypes' => $topRoomTypes,
            'recent' => $recent,
            'upcoming' => $upcoming,
            'chartData' => [
                'labels' => array_map(fn ($day) => Carbon::parse($day)->format('d/m'), $days),
                'occupancy' => $dailyOccupancy,
                'occupiedRooms' => array_map(fn ($day) => count($roomIdsByDay[$day]), $days),
                'totalRooms' => $totalRooms, // Escala real: quartos ocupados / quartos ativos.
                'monthlyLabels' => $monthLabels,
                'monthlyValues' => $monthValues,
                'statusGroups' => $statusGroups,
                'paymentGroups' => $paymentGroups,
                'reservations' => $newReservations,
                'received' => $receivedByDay,
                'expenses' => $spentByDay,
            ],
        ]);
    }

    /** Percentuais e faixas do grafico calculados a partir de dados reais. */
    private function withPercentages(array $groups, float|int $total): array
    {
        $cursor = 0.0;
        foreach ($groups as &$group) {
            $value = (float) ($group['count'] ?? 0);
            $percent = $total > 0 ? round($value * 100 / $total, 1) : 0;
            $group['percentage'] = $percent;
            $group['start'] = $cursor;
            $cursor += $total > 0 ? $value * 100 / $total : 0;
            $group['end'] = round(min(100, $cursor), 3);
        }
        unset($group);
        return $groups;
    }

    public function calendar()
    {
        return view('admin.calendar', ['rooms' => Room::with('type')->orderBy('number')->get()]);
    }

    public function events(Request $request)
    {
        $from = $request->input('start', now()->startOfMonth()->toDateString());
        $to = $request->input('end', now()->endOfMonth()->addDay()->toDateString());
        $request->merge(['start' => $from, 'end' => $to]);
        $request->validate(['start' => 'required|date', 'end' => 'required|date|after:start']);
        $res = Reservation::with('guest', 'rooms')->where('check_in', '<', $to)->where('check_out', '>', $from)->get();
        $canSeeGuest = $request->user()->can('manage-reservations');
        $events = $res->flatMap(fn ($a) => $a->rooms->map(fn ($room) => [
            'title' => 'Quarto ' . $room->number . ' · ' . ($canSeeGuest ? $a->guest->name : 'Ocupado'),
            'start' => $a->check_in->toDateString(),
            'end' => $a->check_out->toDateString(),
            'url' => $canSeeGuest ? route('admin.reservations.show', $a) : null,
            'className' => 'state-' . $a->status,
        ]));
        return response()->json($events);
    }

    public function reports(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->toDateString());
        $request->merge(['start' => $start, 'end' => $end]);
        $request->validate(['start' => 'required|date', 'end' => 'required|date|after_or_equal:start']);
        $reservations = Reservation::whereBetween('check_in', [$start, $end]);
        $report = [
            'reservas' => (clone $reservations)->count(),
            'confirmadas' => (clone $reservations)->whereIn('status', ['confirmada', 'checkin', 'checkout'])->count(),
            'recebido' => Payment::where('status', 'aprovado')->whereDate('paid_at', '>=', $start)->whereDate('paid_at', '<=', $end)->sum('amount'),
            'despesas' => Expense::whereBetween('date', [$start, $end])->sum('amount'),
        ];
        return view('admin.reports', compact('report', 'start', 'end'));
    }
}
