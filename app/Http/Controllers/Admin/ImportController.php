<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Catalog\ProductImporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function form()
    {
        return view('admin.products.import');
    }

    public function store(Request $request, ProductImporter $importer)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel', 'max:8192'],
        ], [], ['file' => 'فایل']);

        $report = $importer->import($request->file('file')->getRealPath());

        return back()
            ->with('success', $report->summary())
            ->with('import_errors', $report->errors);
    }

    /** فایل نمونه با هدرهای درست تا کاربر ساختار را بداند. */
    public function template(): StreamedResponse
    {
        $headers = ['کد کالا', 'نام', 'برند', 'دسته', 'قیمت خرده', 'قیمت همکار',
                    'قیمت نمایندگی', 'موجودی', 'وزن', 'گارانتی', 'وضعیت', 'توضیح کوتاه', 'مشخصات'];

        $sample = ['SIM-EXAMPLE-1', 'آیفون تصویری سیماران مدل نمونه', 'سیماران', 'آیفون تصویری',
                   '9800000', '8600000', '8000000', '12', '1500', '24', 'منتشرشده',
                   'توضیح کوتاه محصول', 'اندازه نمایشگر: ۷ اینچ | حافظه تصویر: دارد'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM تا اکسل فارسی را درست بخواند
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'barghishop-products-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
