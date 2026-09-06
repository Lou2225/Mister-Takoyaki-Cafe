<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ThermalPrintController extends Controller
{
    /**
     * POST /api/thermal-print
     * On Linux/hosted servers immediately returns success:false (HTTP 200)
     * so the caller never sees a 500 error. ThermalPrinterService is only
     * loaded on Windows where a local printer can be reached.
     */
    public function print(Request $request): JsonResponse
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return response()->json([
                'success'  => false,
                'redirect' => true,
                'message'  => 'Direct printer unavailable on this server. The web receipt will open instead.',
            ], 200);
        }

        try {
            $validated = $request->validate([
                'order_id'     => 'required|integer|exists:orders,id',
                'receipt_type' => 'required|in:all,kitchen,barista,customer',
            ]);
            $order = Order::findOrFail($validated['order_id']);
            if (!auth()->user() || (!auth()->user()->isSuperAdmin() && $order->branch_id !== auth()->user()->branch_id)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $result = \App\Services\ThermalPrinterService::printReceipt($order, $validated['receipt_type']);
            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Thermal print error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function test(): JsonResponse
    {
        $this->authorize('manage-settings');
        if (PHP_OS_FAMILY !== 'Windows') {
            return response()->json(['success' => false, 'message' => 'Only available on a local Windows machine.'], 200);
        }
        $config = \App\Services\ThermalPrinterService::getPrinterConfig();
        $result = \App\Services\ThermalPrinterService::testPrinter($config);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function getAvailablePrinters(): JsonResponse
    {
        $this->authorize('manage-settings');
        if (PHP_OS_FAMILY !== 'Windows') {
            return response()->json(['success' => true, 'printers' => [], 'os' => PHP_OS_FAMILY], 200);
        }
        return response()->json(['success' => true, 'printers' => \App\Services\ThermalPrinterService::getWindowsPrinters(), 'os' => PHP_OS_FAMILY]);
    }

    public function getConfig(): JsonResponse
    {
        $this->authorize('manage-settings');
        if (PHP_OS_FAMILY !== 'Windows') {
            return response()->json(['success' => true, 'config' => [], 'os' => PHP_OS_FAMILY], 200);
        }
        return response()->json(['success' => true, 'config' => \App\Services\ThermalPrinterService::getPrinterConfig()]);
    }
}
