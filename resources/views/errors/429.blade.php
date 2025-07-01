@extends('layouts.error')

@section('title', 'Too Many Requests')
@section('code', '429')

@section('message')
    <div class="text-center">
        <h2 class="display-4 text-danger mb-4">
            <i class="bi bi-speedometer2"></i> Too Many Requests
        </h2>
        <p class="lead mb-4">
            {{ $message ?? 'You have made too many requests. Please slow down and try again later.' }}
        </p>
        
        @if(isset($retry_after))
            <div class="alert alert-warning d-inline-block">
                <i class="bi bi-clock"></i> Please wait <strong>{{ $retry_after }} seconds</strong> before trying again.
            </div>
        @endif
        
        <div class="mt-4">
            <a href="{{ url()->previous() }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Go Back
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary ms-2">
                <i class="bi bi-house"></i> Admin Dashboard
            </a>
        </div>
    </div>
@endsection