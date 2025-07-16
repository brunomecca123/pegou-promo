@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-10 p-6 bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Cadastrar Promoção Amazon</h1>
    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-4">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('painel.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block font-semibold mb-1">Título</label>
            <input type="text" name="title" class="w-full border rounded p-2" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Descrição</label>
            <textarea name="description" class="w-full border rounded p-2"></textarea>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Link de Afiliado Amazon</label>
            <input type="url" name="url" class="w-full border rounded p-2" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">URL da Imagem (opcional)</label>
            <input type="url" name="image" class="w-full border rounded p-2">
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cadastrar e Enviar</button>
    </form>
</div>
@endsection
