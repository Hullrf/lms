@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">Finalizar compra</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Formulario de pago --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-6 bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3">
                <span class="text-yellow-600">⚠️</span>
                <p class="text-sm text-yellow-700">Este es un pago simulado. No ingreses datos reales.</p>
            </div>

            <h2 class="font-semibold text-gray-800 mb-5">Datos de pago</h2>

            <form method="POST" action="{{ route('checkout.process', $course->slug) }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre en la tarjeta</label>
                    <input type="text" name="card_name" value="{{ old('card_name') }}"
                           placeholder="Ej: Juan Pérez" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('card_name') border-red-400 @enderror">
                    @error('card_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de tarjeta</label>
                    <input type="text" name="card_number" value="{{ old('card_number') }}"
                           placeholder="1234567890123456" maxlength="16" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('card_number') border-red-400 @enderror">
                    @error('card_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de vencimiento</label>
                        <input type="text" name="card_expiry" value="{{ old('card_expiry') }}"
                               placeholder="MM/AA" maxlength="5" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('card_expiry') border-red-400 @enderror">
                        @error('card_expiry')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                        <input type="text" name="card_cvv" value="{{ old('card_cvv') }}"
                               placeholder="123" maxlength="3" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('card_cvv') border-red-400 @enderror">
                        @error('card_cvv')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 text-sm">
                    💳 Pagar ${{ number_format($course->price, 2) }}
                </button>
            </form>
        </div>

        {{-- Resumen del pedido --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sticky top-6">
                <h2 class="font-semibold text-gray-800 mb-4">Resumen del pedido</h2>

                @if($course->thumbnail)
                    <img src="{{ Storage::url($course->thumbnail) }}"
                         class="w-full h-32 object-cover rounded-lg mb-4">
                @endif

                <p class="font-medium text-gray-900 text-sm">{{ $course->title }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $course->instructor->name }}</p>

                <hr class="my-4 border-gray-200">

                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span>${{ number_format($course->price, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-base mt-2">
                    <span>Total</span>
                    <span class="text-indigo-600">${{ number_format($course->price, 2) }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection