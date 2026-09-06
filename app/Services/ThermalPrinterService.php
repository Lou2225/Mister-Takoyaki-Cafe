<?php

namespace App\Services;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Illuminate\Support\Facades\Log;

class ThermalPrinterService
{
    /**
     * Supported printer connection types
     */
    const TYPE_WINDOWS_PRINTER = 'windows';
    const TYPE_NETWORK = 'network';
    const TYPE_USB = 'usb';
    const TYPE_WIRED = 'wired';
    const TYPE_FILE = 'file';

    /** Paper width in characters for a 58mm thermal printer */
    const CHARS_PER_LINE = 32;

    /**
     * Strip multi-byte characters and replace ₱ with P so byte-level
     * string functions (str_pad, strlen) work correctly for layout.
     */
        private static function safeCurrency(string $symbol): string
    {
        // preg_replace with no /u modifier operates byte-by-byte, not
        // character-by-character. A multi-byte UTF-8 symbol like ₱ (3 bytes)
        // would have every individual byte replaced with 'P', producing
        // "PPP" instead of "P". Detect non-ASCII content first, then
        // collapse the whole symbol to a single 'P' in one step.
        return preg_match('/[^\x00-\x7F]/', $symbol) ? 'P' : $symbol;
    }

    /**
     * Center text within the paper width, truncating if necessary.
     */
    private static function mbCenter(string $text): string
    {
        $w   = self::CHARS_PER_LINE;
        $len = mb_strlen($text);
        if ($len >= $w) {
            return mb_substr($text, 0, $w);
        }
        $pad = (int) floor(($w - $len) / 2);
        return str_repeat(' ', $pad) . $text;
    }

    /**
     * Two-column layout: left label and right value within paper width.
     * Uses mb_strlen so multi-byte chars are counted correctly.
     */
    private static function mbTwoCol(string $left, string $right): string
    {
        $w      = self::CHARS_PER_LINE;
        $rLen   = mb_strlen($right);
        $maxL   = $w - $rLen - 1;
        if (mb_strlen($left) > $maxL) {
            $left = mb_substr($left, 0, $maxL);
        }
        $spaces = $w - mb_strlen($left) - $rLen;
        return $left . str_repeat(' ', max(1, $spaces)) . $right;
    }

    /**
     * Get printer connector based on configuration
     */
    public static function getPrinterConnector($printerType, $printerName)
    {
        try {
            $normalized = self::normalizeLocalPrinterTarget($printerName);

            switch ($printerType) {
                case self::TYPE_WINDOWS_PRINTER:
                    if (self::looksLikeComPort($normalized)) {
                        return new FilePrintConnector($normalized);
                    }
                    return new WindowsPrintConnector($printerName);
                
                case self::TYPE_USB:
                case self::TYPE_WIRED:
                    if (self::looksLikeComPort($normalized)) {
                        return new FilePrintConnector($normalized);
                    }

                    if (PHP_OS_FAMILY === 'Windows') {
                        return new WindowsPrintConnector($printerName);
                    }

                    return new FilePrintConnector($printerName);
                
                case self::TYPE_NETWORK:
                    // Format: "192.168.1.100:9100"
                    [$host, $port] = explode(':', $printerName) + ['', 9100];
                    return new NetworkPrintConnector($host, (int)$port);
                
                case self::TYPE_FILE:
                    // For testing - saves to file
                    return new FilePrintConnector($printerName);
                
                default:
                    throw new \Exception("Unsupported printer type: $printerType");
            }
        } catch (\Exception $e) {
            Log::error("Failed to create printer connector: " . $e->getMessage());
            throw $e;
        }
    }

    private static function looksLikeComPort(string $value): bool
    {
        return (bool) preg_match('/^(?:\\\\\.\\\\)?COM[1-9][0-9]*$/i', $value) ||
            (bool) preg_match('/^(?:\\\\\.\\\\)?LPT[1-9][0-9]*$/i', $value);
    }

    private static function normalizeLocalPrinterTarget(string $value): string
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return $trimmed;
        }

        if (preg_match('/^COM\d+$/i', $trimmed)) {
            return '\\\.\\' . strtoupper($trimmed);
        }

        if (preg_match('/^LPT\d+$/i', $trimmed)) {
            return '\\\.\\' . strtoupper($trimmed);
        }

        return $trimmed;
    }

    /**
     * Print receipt to thermal printer
     */
    public static function printReceipt($order, $receiptType = 'all', $printerConfig = null)
    {
        try {
            if (!$printerConfig) {
                $printerConfig = self::getPrinterConfig();
            }

            if (!$printerConfig['enabled']) {
                return ['success' => false, 'message' => 'Thermal printer is disabled'];
            }

            if (in_array($printerConfig['type'], [self::TYPE_WINDOWS_PRINTER, self::TYPE_USB], true) && !self::canUseDirectLocalPrinting()) {
                return [
                    'success' => false,
                    'message' => 'Direct local printer connection is unavailable on this server. Use the browser-based Bluetooth printing option instead.',
                ];
            }

            $connector = self::getPrinterConnector($printerConfig['type'], $printerConfig['name']);
            $printer = new Printer($connector);

            try {
                // Build receipt content based on type
                $receiptContent = self::buildReceiptContent($order, $receiptType);
                
                // Send to printer
                $printer->text($receiptContent);
                
                // Auto-cut paper (if supported)
                if ($printerConfig['auto_cut']) {
                    $printer->cut();
                }
                
                $printer->close();

                Log::info("Receipt printed successfully", [
                    'order_id' => $order->id,
                    'type' => $receiptType,
                    'printer' => $printerConfig['name']
                ]);

                return ['success' => true, 'message' => 'Receipt printed successfully'];
            } catch (\Throwable $e) {
                try {
                    $printer->close();
                } catch (\Throwable $closeEx) {
                    // Ignore close exception
                }
                throw $e;
            }
        } catch (\Throwable $e) {
            Log::error("Thermal printer error: " . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'type' => $receiptType ?? 'unknown'
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Build plain-text receipt content for thermal printer
     */
    private static function buildReceiptContent($order, $receiptType)
    {
        $order->load(['items.product.category', 'items.options.option', 'items.modifiers.modifier', 'branch', 'user']);
        $settings = ConfigurationService::getPosConfig();
        
        $content = "";

        // Filter items by production_station
        $kitchenItems = $order->items->filter(fn ($item) => (optional($item->product->category)->production_station ?? 'kitchen') === 'kitchen');
        $baristaItems = $order->items->filter(fn ($item) => (optional($item->product->category)->production_station ?? '') === 'barista');

        // Determine which receipts to print
        $printKitchen = in_array($receiptType, ['all', 'kitchen']);
        $printBarista = in_array($receiptType, ['all', 'barista']);
        $printCustomer = in_array($receiptType, ['all', 'customer']);

        // ──── KITCHEN SLIP ────
        if ($printKitchen && $kitchenItems->isNotEmpty()) {
            $content .= self::buildKitchenSlip($order, $kitchenItems, $settings);
            $content .= "\n\n";
        }

        // ──── BARISTA SLIP ────
        if ($printBarista && $baristaItems->isNotEmpty()) {
            $content .= self::buildBaristaSlip($order, $baristaItems, $settings);
            $content .= "\n\n";
        }

        // ──── CUSTOMER RECEIPT ────
        if ($printCustomer) {
            $content .= self::buildCustomerReceipt($order, $settings);
        }

        return $content;
    }

    /**
     * Wrap text into lines not exceeding the given width.
     */
    private static function mbWrap(string $text, int $width = self::CHARS_PER_LINE): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            if ($word === '') continue;
            $test = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($test) > $width) {
                if ($current !== '') {
                    $lines[] = $current;
                }
                while (mb_strlen($word) > $width) {
                    $lines[] = mb_substr($word, 0, $width);
                    $word = mb_substr($word, $width);
                }
                $current = $word;
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

                return $lines;
    }

        /**
     * Delivery-address block so riders can read the drop-off address
     * straight from the printed slip, not just the rider app. Shown on
     * the customer receipt only, matching the Web Bluetooth thermal
     * printer client's behavior.
     */
    private static function deliveryAddressBlock($order, int $width): string
    {
        $orderType = strtolower($order->order_type ?? '');
        if ($orderType !== 'delivery' || empty($order->delivery_address)) {
            return '';
        }

        $block = str_repeat("-", $width) . "\n";
        $block .= "DELIVER TO:\n";
        foreach (self::mbWrap($order->delivery_address, $width) as $l) {
            $block .= $l . "\n";
        }
        if (!empty($order->customer_phone)) {
            $block .= "Phone: " . $order->customer_phone . "\n";
        }
        if (!empty($order->delivery_notes)) {
            foreach (self::mbWrap("Note: " . $order->delivery_notes, $width) as $l) {
                $block .= $l . "\n";
            }
        }

        return $block;
    }

    /**
     * Build kitchen slip
     */
       private static function buildKitchenSlip($order, $kitchenItems, $settings)
    {
        $rawTitle = $settings['kitchen_slip_title'] ?? 'KITCHEN SLIP';
        $title = trim(preg_replace('/[^\x20-\x7E]/', '', $rawTitle)) ?: 'KITCHEN SLIP';
        $subtitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['kitchen_slip_subtitle'] ?? 'Food Preparation Order'));

        $w = self::CHARS_PER_LINE;
        $content = "";
        $content .= str_repeat("=", $w) . "\n";
        $content .= self::mbCenter($title) . "\n";
        if (!empty($subtitle)) {
            $content .= self::mbCenter($subtitle) . "\n";
        }
        $content .= str_repeat("=", $w) . "\n\n";

        $content .= "Order #: " . $order->reference_no . "\n";
        $content .= "Time: " . $order->created_at->format('d/m/y H:i') . "\n";
        $content .= "Type: " . strtoupper($order->order_type) . "\n";

        if ($order->table_number) {
            $content .= "Table: " . $order->table_number . "\n";
        }

        $content .= "\n" . str_repeat("-", $w) . "\n";
        $content .= "ITEMS:\n";
        $content .= str_repeat("-", $w) . "\n\n";

        foreach ($kitchenItems as $item) {
            $itemName = $item->quantity . "x " . ($item->product->name ?? 'Item');
            foreach (self::mbWrap($itemName, $w) as $l) {
                $content .= $l . "\n";
            }

            if ($item->options->count() > 0) {
                foreach ($item->options as $opt) {
                    $optName = "   + " . ($opt->option->name ?? 'Option');
                    foreach (self::mbWrap($optName, $w) as $l) {
                        $content .= $l . "\n";
                    }
                }
            }

            if ($item->modifiers && $item->modifiers->count() > 0) {
                foreach ($item->modifiers as $mod) {
                    $modName = "   + " . ($mod->modifier->name ?? 'Modifier');
                    foreach (self::mbWrap($modName, $w) as $l) {
                        $content .= $l . "\n";
                    }
                }
            }

            if ($item->special_instructions) {
                $note = "   NOTE: " . $item->special_instructions;
                foreach (self::mbWrap($note, $w) as $l) {
                    $content .= $l . "\n";
                }
            }

            $content .= "\n";
        }

        if ($order->delivery_notes) {
            $content .= str_repeat("-", $w) . "\n";
            foreach (self::mbWrap("NOTES: " . $order->delivery_notes, $w) as $l) {
                $content .= $l . "\n";
            }
        }

        $content .= "\n" . str_repeat("=", $w) . "\n";

        return $content;
    }

    /**
     * Build barista slip
     */
        private static function buildBaristaSlip($order, $baristaItems, $settings)
    {
        $rawTitle = $settings['barista_slip_title'] ?? 'BARISTA SLIP';
        $title = trim(preg_replace('/[^\x20-\x7E]/', '', $rawTitle)) ?: 'BARISTA SLIP';
        $subtitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['barista_slip_subtitle'] ?? 'Beverage Preparation Order'));

        $w = self::CHARS_PER_LINE;
        $content = "";
        $content .= str_repeat("=", $w) . "\n";
        $content .= self::mbCenter($title) . "\n";
        if (!empty($subtitle)) {
            $content .= self::mbCenter($subtitle) . "\n";
        }
        $content .= str_repeat("=", $w) . "\n\n";

        $content .= "Order #: " . $order->reference_no . "\n";
        $content .= "Time: " . $order->created_at->format('d/m/y H:i') . "\n";
        $content .= "Type: " . strtoupper($order->order_type) . "\n";

        $content .= "\n" . str_repeat("-", $w) . "\n";
        $content .= "BEVERAGES:\n";
        $content .= str_repeat("-", $w) . "\n\n";

        foreach ($baristaItems as $item) {
            $itemName = $item->quantity . "x " . ($item->product->name ?? 'Item');
            foreach (self::mbWrap($itemName, $w) as $l) {
                $content .= $l . "\n";
            }

            if ($item->options->count() > 0) {
                foreach ($item->options as $opt) {
                    $optName = "   + " . ($opt->option->name ?? 'Option');
                    foreach (self::mbWrap($optName, $w) as $l) {
                        $content .= $l . "\n";
                    }
                }
            }

            if ($item->modifiers && $item->modifiers->count() > 0) {
                foreach ($item->modifiers as $mod) {
                    $modName = "   + " . ($mod->modifier->name ?? 'Modifier');
                    foreach (self::mbWrap($modName, $w) as $l) {
                        $content .= $l . "\n";
                    }
                }
            }

            if ($item->special_instructions) {
                $note = "   NOTE: " . $item->special_instructions;
                foreach (self::mbWrap($note, $w) as $l) {
                    $content .= $l . "\n";
                }
            }

            $content .= "\n";
        }

        $content .= "\n" . str_repeat("=", $w) . "\n";

        return $content;
    }

    /**
     * Build customer receipt
     */
        private static function buildCustomerReceipt($order, $settings)
    {
        $businessConfig = ConfigurationService::getBusinessConfig();
        $financialConfig = ConfigurationService::getFinancialConfig();
        $rawTitle = $settings['customer_receipt_title'] ?? 'Customer Receipt & Invoice';
        $title = trim(preg_replace('/[^\x20-\x7E]/', '', $rawTitle)) ?: 'Customer Receipt & Invoice';
        $businessName = trim(preg_replace('/[^\x20-\x7E]/', '', $businessConfig['name'] ?? 'Mister Takoyaki Cafe'));
        $currencySymbol = self::safeCurrency($financialConfig['currency_symbol'] ?? 'P');
        $w = self::CHARS_PER_LINE;

        $content = "";
        $content .= str_repeat("=", $w) . "\n";
        $content .= self::mbCenter($businessName) . "\n";
        if (!empty($title)) {
            $content .= self::mbCenter($title) . "\n";
        }
        $content .= str_repeat("=", $w) . "\n\n";

        if (!empty($businessConfig['address'])) {
            foreach (self::mbWrap($businessConfig['address'], $w) as $l) {
                $content .= $l . "\n";
            }
        }

        if (!empty($businessConfig['phone'])) {
            $content .= "Tel: " . $businessConfig['phone'] . "\n";
        }

        if (!empty($businessConfig['email'])) {
            $content .= "Email: " . $businessConfig['email'] . "\n";
        }

        $content .= "\nOrder #: " . $order->reference_no . "\n";
        $content .= "Date: " . $order->created_at->format('d/m/y H:i') . "\n";
        $content .= "Type: " . strtoupper($order->order_type) . "\n";
                if ($order->customer_name) {
            $content .= "Customer: " . $order->customer_name . "\n";
        }
        $content .= "Payment: " . $order->payment_method . "\n";

        $content .= self::deliveryAddressBlock($order, $w);

        $content .= "\n" . str_repeat("-", $w) . "\n";
        $content .= "ITEMS:\n";
        $content .= str_repeat("-", $w) . "\n\n";

        foreach ($order->items as $item) {
            $price = $currencySymbol . number_format($item->subtotal, 2);
            $line = $item->quantity . "x " . ($item->product->name ?? 'Item');
            $content .= self::mbTwoCol($line, $price) . "\n";

            if ($item->options->count() > 0) {
                foreach ($item->options as $opt) {
                    $content .= "   + " . ($opt->option->name ?? '') . "\n";
                }
            }

            if ($item->modifiers && $item->modifiers->count() > 0) {
                foreach ($item->modifiers as $mod) {
                    $content .= "   + " . ($mod->modifier->name ?? '') . "\n";
                }
            }
        }

        $content .= "\n" . str_repeat("-", $w) . "\n";

        $subtotal = $order->total_amount + $order->discount_amount;
        $content .= self::mbTwoCol("Subtotal:", $currencySymbol . number_format($subtotal, 2)) . "\n";

        if ($order->discount_amount > 0) {
            $content .= self::mbTwoCol("Discount:", "-" . $currencySymbol . number_format($order->discount_amount, 2)) . "\n";
        }

        if ($order->service_charge > 0) {
            $content .= self::mbTwoCol("Service Charge:", $currencySymbol . number_format($order->service_charge, 2)) . "\n";
        }

        if ($order->delivery_fee > 0) {
            $content .= self::mbTwoCol("Delivery Fee:", $currencySymbol . number_format($order->delivery_fee, 2)) . "\n";
        }

        $content .= "\n" . str_repeat("=", $w) . "\n";
        $content .= self::mbTwoCol("TOTAL:", $currencySymbol . number_format($order->total_amount, 2)) . "\n";
        $content .= str_repeat("=", $w) . "\n\n";

        if (($settings['show_receipt_footer'] ?? true) !== false) {
            $footerMsg = $settings['footer_message'] ?? 'Thank you for your visit!';
            foreach (self::mbWrap($footerMsg, $w) as $l) {
                $content .= self::mbCenter($l) . "\n";
            }
            if (!empty($settings['return_policy'])) {
                foreach (self::mbWrap($settings['return_policy'], $w) as $l) {
                    $content .= self::mbCenter($l) . "\n";
                }
            }
        }

        if (($settings['show_receipt_qr_code'] ?? true) !== false) {
            $qrUrl = $settings['qr_url'] ?? '';
            if (empty($qrUrl)) {
                $qrUrl = route('customer.review', ['branch' => $order->branch_id]);
            } else {
                $qrUrl = str_replace('{order_id}', $order->id, $qrUrl);
                $separator = str_contains($qrUrl, '?') ? '&' : '?';
                $qrUrl .= $separator . 'branch=' . $order->branch_id;
            }
            $content .= "\n" . self::mbCenter("SCAN TO REVIEW & RATE ORDER") . "\n";
            foreach (self::mbWrap($qrUrl, $w) as $l) {
                $content .= self::mbCenter($l) . "\n";
            }
        }

        $content .= str_repeat("*", $w) . "\n";

        return $content;
    }

    /**
     * Get printer configuration from settings
     */
    public static function getPrinterConfig()
    {
        $config = \App\Models\SystemSetting::get('thermal_printer_config', [
            'enabled' => false,
            'type' => self::TYPE_WINDOWS_PRINTER,
            'name' => '',
            'auto_cut' => true
        ]);

        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }

        return array_merge([
            'enabled' => false,
            'type' => self::TYPE_WINDOWS_PRINTER,
            'name' => '',
            'auto_cut' => true
        ], $config);
    }

    /**
     * Whether direct local Windows printing is actually possible in this PHP runtime.
     * This must be a local Windows host with proc_open enabled; remote hosts such as
     * Hostinger cannot reach a physical USB/COM printer attached to the cashier PC.
     */
    public static function canUseDirectLocalPrinting(?bool $isWindowsOverride = null, ?bool $procOpenOverride = null): bool
    {
        $isWindows = $isWindowsOverride ?? (PHP_OS_FAMILY === 'Windows');
        $hasProcOpen = $procOpenOverride ?? function_exists('proc_open');

        return $isWindows && $hasProcOpen;
    }

    /**
     * Backward-compatible wrapper used by settings/UI.
     */
    public static function isWindowsPrintingAvailable(): bool
    {
        return self::canUseDirectLocalPrinting();
    }

    /**
     * Test printer connection
     */
    public static function testPrinter($printerConfig, ?bool $isWindowsOverride = null, ?bool $procOpenOverride = null)
    {
        $type = $printerConfig['type'] ?? self::TYPE_WINDOWS_PRINTER;

        if (in_array($type, [self::TYPE_WINDOWS_PRINTER, self::TYPE_USB], true) && !self::canUseDirectLocalPrinting($isWindowsOverride, $procOpenOverride)) {
            return [
                'success' => false,
                'message' => 'Direct local printer connection only works when this app is installed on the same Windows machine as the printer. On this hosting environment, use the browser-based Bluetooth printing option instead.',
            ];
        }

        try {
            $connector = self::getPrinterConnector($printerConfig['type'], $printerConfig['name']);
            $printer = new Printer($connector);
            $printer->text("PRINTER TEST\n");
            $printer->text("Connection successful!\n");
            $printer->text("Timestamp: " . now()->format('Y-m-d H:i:s') . "\n");
            $printer->cut();
            $printer->close();
            return ['success' => true, 'message' => 'Printer test successful'];
        } catch (\Throwable $e) {
            // \Throwable (not just \Exception) is required here: escpos-php's
            // WindowsPrintConnector calls proc_open() internally, which is
            // disabled on shared hosts like Hostinger. PHP raises that as an
            // \Error ("Call to undefined function"), not an \Exception — a
            // narrower catch silently lets it escalate to a fatal 500 instead
            // of returning a normal failure response.
            $message = str_contains($e->getMessage(), 'proc_open')
                ? 'Direct printer connection is not available on this server (shared hosting restricts required system functions). Use the browser-based Bluetooth printing option instead.'
                : $e->getMessage();

            return ['success' => false, 'message' => $message];
        }
    }

    /**
     * Get available Windows printers
     */
    public static function getWindowsPrinters()
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $printers = [];
                $commands = [
            'powershell -NoProfile -Command "Get-CimInstance Win32_Printer | Select-Object -ExpandProperty Name" 2>$null',
            'powershell -NoProfile -Command "Get-WmiObject Win32_Printer | Select-Object -ExpandProperty Name" 2>$null',
            'powershell -NoProfile -Command "Get-CimInstance Win32_SerialPort | Select-Object -ExpandProperty DeviceID" 2>$null',
            'powershell -NoProfile -Command "Get-WmiObject Win32_SerialPort | Select-Object -ExpandProperty DeviceID" 2>$null',
            // Raw USB thermal printers that were never installed as a Windows
            // print queue (generic ESC/POS drivers) show up here instead.
            'powershell -NoProfile -Command "Get-PnpDevice -PresentOnly | Where-Object { $_.Class -in @(\'USB\',\'Ports\',\'Printer\') -and $_.Status -eq \'OK\' } | Select-Object -ExpandProperty FriendlyName" 2>$null',
            'wmic printer get name 2>nul',
            'wmic path Win32_SerialPort get DeviceID 2>nul',
        ];

        foreach ($commands as $command) {
            try {
                $output = shell_exec($command);
                if (!is_string($output) || trim($output) === '') {
                    continue;
                }

                $rows = array_values(array_filter(array_map(function ($line) {
                    $name = trim((string) $line);
                    if ($name === '') {
                        return null;
                    }

                    if (preg_match('/^(Name|DeviceID)$/i', $name)) {
                        return null;
                    }

                    return strtoupper($name) === $name && preg_match('/^COM\d+$/i', $name) ? strtoupper($name) : $name;
                }, preg_split('/\r\n|\r|\n/', $output))));

                foreach ($rows as $row) {
                    $row = trim((string) $row);
                    if ($row === '') {
                        continue;
                    }

                    $printers[] = $row;
                }
            } catch (\Throwable $e) {
                Log::warning('Windows printer scan failed for command: ' . $command, ['message' => $e->getMessage()]);
            }
        }

        return array_values(array_unique($printers));
    }
}
