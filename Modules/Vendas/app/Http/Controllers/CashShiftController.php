<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;
use Modules\Vendas\App\Exceptions\CashShiftAlreadyOpenException;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Services\CashShiftService;

class CashShiftController extends Controller
{
    public function __construct(
        private readonly CashShiftService $shiftService
    ) {}

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId = app('currentTenant')->id;
        $userId = (int) $request->user()->id;

        try {
            $shift = $this->shiftService->openShift(
                $tenantId,
                $userId,
                (float) $validated['opening_balance'],
                $validated['notes'] ?? null
            );
        } catch (CashShiftAlreadyOpenException $e) {
            $e->openShift->loadMissing('user');

            return response()->json([
                'success' => false,
                'message' => "Ja existe um caixa aberto por {$e->openShift->user->name} desde {$e->openShift->opened_at->format('H:i')}. Peca pra essa pessoa fechar o turno antes de abrir outro.",
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Caixa aberto com sucesso.',
            'shift' => $shift,
        ]);
    }

    public function movement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_shift_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:inflow,outflow'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'authorized_by' => ['nullable', 'string', 'max:100'],
        ]);

        $tenantId = app('currentTenant')->id;
        $userId = (int) $request->user()->id;

        $movement = $this->shiftService->recordMovement(
            $tenantId,
            $userId,
            (int) $validated['cash_shift_id'],
            $validated['type'],
            (float) $validated['amount'],
            $validated['reason'],
            $validated['authorized_by'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => ($validated['type'] === 'outflow' ? 'Sangria' : 'Suprimento') . ' registrado com sucesso.',
            'movement' => $movement,
        ]);
    }

    public function close(Request $request, CashShift $shift): JsonResponse
    {
        $validated = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $closedShift = $this->shiftService->closeShift(
            $shift,
            (float) $validated['closing_balance'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Turno de caixa encerrado com sucesso.',
            'shift_id' => $closedShift->id,
        ]);
    }

    public function receipt(CashShift $shift): View
    {
        $shift->load(['user', 'movements.user']);
        $summary = $this->shiftService->getSummary($shift);
        /** @var Tenant $tenant */
        $tenant = app('currentTenant') ?? Tenant::find($shift->tenant_id);

        return view('vendas::pos.cash_shift_receipt', compact('shift', 'summary', 'tenant'));
    }

    /**
     * Retorna o resumo financeiro atualizado do turno para exibicao no modal de fechamento.
     */
    public function summary(CashShift $shift): JsonResponse
    {
        $summary = $this->shiftService->getSummary($shift);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}