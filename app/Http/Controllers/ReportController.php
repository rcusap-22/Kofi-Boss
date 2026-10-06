<?php
namespace App\Http\Controllers;

use App\Models\DailyStockCount;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index() { return view('reports.index'); }

    public function dailyInventory(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $count = DailyStockCount::with(['items.item','user'])->whereDate('count_date', $date)->first();
        return view('reports.daily_inventory', compact('count','date'));
    }

    public function stockSummary()
    {
        $items = InventoryItem::orderBy('category')->orderBy('name')->get();
        return view('reports.stock_summary', compact('items'));
    }

    public function inventoryMovement(Request $request)
    {
        $period = $request->input('period', 'daily');
        if ($period === 'monthly') {
            $month = $request->input('month', now()->format('Y-m'));
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth(); $end = $start->copy()->endOfMonth(); $periodLabel = $start->format('F Y');
        } elseif ($period === 'custom') {
            $validated = $request->validate(['from'=>['required','date'],'to'=>['required','date','after_or_equal:from']]);
            $start=Carbon::parse($validated['from'])->startOfDay(); $end=Carbon::parse($validated['to'])->endOfDay(); $periodLabel=$start->format('M j, Y').' - '.$end->format('M j, Y');
        } else {
            $period='daily'; $date=$request->input('date',now()->toDateString()); $start=Carbon::parse($date)->startOfDay(); $end=$start->copy()->endOfDay(); $periodLabel=$start->format('F j, Y');
        }
        $items=InventoryItem::orderBy('category')->orderBy('name')->get();
        $periodTransactions=StockTransaction::whereBetween('date',[$start->toDateString(),$end->toDateString()])->get()->groupBy('item_id');
        $afterTransactions=StockTransaction::where('date','>',$end->toDateString())->get()->groupBy('item_id');
        $rows=$items->map(function($item) use($periodTransactions,$afterTransactions){
            $tx=$periodTransactions->get($item->id,collect()); $after=$afterTransactions->get($item->id,collect());
            $isDailyCount=fn($t)=>str_starts_with((string)$t->reference,'Daily Count #');
            $received=(float)$tx->filter(fn($t)=>$t->type==='in'&&!$isDailyCount($t))->sum('quantity');
            $reconIn=(float)$tx->filter(fn($t)=>$t->type==='in'&&$isDailyCount($t))->sum('quantity');
            $reconOut=(float)$tx->filter(fn($t)=>$t->type==='out'&&$isDailyCount($t))->sum('quantity');
            $afterIn=(float)$after->where('type','in')->sum('quantity'); $afterOut=(float)$after->where('type','out')->sum('quantity');
            $ending=(float)$item->stock_qty-$afterIn+$afterOut; $allIn=(float)$tx->where('type','in')->sum('quantity'); $allOut=(float)$tx->where('type','out')->sum('quantity');
            $beginning=$ending-$allIn+$allOut;
            // Estimated reduction is the closing-count decrease. It is not labelled exact consumption because waste/spillage is not separately tracked.
            $estimatedReduction=$reconOut;
            $status=$ending<=0?'Out of Stock':($ending<=(float)$item->min_stock?'Low Stock':'In Stock');
            return (object)['name'=>$item->name,'category'=>$item->category,'unit'=>$item->unit,'beginning_stock'=>$beginning,'received_stock'=>$received,'reconciliation_in'=>$reconIn,'reconciliation_out'=>$reconOut,'estimated_reduction'=>$estimatedReduction,'ending_stock'=>$ending,'status'=>$status];
        });
        $totals=(object)['received_stock'=>$rows->sum('received_stock'),'reconciliation_in'=>$rows->sum('reconciliation_in'),'reconciliation_out'=>$rows->sum('reconciliation_out')];
        return view('reports.inventory_movement',compact('rows','totals','period','periodLabel','start','end'));
    }

    public function transactions(Request $request)
    {
        $from=$request->date('from')??now()->subDays(30); $to=$request->date('to')??now();
        $transactions=StockTransaction::with(['item','user'])->whereBetween('date',[$from,$to])->orderByDesc('date')->get();
        return view('reports.transactions',compact('transactions','from','to'));
    }
    public function lowStock(){ $items=InventoryItem::whereIn('status',['low_stock','out_of_stock'])->get(); return view('reports.low_stock',compact('items')); }
    public function purchases(Request $request)
    {
        $from=$request->date('from')??now()->subDays(90); $to=$request->date('to')??now();
        $purchases=Purchase::with(['supplier','requestedBy','approvedBy'])->whereBetween('order_date',[$from,$to])->orderByDesc('order_date')->get();
        return view('reports.purchases',compact('purchases','from','to'));
    }

    public function exportExcel(Request $request, string $report)
    {
        $title = 'KOFI BOSS Inventory Report';
        $headers = [];
        $rows = [];
        $filename = 'kofi-boss-report-' . now()->format('Y-m-d') . '.xlsx';

        switch ($report) {
            case 'daily-inventory':
                $date = $request->input('date', now()->toDateString());
                $count = DailyStockCount::with(['items.item','user'])->whereDate('count_date', $date)->first();
                $title = 'Daily Inventory Report - ' . Carbon::parse($date)->format('F j, Y');
                $headers = ['Item', 'Unit', 'Expected', 'Actual', 'Variance'];
                if ($count) {
                    foreach ($count->items as $row) {
                        $rows[] = [$row->item->name, $row->item->unit, (int)$row->expected_qty, (int)$row->actual_qty, (int)$row->variance_qty];
                    }
                }
                $filename = 'daily-inventory-' . $date . '.xlsx';
                break;

            case 'stock-summary':
                $items = InventoryItem::orderBy('category')->orderBy('name')->get();
                $title = 'Stock Summary';
                $headers = ['Item', 'Category', 'Quantity', 'Unit', 'Reorder Threshold', 'Status'];
                foreach ($items as $item) {
                    $rows[] = [$item->name, $item->category, (int)$item->stock_qty, $item->unit, (int)$item->min_stock, str_replace('_', ' ', ucfirst($item->status))];
                }
                $filename = 'stock-summary-' . now()->format('Y-m-d') . '.xlsx';
                break;

            case 'inventory-movement':
                [$rowsData, $periodLabel] = $this->movementRowsForExport($request);
                $title = 'Inventory Movement Report - ' . $periodLabel;
                $headers = ['Item', 'Category', 'Unit', 'Beginning Stock', 'Received', 'Count Increase', 'Count Reduction', 'Ending Stock', 'Status'];
                foreach ($rowsData as $row) {
                    $rows[] = [$row->name, $row->category, $row->unit, (int)$row->beginning_stock, (int)$row->received_stock, (int)$row->reconciliation_in, (int)$row->reconciliation_out, (int)$row->ending_stock, $row->status];
                }
                $filename = 'inventory-movement-' . now()->format('Y-m-d') . '.xlsx';
                break;

            case 'transactions':
                $from = $request->date('from') ?? now()->subDays(30);
                $to = $request->date('to') ?? now();
                $transactions = StockTransaction::with(['item','user'])->whereBetween('date', [$from, $to])->orderByDesc('date')->get();
                $title = 'Transaction Report - ' . $from->format('M j, Y') . ' to ' . $to->format('M j, Y');
                $headers = ['Date', 'Item', 'Type', 'Quantity', 'Unit', 'Recorded By', 'Reference'];
                foreach ($transactions as $tx) {
                    $rows[] = [$tx->date->format('Y-m-d'), $tx->item->name, ucfirst($tx->type), (int)$tx->quantity, $tx->item->unit, $tx->user->name, $tx->reference ?? ''];
                }
                $filename = 'transactions-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.xlsx';
                break;

            case 'low-stock':
                $items = InventoryItem::whereIn('status', ['low_stock','out_of_stock'])->orderBy('name')->get();
                $title = 'Low Stock Report';
                $headers = ['Item', 'Category', 'Current Stock', 'Unit', 'Minimum', 'Status'];
                foreach ($items as $item) {
                    $rows[] = [$item->name, $item->category, (int)$item->stock_qty, $item->unit, (int)$item->min_stock, str_replace('_', ' ', ucfirst($item->status))];
                }
                $filename = 'low-stock-' . now()->format('Y-m-d') . '.xlsx';
                break;

            case 'purchases':
                $from = $request->date('from') ?? now()->subDays(90);
                $to = $request->date('to') ?? now();
                $purchases = Purchase::with(['supplier','requestedBy','approvedBy'])->whereBetween('order_date', [$from, $to])->orderByDesc('order_date')->get();
                $title = 'Purchase Report - ' . $from->format('M j, Y') . ' to ' . $to->format('M j, Y');
                $headers = ['Date', 'Supplier', 'Requested By', 'Approved By', 'Status'];
                foreach ($purchases as $purchase) {
                    $rows[] = [$purchase->order_date->format('Y-m-d'), $purchase->supplier->name ?? '', $purchase->requestedBy->name ?? '', $purchase->approvedBy->name ?? '', ucfirst($purchase->status)];
                }
                $filename = 'purchases-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.xlsx';
                break;

            default:
                abort(404);
        }

        return $this->downloadXlsx($title, $headers, $rows, $filename);
    }

    private function movementRowsForExport(Request $request): array
    {
        $period = $request->input('period', 'daily');
        if ($period === 'monthly') {
            $month = $request->input('month', now()->format('Y-m'));
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $periodLabel = $start->format('F Y');
        } elseif ($period === 'custom') {
            $start = Carbon::parse($request->input('from', now()->toDateString()))->startOfDay();
            $end = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
            $periodLabel = $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
        } else {
            $date = $request->input('date', now()->toDateString());
            $start = Carbon::parse($date)->startOfDay();
            $end = $start->copy()->endOfDay();
            $periodLabel = $start->format('F j, Y');
        }

        $items = InventoryItem::orderBy('category')->orderBy('name')->get();
        $periodTransactions = StockTransaction::whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()->groupBy('item_id');
        $afterTransactions = StockTransaction::where('date', '>', $end->toDateString())->get()->groupBy('item_id');
        $rows = $items->map(function ($item) use ($periodTransactions, $afterTransactions) {
            $tx = $periodTransactions->get($item->id, collect());
            $after = $afterTransactions->get($item->id, collect());
            $isDailyCount = fn($t) => str_starts_with((string)$t->reference, 'Daily Count #');
            $received = (float)$tx->filter(fn($t) => $t->type === 'in' && !$isDailyCount($t))->sum('quantity');
            $reconIn = (float)$tx->filter(fn($t) => $t->type === 'in' && $isDailyCount($t))->sum('quantity');
            $reconOut = (float)$tx->filter(fn($t) => $t->type === 'out' && $isDailyCount($t))->sum('quantity');
            $afterIn = (float)$after->where('type', 'in')->sum('quantity');
            $afterOut = (float)$after->where('type', 'out')->sum('quantity');
            $ending = (float)$item->stock_qty - $afterIn + $afterOut;
            $allIn = (float)$tx->where('type', 'in')->sum('quantity');
            $allOut = (float)$tx->where('type', 'out')->sum('quantity');
            $beginning = $ending - $allIn + $allOut;
            $status = $ending <= 0 ? 'Out of Stock' : ($ending <= (float)$item->min_stock ? 'Low Stock' : 'In Stock');
            return (object)['name'=>$item->name,'category'=>$item->category,'unit'=>$item->unit,'beginning_stock'=>$beginning,'received_stock'=>$received,'reconciliation_in'=>$reconIn,'reconciliation_out'=>$reconOut,'ending_stock'=>$ending,'status'=>$status];
        });
        return [$rows, $periodLabel];
    }

    private function downloadXlsx(string $title, array $headers, array $rows, string $filename)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'kofi_xlsx_');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create Excel file.');
        }

        $xml = fn($value) => htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $n) { $s=''; while ($n>0) { $n--; $s=chr(65+($n%26)).$s; $n=intdiv($n,26); } return $s; };
        $sheetRows = [];
        $r = 1;
        $sheetRows[] = '<row r="1"><c r="A1" t="inlineStr" s="1"><is><t>'.$xml($title).'</t></is></c></row>';
        $r++;
        $sheetRows[] = '<row r="'.$r.'"><c r="A'.$r.'" t="inlineStr"><is><t>Generated: '.$xml(now()->format('F j, Y g:i A')).'</t></is></c></row>';
        $r += 2;
        $cells=[];
        foreach ($headers as $i=>$h) { $ref=$col($i+1).$r; $cells[]='<c r="'.$ref.'" t="inlineStr" s="2"><is><t>'.$xml($h).'</t></is></c>'; }
        $sheetRows[]='<row r="'.$r.'">'.implode('', $cells).'</row>';
        foreach ($rows as $row) {
            $r++; $cells=[];
            foreach (array_values($row) as $i=>$v) {
                $ref=$col($i+1).$r;
                if (is_int($v) || is_float($v)) $cells[]='<c r="'.$ref.'"><v>'.$v.'</v></c>';
                else $cells[]='<c r="'.$ref.'" t="inlineStr"><is><t>'.$xml($v).'</t></is></c>';
            }
            $sheetRows[]='<row r="'.$r.'">'.implode('', $cells).'</row>';
        }
        $lastCol=$col(max(1,count($headers)));
        $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'.$lastCol.$r.'"/><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols>';
        for($i=1;$i<=count($headers);$i++) $sheet.='<col min="'.$i.'" max="'.$i.'" width="20" customWidth="1"/>';
        $sheet.='</cols><sheetData>'.implode('', $sheetRows).'</sheetData><autoFilter ref="A4:'.$lastCol.$r.'"/></worksheet>';

        $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml','<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF0B5D3B"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9A441"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFill="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml',$sheet);
        $zip->close();

        return response()->download($tmp, $filename, ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

}
