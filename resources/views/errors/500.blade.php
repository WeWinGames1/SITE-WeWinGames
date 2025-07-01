@extends('layouts.error')

@section('title', 'Server Error')
@section('code', '500')

@section('message')
    <div class="text-center">
        <h2 class="display-4 text-danger mb-4">
            <i class="bi bi-exclamation-triangle"></i> Server Error
        </h2>
        <p class="lead mb-4">
            {{ $message ?? 'Something went wrong on our servers. We\'re working to fix it.' }}
        </p>
        
        @if(config('app.debug') && isset($error))
            <div class="alert alert-danger text-start">
                <h6 class="alert-heading">Debug Information:</h6>
                <pre class="mb-0">{{ $error }}</pre>
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