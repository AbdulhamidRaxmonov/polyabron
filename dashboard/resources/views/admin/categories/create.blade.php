@extends('admin.layouts.app')
@section('title', 'Kategoriya yaratish')
@section('page_title', 'Yangi kategoriya')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="form-card">
            <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-600">Nomi (O'zbek) *</label>
                        <input type="text" name="name_uz" value="{{ old('name_uz') }}"
                               class="form-control @error('name_uz') is-invalid @enderror" required>
                        @error('name_uz')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Nomi (Rus) *</label>
                        <input type="text" name="name_ru" value="{{ old('name_ru') }}"
                               class="form-control @error('name_ru') is-invalid @enderror" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Nomi (English)</label>
                        <input type="text" name="name_en" value="{{ old('name_en') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Tartib raqami</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                               class="form-control" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Ikonka</label>
                        <input type="file" name="icon" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Rasm</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" checked>
                            <label class="form-check-label" for="is_active">Faol</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.categories.index') }}"
                           class="btn btn-outline-secondary rounded-pill px-4">Bekor qilish</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-check-lg me-1"></i>Saqlash
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
