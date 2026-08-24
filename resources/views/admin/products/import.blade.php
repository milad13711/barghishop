@extends('admin.layouts.app')
@section('heading', 'ایمپورت محصولات')

@section('content')
<div class="grid max-w-4xl gap-5 lg:grid-cols-[1fr_320px]">
    <div class="card space-y-5 p-6">
        <h2 class="text-sm font-bold text-navy-900">بارگذاری فایل CSV</h2>

        @if($lines = session('import_errors'))
            @if(count($lines))
                <div class="rounded-xl bg-rose-50 p-4 text-xs leading-7 text-rose-700">
                    <div class="mb-1 font-bold">ردیف‌هایی که وارد نشدند:</div>
                    @foreach($lines as $line)<div>{{ $line }}</div>@endforeach
                </div>
            @endif
        @endif

        <form action="{{ route('admin.products.import.store') }}" method="post" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" required
                   class="w-full rounded-xl bg-slate-50 p-4 text-xs text-navy-600 ring-1 ring-navy-100
                          file:ml-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-3 file:py-2 file:text-xs file:text-white">
            <button type="submit" class="btn-primary">شروع ایمپورت</button>
        </form>

        <div class="rounded-xl bg-slate-50 p-4 text-xs leading-7 text-navy-600">
            <div class="mb-2 font-bold text-navy-900">نکات مهم</div>
            <ul class="space-y-1.5">
                <li>• کلید یکتا ستون <strong>کد کالا</strong> است؛ اگر محصولی با همان کد باشد به‌روزرسانی می‌شود نه تکراری.</li>
                <li>• همه قیمت‌ها به <strong>تومان</strong> وارد شوند. جداکننده هزارگان و ارقام فارسی مشکلی ندارند.</li>
                <li>• برند و دسته اگر وجود نداشته باشند خودکار ساخته می‌شوند.</li>
                <li>• ستون مشخصات با الگوی <code class="rounded bg-white px-1">کلید: مقدار | کلید: مقدار</code> پر شود.</li>
                <li>• فایل باید با انکدینگ UTF-8 ذخیره شود (در اکسل: CSV UTF-8).</li>
            </ul>
        </div>
    </div>

    <aside class="card h-fit space-y-4 p-6">
        <h2 class="text-sm font-bold text-navy-900">فایل نمونه</h2>
        <p class="text-xs leading-7 text-navy-500">
            برای اطمینان از درستی ستون‌ها، فایل نمونه را بگیرید و داده‌های خود را در همان ساختار وارد کنید.
        </p>
        <a href="{{ route('admin.products.import.template') }}" class="btn-ghost w-full !py-2.5 !text-xs">دانلود فایل نمونه</a>
    </aside>
</div>
@endsection
