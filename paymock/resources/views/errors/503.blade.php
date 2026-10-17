@extends('layouts.app')
@section('title', '503 Service Unavailable')

@section('content')
<div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-4 border-red-600">
    <div class="px-6 py-12 text-center">
        <div class="text-6xl mb-4">🔴</div>
        <h1 class="text-2xl font-bold text-red-700">Gateway มีปัญหา</h1>
        <p class="text-gray-700 font-semibold mt-2">โปรดลองอีกครั้ง</p>
    </div>
</div>
@endsection
