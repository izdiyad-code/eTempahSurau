@extends('layouts.app')
@section('title', __('Facilities & Equipment'))

@section('content')
<h1>{{ __('Facilities & Equipment') }}</h1>
<p class="lead">{{ __('Change names, pictures, deposit and stock. Untick Active to hide an item from bookers.') }}</p>

<div class="card">
    <h2>{{ __('Facilities') }}</h2>
    @foreach ($facilities as $f)
        <form method="POST" action="{{ route('pic.facilities.update', $f) }}" enctype="multipart/form-data" class="form-grid" style="padding-bottom:16px;border-bottom:1px solid #e5e7eb;margin-bottom:16px;">
            @csrf @method('PUT')
            <div><label>{{ __('Name (English)') }}</label><input name="name_en" value="{{ $f->name_en }}" required></div>
            <div><label>{{ __('Name (Bahasa Melayu)') }}</label><input name="name_ms" value="{{ $f->name_ms }}" required></div>
            <div><label>{{ __('Deposit (RM)') }}</label><input type="number" step="0.01" min="0" name="deposit_amount" value="{{ $f->deposit_amount }}" required></div>
            <div><label>{{ __('Picture (JPG, PNG, WEBP, max 3 MB)') }}</label><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></div>
            <div><label>{{ __('Description (English)') }}</label><input name="description" value="{{ $f->description }}"></div>
            <div><label>{{ __('Description (Bahasa Melayu)') }}</label><input name="description_ms" value="{{ $f->description_ms }}"></div>
            @if ($f->imageUrl())
                <div class="full"><img src="{{ $f->imageUrl() }}" alt="{{ $f->name() }}" style="max-height:120px;border-radius:10px;"></div>
            @endif
            <div class="actions" style="margin:0;">
                <label style="margin:0;"><input type="checkbox" name="active" value="1" @checked($f->status === 'active')> {{ __('Active') }}</label>
                <button class="btn btn-sm" type="submit">{{ __('Save') }}</button>
            </div>
        </form>
    @endforeach

    <h2 style="margin-top:24px;">{{ __('Add facility') }}</h2>
    <form method="POST" action="{{ route('pic.facilities.store') }}" enctype="multipart/form-data" class="form-grid">@csrf
        <div><label>{{ __('Name (English)') }}</label><input name="name_en" required></div>
        <div><label>{{ __('Name (Bahasa Melayu)') }}</label><input name="name_ms" required></div>
        <div><label>{{ __('Deposit (RM)') }}</label><input type="number" step="0.01" min="0" name="deposit_amount" value="10" required></div>
        <div><label>{{ __('Picture (JPG, PNG, WEBP, max 3 MB)') }}</label><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></div>
        <div><label>{{ __('Description (English)') }}</label><input name="description"></div>
        <div><label>{{ __('Description (Bahasa Melayu)') }}</label><input name="description_ms"></div>
        <div class="actions" style="margin:0;"><button class="btn" type="submit">{{ __('Add facility') }}</button></div>
    </form>
</div>

<div class="card">
    <h2>{{ __('Equipment') }}</h2>
    @foreach ($equipment as $e)
        <form method="POST" action="{{ route('pic.equipment.update', $e) }}" class="form-grid" style="padding-bottom:16px;border-bottom:1px solid #e5e7eb;margin-bottom:16px;">
            @csrf @method('PUT')
            <div><label>{{ __('Name (English)') }}</label><input name="name_en" value="{{ $e->name_en }}" required></div>
            <div><label>{{ __('Name (Bahasa Melayu)') }}</label><input name="name_ms" value="{{ $e->name_ms }}" required></div>
            <div><label>{{ __('Total quantity') }}</label><input type="number" name="total_quantity" min="0" value="{{ $e->total_quantity }}" required></div>
            <div class="actions" style="margin:0;align-self:end;">
                <label style="margin:0;"><input type="checkbox" name="active" value="1" @checked($e->status === 'available')> {{ __('Available') }}</label>
                <button class="btn btn-sm" type="submit">{{ __('Save') }}</button>
            </div>
        </form>
    @endforeach

    <h2 style="margin-top:24px;">{{ __('Add equipment') }}</h2>
    <form method="POST" action="{{ route('pic.equipment.store') }}" class="form-grid">@csrf
        <div><label>{{ __('Name (English)') }}</label><input name="name_en" required></div>
        <div><label>{{ __('Name (Bahasa Melayu)') }}</label><input name="name_ms" required></div>
        <div><label>{{ __('Total quantity') }}</label><input type="number" name="total_quantity" min="0" required></div>
        <div class="actions" style="margin:0;align-self:end;"><button class="btn" type="submit">{{ __('Add equipment') }}</button></div>
    </form>
</div>

<div class="card">
    <h2>{{ __('Event types') }}</h2>
    @foreach ($eventTypes as $t)
        <form method="POST" action="{{ route('pic.eventtypes.update', $t) }}" class="form-grid" style="padding-bottom:14px;border-bottom:1px solid #e5e7eb;margin-bottom:14px;">
            @csrf @method('PUT')
            <div><label>{{ __('Name (English)') }}</label><input name="name_en" value="{{ $t->name_en }}" required></div>
            <div><label>{{ __('Name (Bahasa Melayu)') }}</label><input name="name_ms" value="{{ $t->name_ms }}" required></div>
            <div class="actions" style="margin:0;">
                <label style="margin:0;"><input type="checkbox" name="active" value="1" @checked($t->status === 'active')> {{ __('Active') }}</label>
                <button class="btn btn-sm" type="submit">{{ __('Save') }}</button>
            </div>
        </form>
    @endforeach

    <h2 style="margin-top:24px;">{{ __('Add event type') }}</h2>
    <form method="POST" action="{{ route('pic.eventtypes.store') }}" class="form-grid">@csrf
        <div><label>{{ __('Name (English)') }}</label><input name="name_en" required></div>
        <div><label>{{ __('Name (Bahasa Melayu)') }}</label><input name="name_ms" required></div>
        <div class="actions" style="margin:0;"><button class="btn" type="submit">{{ __('Add event type') }}</button></div>
    </form>
</div>
@endsection
