<?php

namespace Tests\Unit;

use App\Services\ThermalPrinterService;
use PHPUnit\Framework\TestCase;

class ThermalPrinterServiceTest extends TestCase
{
    public function test_direct_local_printing_is_only_available_on_windows_hosts(): void
    {
        $this->assertFalse(ThermalPrinterService::canUseDirectLocalPrinting(false, true));
        $this->assertTrue(ThermalPrinterService::canUseDirectLocalPrinting(true, true));
    }

    public function test_test_printer_returns_non_windows_host_message(): void
    {
        $result = ThermalPrinterService::testPrinter([
            'enabled' => true,
            'type' => ThermalPrinterService::TYPE_USB,
            'name' => 'COM4',
            'auto_cut' => true,
        ], false, false);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('browser-based Bluetooth printing', $result['message']);
    }
}
